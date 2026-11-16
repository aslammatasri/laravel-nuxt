<script setup lang="ts">
definePageMeta({ middleware: "brand", layout: "brand" });

interface Task {
    id: number;
    title: string;
    type: string;
    due_date: string | null;
    link: string | null;
    done: boolean;
    review: "pending" | "approved" | "changes_requested" | null;
    review_note: string | null;
}
interface Collab {
    id: number;
    ended: boolean;
    fee: { amount: number; status: "none" | "pending" | "due" | "paid"; approved: number; total: number; reference: string | null };
    product: { id: number; name: string; images?: string[] | null };
    tasks: Task[];
}
interface CreatorRow {
    id: number;
    name: string;
    email: string;
    avatar: string | null;
    payout: { bank: string | null; account_name: string | null; account_number: string | null };
    collaborations: Collab[];
}

const nowMs = useState<number>("now-ms", () => Date.now()); // same value on server and client
const { $api } = useApi();
const { data, error, refresh } = await useAsyncData("brand-creators", () =>
    $api<{ data: CreatorRow[] }>("/brand/creators"),
);

type Filter = "all" | "late" | "progress" | "done";
const filter = ref<Filter>("all");
const query = ref("");
const open = ref<number | null>(null);

const doneOf = (c: Collab) => c.tasks.filter((t) => t.done).length;
const pctOf = (c: Collab) => (c.tasks.length ? Math.round((doneOf(c) / c.tasks.length) * 100) : 0);
const isLate = (c: Collab, t: Task) =>
    !t.done && !c.ended && !!t.due_date && new Date(t.due_date + "T23:59:59").getTime() < nowMs.value;
const linksOf = (c: Collab) => c.tasks.filter((t) => t.link).length;
const lateOf = (c: Collab) => c.tasks.filter((t) => isLate(c, t)).length;

type Status = "late" | "progress" | "done" | "empty" | "ended";
function statusOf(c: Collab): Status {
    if (!c.tasks.length) return "empty";
    if (doneOf(c) === c.tasks.length) return "done";
    if (c.ended) return "ended";
    return lateOf(c) ? "late" : "progress";
}
const STATUS_LABEL: Record<Status, string> = {
    late: "Overdue",
    progress: "In progress",
    done: "Complete",
    empty: "No tasks yet",
    ended: "Ended, unfinished",
};

const rows = computed(() => {
    const q = query.value.trim().toLowerCase();
    return (data.value?.data ?? [])
        .map((cr) => {
            const late = cr.collaborations.reduce((n, c) => n + lateOf(c), 0);
            return { ...cr, late };
        })
        .filter((cr) => !q || cr.name.toLowerCase().includes(q) || cr.email.toLowerCase().includes(q))
        .map((cr) => ({
            ...cr,
            collaborations: cr.collaborations.filter((c) => {
                const s = statusOf(c);
                return filter.value === "all" || (filter.value === "late" && s === "late") || (filter.value === "progress" && (s === "progress" || s === "late")) || (filter.value === "done" && s === "done");
            }),
        }))
        .filter((cr) => cr.collaborations.length)
        // most urgent first: overdue tasks, then least progress
        .sort((a, b) => b.late - a.late || a.name.localeCompare(b.name));
});

const all = computed(() => data.value?.data ?? []);
const stats = computed(() => {
    const collabs = all.value.flatMap((cr) => cr.collaborations);
    return {
        creators: all.value.length,
        late: collabs.filter((c) => statusOf(c) === "late").length,
        progress: collabs.filter((c) => statusOf(c) === "progress" || statusOf(c) === "late").length,
        done: collabs.filter((c) => statusOf(c) === "done").length,
    };
});
const tabs = computed(() => [
    { key: "all", label: "All", n: all.value.flatMap((c) => c.collaborations).length },
    { key: "late", label: "Overdue", n: stats.value.late },
    { key: "progress", label: "In progress", n: stats.value.progress },
    { key: "done", label: "Complete", n: stats.value.done },
] as const);

// Review tasks and pay the flat fee
const money = (n: number) => "RM " + n.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const actionError = ref("");
const noteFor = ref<number | null>(null); // task id with the "request changes" box open
const note = ref("");
const payRef = reactive<Record<number, string>>({});

