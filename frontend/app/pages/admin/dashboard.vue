<script setup lang="ts">
definePageMeta({ middleware: "admin", layout: "admin" });

interface Stats {
    users: { creators: number; brands: number; suspended: number };
    verification: { pending: number; verified: number; rejected: number; not_started: number };
    products: { active: number; paused: number; closed: number; total: number };
    applications: { pending: number; approved: number; rejected: number; total: number };
    signups: { label: string; creators: number; brands: number }[];
}

const { $api } = useApi();
const authStore = useAuth();

const { data: stats } = await useAsyncData("admin-stats", () =>
    $api<Stats>("/admin/stats").catch(() => null),
);

const users = computed(() => stats.value?.users ?? { creators: 0, brands: 0, suspended: 0 });
const verification = computed(() => stats.value?.verification ?? { pending: 0, verified: 0, rejected: 0, not_started: 0 });
const products = computed(() => stats.value?.products ?? { active: 0, paused: 0, closed: 0, total: 0 });
const applications = computed(() => stats.value?.applications ?? { pending: 0, approved: 0, rejected: 0, total: 0 });
const signups = computed(() => stats.value?.signups ?? []);
const signupMax = computed(() =>
    Math.max(1, ...signups.value.map((m) => m.creators + m.brands)),
);

// Donut: circle of circumference 100 (r = 15.9155)
function donut(parts: { key: string; label: string; value: number; color: string }[]) {
    const total = parts.reduce((n, p) => n + p.value, 0);
    let offset = 0;
    return parts.map((p) => {
        const pct = total ? (p.value / total) * 100 : 0;
        const seg = { ...p, pct, dash: `${pct} ${100 - pct}`, offset: -offset };
        offset += pct;
        return seg;
    });
}
const appDonut = computed(() =>
    donut([
        { key: "approved", label: "Approved", value: applications.value.approved, color: "#22c55e" },
        { key: "pending", label: "Pending", value: applications.value.pending, color: "#eab308" },
        { key: "rejected", label: "Rejected", value: applications.value.rejected, color: "#ef4444" },
    ]),
);
const approvalRate = computed(() => {
    const decided = applications.value.approved + applications.value.rejected;
    return decided ? Math.round((applications.value.approved / decided) * 100) : null;
});
const activePct = computed(() =>
    products.value.total ? (products.value.active / products.value.total) * 100 : 0,
);
const brandTotal = computed(
    () => verification.value.pending + verification.value.verified + verification.value.rejected + verification.value.not_started,
);
const verifRows = computed(() => [
    { label: "Verified", value: verification.value.verified, bar: "bg-green-500" },
    { label: "Pending", value: verification.value.pending, bar: "bg-yellow-500" },
    { label: "Rejected", value: verification.value.rejected, bar: "bg-red-500" },
    { label: "Not submitted", value: verification.value.not_started, bar: "bg-gray-300" },
]);
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Platform overview</h1>
            <p class="text-gray-500 mt-1">Welcome, {{ authStore.user?.name }}</p>
        </div>

        <!-- Action needed -->
        <NuxtLink
            v-if="verification.pending > 0"
            to="/admin/brands?status=pending"
            class="flex items-center justify-between gap-4 rounded-2xl border border-yellow-100 bg-yellow-50 text-yellow-800 hover:bg-yellow-100 px-5 py-4 mb-6 transition-colors"
        >
            <span>
                <span class="font-semibold block">
                    {{ verification.pending }} brand{{ verification.pending === 1 ? "" : "s" }} waiting for verification
                </span>
                <span class="text-sm">Review their SSM details.</span>
            </span>
            <span class="text-sm font-medium shrink-0">Review →</span>
        </NuxtLink>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-2xl p-5 border border-gray-100">
                <p class="text-sm text-gray-500">Creators</p>
                <p class="text-3xl font-bold text-gray-900">{{ users.creators }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100">
                <p class="text-sm text-gray-500">Brands</p>
                <p class="text-3xl font-bold text-gray-900">{{ users.brands }}</p>
            </div>
            <div class="bg-white rounded-2xl p-5 border border-gray-100">
                <p class="text-sm text-gray-500">Campaigns</p>
                <p class="text-3xl font-bold text-gray-900">{{ products.total }}</p>
            </div>
            <NuxtLink to="/admin/users?status=suspended" class="rounded-2xl p-5 border border-red-100 bg-red-50 hover:bg-red-100 transition-colors">
                <p class="text-sm text-red-700">Suspended accounts</p>
                <p class="text-3xl font-bold text-red-800">{{ users.suspended }}</p>
            </NuxtLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
            <!-- Applications donut -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Applications</h2>
                <div class="flex items-center gap-6">
                    <div class="relative w-32 h-32 shrink-0">
                        <svg viewBox="0 0 36 36" class="w-full h-full -rotate-90">
                            <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f3f4f6" stroke-width="4" />
                            <circle
                                v-for="seg in appDonut"
                                :key="seg.key"
                                v-show="seg.pct > 0"
                                cx="18" cy="18" r="15.9155" fill="none"
                                :stroke="seg.color" stroke-width="4"
                                :stroke-dasharray="seg.dash"
                                :stroke-dashoffset="seg.offset"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold text-gray-900">{{ applications.total }}</span>
                            <span class="text-[11px] text-gray-400">
                                {{ approvalRate === null ? "total" : approvalRate + "% approved" }}
                            </span>
                        </div>
                    </div>
                    <ul class="space-y-2 text-sm">
                        <li v-for="seg in appDonut" :key="seg.key" class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :style="{ background: seg.color }" />
                            <span class="text-gray-600">{{ seg.label }}</span>
                            <span class="font-semibold text-gray-900 ml-auto pl-3">{{ seg.value }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Products -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <h2 class="font-semibold text-gray-900 mb-4">Campaigns</h2>
                <p class="text-3xl font-bold text-gray-900">{{ products.total }}</p>
                <p class="text-sm text-gray-500 mb-4">listed on the platform</p>
                <div class="h-3 w-full rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full bg-violet-500 transition-all" :style="{ width: activePct + '%' }" />
                </div>
                <div class="grid grid-cols-3 text-sm mt-3 gap-2">
                    <span class="text-gray-600">Active <b class="text-gray-900 block">{{ products.active }}</b></span>
                    <span class="text-gray-600">Paused <b class="text-gray-900 block">{{ products.paused }}</b></span>
                    <span class="text-gray-600">Closed <b class="text-gray-900 block">{{ products.closed }}</b></span>
                </div>
            </div>

            <!-- Brand verification -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">Brand verification</h2>
                    <NuxtLink to="/admin/brands" class="text-sm text-violet-600 hover:underline">Open</NuxtLink>
                </div>
                <ul class="space-y-3">
                    <li v-for="r in verifRows" :key="r.label">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-700">{{ r.label }}</span>
                            <span class="font-semibold text-gray-900">{{ r.value }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full" :class="r.bar" :style="{ width: brandTotal ? (r.value / brandTotal) * 100 + '%' : '0%' }" />
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Signups -->
        <div class="bg-white rounded-2xl border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900">New sign-ups (6 months)</h2>
                <div class="flex gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-green-500" />Creators</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500" />Brands</span>
                </div>
            </div>
            <div class="flex items-end justify-between gap-3 h-36">
                <div v-for="m in signups" :key="m.label" class="flex-1 flex flex-col items-center justify-end h-full">
                    <span class="text-xs font-medium text-gray-700 mb-1">{{ m.creators + m.brands }}</span>
                    <div class="w-full flex flex-col justify-end rounded-t-md overflow-hidden" :style="{ height: m.creators + m.brands ? Math.max(6, ((m.creators + m.brands) / signupMax) * 100) + '%' : '4px' }" :class="{ 'bg-gray-100': !(m.creators + m.brands) }">
                        <div v-if="m.brands" class="bg-blue-500" :style="{ flex: m.brands }" />
                        <div v-if="m.creators" class="bg-green-500" :style="{ flex: m.creators }" />
                    </div>
                </div>
            </div>
            <div class="flex justify-between gap-3 mt-2">
                <span v-for="m in signups" :key="m.label" class="flex-1 text-center text-[11px] text-gray-400">{{ m.label }}</span>
            </div>
        </div>
    </div>
</template>
