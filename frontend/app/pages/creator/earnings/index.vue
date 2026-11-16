<script setup lang="ts">
definePageMeta({ middleware: "creator", layout: "creator" });

interface Fee {
    amount: number;
    status: "none" | "pending" | "due" | "paid";
    paid_at: string | null;
    reference: string | null;
    approved: number;
    total: number;
}
interface Campaign {
    id: number;
    name: string;
    fee: Fee;
}
interface Payout {
    bank: string | null;
    account_name: string | null;
    account_number: string | null;
}

const { $api } = useApi();
const { data, error } = await useAsyncData("creator-earnings", () =>
    $api<{ totals: { due: number; pending: number; paid: number }; campaigns: Campaign[] }>("/creator/earnings"),
);
const { data: payout, refresh: refreshPayout } = await useAsyncData("creator-payout", () =>
    $api<{ data: Payout }>("/creator/payout-details").catch(() => null),
);

const totals = computed(() => data.value?.totals);
const campaigns = computed(() => (data.value?.campaigns ?? []).filter((c) => c.fee.status !== "none"));
const hasPayout = computed(() => !!payout.value?.data.account_number);

const money = (n: number) => "RM " + n.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = (d: string) =>
    new Date(d).toLocaleDateString("en-MY", { timeZone: "Asia/Kuala_Lumpur", year: "numeric", month: "short", day: "numeric" });
const STATUS = { pending: "Awaiting approval", due: "Brand to pay", paid: "Paid", none: "" } as const;

// Payout details form
const editing = ref(false);
const form = reactive({ bank: "", account_name: "", account_number: "" });
const saving = ref(false);
const formError = ref("");