function apiMessage(e: unknown, fallback: string) {
    const d = (e as { data?: { message?: string; errors?: Record<string, string[]> } })?.data;
    return d?.errors ? Object.values(d.errors)[0]?.[0] ?? fallback : d?.message ?? fallback;
}
async function review(t: Task, status: "approved" | "changes_requested") {
    actionError.value = "";
    try {
        await $api(`/brand/tasks/${t.id}/review`, { method: "PATCH", body: { status, note: status === "changes_requested" ? note.value : null } });
        noteFor.value = null;
        note.value = "";
        await refresh();
    } catch (e) {
        actionError.value = apiMessage(e, "Could not save review.");
    }
}
async function pay(c: Collab) {
    actionError.value = "";
    try {
        await $api(`/brand/applications/${c.id}/payout`, { method: "PATCH", body: { reference: payRef[c.id] || null } });
        await refresh();
    } catch (e) {
        actionError.value = apiMessage(e, "Could not mark as paid.");
    }
}
const FEE_LABEL = { pending: "Fee awaiting approval", due: "Fee to pay", paid: "Fee paid", none: "" } as const;

const fmt = (d: string) => new Date(d).toLocaleDateString("en-MY", { timeZone: "Asia/Kuala_Lumpur", month: "short", day: "numeric" });
const toggle = (id: number) => (open.value = open.value === id ? null : id);
</script>

