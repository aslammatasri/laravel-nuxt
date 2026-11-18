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
    campaign_start: "",
    campaign_end: "",
    max_affiliates: "",
    ...emptyExtras(),
});



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

function validate(): boolean {
    Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);

    if (!form.name.trim()) fieldErrors.name = "Enter a campaign name.";
    if (!form.description.trim()) fieldErrors.description = "Add a short description.";

    if (form.price !== "" && Number(form.price) < 0) fieldErrors.price = "Price cannot be negative.";

    if (!form.category) fieldErrors.category = "Choose a category.";

    if (!form.campaign_start) fieldErrors.campaign_start = "Choose a valid start date (dd/mm/yyyy).";
    if (!form.campaign_end) fieldErrors.campaign_end = "Choose a valid end date (dd/mm/yyyy).";
    else if (form.campaign_start && form.campaign_end <= form.campaign_start)
        fieldErrors.campaign_end = "End date must be after the start date.";

    Object.assign(fieldErrors, validateExtras(form, form.campaign_end));

    if (form.max_affiliates !== "") {
        const n = Number(form.max_affiliates);
        if (!Number.isInteger(n) || n < 1) fieldErrors.max_affiliates = "Use a whole number of 1 or more, or leave empty for unlimited.";
    }

    return !Object.keys(fieldErrors).length;
}

function scrollToFirstError() {
    nextTick(() => {
        const el = document.querySelector<HTMLElement>('[aria-invalid="true"], [data-error]');
        el?.scrollIntoView({ behavior: "smooth", block: "center" });
    });
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
        const body = new FormData();
        appendFormData(body, form);
        imageFiles.value.forEach((f) => body.append("images[]", f));

        await $api("/brand/products", { method: "POST", body });
        await router.push("/brand/products");
    } catch (err: any) {
        const parsed = parseApiError(err, "Failed to create product");
        Object.assign(fieldErrors, parsed.fields);
        error.value = parsed.message;
        scrollToFirstError();
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
                    <h2 class="font-semibold text-gray-900">Campaign</h2>

                    <!-- Image upload -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Campaign Images</label
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
                            >Campaign Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Skintific Sunscreen SPF50 Launch"
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
                            placeholder="Describe the campaign and what creators should highlight..."
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
                                >Price (RM) <span class="text-gray-400 font-normal">(optional)</span></label
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
            </div>

            <!-- Right column — settings -->
            <div class="space-y-5">
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