function startEdit() {
    const p = payout.value?.data;
    Object.assign(form, { bank: p?.bank ?? "", account_name: p?.account_name ?? "", account_number: p?.account_number ?? "" });
    formError.value = "";
    editing.value = true;
}
async function save() {
    saving.value = true;
    formError.value = "";
    try {
        await $api("/creator/payout-details", { method: "PUT", body: { ...form } });
        await refreshPayout();
        editing.value = false;
    } catch (e) {
        const d = (e as { data?: { errors?: Record<string, string[]> } })?.data;
        formError.value = (d?.errors && Object.values(d.errors)[0]?.[0]) || "Could not save. Try again.";
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="min-w-0">
        <header class="mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-gray-900">Earnings</h1>
            <p class="mt-1 text-sm text-gray-500">Each campaign pays its fee once the brand approves all your deliverables.</p>
        </header>

        <p v-if="error" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800" role="alert">
            Could not load earnings. Refresh to try again.
        </p>

        <template v-else>
            <dl class="grid grid-cols-3 gap-px overflow-hidden rounded-2xl border border-gray-100 bg-gray-100">
                <div class="bg-white p-4">
                    <dt class="text-xs text-gray-500">Brand to pay</dt>
                    <dd class="mt-1 text-xl font-extrabold tabular-nums text-green-700">{{ money(totals?.due ?? 0) }}</dd>
                </div>
                <div class="bg-white p-4">
                    <dt class="text-xs text-gray-500">Awaiting approval</dt>
                    <dd class="mt-1 text-xl font-extrabold tabular-nums text-gray-900">{{ money(totals?.pending ?? 0) }}</dd>
                </div>
                <div class="bg-white p-4">
                    <dt class="text-xs text-gray-500">Paid</dt>
                    <dd class="mt-1 text-xl font-extrabold tabular-nums text-gray-900">{{ money(totals?.paid ?? 0) }}</dd>
                </div>
            </dl>

            <!-- Payout details -->
            <section class="mt-6 rounded-2xl border border-gray-100 bg-white p-5" aria-labelledby="payout-h">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 id="payout-h" class="font-bold text-gray-900">Where to pay you</h2>
                    <button v-if="!editing" type="button" class="text-sm font-semibold text-green-700 underline underline-offset-4" @click="startEdit">
                        {{ hasPayout ? "Edit" : "Add bank details" }}
                    </button>
                </div>

                <p v-if="!editing && !hasPayout" class="mt-2 text-sm text-amber-700">
                    Add your bank details so brands know where to send your fee.
                </p>
                <dl v-else-if="!editing" class="mt-3 grid gap-3 text-sm sm:grid-cols-3">
                    <div><dt class="text-xs text-gray-500">Bank</dt><dd class="font-semibold">{{ payout?.data.bank }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Account name</dt><dd class="font-semibold">{{ payout?.data.account_name }}</dd></div>
                    <div><dt class="text-xs text-gray-500">Account number</dt><dd class="font-semibold tabular-nums">{{ payout?.data.account_number }}</dd></div>
                </dl>

                <form v-else class="mt-3 grid gap-3 sm:grid-cols-3" @submit.prevent="save">
                    <label class="grid gap-1 text-xs text-gray-500">Bank
                        <input v-model="form.bank" required maxlength="80" placeholder="e.g. Maybank" class="min-h-10 rounded-xl border border-gray-200 px-3 text-sm text-gray-900" />
                    </label>
                    <label class="grid gap-1 text-xs text-gray-500">Account name
                        <input v-model="form.account_name" required maxlength="120" class="min-h-10 rounded-xl border border-gray-200 px-3 text-sm text-gray-900" />
                    </label>
                    <label class="grid gap-1 text-xs text-gray-500">Account number
                        <input v-model="form.account_number" required maxlength="40" inputmode="numeric" class="min-h-10 rounded-xl border border-gray-200 px-3 text-sm text-gray-900" />
                    </label>
                    <div class="flex items-center gap-3 sm:col-span-3">
                        <button type="submit" :disabled="saving" class="min-h-10 rounded-full bg-green-700 px-5 text-sm font-semibold text-white disabled:opacity-50">
                            {{ saving ? "Saving…" : "Save" }}
                        </button>
                        <button type="button" class="text-sm font-semibold text-gray-600 underline underline-offset-4" @click="editing = false">Cancel</button>
                        <p v-if="formError" class="text-sm text-amber-700" role="alert">{{ formError }}</p>
                    </div>
                </form>
            </section>

            <div v-if="!campaigns.length" class="mt-6 rounded-2xl border border-gray-100 bg-white px-6 py-14 text-center">
                <p class="font-medium text-gray-700">No fees yet</p>
                <p class="mt-1 text-sm text-gray-400">Campaigns with a flat fee appear here once you are approved.</p>
                <NuxtLink to="/creator/collaborations" class="mt-4 inline-block text-sm font-semibold text-green-700 underline">Go to collaborations</NuxtLink>
            </div>

            <ul v-else class="mt-6 divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-100 bg-white">
                <li v-for="c in campaigns" :key="c.id" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-4">
                    <div class="min-w-0 flex-1 basis-48">
                        <p class="font-semibold text-gray-900">{{ c.name }}</p>
                        <p class="text-sm text-gray-500 tabular-nums">
                            <template v-if="c.fee.status === 'paid'">Paid {{ c.fee.paid_at ? date(c.fee.paid_at) : "" }}<template v-if="c.fee.reference"> · ref {{ c.fee.reference }}</template></template>
                            <template v-else>{{ c.fee.approved }}/{{ c.fee.total }} deliverables approved</template>
                        </p>
                    </div>
                    <span class="font-extrabold tabular-nums text-gray-900">{{ money(c.fee.amount) }}</span>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="{
                            'bg-green-700 text-white': c.fee.status === 'paid',
                            'bg-green-50 text-green-700': c.fee.status === 'due',
                            'bg-gray-100 text-gray-600': c.fee.status === 'pending',
                        }"
                    >{{ STATUS[c.fee.status] }}</span>
                    <NuxtLink to="/creator/collaborations" class="text-sm font-semibold text-gray-600 underline underline-offset-4">Checklist</NuxtLink>
                </li>
            </ul>
        </template>
    </div>
</template>
