<script setup lang="ts">
definePageMeta({ middleware: "admin", layout: "admin" });

interface BrandRow {
    id: number;
    company_name: string | null;
    industry: string | null;
    ssm_no: string | null;
    verification_status: "pending" | "verified" | "rejected" | "unverified";
    verification_note: string | null;
    submitted_at: string | null;
    verified_at: string | null;
    user: { id: number; name: string; email: string };
}
interface Response {
    data: BrandRow[];
    total: number;
    counts: { pending: number; verified: number; rejected: number };
}

const { $api } = useApi();
const route = useRoute();

const status = ref<string>((route.query.status as string) || "pending");
const search = ref("");

const { data, refresh, status: loadState } = await useAsyncData(
    "admin-brands",
    () =>
        $api<Response>("/admin/brands", {
            params: { status: status.value || undefined, search: search.value || undefined },
        }).catch(() => null),
);

watch(status, () => refresh());
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => refresh(), 300);
});
onBeforeUnmount(() => {
    if (searchTimer) clearTimeout(searchTimer);
});

const counts = computed(() => data.value?.counts ?? { pending: 0, verified: 0, rejected: 0 });
const rows = computed(() => data.value?.data ?? []);

const tabs = computed(() => [
    { key: "pending", label: "Pending", count: counts.value.pending },
    { key: "verified", label: "Verified", count: counts.value.verified },
    { key: "rejected", label: "Rejected", count: counts.value.rejected },
    { key: "", label: "All", count: counts.value.pending + counts.value.verified + counts.value.rejected },
]);

const badge: Record<string, string> = {
    pending: "bg-yellow-100 text-yellow-700",
    verified: "bg-green-100 text-green-700",
    rejected: "bg-red-100 text-red-700",
};

function formatDate(d: string | null) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-MY", { year: "numeric", month: "short", day: "numeric" });
}

// ── Actions ────────────────────────────────────────────────────
const actingId = ref<number | null>(null);
const actionError = ref<string | null>(null);
const showRejectModal = ref(false);
const rejectId = ref<number | null>(null);
const rejectReason = ref("");

async function verify(id: number) {
    actingId.value = id;
    actionError.value = null;
    try {
        await $api(`/admin/brands/${id}/verify`, { method: "PATCH" });
        await refresh();
    } catch (err: any) {
        actionError.value = err?.data?.message || "Could not verify this brand";
    } finally {
        actingId.value = null;
    }
}
function openReject(id: number) {
    rejectId.value = id;
    rejectReason.value = "";
    showRejectModal.value = true;
}
async function confirmReject() {
    if (!rejectReason.value.trim() || rejectId.value === null) return;
    actingId.value = rejectId.value;
    actionError.value = null;
    try {
        await $api(`/admin/brands/${rejectId.value}/reject`, {
            method: "PATCH",
            body: { reason: rejectReason.value },
        });
        showRejectModal.value = false;
        await refresh();
    } catch (err: any) {
        actionError.value = err?.data?.message || "Could not reject this brand";
    } finally {
        actingId.value = null;
    }
}
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Brand verification</h1>
            <p class="text-gray-500 mt-1">
                Check each brand's SSM registration before marking it verified.
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div class="flex gap-2">
                <button
                    v-for="t in tabs"
                    :key="t.key"
                    @click="status = t.key"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                    :class="
                        status === t.key
                            ? 'bg-violet-600 text-white'
                            : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'
                    "
                >
                    {{ t.label }}
                    <span class="ml-1 opacity-70">{{ t.count }}</span>
                </button>
            </div>
            <input
                v-model="search"
                type="search"
                placeholder="Search company, SSM no, name or email"
                class="w-full sm:w-80 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
            />
        </div>

        <p v-if="actionError" class="mb-4 text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-3" role="alert">
            {{ actionError }}
        </p>

        <div v-if="!rows.length" class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400">
            <div class="w-14 h-14 rounded-full bg-violet-50 flex items-center justify-center mx-auto mb-4">
                <AppIcon name="shield" class="text-violet-500" />
            </div>
            <p class="font-medium text-gray-600">
                {{ loadState === "pending" ? "Loading…" : "No brands in this view" }}
            </p>
            <p class="text-sm mt-1">Brands appear here after they submit their company details.</p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <ul class="divide-y divide-gray-50">
                <li v-for="b in rows" :key="b.id" class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold text-gray-900 truncate">{{ b.company_name || "(no company name)" }}</p>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-medium capitalize" :class="badge[b.verification_status]">
                                {{ b.verification_status }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5">
                            SSM <span class="font-mono text-gray-700">{{ b.ssm_no }}</span>
                            <span v-if="b.industry"> · {{ b.industry }}</span>
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ b.user.name }} · {{ b.user.email }} · submitted {{ formatDate(b.submitted_at) }}
                        </p>
                        <p v-if="b.verification_status === 'rejected' && b.verification_note" class="text-xs text-red-500 mt-1">
                            Reason: {{ b.verification_note }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            v-if="b.verification_status !== 'verified'"
                            :disabled="actingId === b.id"
                            @click="verify(b.id)"
                            class="text-xs px-3 py-1.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50 font-medium transition-colors"
                        >
                            Verify
                        </button>
                        <button
                            v-if="b.verification_status !== 'rejected'"
                            :disabled="actingId === b.id"
                            @click="openReject(b.id)"
                            class="text-xs px-3 py-1.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50 font-medium transition-colors"
                        >
                            {{ b.verification_status === "verified" ? "Revoke" : "Reject" }}
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/30" @click="showRejectModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Reject verification</h3>
                    <p class="text-sm text-gray-400 mb-4">The brand sees this reason and can resubmit.</p>
                    <textarea
                        v-model="rejectReason"
                        rows="4"
                        maxlength="500"
                        placeholder="e.g. SSM number not found, please check and resubmit."
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
