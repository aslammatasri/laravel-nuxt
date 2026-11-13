<script setup lang="ts">
definePageMeta({
    middleware: "creator",
    layout: "creator",
});

interface Product {
    id: number;
    name: string;
    commission_rate: string;
    commission_type: string;
    category: string;
    price: number;
}

const { $api } = useApi();
const authStore = useAuth();

interface Stats {
    applications: {
        total: number;
        pending: number;
        approved: number;
        rejected: number;
        approval_rate: number | null;
    };
    by_month: { label: string; count: number }[];
    by_category: { category: string; count: number }[];
    ending_soon: {
        product_id: number;
        name: string;
        campaign_end: string;
        days_left: number;
    }[];
    social: {
        accounts: number;
        subscribers: number;
        avg_recent_views: number;
        engagement_rate: number | null;
        last_synced_at: string | null;
    };
}

const { data: stats } = await useAsyncData("creator-dashboard-stats", () =>
    $api<Stats>("/creator/dashboard/stats").catch(() => null),
);

const { data: marketplace } = await useAsyncData("creator-marketplace", () =>
    $api<{ data: Product[] }>("/creator/marketplace", {
        params: { per_page: 5 },
    }).catch(() => null),
);

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
const byMonth = computed(() => stats.value?.by_month ?? []);
const monthMax = computed(() =>
    Math.max(1, ...byMonth.value.map((m) => m.count)),
);
const byCategory = computed(() => stats.value?.by_category ?? []);
const categoryMax = computed(() =>
    Math.max(1, ...byCategory.value.map((c) => c.count)),
);
const endingSoon = computed(() => stats.value?.ending_soon ?? []);
const social = computed(() => stats.value?.social);

// Donut: circle of circumference 100 (r = 15.9155)
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

