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

function onImagesChange(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files || []);
    imageFiles.value.push(...files);
    files.forEach((f) => imagePreviews.value.push(URL.createObjectURL(f)));
}

function removeImageAt(index: number) {
    URL.revokeObjectURL(imagePreviews.value[index] ?? "");
    imagePreviews.value.splice(index, 1);
    imageFiles.value.splice(index, 1);
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

async function handleSubmit() {
    loading.value = true;
    error.value = null;

    try {
        const body = new FormData();
        Object.entries(form).forEach(([k, v]) => {
            if (v !== "") body.append(k, v as string);
        });
        imageFiles.value.forEach((f) => body.append("images[]", f));

        const product = await $api<{ id: number }>("/brand/products", {
            method: "POST",
            body,
        });

        for (const v of variations.value) {
            if (!v.name.trim()) continue;
            const opts = v.options.filter((o) => o.value.trim());
            if (!opts.length) continue;
            await $api(`/brand/products/${product.id}/variations`, {
                method: "POST",
                body: {
                    name: v.name,
                    options: opts.map((o) => ({
                        value: o.value,
                        price: parseFloat(o.price) || 0,
                    })),
                },
            });
        }

        await router.push("/brand/products");
    } catch (err: any) {
        error.value = err?.data?.message || "Failed to create product";
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
                    ← Back to products
                </NuxtLink>
                <h1 class="text-2xl font-bold text-gray-900">
                    Add New Product
                </h1>
            </div>
        </div>

        <!-- Error -->
        <div
            v-if="error"
            class="bg-red-50 text-red-600 text-sm rounded-xl p-4 mb-6"
        >
            {{ error }}
        </div>

        <div class="grid grid-cols-3 gap-6">
            <!-- Left column — main info -->
            <div class="col-span-2 space-y-5">
                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5"
                >
                    <h2 class="font-semibold text-gray-900">Product Info</h2>

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
                                >PNG, JPG, WEBP — max 2MB each</span
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

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Product Name</label
                        >
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. Skintific Sunscreen SPF50"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
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
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Price (RM)</label
                            >
                            <input
                                v-model="form.price"
                                type="number"
                                placeholder="0.00"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Category</label
                            >
                            <select
                                v-model="form.category"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
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
                        </div>
                    </div>
                </div>

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
                            <input
                                v-model="form.campaign_start"
                                type="date"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >End Date</label
                            >
                            <input
                                v-model="form.campaign_end"
                                type="date"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Variations -->
                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-gray-900">Variations</h2>
                        <button
                            type="button"
                            @click="addVariation"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
                        >
                            + Add Variation
                        </button>
                    </div>

                    <p
                        v-if="variations.length === 0"
                        class="text-sm text-gray-400 text-center py-3"
                    >
                        Optional — add if your product comes in sizes, flavors,
                        pack sizes, etc.
                    </p>

                    <div
                        v-for="(variation, vi) in variations"
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
                                <div class="relative">
                                    <span
                                        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"
                                        >RM</span
                                    >
                                    <input
                                        v-model="option.price"
                                        type="number"
                                        min="0"
                                        placeholder="0.00"
                                        class="w-28 border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
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
                            placeholder="10"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
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
                            placeholder="Unlimited"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <button
                    @click="handleSubmit"
                    :disabled="loading"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold rounded-xl py-3.5 text-sm transition-colors"
                >
                    {{ loading ? "Creating..." : "Create Product" }}
                </button>
            </div>
        </div>
    </div>
</template>