<template>
    <div class="cr-page">
        <header class="cr-head">
            <h1 class="cr-title">Creators</h1>
            <p class="cr-lede">
                <template v-if="stats.creators">
                    {{ stats.creators }} creator{{ stats.creators === 1 ? "" : "s" }} on your campaigns
                    <template v-if="stats.late"> · <strong class="cr-warn">{{ stats.late }} overdue</strong></template>.
                    Those who need a nudge come first.
                </template>
                <template v-else>Creators you approve will appear here with their delivery progress.</template>
            </p>
        </header>

        <p v-if="error" class="cr-alert" role="alert">Could not load creators. Refresh to try again.</p>

        <div v-else-if="!all.length" class="cr-empty">
            <p class="cr-empty__title">No creators yet</p>
            <p class="cr-empty__text">Approve an application and the creator shows up here.</p>
            <NuxtLink to="/brand/applications" class="cr-btn">Review applications</NuxtLink>
        </div>

        <template v-else>
            <div class="cr-bar">
                <div class="cr-tabs" role="group" aria-label="Filter by status">
                    <button
                        v-for="t in tabs"
                        :key="t.key"
                        type="button"
                        class="cr-tab"
                        :class="{ 'is-on': filter === t.key }"
                        :aria-pressed="filter === t.key"
                        @click="filter = t.key"
                    >
                        {{ t.label }} <span class="cr-tab__n">{{ t.n }}</span>
                    </button>
                </div>
                <label class="cr-search">
                    <span class="sr-only">Search creators</span>
                    <input v-model="query" type="search" placeholder="Search by name or email" />
                </label>
            </div>

            <div v-if="!rows.length" class="cr-empty">
                <p class="cr-empty__title">Nothing matches</p>
                <p class="cr-empty__text">Change the filter or search.</p>
            </div>

            <ul v-else class="cr-list">
                <li v-for="cr in rows" :key="cr.id" class="cr-creator">
                    <div class="cr-who">
                        <Avatar :src="cr.avatar" :name="cr.name" size="md" />
                        <div class="cr-who__text">
                            <p class="cr-name">{{ cr.name }}</p>
                            <p class="cr-email">{{ cr.email }}</p>
                        </div>
                        <NuxtLink :to="`/brand/creators/${cr.id}`" class="cr-link">Portfolio</NuxtLink>
                    </div>

                    <ul class="cr-collabs">
                        <li v-for="c in cr.collaborations" :key="c.id" class="cr-collab">
                            <div class="cr-collab__row">
                                <button
                                    type="button"
                                    class="cr-collab__main"
                                    :aria-expanded="open === c.id"
                                    :aria-controls="`cr-tasks-${c.id}`"
                                    @click="toggle(c.id)"
                                >
                                    <span class="cr-chev" :class="{ 'is-open': open === c.id }" aria-hidden="true">›</span>
                                    <span class="cr-campaign">{{ c.product.name }}</span>
                                    <span class="cr-status" :class="`is-${statusOf(c)}`">{{ STATUS_LABEL[statusOf(c)] }}<template v-if="statusOf(c) === 'late'"> · {{ lateOf(c) }}</template></span>
                                    <span v-if="linksOf(c)" class="cr-links">{{ linksOf(c) }} link{{ linksOf(c) === 1 ? "" : "s" }} ↗</span>
                                    <span class="cr-progress">
                                        <span class="cr-progress__n">{{ c.tasks.length ? `${doneOf(c)}/${c.tasks.length}` : "–" }}</span>
                                        <span class="cr-meter" role="progressbar" :aria-label="`${c.product.name} progress`" :aria-valuenow="pctOf(c)" aria-valuemin="0" aria-valuemax="100">
                                            <span :class="`is-${statusOf(c)}`" :style="{ width: pctOf(c) + '%' }" />
                                        </span>
                                    </span>
                                </button>
                                <NuxtLink :to="`/messages?application=${c.id}`" class="cr-btn cr-btn--sm">Message</NuxtLink>
                            </div>

                            <div v-if="open === c.id" :id="`cr-tasks-${c.id}`" class="cr-tasks">
                                <p v-if="!c.tasks.length" class="cr-tasks__empty">
                                    This creator has not opened their checklist yet.
                                </p>
                                <ul v-else>
                                    <li v-for="t in c.tasks" :key="t.id" class="cr-task" :class="{ 'is-done': t.done }">
                                        <span class="cr-tick" :class="{ 'is-on': t.done }" aria-hidden="true">{{ t.done ? "✓" : "" }}</span>
                                        <span class="sr-only">{{ t.done ? "Done:" : "Not done:" }}</span>
                                        <span class="cr-task__title">{{ t.title }}</span>
                                        <span v-if="t.due_date" class="cr-task__due" :class="{ 'is-late': isLate(c, t) }">
                                            {{ isLate(c, t) ? "Overdue · " : "Due " }}{{ fmt(t.due_date) }}
                                        </span>
                                        <a v-if="t.link" :href="t.link" target="_blank" rel="noopener noreferrer" class="cr-ext">View content ↗</a>
                                        <span v-else-if="t.done && t.type !== 'sample'" class="cr-task__due">No link added</span>
                                        <template v-if="t.done && t.review">
                                            <span class="cr-pill" :class="`is-${t.review}`">
                                                {{ t.review === "approved" ? "Approved" : t.review === "changes_requested" ? "Changes requested" : "Needs review" }}
                                            </span>
                                            <button v-if="t.review !== 'approved'" type="button" class="cr-act" @click="review(t, 'approved')">Approve</button>
                                            <button v-if="t.review !== 'changes_requested'" type="button" class="cr-act cr-act--warn" @click="noteFor = noteFor === t.id ? null : t.id; note = ''">Request changes</button>
                                        </template>
                                        <form v-if="noteFor === t.id" class="cr-note" @submit.prevent="review(t, 'changes_requested')">
                                            <input v-model="note" required maxlength="500" placeholder="What should the creator fix?" aria-label="Note to creator" />
                                            <button type="submit" class="cr-btn cr-btn--solid" :disabled="!note.trim()">Send</button>
                                        </form>
                                        <p v-if="t.review === 'changes_requested' && t.review_note" class="cr-task__note">Your note: {{ t.review_note }}</p>
                                    </li>
                                </ul>

                                <section v-if="c.fee.status !== 'none'" class="cr-sales" aria-label="Flat fee">
                                    <h4 class="cr-sales__h">Flat fee</h4>
                                    <p class="cr-sales__sum">
                                        <strong>{{ money(c.fee.amount) }}</strong> · {{ FEE_LABEL[c.fee.status] }}
                                        <template v-if="c.fee.status === 'pending'">({{ c.fee.approved }}/{{ c.fee.total }} deliverables approved)</template>
                                        <template v-if="c.fee.status === 'paid' && c.fee.reference"> · ref {{ c.fee.reference }}</template>
                                    </p>

                                    <dl v-if="c.fee.status === 'due' || c.fee.status === 'pending'" class="cr-bank">
                                        <template v-if="cr.payout.account_number">
                                            <div><dt>Bank</dt><dd>{{ cr.payout.bank }}</dd></div>
                                            <div><dt>Account name</dt><dd>{{ cr.payout.account_name }}</dd></div>
                                            <div><dt>Account number</dt><dd>{{ cr.payout.account_number }}</dd></div>
                                        </template>
                                        <p v-else class="cr-tasks__empty">The creator has not added bank details yet. Ask in Messages.</p>
                                    </dl>

                                    <form v-if="c.fee.status === 'due'" class="cr-sale-form" @submit.prevent="pay(c)">
                                        <input v-model="payRef[c.id]" type="text" maxlength="100" placeholder="Transfer reference (optional)" aria-label="Transfer reference" />
                                        <button type="submit" class="cr-btn cr-btn--solid">Mark as paid</button>
                                    </form>
                                    <p v-if="actionError" class="cr-sale-err" role="alert">{{ actionError }}</p>
                                </section>
                                <p v-else-if="actionError" class="cr-sale-err" role="alert">{{ actionError }}</p>
                            </div>
                        </li>
                    </ul>
                </li>
            </ul>
        </template>
    </div>
