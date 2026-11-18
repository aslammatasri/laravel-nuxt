<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

interface Product {
    id: number;
    name: string;
    status: string;
    flat_fee?: string | number | null;
}
interface Application {
    id: number;
    status: string;
    creator: { name: string; avatar?: string | null };
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
    actions: {
        pending_applications: number;
        deliverables_to_review: number;
        fees_due: { count: number; amount: number };
        overdue_creators: number;
    };
    spend: { due: number; awaiting: number; paid_this_month: number; paid_total: number };
    rating: {
        average: number | null;
        count: number;
        latest: { rating: number; body: string | null; creator: string | null; campaign: string | null } | null;
    };
    setup: { has_about: boolean; has_campaign: boolean; has_creator: boolean };
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

const { data: verification } = await useAsyncData("brand-verification-status", () =>
    $api<{ verification_status: string }>("/brand/verification").catch(() => null),
);
const verificationStatus = computed(() => verification.value?.verification_status ?? "unverified");

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

const money = (n: number) =>
    "RM " + n.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// "Needs your action": the things only the brand can unblock, most urgent first
const actionItems = computed(() => {
    const a = stats.value?.actions;
    if (!a) return [];
    return [
        { key: "fees", label: "Fees to pay", value: a.fees_due.count, sub: a.fees_due.count ? money(a.fees_due.amount) + " due" : "Nothing due", to: "/brand/creators", tone: "amber" },
        { key: "deliverables", label: "Deliverables to review", value: a.deliverables_to_review, sub: "Waiting for your approval", to: "/brand/creators", tone: "blue" },
        { key: "applications", label: "Applications to review", value: a.pending_applications, sub: "Creators waiting for an answer", to: "/brand/applications?status=pending", tone: "blue" },
        { key: "overdue", label: "Overdue creators", value: a.overdue_creators, sub: "Past a task due date", to: "/brand/creators", tone: "red" },
    ];
});
const allCaughtUp = computed(() => actionItems.value.length > 0 && actionItems.value.every((i) => i.value === 0));

const spend = computed(() => stats.value?.spend ?? { due: 0, awaiting: 0, paid_this_month: 0, paid_total: 0 });
const rating = computed(() => stats.value?.rating ?? { average: null, count: 0, latest: null });

const setupSteps = computed(() => {
    const st = stats.value?.setup;
    if (!st) return [];
    return [
        { key: "about", label: "Write your brand introduction", done: st.has_about, to: "/profile" },
        { key: "campaign", label: "Create your first campaign", done: st.has_campaign, to: "/brand/products/create" },
        { key: "creator", label: "Approve your first creator", done: st.has_creator, to: "/brand/applications" },
    ];
});
const setupDone = computed(() => setupSteps.value.filter((x) => x.done).length);
const showSetup = computed(() => setupSteps.value.length > 0 && setupDone.value < setupSteps.value.length);

const activePct = computed(() =>
    prod.value.total ? (prod.value.active / prod.value.total) * 100 : 0,
);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Welcome back, {{ authStore.user?.name }}
            </h1>
            <p class="text-gray-500 mt-1">
                What needs you today, and where your budget stands
            </p>
        </div>

        <!-- Verification prompt -->
        <NuxtLink
            v-if="verificationStatus !== 'verified'"
            to="/brand/verification"
            class="flex items-center justify-between gap-4 rounded-2xl border px-5 py-4 mb-6 transition-colors"
            :class="
                verificationStatus === 'rejected'
                    ? 'bg-red-50 border-red-100 text-red-800 hover:bg-red-100'
                    : verificationStatus === 'pending'
                      ? 'bg-yellow-50 border-yellow-100 text-yellow-800 hover:bg-yellow-100'
                      : 'bg-blue-50 border-blue-100 text-blue-800 hover:bg-blue-100'
            "
        >
            <span>
                <span class="font-semibold block">
                    {{
                        verificationStatus === "pending"
                            ? "Verification under review"
                            : verificationStatus === "rejected"
                              ? "Verification rejected"
                              : "Verify your brand"
                    }}
                </span>
                <span class="text-sm">
                    {{
                        verificationStatus === "pending"
                            ? "We are checking your SSM details."
                            : verificationStatus === "rejected"
                              ? "See the reason and resubmit."
                              : "Add your SSM number so creators can trust you."
                    }}
                </span>
            </span>
            <span class="text-sm font-medium shrink-0">Open →</span>
        </NuxtLink>

        <!-- Setup checklist (new brands only) -->
        <section v-if="showSetup" class="bg-white rounded-2xl border border-gray-100 p-6 mb-6" aria-labelledby="bd-setup">
            <div class="flex items-center justify-between gap-4 mb-3">
                <h2 id="bd-setup" class="font-semibold text-gray-900">Get set up</h2>
                <span class="text-sm text-gray-500 tabular-nums">{{ setupDone }} of {{ setupSteps.length }} done</span>
            </div>
            <ul class="divide-y divide-gray-50">
                <li v-for="step in setupSteps" :key="step.key">
                    <NuxtLink :to="step.to" class="flex items-center gap-3 py-3 group">
                        <span
                            class="w-5 h-5 rounded-full border flex items-center justify-center shrink-0"
                            :class="step.done ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300'"
                            aria-hidden="true"
                        >
                            <svg v-if="step.done" viewBox="0 0 20 20" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m4.5 10.5 3.5 3.5 7.5-8" /></svg>
                        </span>
                        <span class="text-sm" :class="step.done ? 'text-gray-400 line-through' : 'text-gray-800 group-hover:text-blue-700'">{{ step.label }}</span>
                        <span v-if="!step.done" class="ml-auto text-sm font-medium text-blue-600">Start →</span>
                    </NuxtLink>
                </li>
            </ul>
        </section>

