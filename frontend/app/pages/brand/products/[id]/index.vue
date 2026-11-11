<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

interface Variation {
    id: number;
    name: string;
    options: { value: string; price: number | string }[];
}
interface Product {
    id: number;
    name: string;
    description: string;
    images: string[];
    status: string;
    price: number | string;
    category: string;
    commission_rate: string;
    commission_type: string;
    campaign_start: string | null;
    campaign_end: string | null;
    max_affiliates: number | null;
    spots_left: number | null;
    variations: Variation[];
    application_counts: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
    };
}
interface Application {
    id: number;
    status: string;
    pitch_message: string;
    created_at: string;
    creator: { id: number; name: string };
}

const route = useRoute();
const config = useRuntimeConfig();
const { $api } = useApi();
const productId = route.params.id as string;

const { data: product, refresh: refreshProduct } = await useAsyncData(
    `brand-product-${productId}`,
    () => $api<Product>(`/brand/products/${productId}`).catch(() => null),
);
const { data: applications, refresh: refreshApplications } = await useAsyncData(
    `brand-product-apps-${productId}`,
    () =>
        $api<{ data: Application[] }>("/brand/applications", {
            params: { product_id: productId },
        }).catch(() => null),
);

const activeImage = ref(0);
const counts = computed(
    () =>
        product.value?.application_counts ?? {
            total: 0,
            pending: 0,
            approved: 0,
            rejected: 0,
        },
);
const pendingApps = computed(
    () => applications.value?.data?.filter((a) => a.status === "pending") ?? [],
);
const decidedApps = computed(
    () => applications.value?.data?.filter((a) => a.status !== "pending") ?? [],
);
const approvedApps = computed(
    () => applications.value?.data?.filter((a) => a.status === "approved") ?? [],
);

// ── Campaign status ────────────────────────────────────────────
const daysLeft = computed(() => {
    const end = product.value?.campaign_end;
    if (!end) return null;
    const ms = new Date(end).getTime() - new Date().setHours(0, 0, 0, 0);
    return Math.ceil(ms / 86400000);
});
const campaignLabel = computed(() => {
    if (daysLeft.value === null) return "No end date";
    if (daysLeft.value < 0) return "Ended";
    if (daysLeft.value === 0) return "Ends today";
    return `${daysLeft.value} day${daysLeft.value === 1 ? "" : "s"} left`;
});
const spotsPct = computed(() => {
    const p = product.value;
    if (!p?.max_affiliates) return null;
    return Math.min(100, Math.round((counts.value.approved / p.max_affiliates) * 100));
});

function formatDate(d: string | null) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-MY", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
const statusClass = (s: string) =>
    s === "active"
        ? "bg-green-100 text-green-700"
        : s === "paused"
          ? "bg-yellow-100 text-yellow-700"
          : "bg-gray-100 text-gray-500";

// ── Actions ────────────────────────────────────────────────────
const busy = ref(false);
async function toggleStatus() {
    if (!product.value) return;
    busy.value = true;
    try {
        await $api(`/brand/products/${productId}`, {
            method: "PUT",
            body: { status: product.value.status === "active" ? "paused" : "active" },
        });
        await refreshProduct();
    } finally {
        busy.value = false;
    }
}

const actingId = ref<number | null>(null);
const showRejectModal = ref(false);
const rejectAppId = ref<number | null>(null);
const rejectReason = ref("");

async function refreshAll() {
    await Promise.all([refreshProduct(), refreshApplications()]);
}
async function approve(id: number) {
    actingId.value = id;
    try {
        await $api(`/brand/applications/${id}/approve`, { method: "PATCH" });
        await refreshAll();
    } finally {
        actingId.value = null;
    }
}
function openReject(id: number) {
    rejectAppId.value = id;
    rejectReason.value = "";
    showRejectModal.value = true;
}
async function confirmReject() {
    if (!rejectReason.value.trim() || rejectAppId.value === null) return;
    actingId.value = rejectAppId.value;
    try {
        await $api(`/brand/applications/${rejectAppId.value}/reject`, {
            method: "PATCH",
            body: { reason: rejectReason.value },
        });
        showRejectModal.value = false;
        await refreshAll();
    } finally {
        actingId.value = null;
    }
}
</script>

