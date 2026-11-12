<script setup lang="ts">
definePageMeta({ middleware: "admin", layout: "admin" });

interface UserRow {
    id: number;
    name: string;
    email: string;
    role: "creator" | "brand";
    avatar?: string | null;
    created_at: string;
    suspended_at: string | null;
    suspended_reason: string | null;
}
interface Response {
    data: UserRow[];
    total: number;
    counts: { creators: number; brands: number; suspended: number };
}

const { $api } = useApi();
const route = useRoute();

const role = ref("");
const status = ref<string>((route.query.status as string) || "");
const search = ref("");

const { data, refresh } = await useAsyncData("admin-users", () =>
    $api<Response>("/admin/users", {
        params: {
            role: role.value || undefined,
            status: status.value || undefined,
            search: search.value || undefined,
        },
    }).catch(() => null),
);

watch([role, status], () => refresh());
let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => refresh(), 300);
});
onBeforeUnmount(() => {
    if (searchTimer) clearTimeout(searchTimer);
});

const rows = computed(() => data.value?.data ?? []);
const counts = computed(() => data.value?.counts ?? { creators: 0, brands: 0, suspended: 0 });

const roleTabs = computed(() => [
    { key: "", label: "All", count: undefined as number | undefined },
    { key: "creator", label: "Creators", count: counts.value.creators },
    { key: "brand", label: "Brands", count: counts.value.brands },
]);

function formatDate(d: string | null) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-MY", { year: "numeric", month: "short", day: "numeric" });
}

// ── Actions ────────────────────────────────────────────────────
const actingId = ref<number | null>(null);
const actionError = ref<string | null>(null);
const showModal = ref(false);
const targetId = ref<number | null>(null);
const targetName = ref("");
const reason = ref("");

function openSuspend(u: UserRow) {
    targetId.value = u.id;
    targetName.value = u.name;
    reason.value = "";
    showModal.value = true;
}
async function confirmSuspend() {
    if (!reason.value.trim() || targetId.value === null) return;
    actingId.value = targetId.value;
    actionError.value = null;
    try {
        await $api(`/admin/users/${targetId.value}/suspend`, {
            method: "PATCH",
            body: { reason: reason.value },
        });
        showModal.value = false;
        await refresh();
    } catch (err: any) {
        actionError.value = err?.data?.message || "Could not suspend this account";
    } finally {
        actingId.value = null;
    }
}
async function reactivate(u: UserRow) {
    actingId.value = u.id;
    actionError.value = null;
    try {
        await $api(`/admin/users/${u.id}/reactivate`, { method: "PATCH" });
        await refresh();
    } catch (err: any) {
        actionError.value = err?.data?.message || "Could not reactivate this account";
    } finally {
        actingId.value = null;
    }
}
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Users</h1>
            <p class="text-gray-500 mt-1">Suspending an account signs the user out and blocks login.</p>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="t in roleTabs"
                    :key="t.key"
                    @click="role = t.key"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                    :class="role === t.key ? 'bg-violet-600 text-white' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'"
                >
                    {{ t.label }}<span v-if="t.count !== undefined" class="ml-1 opacity-70">{{ t.count }}</span>
                </button>
                <button
                    @click="status = status === 'suspended' ? '' : 'suspended'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                    :class="status === 'suspended' ? 'bg-red-600 text-white' : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'"
                >
                    Suspended<span class="ml-1 opacity-70">{{ counts.suspended }}</span>
                </button>
            </div>
            <input
                v-model="search"
                type="search"
                placeholder="Search name or email"
                class="w-full sm:w-72 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
            />
        </div>

        <p v-if="actionError" class="mb-4 text-sm text-red-600 bg-red-50 border border-red-100 rounded-xl px-4 py-3" role="alert">
            {{ actionError }}
        </p>

        <div v-if="!rows.length" class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400">
            <p class="font-medium text-gray-600">No users match</p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <ul class="divide-y divide-gray-50">
                <li v-for="u in rows" :key="u.id" class="px-6 py-4 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <Avatar :src="u.avatar" :name="u.name" size="md" :tone="u.role === 'brand' ? 'blue' : 'green'" />
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-medium text-gray-900 truncate">{{ u.name }}</p>
                                <span class="text-[11px] px-2 py-0.5 rounded-full capitalize" :class="u.role === 'brand' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700'">{{ u.role }}</span>
                                <span v-if="u.suspended_at" class="text-[11px] px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Suspended</span>
                            </div>
                            <p class="text-sm text-gray-500 truncate">{{ u.email }} · joined {{ formatDate(u.created_at) }}</p>
                            <p v-if="u.suspended_at && u.suspended_reason" class="text-xs text-red-500 mt-0.5">
                                Reason: {{ u.suspended_reason }}
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="u.suspended_at"
                        :disabled="actingId === u.id"
                        @click="reactivate(u)"
                        class="text-xs px-3 py-1.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 disabled:opacity-50 font-medium transition-colors"
                    >
                        Reactivate
                    </button>
                    <button
                        v-else
                        :disabled="actingId === u.id"
                        @click="openSuspend(u)"
                        class="text-xs px-3 py-1.5 rounded-xl bg-red-50 text-red-700 hover:bg-red-100 disabled:opacity-50 font-medium transition-colors"
                    >
                        Suspend
                    </button>
                </li>
            </ul>
        </div>

        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center">
                <div class="absolute inset-0 bg-black/30" @click="showModal = false" />
                <div class="relative bg-white rounded-2xl shadow-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Suspend {{ targetName }}?</h3>
                    <p class="text-sm text-gray-400 mb-4">
                        They are signed out now and cannot log in until reactivated. A suspended brand's products are hidden from creators.
                    </p>
                    <textarea
                        v-model="reason"
                        rows="3"
                        maxlength="500"
                        placeholder="Reason (kept for your records)"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"
                    />
                    <p class="text-xs text-gray-400 mt-1 text-right">{{ reason.length }}/500</p>
                    <div class="flex gap-3 mt-4">
                        <button @click="showModal = false" class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">Cancel</button>
                        <button @click="confirmSuspend" :disabled="!reason.trim() || actingId !== null" class="flex-1 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white py-2.5 rounded-xl text-sm font-medium transition-colors">Suspend</button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