</template>

<style scoped>
/* Hallmark · genre: modern-minimal (app page) · macrostructure: Index-First (matches creator Collaborations) · theme: existing green + neutral grey, amber for overdue · enrichment: none
 * audience: brand owners checking creator delivery · use: spot who is late, open content links, message creator · tone: utilitarian
 * motion: colour + width transitions only; off under reduced-motion
 * every colour is a --cr-* token; all numbers come from real task data
 * pre-emit critique: P4 H5 E4 S4 R5 V4 */
.cr-page {
    --cr-ink: oklch(21% 0.034 264.7);
    --cr-ink-2: oklch(44.6% 0.03 256.8);
    --cr-muted: oklch(55.1% 0.027 264.4);
    --cr-rule: oklch(92.8% 0.006 264.5);
    --cr-paper: oklch(100% 0 0);
    --cr-paper-2: oklch(98.5% 0.002 247.8);
    --cr-green-50: oklch(98.2% 0.018 155.8);
    --cr-green-100: oklch(96.2% 0.044 156.7);
    --cr-green-700: oklch(52.7% 0.154 150.07);
    --cr-amber-50: oklch(98.7% 0.022 95.3);
    --cr-amber-500: oklch(76.9% 0.188 70.08);
    --cr-amber-700: oklch(55.5% 0.163 48.4);
    --cr-radius: 14px;
    --cr-ease: cubic-bezier(0.16, 1, 0.3, 1);

    color: var(--cr-ink);
    min-width: 0;
}
.cr-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}
.cr-lede {
    margin: 0.35rem 0 0;
    max-width: 60ch;
    font-size: 0.95rem;
    color: var(--cr-muted);
    font-variant-numeric: tabular-nums;
}
.cr-warn {
    color: var(--cr-amber-700);
}
.cr-alert {
    margin: 1.5rem 0 0;
    padding: 0.8rem 1rem;
    border: 1px solid var(--cr-amber-500);
    border-radius: var(--cr-radius);
    background: var(--cr-amber-50);
    font-size: 0.9rem;
    color: var(--cr-amber-700);
}

/* Controls */
.cr-bar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
    align-items: center;
    justify-content: space-between;
    margin: 1.5rem 0 1rem;
}
.cr-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.cr-tab {
    min-height: 2.5rem;
    padding: 0 1rem;
    border: 1px solid var(--cr-rule);
    border-radius: 999px;
    background: var(--cr-paper);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cr-ink-2);
    cursor: pointer;
    transition: background-color 0.2s var(--cr-ease), color 0.2s var(--cr-ease), border-color 0.2s var(--cr-ease);
}
.cr-tab:hover {
    background: var(--cr-paper-2);
}
.cr-tab:active {
    background: var(--cr-green-50);
}
.cr-tab.is-on {
    border-color: var(--cr-green-700);
    background: var(--cr-green-700);
    color: var(--cr-paper);
}
.cr-tab__n {
    margin-left: 0.2rem;
    font-variant-numeric: tabular-nums;
    opacity: 0.7;
}
.cr-search input {
    min-height: 2.5rem;
    width: 16rem;
    max-width: 100%;
    padding: 0 0.9rem;
    border: 1px solid var(--cr-rule);
    border-radius: 999px;
    background: var(--cr-paper);
    font: inherit;
    font-size: 0.875rem;
    color: var(--cr-ink);
}
.cr-search input::placeholder {
    color: var(--cr-muted);
}
.cr-tab:focus-visible,
.cr-search input:focus-visible,
.cr-btn:focus-visible,
.cr-link:focus-visible,
.cr-collab__main:focus-visible,
.cr-ext:focus-visible {
    outline: 2px solid var(--cr-green-700);
    outline-offset: 2px;
}