const compact = (n: number) =>
    new Intl.NumberFormat("en", { notation: "compact" }).format(n);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Welcome back, {{ authStore.user?.name }} 👋
            </h1>
            <p class="text-gray-500 mt-1">
                Track your applications and find new campaigns to join
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Total Applications</p>
                <p class="text-3xl font-bold text-gray-900">{{ appStats.total }}</p>
                <NuxtLink to="/creator/dashboard#applications" class="text-xs text-green-600 hover:underline mt-2 block">
                    View all →
                </NuxtLink>
            </div>
            <div class="bg-yellow-50 rounded-2xl p-6 border border-yellow-100">
                <p class="text-sm text-yellow-700 mb-1">Pending</p>
                <p class="text-3xl font-bold text-yellow-800">{{ appStats.pending }}</p>
                <NuxtLink to="/creator/dashboard?status=pending#applications" class="text-xs text-yellow-700 hover:underline mt-2 block">
                    Check status →
                </NuxtLink>
            </div>
            <div class="bg-green-50 rounded-2xl p-6 border border-green-100">
                <p class="text-sm text-green-700 mb-1">Active Collabs</p>
                <p class="text-3xl font-bold text-green-800">{{ appStats.approved }}</p>
                <NuxtLink to="/creator/collaborations" class="text-xs text-green-700 hover:underline mt-2 block">
                    View collabs →
                </NuxtLink>
            </div>
            <div class="bg-red-50 rounded-2xl p-6 border border-red-100">
                <p class="text-sm text-red-700 mb-1">Rejected</p>
                <p class="text-3xl font-bold text-red-800">{{ appStats.rejected }}</p>
                <NuxtLink to="/creator/dashboard?status=rejected#applications" class="text-xs text-red-700 hover:underline mt-2 block">
                    View rejected →
                </NuxtLink>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
            <!-- Outcomes donut -->
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
                            <span class="text-[11px] text-gray-400">success rate</span>
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

            <!-- Applications by month -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Applications (6 months)</h2>
                <div class="flex items-end justify-between gap-2 h-32">
                    <div v-for="m in byMonth" :key="m.label" class="flex-1 flex flex-col items-center justify-end h-full">
                        <span class="text-xs font-medium text-gray-700 mb-1">{{ m.count }}</span>
                        <div
                            class="w-full rounded-t-md bg-green-500"
                            :class="{ 'bg-gray-100': !m.count }"
                            :style="{ height: m.count ? Math.max(6, (m.count / monthMax) * 100) + '%' : '4px' }"
                        />
                    </div>
                </div>
                <div class="flex justify-between gap-2 mt-2">
                    <span v-for="m in byMonth" :key="m.label" class="flex-1 text-center text-[11px] text-gray-400">
                        {{ m.label }}
                    </span>
                </div>
            </div>

            <!-- By category -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Applications by Category</h2>
                <p v-if="!byCategory.length" class="text-sm text-gray-400 py-6 text-center">
                    No applications yet
                </p>
                <ul v-else class="space-y-3">
                    <li v-for="c in byCategory" :key="c.category">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700 capitalize">{{ c.category }}</span>
                            <span class="font-semibold text-gray-900">{{ c.count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-green-500 rounded-full" :style="{ width: (c.count / categoryMax) * 100 + '%' }" />
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Ending soon + social reach -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Campaigns Ending Soon</h2>
                <p v-if="!endingSoon.length" class="text-sm text-gray-400 py-4 text-center">
                    No approved campaigns ending in the next 14 days
                </p>
                <ul v-else class="divide-y divide-gray-50">
                    <li v-for="c in endingSoon" :key="c.product_id" class="py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-900">{{ c.name }}</span>
                        <span
                            class="text-xs px-3 py-1 rounded-full font-medium"
                            :class="c.days_left <= 3 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'"
                        >
                            {{ c.days_left === 0 ? "Ends today" : c.days_left + "d left" }}
                        </span>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">Social Reach</h2>
                    <NuxtLink to="/creator/portfolio" class="text-sm text-green-600 hover:underline">Portfolio</NuxtLink>
                </div>
                <div v-if="social?.accounts" class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ compact(social.subscribers) }}</p>
                        <p class="text-xs text-gray-500">Subscribers</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">{{ compact(social.avg_recent_views) }}</p>
                        <p class="text-xs text-gray-500">Avg. views</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ social.engagement_rate === null ? "–" : Number(social.engagement_rate).toFixed(1) + "%" }}
                        </p>
                        <p class="text-xs text-gray-500">Engagement</p>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-400 py-4 text-center">
                    Connect a social account in your portfolio to show your reach to brands.
                </p>
            </div>
        </div>

        <!-- Applications -->
        <div class="mb-8">
            <CreatorApplicationsPanel />
        </div>

        <!-- Latest Campaigns in Marketplace -->
        <div
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden"
        >
            <div
                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
            >
                <h2 class="font-semibold text-gray-900">Latest Campaigns</h2>
                <NuxtLink
                    to="/creator/marketplace"
                    class="text-sm text-green-600 hover:underline"
                >
                    Browse all
                </NuxtLink>
            </div>

            <!-- Empty state -->
            <div
                v-if="!marketplace?.data?.length"
                class="px-6 py-12 text-center text-gray-400"
            >
                <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                    <AppIcon name="marketplace" class="text-green-500" />
                </div>
                <p class="font-medium">No campaigns available yet</p>
                <p class="text-sm mt-1">
                    Check back soon for new campaigns to join
                </p>
            </div>

            <!-- Products list -->
            <div v-else class="divide-y divide-gray-50">
                <div
                    v-for="product in marketplace?.data?.slice(0, 5)"
                    :key="product.id"
                    class="px-6 py-4 flex items-center justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            {{ product.name }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ product.category }} · RM {{ product.price }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-green-600">
                            {{ product.commission_rate
                            }}{{
                                product.commission_type === "percentage"
                                    ? "%"
                                    : " RM"
                            }}
                        </span>
                        <NuxtLink
                            :to="`/creator/marketplace/${product.id}`"
                            class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded-xl hover:bg-green-100 transition-colors"
                        >
                            View
                        </NuxtLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
