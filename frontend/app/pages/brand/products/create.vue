<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

const { $api } = useApi();
const router = useRouter();
const error = ref<string | null>(null);
const loading = ref(false);

const imageFiles = ref<File[]>([]);
const imagePreviews = ref<string[]>([]);

const fieldErrors = reactive<Record<string, string>>({});
const imageProblems = ref<string[]>([]);
const createdProductId = ref<number | null>(null);

function onImagesChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const picked = Array.from(input.files || []);
    const existing = imageFiles.value.reduce((n, f) => n + f.size, 0);
    const { accepted, problems } = checkImageFiles(picked, existing);

    imageProblems.value = problems;
    delete fieldErrors.images;
    imageFiles.value.push(...accepted);
    accepted.forEach((f) => imagePreviews.value.push(URL.createObjectURL(f)));
    input.value = ""; // allow picking the same file again after fixing it
}

function removeImageAt(index: number) {
    URL.revokeObjectURL(imagePreviews.value[index] ?? "");
    imagePreviews.value.splice(index, 1);
    imageFiles.value.splice(index, 1);
    imageProblems.value = [];
}

const form = reactive({
    name: "",
    description: "",
    price: "",
    category: "",
    commission_rate: "",
    commission_type: "percentage",
    campaign_start: "",
    campaign_end: "",
    max_affiliates: "",
    ...emptyExtras(),
});
const commissionHint = computed(() => {
    const rate = Number(form.commission_rate);
    if (!form.commission_rate || isNaN(rate) || rate <= 0) return "";
    const money = (n: number) =>
        "RM " + n.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (form.commission_type === "fixed") return `${money(rate)} per sale`;
    const price = Number(form.price);
    if (!price) return `${rate}% of each sale`;
    return `${rate}% of ${money(price)} = ${money((price * rate) / 100)} per sale`;
});


interface VariationOption {
    _key: number;
    value: string;
    price: string;
}
interface Variation {
    _key: number;
    name: string;
    options: VariationOption[];
}

let _seq = 0;
const nextKey = () => ++_seq;

const variations = ref<Variation[]>([]);

function addVariation() {
    variations.value.push({
        _key: nextKey(),
        name: "",
        options: [{ _key: nextKey(), value: "", price: "" }],
    });
}
function removeVariation(i: number) {
    variations.value.splice(i, 1);
}
function addOption(vi: number) {
    variations.value[vi]!.options.push({
        _key: nextKey(),
        value: "",
        price: "",
    });
}
function removeOption(vi: number, oi: number) {
    variations.value[vi]!.options.splice(oi, 1);
}

const hasVariations = ref(false);
function setHasVariations(v: boolean) {
    hasVariations.value = v;
    if (v && !variations.value.length) addVariation();
}

const categories = [
    "beauty",
    "fashion",
    "food",
    "tech",
    "lifestyle",
    "health",
    "home",
    "sports",
];

// Clear a field's error as soon as the user edits it
watch(
    () => ({ ...form }),
    (now, before) => {
        for (const k of Object.keys(now) as (keyof typeof now)[]) {
            if (now[k] !== before[k]) delete fieldErrors[k];
        }
    },
);

const variationError = ref<string | null>(null);

function validate(): boolean {
    Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);
    variationError.value = null;

    if (!form.name.trim()) fieldErrors.name = "Enter a product name.";
    if (!form.description.trim()) fieldErrors.description = "Add a short description.";

    if (form.price === "") fieldErrors.price = "Enter the product price.";
    else if (Number(form.price) < 0) fieldErrors.price = "Price cannot be negative.";

    if (!form.category) fieldErrors.category = "Choose a category.";

    if (form.commission_rate === "") fieldErrors.commission_rate = "Enter the commission rate.";
    else if (Number(form.commission_rate) < 0) fieldErrors.commission_rate = "Commission cannot be negative.";
    else if (form.commission_type === "percentage" && Number(form.commission_rate) > 100)
        fieldErrors.commission_rate = "A percentage commission cannot be more than 100.";

    if (!form.campaign_start) fieldErrors.campaign_start = "Choose a valid start date (dd/mm/yyyy).";
    if (!form.campaign_end) fieldErrors.campaign_end = "Choose a valid end date (dd/mm/yyyy).";
    else if (form.campaign_start && form.campaign_end <= form.campaign_start)
        fieldErrors.campaign_end = "End date must be after the start date.";

    Object.assign(fieldErrors, validateExtras(form, form.campaign_end));

    if (form.max_affiliates !== "") {
        const n = Number(form.max_affiliates);
        if (!Number.isInteger(n) || n < 1) fieldErrors.max_affiliates = "Use a whole number of 1 or more, or leave empty for unlimited.";
    }

    if (hasVariations.value) {
        for (const v of variations.value) {
            const filled = v.options.filter((o) => o.value.trim());
            if (!v.name.trim() && !filled.length) continue; // untouched row, ignored on save
            if (!v.name.trim()) {
                variationError.value = "Give every variation a name (for example Size).";
                break;
            }
            if (!filled.length) {
                variationError.value = `Add at least one option to "${v.name}".`;
                break;
            }
        }
    }

    return !Object.keys(fieldErrors).length && !variationError.value;
}