        <!-- Needs your action -->
        <section class="mb-6" aria-labelledby="bd-actions">
            <div class="flex items-baseline justify-between mb-3">
                <h2 id="bd-actions" class="font-semibold text-gray-900">Needs your action</h2>
                <span v-if="allCaughtUp" class="text-sm text-green-700">You're all caught up</span>
            </div>
            <ul class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <li v-for="i in actionItems" :key="i.key">
                    <NuxtLink
                        :to="i.to"
                        class="block h-full rounded-2xl border p-5 transition-colors focus-visible:outline-2 focus-visible:outline-blue-600"
                        :class="
                            i.value === 0
                                ? 'bg-white border-gray-100 hover:border-gray-200'
                                : i.tone === 'amber'
                                  ? 'bg-yellow-50 border-yellow-100 hover:border-yellow-200'
                                  : i.tone === 'red'
                                    ? 'bg-red-50 border-red-100 hover:border-red-200'
                                    : 'bg-blue-50 border-blue-100 hover:border-blue-200'
                        "
                    >
                        <p class="text-sm" :class="i.value === 0 ? 'text-gray-500' : 'text-gray-700'">{{ i.label }}</p>
                        <p class="text-3xl font-bold mt-1 tabular-nums" :class="i.value === 0 ? 'text-gray-300' : 'text-gray-900'">{{ i.value }}</p>
                        <p class="text-xs mt-1" :class="i.value === 0 ? 'text-gray-400' : 'text-gray-600'">{{ i.sub }}</p>
                    </NuxtLink>
                </li>
            </ul>
        </section>

        <!-- Spend + rating -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
            <section class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6" aria-labelledby="bd-spend">
                <div class="flex items-center justify-between mb-4">
                    <h2 id="bd-spend" class="font-semibold text-gray-900">Creator fees</h2>
                    <NuxtLink to="/brand/creators" class="text-sm text-blue-600 hover:underline">Pay creators</NuxtLink>
                </div>
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-4">
                    <div>
                        <dt class="text-xs text-gray-400">Due now</dt>
                        <dd class="text-xl font-bold tabular-nums" :class="spend.due ? 'text-yellow-700' : 'text-gray-900'">{{ money(spend.due) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Awaiting approval</dt>
                        <dd class="text-xl font-bold text-gray-900 tabular-nums">{{ money(spend.awaiting) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Paid this month</dt>
                        <dd class="text-xl font-bold text-gray-900 tabular-nums">{{ money(spend.paid_this_month) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-400">Paid in total</dt>
                        <dd class="text-xl font-bold text-gray-900 tabular-nums">{{ money(spend.paid_total) }}</dd>
                    </div>
                </dl>
                <p class="text-xs text-gray-400 mt-4">
                    "Due now" is work you have approved but not paid. "Awaiting approval" becomes due once you approve every deliverable.
                </p>
            </section>

            <section class="bg-white rounded-2xl border border-gray-100 p-6" aria-labelledby="bd-rating">
                <div class="flex items-center justify-between mb-4">
                    <h2 id="bd-rating" class="font-semibold text-gray-900">Creator rating</h2>
                    <NuxtLink to="/brand/reviews" class="text-sm text-blue-600 hover:underline">All reviews</NuxtLink>
                </div>
                <template v-if="rating.count">
                    <div class="flex items-center gap-3">
                        <span class="text-3xl font-bold text-gray-900 tabular-nums">{{ rating.average?.toFixed(1) }}</span>
                        <div>
                            <StarRating :model-value="rating.average ?? 0" readonly size="sm" />
                            <p class="text-xs text-gray-400 mt-0.5">{{ rating.count }} review{{ rating.count === 1 ? "" : "s" }}</p>
                        </div>
                    </div>
                    <blockquote v-if="rating.latest?.body" class="mt-4 border-l-2 border-gray-100 pl-3 text-sm text-gray-600 line-clamp-3">
                        {{ rating.latest.body }}
                        <footer class="text-xs text-gray-400 mt-1">{{ rating.latest.creator }}<template v-if="rating.latest.campaign"> · {{ rating.latest.campaign }}</template></footer>
                    </blockquote>
                </template>
                <p v-else class="text-sm text-gray-400">
                    No reviews yet. Creators can review you once their work is approved or paid.
                </p>
            </section>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
            <!-- Products active vs inactive -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-gray-900">Campaigns</h2>
                    <NuxtLink to="/brand/products" class="text-sm text-blue-600 hover:underline">Manage</NuxtLink>
                </div>
                <p class="text-3xl font-bold text-gray-900">{{ prod.total }}</p>
                <p class="text-sm text-gray-500 mb-4">total campaigns</p>
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
                <h2 class="font-semibold text-gray-900 mb-4">Most Applied Campaigns</h2>
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
            <p v-if="appStats.total" class="px-6 py-2.5 text-xs text-gray-500 bg-gray-50 border-b border-gray-100 tabular-nums">
                {{ appStats.total }} total · {{ appStats.approved }} approved · {{ appStats.rejected }} rejected
                <template v-if="appStats.approval_rate !== null"> · {{ appStats.approval_rate }}% approval rate</template>
            </p>

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
                        <Avatar :src="app.creator?.avatar" :name="app.creator?.name" size="sm" />
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