/* Creator cards */
.cr-list,
.cr-collabs,
.cr-tasks ul {
    margin: 0;
    padding: 0;
    list-style: none;
}
.cr-list {
    display: grid;
    gap: 1rem;
}
.cr-creator {
    border: 1px solid var(--cr-rule);
    border-radius: var(--cr-radius);
    background: var(--cr-paper);
    overflow: hidden;
}
.cr-who {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    padding: 1rem 1.25rem;
}
.cr-who__text {
    flex: 1;
    min-width: 0;
}
.cr-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.015em;
}
.cr-email {
    margin: 0.1rem 0 0;
    font-size: 0.85rem;
    color: var(--cr-muted);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.cr-link {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cr-ink-2);
    text-decoration: underline;
    text-underline-offset: 3px;
    white-space: nowrap;
}
.cr-link:hover {
    color: var(--cr-ink);
}

/* Campaign rows */
.cr-collab {
    border-top: 1px solid var(--cr-rule);
}
.cr-collab__row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.35rem 1.25rem 0.35rem 0.5rem;
}
.cr-collab__main {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    grid-template-areas:
        "chev name"
        ". status"
        ". links"
        ". prog";
    gap: 0.25rem 0.5rem;
    align-items: center;
    flex: 1;
    min-width: 0;
    min-height: 3rem;
    padding: 0.5rem;
    border: 0;
    border-radius: 10px;
    background: none;
    font: inherit;
    text-align: left;
    color: inherit;
    cursor: pointer;
}
.cr-collab__main:hover {
    background: var(--cr-paper-2);
}
.cr-chev {
    grid-area: chev;
    display: inline-block;
    width: 1rem;
    text-align: center;
    color: var(--cr-muted);
    transition: transform 0.2s var(--cr-ease);
}
.cr-chev.is-open {
    transform: rotate(90deg);
}
.cr-campaign {
    grid-area: name;
    font-size: 0.95rem;
    font-weight: 600;
    overflow-wrap: anywhere;
}
.cr-status {
    grid-area: status;
    justify-self: start;
    padding: 0.1rem 0.6rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--cr-paper-2);
    color: var(--cr-ink-2);
    white-space: nowrap;
}
.cr-status.is-progress {
    background: var(--cr-green-50);
    color: var(--cr-green-700);
}
.cr-status.is-done {
    background: var(--cr-green-700);
    color: var(--cr-paper);
}
.cr-status.is-late {
    background: var(--cr-amber-50);
    color: var(--cr-amber-700);
}
.cr-links {
    grid-area: links;
    justify-self: start;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--cr-green-700);
    white-space: nowrap;
}
.cr-progress {
    grid-area: prog;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.cr-progress__n {
    min-width: 2rem;
    font-size: 0.85rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    color: var(--cr-ink-2);
}
.cr-meter {
    flex: 1;
    max-width: 12rem;
    height: 0.35rem;
    overflow: hidden;
    border-radius: 999px;
    background: var(--cr-rule);
}
.cr-meter span {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: var(--cr-green-700);
    transition: width 0.3s var(--cr-ease);
}
.cr-meter span.is-late {
    background: var(--cr-amber-500);
}

.cr-btn {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    padding: 0 1rem;
    border: 1px solid var(--cr-green-700);
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cr-green-700);
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.2s var(--cr-ease);
}
.cr-btn:hover {
    background: var(--cr-green-50);
}
.cr-btn--solid {
    background: var(--cr-green-700);
    color: var(--cr-paper);
}
.cr-btn--solid:hover {
    background: var(--cr-green-700);
    opacity: 0.9;
}
.cr-btn--solid:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.cr-btn:active {
    background: var(--cr-green-100);
}

/* Tasks */
.cr-tasks {
    margin: 0 1.25rem 1rem 2.25rem;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    background: var(--cr-paper-2);
}
.cr-tasks__empty {
    margin: 0.4rem 0;
    font-size: 0.875rem;
    color: var(--cr-muted);
}
.cr-task {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem 0.75rem;
    padding: 0.6rem 0;
    font-size: 0.9rem;
}
.cr-task + .cr-task {
    border-top: 1px solid var(--cr-rule);
}
.cr-tick {
    display: inline-grid;
    place-items: center;
    flex: none;
    width: 1.15rem;
    height: 1.15rem;
    border: 1.5px solid var(--cr-rule);
    border-radius: 5px;
    background: var(--cr-paper);
    font-size: 0.75rem;
    line-height: 1;
    color: var(--cr-paper);
}
.cr-tick.is-on {
    border-color: var(--cr-green-700);
    background: var(--cr-green-700);
}
.cr-task__title {
    flex: 1 1 12rem;
    min-width: 0;
    overflow-wrap: anywhere;
}
.cr-task.is-done .cr-task__title {
    color: var(--cr-muted);
    text-decoration: line-through;
}
.cr-task__due {
    font-size: 0.8rem;
    color: var(--cr-muted);
    font-variant-numeric: tabular-nums;
}
.cr-task__due.is-late {
    color: var(--cr-amber-700);
    font-weight: 600;
}
.cr-ext {
    display: inline-flex;
    align-items: center;
    min-height: 2rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--cr-green-700);
    text-decoration: underline;
    white-space: nowrap;
}