function scrollToFirstError() {
    nextTick(() => {
        const el = document.querySelector<HTMLElement>('[aria-invalid="true"], [data-error]');
        el?.scrollIntoView({ behavior: "smooth", block: "center" });
    });
}

async function saveVariations(productId: number) {
    for (const v of hasVariations.value ? variations.value : []) {
        if (!v.name.trim()) continue;
        const opts = v.options.filter((o) => o.value.trim());
        if (!opts.length) continue;
        try {
            await $api(`/brand/products/${productId}/variations`, {
                method: "POST",
                body: {
                    name: v.name,
                    options: opts.map((o) => ({
                        value: o.value,
                        price: parseFloat(o.price) || 0,
                    })),
                },
            });
        } catch (err: any) {
            const parsed = parseApiError(err);
            throw new Error(
                `Variation "${v.name}" could not be saved: ${parsed.all[0] || parsed.message}`,
            );
        }
        // Don't re-send on a retry
        variations.value = variations.value.filter((x) => x !== v);
    }
}

async function handleSubmit() {
    error.value = null;
    imageProblems.value = [];

    if (!validate()) {
        error.value = "Please fix the highlighted fields.";
        scrollToFirstError();
        return;
    }

    loading.value = true;

    try {
        // On a retry the product already exists: only the variations are left
        if (createdProductId.value === null) {
            const body = new FormData();
            appendFormData(body, form);
            imageFiles.value.forEach((f) => body.append("images[]", f));

            const product = await $api<{ id: number }>("/brand/products", {
                method: "POST",
                body,
            });
            createdProductId.value = product.id;
        }

        await saveVariations(createdProductId.value);
        await router.push("/brand/products");
    } catch (err: any) {
        if (createdProductId.value !== null) {
            // Product exists, a variation failed
            error.value = `Your product was created, but ${err?.message ?? "a variation failed"}. Fix it and press the button again, or finish later by editing the product.`;
        } else {
            const parsed = parseApiError(err, "Failed to create product");
            Object.assign(fieldErrors, parsed.fields);
            error.value = parsed.message;
            scrollToFirstError();
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <NuxtLink
                    to="/brand/products"
                    class="text-sm text-gray-400 hover:text-gray-600 mb-1 inline-flex items-center gap-1"
                >
                    ← Back to campaigns
                </NuxtLink>
                <h1 class="text-2xl font-bold text-gray-900">
                    Create campaign
                </h1>
            </div>
        </div>

        <!-- Error -->
        <div
            v-if="error"
            class="bg-red-50 text-red-600 text-sm rounded-xl p-4 mb-6"
            role="alert"
            data-error
        >
            {{ error }}
            <ul
                v-if="Object.keys(fieldErrors).length"
                class="list-disc pl-5 mt-2 space-y-0.5"
            >
                <li v-for="(msg, key) in fieldErrors" :key="key">{{ msg }}</li>
            </ul>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- Left column — main info -->
            <div class="col-span-2 space-y-5">
                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5"
                >
                    <h2 class="font-semibold text-gray-900">Product</h2>

                    <!-- Image upload -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Product Images</label
                        >

                        <!-- Previews -->
                        <div
                            v-if="imagePreviews.length"
                            class="grid grid-cols-3 gap-2 mb-3"
                        >
                            <div
                                v-for="(preview, i) in imagePreviews"
                                :key="preview"
                                class="relative"
                            >
                                <img
                                    :src="preview"
                                    class="w-full h-28 object-cover rounded-xl border border-gray-200"
                                />
                                <button
                                    type="button"
                                    @click="removeImageAt(i)"
                                    class="absolute top-1 right-1 bg-white border border-gray-200 text-gray-500 hover:text-red-500 rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-sm transition-colors"
                                >
                                    ✕
                                </button>
                            </div>
                            <!-- Add more button -->
                            <label
                                class="flex items-center justify-center h-28 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors"
                            >
                                <span class="text-2xl">+</span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    multiple
                                    @change="onImagesChange"
                                />
                            </label>
                        </div>

                        <!-- Drop zone (no images yet) -->
                        <label
                            v-else
                            class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors"
                        >
                            <AppIcon name="image" class="w-8 h-8 mb-2 text-gray-400" />
                            <span class="text-sm font-medium text-gray-600"
                                >Click to upload images</span
                            >
                            <span class="text-xs text-gray-400 mt-1"
                                >PNG, JPG or WEBP — max 2 MB each</span
                            >
                            <input
                                type="file"
                                accept="image/*"
                                class="hidden"
                                multiple
                                @change="onImagesChange"
                            />
                        </label>
                    </div>

                    <div v-if="imageProblems.length || fieldErrors.images" class="-mt-2 space-y-1" role="alert" data-error>
                        <p v-for="p in imageProblems" :key="p" class="text-xs text-red-500">{{ p }}</p>
                        <p v-if="fieldErrors.images" class="text-xs text-red-500">{{ fieldErrors.images }}</p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Product Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Skintific Sunscreen SPF50"
                            :aria-invalid="fieldErrors.name ? 'true' : undefined"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-300! focus:ring-red-400!': fieldErrors.name }"
                        />
                        <p v-if="fieldErrors.name" class="text-xs text-red-500 mt-1">{{ fieldErrors.name }}</p>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Description</label
                        >
                        <textarea
                            v-model="form.description"
                            rows="5"
                            placeholder="Describe your product and what creators should highlight..."
                            :aria-invalid="fieldErrors.description ? 'true' : undefined"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                            :class="{ 'border-red-300! focus:ring-red-400!': fieldErrors.description }"
                        />
                        <p v-if="fieldErrors.description" class="text-xs text-red-500 mt-1">{{ fieldErrors.description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Price (RM)</label
                            >
                            <CurrencyInput
                                v-model="form.price"
                                :invalid="!!fieldErrors.price"
                            />
                            <p v-if="fieldErrors.price" class="text-xs text-red-500 mt-1">{{ fieldErrors.price }}</p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Category</label
                            >
                            <select
                                v-model="form.category"
                                :aria-invalid="fieldErrors.category ? 'true' : undefined"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-300! focus:ring-red-400!': fieldErrors.category }"
                            >
                                <option value="">Select category</option>
                                <option
                                    v-for="cat in categories"
                                    :key="cat"
                                    :value="cat"
                                >
                                    {{
                                        cat.charAt(0).toUpperCase() +
                                        cat.slice(1)
                                    }}
                                </option>
                            </select>
                            <p v-if="fieldErrors.category" class="text-xs text-red-500 mt-1">{{ fieldErrors.category }}</p>
                        </div>
                    </div>
                </div>

                <CampaignBrief :form="form" :errors="fieldErrors" />

                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5"
                >
                    <h2 class="font-semibold text-gray-900">Campaign Period</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Start Date</label
                            >
                            <DateInput
                                v-model="form.campaign_start"
                                :invalid="!!fieldErrors.campaign_start"
                            />
                            <p v-if="fieldErrors.campaign_start" class="text-xs text-red-500 mt-1">{{ fieldErrors.campaign_start }}</p>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >End Date</label
                            >
                            <DateInput
                                v-model="form.campaign_end"
                                :invalid="!!fieldErrors.campaign_end"
                            />
                            <p v-if="fieldErrors.campaign_end" class="text-xs text-red-500 mt-1">{{ fieldErrors.campaign_end }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Application deadline <span class="text-gray-400 font-normal">(optional)</span>
                        </label>
                        <DateInput v-model="form.application_deadline" :invalid="!!fieldErrors.application_deadline" />
                        <p v-if="fieldErrors.application_deadline" class="text-xs text-red-500 mt-1">{{ fieldErrors.application_deadline }}</p>
                        <p v-else class="text-xs text-gray-400 mt-1">Creators cannot apply after this date. Leave empty to accept applications until the campaign ends.</p>
                    </div>
                </div>

                <!-- Variations -->
                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Variations</h2>
                        <button
                            v-if="hasVariations"
                            type="button"
                            @click="addVariation"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
                        >
                            + Add Variation
                        </button>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                This product has variations
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Sizes, flavors, pack sizes, etc.
                            </p>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-checked="hasVariations"
                            aria-label="This product has variations"
                            @click="setHasVariations(!hasVariations)"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            :class="hasVariations ? 'bg-blue-600' : 'bg-gray-200'"
                        >
                            <span
                                class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform"
                                :class="hasVariations ? 'translate-x-5' : 'translate-x-0.5'"
                            />
                        </button>
                    </div>

                    <p
                        v-if="!hasVariations"
                        class="text-sm text-gray-400 text-center py-3"
                    >
                        No variations — sold as a single option.
                    </p>

                    <p v-if="variationError" class="text-xs text-red-500" role="alert" data-error>{{ variationError }}</p>

                    <div
                        v-for="(variation, vi) in hasVariations ? variations : []"
                        :key="variation._key"
                        class="border border-gray-100 rounded-xl p-4 space-y-3"
                    >
                        <div class="flex items-center gap-3">
                            <input
                                v-model="variation.name"
                                type="text"
                                placeholder="Variation name (e.g. Size, Flavor)"
                                class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                            <button
                                type="button"
                                @click="removeVariation(vi)"
                                class="text-gray-400 hover:text-red-500 transition-colors text-lg leading-none"
                            >
                                ✕
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(option, oi) in variation.options"
                                :key="option._key"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="option.value"
                                    type="text"
                                    placeholder="Option (e.g. Small)"
                                    class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                <div class="w-32">
                                    <CurrencyInput v-model="option.price" compact />
                                </div>
                                <button
                                    type="button"
                                    @click="removeOption(vi, oi)"
                                    :disabled="variation.options.length === 1"
                                    class="text-gray-400 hover:text-red-500 disabled:opacity-30 transition-colors"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="addOption(vi)"
                            class="text-xs text-blue-600 hover:text-blue-700 font-medium transition-colors"
                        >
                            + Add option
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right column — commission & settings -->
            <div class="space-y-5">
                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5"
                >
                    <h2 class="font-semibold text-gray-900">Commission</h2>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Rate</label
                        >
                        <input
                            v-model="form.commission_rate"
                            type="number"
                            min="0"
                            placeholder="10"
                            :aria-invalid="fieldErrors.commission_rate ? 'true' : undefined"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-300! focus:ring-red-400!': fieldErrors.commission_rate }"
                        />
                        <p v-if="fieldErrors.commission_rate" class="text-xs text-red-500 mt-1">{{ fieldErrors.commission_rate }}</p>
                        <p v-if="commissionHint" class="text-xs text-gray-500 mt-1.5">{{ commissionHint }}</p>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Type</label
                        >
                        <select
                            v-model="form.commission_type"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed (RM)</option>
                        </select>
                    </div>
                </div>

                <CampaignPerks :form="form" :errors="fieldErrors" />

                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5"
                >
                    <h2 class="font-semibold text-gray-900">Settings</h2>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Max Affiliates
                            <span class="text-gray-400 font-normal"
                                >(optional)</span
                            >
                        </label>
                        <input
                            v-model="form.max_affiliates"
                            type="number"
                            min="1"
                            placeholder="Unlimited"
                            :aria-invalid="fieldErrors.max_affiliates ? 'true' : undefined"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-300! focus:ring-red-400!': fieldErrors.max_affiliates }"
                        />
                        <p v-if="fieldErrors.max_affiliates" class="text-xs text-red-500 mt-1">{{ fieldErrors.max_affiliates }}</p>
                    </div>
                </div>

                <button
                    @click="handleSubmit"
                    :disabled="loading"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold rounded-xl py-3.5 text-sm transition-colors"
                >
                    {{ loading ? "Creating..." : "Create campaign" }}
                </button>
            </div>
        </div>
    </div>
</template>
