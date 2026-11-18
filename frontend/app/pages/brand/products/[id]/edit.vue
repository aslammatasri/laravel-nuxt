<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

const config = useRuntimeConfig();
const { $api } = useApi();
const router = useRouter();
const route = useRoute();
const error = ref<string | null>(null);
const loading = ref(false);

const productId = route.params.id;

// ── Fetch existing product data ────────────────────────────────
interface Product {
    name: string;
    description: string;
    price: number;
    category: string;
    image: string | null;
    images: string[];
    commission_type: string;
    campaign_start: string;
    campaign_end: string;
    max_affiliates: number;
    status: string;
}

const { data: product } = await useAsyncData(`product-${productId}`, () =>
    $api<Product>(`/brand/products/${productId}`).catch(() => null),
);

const imagePreviews = ref<string[]>([]);
const imageFiles = ref<File[]>([]);
const deleteImagePaths = ref<string[]>([]);

const existingImages = computed(() =>
    (product.value?.images || []).filter(
        (p) => !deleteImagePaths.value.includes(p),
    ),
);

const existingImageUrls = computed(() =>
    existingImages.value.map((p) => `${config.public.storageBase}/${p}`),
);

const imageProblems = ref<string[]>([]);

function onImagesChange(e: Event) {
    const input = e.target as HTMLInputElement;
    const picked = Array.from(input.files || []);
    const existing = imageFiles.value.reduce((n, f) => n + f.size, 0);
    const { accepted, problems } = checkImageFiles(picked, existing);

    imageProblems.value = problems;
    imageFiles.value.push(...accepted);
    accepted.forEach((f) => imagePreviews.value.push(URL.createObjectURL(f)));
    input.value = "";
}

function removeNewImageAt(index: number) {
    URL.revokeObjectURL(imagePreviews.value[index] ?? "");
    imagePreviews.value.splice(index, 1);
    imageFiles.value.splice(index, 1);
}

function removeExistingImage(path: string) {
    deleteImagePaths.value.push(path);
}

// ── Pre-fill form with existing data ──────────────────────────
const form = reactive({
    name: product.value?.name ?? "",
    description: product.value?.description ?? "",
    price: product.value?.price ?? "",
    category: product.value?.category ?? "",
    campaign_start: product.value?.campaign_start ?? "",
    campaign_end: product.value?.campaign_end ?? "",
    max_affiliates: product.value?.max_affiliates ?? "",
    status: product.value?.status ?? "active",
    ...extrasFrom(product.value),
});

const fieldErrors = reactive<Record<string, string>>({});




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
    Object.keys(fieldErrors).forEach((k) => delete fieldErrors[k]);
    Object.assign(fieldErrors, validateExtras(form, form.campaign_end));
    if (Object.keys(fieldErrors).length) {
        error.value = Object.values(fieldErrors).join(" ");
        return;
    }

    loading.value = true;
    error.value = null;
    imageProblems.value = [];

    try {
        const body = new FormData();
        appendFormData(body, form, { clearEmpty: true });
        imageFiles.value.forEach((f) => body.append("images[]", f));
        deleteImagePaths.value.forEach((p) =>
            body.append("delete_images[]", p),
        );
        body.append("_method", "PUT");

        await $api(`/brand/products/${productId}`, { method: "POST", body });

        await router.push("/brand/products");
    } catch (err: any) {
        const parsed = parseApiError(err, "Failed to update product");
        error.value = [parsed.message, ...parsed.all].join(" ");
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
                <h1 class="text-2xl font-bold text-gray-900">Edit campaign</h1>
                <p class="text-gray-500 mt-1">Update your product details</p>
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
                    <h2 class="font-semibold text-gray-900">Campaign Info</h2>

                    <!-- Image upload -->
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Campaign Images</label
                        >

                        <!-- Existing images -->
                        <div
                            v-if="existingImageUrls.length"
                            class="grid grid-cols-3 gap-2 mb-3"
                        >
                            <div
                                v-for="(url, i) in existingImageUrls"
                                :key="url"
                                class="relative group"
                            >
                                <img
                                    :src="url"
                                    class="w-full h-28 object-cover rounded-xl border border-gray-200"
                                />
                                <button
                                    type="button"
                                    @click="
                                        removeExistingImage(
                                            product!.images[i] ?? '',
                                        )
                                    "
                                    class="absolute top-1 right-1 bg-white/80 hover:bg-red-500 hover:text-white text-gray-500 rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-sm transition-colors opacity-0 group-hover:opacity-100"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- New image previews -->
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
                                    @click="removeNewImageAt(i)"
                                    class="absolute top-1 right-1 bg-white border border-gray-200 text-gray-500 hover:text-red-500 rounded-full w-6 h-6 flex items-center justify-center text-xs shadow-sm transition-colors"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>

                        <!-- Add more button -->
                        <label
                            class="flex items-center justify-center w-full h-28 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors"
                        >
                            <span class="text-2xl text-gray-400"
                                >+ Add Images</span
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

                    <div v-if="imageProblems.length" class="-mt-2 space-y-1" role="alert">
                        <p v-for="p in imageProblems" :key="p" class="text-xs text-red-500">{{ p }}</p>
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
                            placeholder="Describe the campaign and what creators should highlight..."
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >Price (RM) <span class="text-gray-400 font-normal">(optional)</span></label
                            >
                            <CurrencyInput v-model="form.price" />
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
                            <DateInput v-model="form.campaign_start" />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                                >End Date</label
                            >
                            <DateInput v-model="form.campaign_end" />
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

            <!-- Right column — settings & actions -->
            <div class="space-y-5">
                <CampaignPerks :form="form" :errors="fieldErrors" />

                <div
                    class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5"
                >
                    <h2 class="font-semibold text-gray-900">Settings</h2>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-1"
                            >Status</label
                        >
                        <select
                            v-model="form.status"
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

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

                <!-- Action buttons -->
                <div class="flex gap-3">
                    <NuxtLink
                        to="/brand/products"
                        class="flex-1 text-center border border-gray-200 text-gray-600 px-5 py-3.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                    >
                        Cancel
                    </NuxtLink>
                    <button
                        @click="handleSubmit"
                        :disabled="loading"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-300 text-white font-semibold rounded-xl py-3.5 text-sm transition-colors"
                    >
                        {{ loading ? "Saving..." : "Save Changes" }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
