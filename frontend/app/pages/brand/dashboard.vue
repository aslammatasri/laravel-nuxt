<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

interface Product {
    id: number;
    name: string;
    status: string;
    commission_rate: string;
}
interface Application {
    id: number;
    status: string;
    creator: { name: string };
    product: Product;
}
interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
}

const { $api } = useApi();
const authStore = useAuth();

// ── Fetch stats ────────────────────────────────────────────────
interface Stats {
    applications: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
        approval_rate: number | null;
    };
    products: { total: number; active: number; inactive: number };
    top_products: { id: number; name: string; applications: number }[];
}

const { data: stats, refresh: refreshStats } = await useAsyncData("brand-dashboard-stats", () =>
    $api<Stats>("/brand/dashboard/stats").catch(() => null),
);

const { data: applications, refresh: refreshApplications } = await useAsyncData("brand-applications", () =>
    $api<Paginated<Application>>("/brand/applications").catch(() => null),
);

// ── Quick review actions ───────────────────────────────────────
const actingId = ref<number | null>(null);
const showRejectModal = ref(false);
const rejectAppId = ref<number | null>(null);
const rejectReason = ref("");

async function refreshAll() {
    await Promise.all([refreshApplications(), refreshStats()]);
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

// ── Computed ───────────────────────────────────────────────────
const appStats = computed(
    () =>
        stats.value?.applications ?? {
            total: 0,
            pending: 0,
            approved: 0,
            rejected: 0,
            approval_rate: null,
        },
);
const prod = computed(
    () => stats.value?.products ?? { total: 0, active: 0, inactive: 0 },
);
// Pending first so there is always something to act on
const recentApplications = computed(() => {
    const list = applications.value?.data ?? [];
    return [
        ...list.filter((a) => a.status === "pending"),
        ...list.filter((a) => a.status !== "pending"),
    ].slice(0, 5);
});
const topProducts = computed(() => stats.value?.top_products ?? []);
const topMax = computed(() =>
    Math.max(1, ...topProducts.value.map((p) => p.applications)),
);

// Donut: three arcs on a circle of circumference 100 (r = 15.9155)
const donutSegments = computed(() => {
    const total = appStats.value.total;
    const parts = [
        { key: "approved", label: "Approved", value: appStats.value.approved, color: "#22c55e" },
        { key: "pending", label: "Pending", value: appStats.value.pending, color: "#eab308" },
        { key: "rejected", label: "Rejected", value: appStats.value.rejected, color: "#ef4444" },
    ];
    let offset = 0;
    return parts.map((p) => {
        const pct = total ? (p.value / total) * 100 : 0;
        const seg = { ...p, pct, dash: `${pct} ${100 - pct}`, offset: -offset };
        offset += pct;
        return seg;
    });
});
const activePct = computed(() =>
    prod.value.total ? (prod.value.active / prod.value.total) * 100 : 0,
);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Good morning, {{ authStore.user?.name }} 👋
            </h1>
            <p class="text-gray-500 mt-1">
                Here's what's happening with your campaigns
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Total Applications</p>
                <p class="text-3xl font-bold text-gray-900">{{ appStats.total }}</p>
                <NuxtLink to="/brand/applications" class="text-xs text-blue-600 hover:underline mt-2 block">
                    View all →
                </NuxtLink>
            </div>
            <div class="rounded-2xl p-6 border border-yellow-100 bg-yellow-50">
                <p class="text-sm text-yellow-700 mb-1">Pending Review</p>
                <p class="text-3xl font-bold text-yellow-800">{{ appStats.pending }}</p>
                <NuxtLink to="/brand/applications?status=pending" class="text-xs text-yellow-700 hover:underline mt-2 block">
                    Review now →
                </NuxtLink>
            </div>
            <div class="rounded-2xl p-6 border border-green-100 bg-green-50">
                <p class="text-sm text-green-700 mb-1">Approved</p>
                <p class="text-3xl font-bold text-green-800">{{ appStats.approved }}</p>
                <NuxtLink to="/brand/applications?status=approved" class="text-xs text-green-700 hover:underline mt-2 block">
                    View creators →
                </NuxtLink>
            </div>
            <div class="rounded-2xl p-6 border border-red-100 bg-red-50">
                <p class="text-sm text-red-700 mb-1">Rejected</p>
                <p class="text-3xl font-bold text-red-800">{{ appStats.rejected }}</p>
                <NuxtLink to="/brand/applications?status=rejected" class="text-xs text-red-700 hover:underline mt-2 block">
                    View rejected →
                </NuxtLink>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">
            <!-- Application outcomes donut -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Application Outcomes</h2>
                <div class="flex items-center gap-6">
                    <div class="relative w-32 h-32 shrink-0">
                        <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f3f4f6" stroke-width="4" />
                            <circle
                                v-for="seg in donutSegments"
                                :key="seg.key"
                                v-show="seg.pct > 0"
                                cx="18" cy="18" r="15.9155" fill="none"
                                :stroke="seg.color" stroke-width="4"
                                :stroke-dasharray="seg.dash"
                                :stroke-dashoffset="seg.offset"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold text-gray-900">
                                {{ appStats.approval_rate === null ? "–" : appStats.approval_rate + "%" }}
                            </span>
                            <span class="text-[11px] text-gray-400">approval rate</span>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm">
                        <li v-for="seg in donutSegments" :key="seg.key" class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ background: seg.color }" />
                            <span class="text-gray-600">{{ seg.label }}</span>
                            <span class="font-semibold text-gray-900 ml-auto pl-3">{{ seg.value }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Products active vs inactive -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">Products</h2>
                    <NuxtLink to="/brand/products" class="text-sm text-blue-600 hover:underline">Manage</NuxtLink>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ prod.total }}</p>
                <p class="text-sm text-gray-500 mb-4">total products</p>
                <div class="h-3 w-full rounded-full bg-gray-100 overflow-hidden flex">
                    <div class="h-full bg-blue-500 transition-all" :style="{ width: activePct + '%' }" />
                </div>
                <div class="flex justify-between text-sm mt-3">
                    <span class="flex items-center gap-2 text-gray-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500" />
                        Active <b class="text-gray-900">{{ prod.active }}</b>
                    </span>
                    <span class="flex items-center gap-2 text-gray-600">
                        <span class="w-2.5 h-2.5 rounded-full bg-gray-200" />
                        Inactive <b class="text-gray-900">{{ prod.inactive }}</b>
                    </span>
                </div>
            </div>

            <!-- Top products -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Most Applied Products</h2>
                <p v-if="!topProducts.length" class="text-sm text-gray-400 py-6 text-center">
                    No applications yet
                </p>
                <ul v-else class="space-y-3">
                    <li v-for="p in topProducts" :key="p.id">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700 truncate pr-3">{{ p.name }}</span>
                            <span class="font-semibold text-gray-900">{{ p.applications }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" :style="{ width: (p.applications / topMax) * 100 + '%' }" />
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Recent Applications -->
        <div
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden"
        >
            <div
                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
            >
                <h2 class="font-semibold text-gray-900">Applications to Review</h2>
                <NuxtLink
                    to="/brand/applications"
                    class="text-sm text-blue-600 hover:underline"
                >
                    View all
                </NuxtLink>
            </div>

            <!-- Empty state -->
            <div
                v-if="!applications?.data?.length"
                class="px-6 py-12 text-center text-gray-400"
            >
                <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                    <AppIcon name="applications" class="text-blue-500" />
                </div>
                <p class="font-medium">No applications yet</p>
                <p class="text-sm mt-1">
                    Applications will appear here when creators apply to your
                    products
                </p>
            </div>

            <!-- Applications list -->
            <div v-else class="divide-y divide-gray-50">
                <div
                    v-for="app in recentApplications"
                    :key="app.id"
                    class="px-6 py-4 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-sm font-bold"
                        >
                            {{ app.creator?.name?.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ app.creator?.name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ app.product?.name }}
                            </p>
                        </div>
                    </div>
                    <div v-if="app.status === 'pending'" class="flex items-center gap-2">
                        <button
                            :disabled="actingId === app.id"
                            @click="approve(app.id)"
                            class="text-xs px-3 py-1.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50 font-medium transition-colors"
                        >
                            Approve
                        </button>
                        <button
                            :disabled="actingId === app.id"
                            @click="openReject(app.id)"
                            class="text-xs px-3 py-1.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50 font-medium transition-colors"
                        >
                            Reject
                        </button>
                    </div>
                    <span
                        v-else
                        class="text-xs px-3 py-1 rounded-full font-medium"
                        :class="{
                            'bg-green-100 text-green-700': app.status === 'approved',
                            'bg-red-100 text-red-700': app.status === 'rejected',
                        }"
                    >
                        {{ app.status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Reject modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/30" @click="showRejectModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Reject Application</h3>
                    <p class="text-sm text-gray-400 mb-4">
                        Let the creator know why their application was rejected.
                    </p>
                    <textarea
                        v-model="rejectReason"
                        rows="4"
                        maxlength="500"
                        placeholder="e.g. We are looking for creators with a different audience demographic..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                    />
                    <p class="text-xs text-gray-400 mt-1 text-right">{{ rejectReason.length }}/500</p>
                    <div class="flex gap-3 mt-4">
                        <button
                            @click="showRejectModal = false"
                            class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            @click="confirmReject"
                            :disabled="!rejectReason.trim() || actingId !== null"
                            class="flex-1 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white py-2.5 rounded-xl text-sm font-medium transition-colors"
                        >
                            Reject
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