<template>
    <div>
        <NuxtLink to="/brand/products" class="text-sm text-gray-500 hover:text-gray-900">
            ← Back to products
        </NuxtLink>

        <div v-if="!product" class="mt-6 bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400">
            Product not found.
        </div>

        <template v-else>
            <!-- Header -->
            <div class="mt-4 mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900">{{ product.name }}</h1>
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-medium capitalize" :class="statusClass(product.status)">
                            {{ product.status }}
                        </span>
                    </div>
                    <p class="text-gray-500 mt-1 capitalize">{{ product.category }}</p>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="product.status !== 'closed'"
                        :disabled="busy"
                        @click="toggleStatus"
                        class="border border-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-50 disabled:opacity-50 transition-colors"
                    >
                        {{ product.status === "active" ? "Pause" : "Activate" }}
                    </button>
                    <NuxtLink
                        :to="`/brand/products/${product.id}/edit`"
                        class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors"
                    >
                        Edit
                    </NuxtLink>
                </div>
            </div>

            <!-- Performance -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl p-5 border border-gray-100">
                    <p class="text-sm text-gray-500">Applications</p>
                    <p class="text-2xl font-bold text-gray-900">{{ counts.total }}</p>
                </div>
                <div class="rounded-2xl p-5 border border-yellow-100 bg-yellow-50">
                    <p class="text-sm text-yellow-700">Pending</p>
                    <p class="text-2xl font-bold text-yellow-800">{{ counts.pending }}</p>
                </div>
                <div class="rounded-2xl p-5 border border-green-100 bg-green-50">
                    <p class="text-sm text-green-700">Approved</p>
                    <p class="text-2xl font-bold text-green-800">{{ counts.approved }}</p>
                </div>
                <div class="rounded-2xl p-5 border border-red-100 bg-red-50">
                    <p class="text-sm text-red-700">Rejected</p>
                    <p class="text-2xl font-bold text-red-800">{{ counts.rejected }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Left: applications + creators -->
                <div class="lg:col-span-2 space-y-5">
                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100">
                            <h2 class="font-semibold text-gray-900">Applications</h2>
                        </div>
                        <div v-if="!applications?.data?.length" class="px-6 py-12 text-center text-gray-400">
                            <p class="font-medium">No applications yet</p>
                            <p class="text-sm mt-1">Creators who apply to this product will appear here.</p>
                        </div>
                        <ul v-else class="divide-y divide-gray-50">
                            <li v-for="a in [...pendingApps, ...decidedApps]" :key="a.id" class="px-6 py-4">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-sm font-bold shrink-0">
                                            {{ a.creator?.name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <NuxtLink :to="`/brand/creators/${a.creator.id}`" class="text-sm font-medium text-gray-900 hover:underline">
                                                {{ a.creator?.name }}
                                            </NuxtLink>
                                            <p class="text-xs text-gray-400">{{ formatDate(a.created_at) }}</p>
                                        </div>
                                    </div>
                                    <div v-if="a.status === 'pending'" class="flex items-center gap-2 shrink-0">
                                        <button :disabled="actingId === a.id" @click="approve(a.id)" class="text-xs px-3 py-1.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50 font-medium transition-colors">
                                            Approve
                                        </button>
                                        <button :disabled="actingId === a.id" @click="openReject(a.id)" class="text-xs px-3 py-1.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50 font-medium transition-colors">
                                            Reject
                                        </button>
                                    </div>
                                    <span
                                        v-else
                                        class="text-xs px-3 py-1 rounded-full font-medium shrink-0"
                                        :class="a.status === 'approved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                    >
                                        {{ a.status }}
                                    </span>
                                </div>
                                <p v-if="a.pitch_message" class="text-sm text-gray-500 mt-2 pl-12 line-clamp-3">
                                    {{ a.pitch_message }}
                                </p>
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 p-6">
                        <h2 class="font-semibold text-gray-900 mb-4">Approved Creators</h2>
                        <p v-if="!approvedApps.length" class="text-sm text-gray-400">No approved creators yet.</p>
                        <div v-else class="flex flex-wrap gap-2">
                            <div
                                v-for="a in approvedApps"
                                :key="a.id"
                                class="flex items-center gap-2 border border-gray-100 rounded-full pl-1 pr-1 py-1"
                            >
                                <NuxtLink :to="`/brand/creators/${a.creator.id}`" class="flex items-center gap-2 hover:underline">
                                    <span class="w-7 h-7 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center">
                                        {{ a.creator?.name?.charAt(0).toUpperCase() }}
                                    </span>
                                    <span class="text-sm text-gray-700">{{ a.creator?.name }}</span>
                                </NuxtLink>
                                <NuxtLink
                                    :to="`/messages?application=${a.id}`"
                                    class="text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 hover:bg-blue-100 font-medium transition-colors"
                                >
                                    Message
                                </NuxtLink>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: facts -->
                <div class="space-y-5">
                    <div class="bg-white rounded-2xl border border-gray-100 p-5">
                        <div class="aspect-[4/3] rounded-xl bg-gray-50 overflow-hidden flex items-center justify-center">
                            <img
                                v-if="product.images?.[activeImage]"
                                :src="`${config.public.storageBase}/${product.images[activeImage]}`"
                                :alt="product.name"
                                class="w-full h-full object-cover"
                            />
                            <AppIcon v-else name="products" class="w-10 h-10 text-gray-300" />
                        </div>
                        <div v-if="product.images?.length > 1" class="flex gap-2 mt-3">
                            <button
                                v-for="(img, i) in product.images"
                                :key="img"
                                @click="activeImage = i"
                                class="w-14 h-14 rounded-lg overflow-hidden border-2"
                                :class="i === activeImage ? 'border-blue-500' : 'border-transparent'"
                            >
                                <img :src="`${config.public.storageBase}/${img}`" alt="" class="w-full h-full object-cover" />
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 mt-4 whitespace-pre-line">{{ product.description }}</p>
                    </div>

                    <div class="bg-white rounded-2xl border border-gray-100 p-5 space-y-3 text-sm">
                        <h2 class="font-semibold text-gray-900">Details</h2>
                        <div class="flex justify-between"><span class="text-gray-500">Price</span><span class="text-gray-900">RM {{ product.price }}</span></div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Commission</span>
                            <span class="font-medium text-green-600">
                                {{ product.commission_rate }}{{ product.commission_type === "percentage" ? "%" : " RM" }}
                            </span>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Start</span><span class="text-gray-900">{{ formatDate(product.campaign_start) }}</span></div>
                        <div class="flex justify-between"><span class="text-gray-500">End</span><span class="text-gray-900">{{ formatDate(product.campaign_end) }}</span></div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500">Campaign</span>
                            <span
                                class="text-xs px-2.5 py-0.5 rounded-full font-medium"
                                :class="
                                    daysLeft !== null && daysLeft < 0
                                        ? 'bg-gray-100 text-gray-500'
                                        : daysLeft !== null && daysLeft <= 7
                                          ? 'bg-red-100 text-red-700'
                                          : 'bg-green-100 text-green-700'
                                "
                            >
                                {{ campaignLabel }}
                            </span>
                        </div>
                        <div v-if="product.max_affiliates">
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-500">Affiliate spots</span>
                                <span class="text-gray-900">{{ counts.approved }} / {{ product.max_affiliates }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full" :style="{ width: spotsPct + '%' }" />
                            </div>
                        </div>
                        <div v-else class="flex justify-between"><span class="text-gray-500">Affiliate spots</span><span class="text-gray-900">Unlimited</span></div>
                    </div>

                    <div v-if="product.variations?.length" class="bg-white rounded-2xl border border-gray-100 p-5">
                        <h2 class="font-semibold text-gray-900 mb-3">Variations</h2>
                        <div v-for="v in product.variations" :key="v.id" class="mb-3 last:mb-0">
                            <p class="text-sm font-medium text-gray-700 mb-1">{{ v.name }}</p>
                            <ul class="text-sm text-gray-500 space-y-0.5">
                                <li v-for="o in v.options" :key="o.value" class="flex justify-between">
                                    <span>{{ o.value }}</span><span>RM {{ o.price }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Reject modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/30" @click="showRejectModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Reject Application</h3>
                    <p class="text-sm text-gray-400 mb-4">Let the creator know why their application was rejected.</p>
                    <textarea
                        v-model="rejectReason"
                        rows="4"
                        maxlength="500"
                        placeholder="e.g. We are looking for creators with a different audience demographic..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                    />
                    <p class="text-xs text-gray-400 mt-1 text-right">{{ rejectReason.length }}/500</p>
                    <div class="flex gap-3 mt-4">
                        <button @click="showRejectModal = false" class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</button>
                        <button @click="confirmReject" :disabled="!rejectReason.trim() || actingId !== null" class="flex-1 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white py-2.5 rounded-xl text-sm font-medium transition-colors">Reject</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