/* Sales */
.cr-sales {
    margin-top: 0.75rem;
    padding-top: 0.9rem;
    border-top: 1px solid var(--cr-rule);
}
.cr-sales__h {
    margin: 0;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--cr-muted);
}
.cr-sales__h span {
    font-weight: 500;
}
.cr-sales__sum {
    margin: 0.3rem 0 0.7rem;
    font-size: 0.875rem;
    color: var(--cr-ink-2);
    font-variant-numeric: tabular-nums;
}
.cr-sale-form {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.cr-sale-form input {
    flex: 1 1 9rem;
    min-width: 0;
    min-height: 2.5rem;
    padding: 0 0.9rem;
    border: 1px solid var(--cr-rule);
    border-radius: 999px;
    background: var(--cr-paper);
    font: inherit;
    font-size: 0.875rem;
    color: var(--cr-ink);
}
.cr-sale-form input:focus-visible,
.cr-act:focus-visible {
    outline: 2px solid var(--cr-green-700);
    outline-offset: 2px;
}
.cr-sale-err {
    margin: 0.5rem 0 0;
    font-size: 0.85rem;
    color: var(--cr-amber-700);
}
.cr-bank {
    display: grid;
    gap: 0.5rem 1.5rem;
    grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
    margin: 0 0 0.7rem;
}
.cr-bank dt {
    font-size: 0.75rem;
    color: var(--cr-muted);
}
.cr-bank dd {
    margin: 0.1rem 0 0;
    font-size: 0.9rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.cr-note {
    display: flex;
    flex: 1 1 100%;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.cr-note input {
    flex: 1 1 14rem;
    min-width: 0;
    min-height: 2.5rem;
    padding: 0 0.9rem;
    border: 1px solid var(--cr-rule);
    border-radius: 999px;
    font: inherit;
    font-size: 0.875rem;
}
.cr-task__note {
    flex: 1 1 100%;
    margin: 0;
    font-size: 0.8rem;
    color: var(--cr-amber-700);
}
.cr-pill {
    padding: 0.1rem 0.6rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    background: var(--cr-green-50);
    color: var(--cr-green-700);
}
.cr-pill.is-paid {
    background: var(--cr-green-700);
    color: var(--cr-paper);
}
.cr-pill.is-pending {
    background: var(--cr-paper);
    color: var(--cr-ink-2);
}
.cr-pill.is-changes_requested {
    background: var(--cr-amber-50);
    color: var(--cr-amber-700);
}
.cr-act {
    min-height: 2.25rem;
    padding: 0;
    border: 0;
    background: none;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--cr-green-700);
    text-decoration: underline;
    text-underline-offset: 3px;
    cursor: pointer;
}
.cr-act--warn {
    color: var(--cr-amber-700);
}

/* Empty */
.cr-empty {
    display: grid;
    gap: 0.4rem;
    justify-items: start;
    margin-top: 1.5rem;
    padding: 2rem 0;
    border-top: 1px solid var(--cr-rule);
    border-bottom: 1px solid var(--cr-rule);
}
.cr-empty__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}
.cr-empty__text {
    margin: 0 0 0.6rem;
    font-size: 0.9rem;
    color: var(--cr-muted);
}

/* Wider screens: one line per campaign */
@media (min-width: 48rem) {
    .cr-collab__main {
        grid-template-columns: auto minmax(0, 1.4fr) auto auto minmax(10rem, 1fr);
        grid-template-areas: "chev name status links prog";
        gap: 0.25rem 1rem;
    }
}
@media (prefers-reduced-motion: reduce) {
    .cr-tab,
    .cr-btn,
    .cr-chev,
    .cr-meter span {
        transition: none;
    }
}
</style>
