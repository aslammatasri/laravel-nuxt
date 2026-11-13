<script setup lang="ts">
definePageMeta({ middleware: "creator", layout: "creator" });

interface Collab {
    id: number;
    approved_at: string;
    pitch_message: string | null;
    state: "active" | "ending_soon" | "ended";
    days_left: number | null;
    tasks: CollabTask[];
    product: {
        id: number;
        name: string;
        description: string | null;
        images: string[];
        category: string | null;
        price: string | number | null;
        commission_rate: string;
        commission_type: string | null;
        campaign_start: string | null;
        campaign_end: string | null;
        brand: { name: string | null; avatar?: string | null };
        platforms?: string[] | null;
        content_types?: string[] | null;
        posts_required?: number | null;
        brief?: string | null;
        hashtags?: string | null;
        product_url?: string | null;
        free_sample?: boolean;
        flat_fee?: string | number | null;
        variations: { id: number; name: string; options: { value: string; price: number | string }[] }[];
    };
}

import type { CollabTask } from "~/components/CollabChecklist.vue";

const config = useRuntimeConfig();
const { $api } = useApi();

const { data } = await useAsyncData("creator-collaborations", () =>
    $api<{ data: Collab[] }>("/creator/collaborations").catch(() => null),
    { deep: true },
);

const filter = ref<"all" | "active" | "ended">("all");
const expandedId = ref<number | null>(null);

const collabs = computed(() => data.value?.data ?? []);
const counts = computed(() => ({
    all: collabs.value.length,
    active: collabs.value.filter((c) => c.state !== "ended").length,
    ending: collabs.value.filter((c) => c.state === "ending_soon").length,
    ended: collabs.value.filter((c) => c.state === "ended").length,
}));

const tabs = computed(() => [
    { key: "all", label: "All", count: counts.value.all },
    { key: "active", label: "Active", count: counts.value.active },
    { key: "ended", label: "Ended", count: counts.value.ended },
] as const);

const visible = computed(() =>
    collabs.value.filter((c) =>
        filter.value === "all"
            ? true
            : filter.value === "ended"
              ? c.state === "ended"
              : c.state !== "ended",
    ),
);

// Index sorted by urgency: ending soon, then running, then finished
const groups = computed(() => {
    const byDays = (a: Collab, b: Collab) =>
        (a.days_left ?? Number.MAX_SAFE_INTEGER) - (b.days_left ?? Number.MAX_SAFE_INTEGER);
    const byEndDesc = (a: Collab, b: Collab) =>
        new Date(b.product.campaign_end ?? 0).getTime() - new Date(a.product.campaign_end ?? 0).getTime();

    return [
        { key: "ending", title: "Ending soon", hint: "within 14 days", items: visible.value.filter((c) => c.state === "ending_soon").sort(byDays) },
        { key: "running", title: "Running", hint: "", items: visible.value.filter((c) => c.state === "active").sort(byDays) },
        { key: "finished", title: "Finished", hint: "", items: visible.value.filter((c) => c.state === "ended").sort(byEndDesc) },
    ].filter((g) => g.items.length);
});

function setTasks(c: Collab, t: CollabTask[]) {
    c.tasks = t;
}
const taskSummary = (c: Collab) => `${c.tasks.filter((t) => t.done).length}/${c.tasks.length} tasks`;

function formatDate(d: string | null) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-MY", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
function money(n: number) {
    return "RM " + n.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function commission(c: Collab) {
    const rate = Number(c.product.commission_rate);
    return c.product.commission_type === "percentage" ? `${rate}%` : money(rate);
}
function timeLabel(c: Collab) {
    if (c.state === "ended") return `Ended ${formatDate(c.product.campaign_end)}`;
    if (c.days_left === null) return "No end date";
    if (c.days_left === 0) return "Ends today";
    return `${c.days_left} day${c.days_left === 1 ? "" : "s"} left`;
}
// How much of the campaign window has passed (real dates only)
function elapsedPct(c: Collab): number | null {
    const s = c.product.campaign_start;
    const e = c.product.campaign_end;
    if (!s || !e) return null;
    const a = new Date(s).getTime();
    const b = new Date(e).getTime();
    if (b <= a) return null;
    return Math.min(100, Math.max(0, Math.round(((Date.now() - a) / (b - a)) * 100)));
}
const hasFee = (c: Collab) => Number(c.product.flat_fee) > 0;
const hasDeliverables = (c: Collab) =>
    !!(c.product.platforms?.length || c.product.content_types?.length || c.product.brief || c.product.hashtags || c.product.product_url);
</script>

<template>
    <div class="cl-page">
        <header class="cl-head">
            <h1 class="cl-title">Collaborations</h1>
            <p class="cl-lede">
                <template v-if="counts.all">
                    {{ counts.active }} running<template v-if="counts.ending"> · {{ counts.ending }} ending soon</template>
                    <template v-if="counts.ended"> · {{ counts.ended }} finished</template>.
                    Campaigns you are approved to join, most urgent first.
                </template>
                <template v-else>Campaigns you are approved to join will appear here.</template>
            </p>
        </header>

        <div v-if="collabs.length" class="cl-tabs" role="group" aria-label="Filter collaborations">
            <button
                v-for="t in tabs"
                :key="t.key"
                type="button"
                class="cl-tab"
                :class="{ 'is-on': filter === t.key }"
                :aria-pressed="filter === t.key"
                @click="filter = t.key"
            >
                {{ t.label }} <span class="cl-tab__n">{{ t.count }}</span>
            </button>
        </div>

        <!-- Empty -->
        <div v-if="!groups.length" class="cl-empty">
            <p class="cl-empty__title">
                {{ collabs.length ? "Nothing in this filter" : "No collaborations yet" }}
            </p>
            <p class="cl-empty__text">
                {{ collabs.length ? "Try another filter." : "Once a brand approves your application, the campaign appears here." }}
            </p>
            <NuxtLink v-if="!collabs.length" to="/creator/marketplace" class="cl-btn">Browse the marketplace</NuxtLink>
        </div>

        <!-- Index -->
        <section v-for="g in groups" :key="g.key" class="cl-group" :aria-labelledby="`cl-g-${g.key}`">
            <h2 :id="`cl-g-${g.key}`" class="cl-group__title">
                {{ g.title }}
                <span class="cl-group__n">{{ g.items.length }}<template v-if="g.hint"> · {{ g.hint }}</template></span>
            </h2>

            <ul class="cl-list">
                <li v-for="c in g.items" :key="c.id" class="cl-row" :class="{ 'is-ended': c.state === 'ended' }">
                    <div class="cl-row__main">
                        <div class="cl-ident">
                            <div class="cl-thumb">
                                <img
                                    v-if="c.product.images?.[0]"
                                    :src="`${config.public.storageBase}/${c.product.images[0]}`"
                                    :alt="c.product.name"
                                    loading="lazy"
                                />
                                <AppIcon v-else name="marketplace" class="cl-thumb__ph" />
                            </div>
                            <div class="cl-ident__text">
                                <h3 class="cl-name">{{ c.product.name }}</h3>
                                <p class="cl-brand">
                                    <Avatar :src="c.product.brand?.avatar" :name="c.product.brand?.name" size="xxs" />
                                    <span>by {{ c.product.brand?.name }}</span>
                                </p>
                                <ul v-if="c.product.platforms?.length" class="cl-tags" aria-label="Platforms">
                                    <li v-for="p in c.product.platforms" :key="p" class="cl-tag">{{ PLATFORM_LABELS[p] || p }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="cl-terms">
                            <p class="cl-terms__figure">{{ commission(c) }}</p>
                            <p class="cl-terms__sub">
                                commission<template v-if="hasFee(c)"> + {{ money(Number(c.product.flat_fee)) }}</template><template v-if="c.product.free_sample"> · free sample</template>
                            </p>
                        </div>

                        <div class="cl-time" :class="{ 'is-soon': c.state === 'ending_soon' }">
                            <p class="cl-time__label">{{ timeLabel(c) }}</p>
                            <div
                                v-if="c.state !== 'ended' && elapsedPct(c) !== null"
                                class="cl-bar"
                                role="img"
                                :aria-label="`${elapsedPct(c)}% of the campaign period has passed`"
                            >
                                <span :style="{ width: elapsedPct(c) + '%' }" />
                            </div>
                            <p class="cl-time__sub">
                                Approved {{ formatDate(c.approved_at) }}<template v-if="c.tasks.length"> · {{ taskSummary(c) }}</template>
                            </p>
                        </div>

                        <div class="cl-actions">
                            <NuxtLink :to="`/messages?application=${c.id}`" class="cl-btn">Message brand</NuxtLink>
                            <button
                                type="button"
                                class="cl-link"
                                :aria-expanded="expandedId === c.id"
                                :aria-controls="`cl-brief-${c.id}`"
                                @click="expandedId = expandedId === c.id ? null : c.id"
                            >
                                {{ expandedId === c.id ? "Hide details" : "Checklist & brief" }}
                            </button>
                        </div>
                    </div>

                    <!-- Brief -->
                    <div v-if="expandedId === c.id" :id="`cl-brief-${c.id}`" class="cl-brief">
                        <div class="cl-brief__check">
                            <CollabChecklist :collab-id="c.id" :tasks="c.tasks" :ended="c.state === 'ended'" @update:tasks="setTasks(c, $event)" />
                        </div>
                        <div class="cl-brief__col">
                            <template v-if="hasDeliverables(c)">
                                <h4 class="cl-h">Your deliverables</h4>
                                <ul v-if="c.product.platforms?.length || c.product.content_types?.length || c.product.posts_required" class="cl-tags">
                                    <li v-for="p in c.product.platforms" :key="p" class="cl-tag cl-tag--strong">{{ PLATFORM_LABELS[p] || p }}</li>
                                    <li v-for="t in c.product.content_types" :key="t" class="cl-tag">{{ CONTENT_TYPE_LABELS[t] || t }}</li>
                                    <li v-if="c.product.posts_required" class="cl-tag">
                                        {{ c.product.posts_required }} post{{ c.product.posts_required === 1 ? "" : "s" }}
                                    </li>
                                </ul>
                                <p v-if="c.product.brief" class="cl-text">{{ c.product.brief }}</p>
                                <p v-if="c.product.hashtags" class="cl-row-line"><span>Hashtags</span> {{ c.product.hashtags }}</p>
                                <p v-if="c.product.product_url" class="cl-row-line">
                                    <span>Product page</span>
                                    <a :href="c.product.product_url" target="_blank" rel="noopener noreferrer" class="cl-ext">{{ c.product.product_url }} ↗</a>
                                </p>
                            </template>
                            <template v-if="c.product.description">
                                <h4 class="cl-h">About the product</h4>
                                <p class="cl-text">{{ c.product.description }}</p>
                            </template>
                            <template v-if="c.pitch_message">
                                <h4 class="cl-h">Your pitch</h4>
                                <p class="cl-text">{{ c.pitch_message }}</p>
                            </template>
                        </div>

                        <dl class="cl-brief__facts">
                            <div>
                                <dt>Campaign</dt>
                                <dd>{{ formatDate(c.product.campaign_start) }} – {{ formatDate(c.product.campaign_end) }}</dd>
                            </div>
                            <div v-if="c.product.price">
                                <dt>Product price</dt>
                                <dd>{{ money(Number(c.product.price)) }}</dd>
                            </div>
                            <div v-if="hasFee(c)">
                                <dt>Flat fee</dt>
                                <dd>{{ money(Number(c.product.flat_fee)) }}</dd>
                            </div>
                            <div v-if="c.product.free_sample">
                                <dt>Free sample</dt>
                                <dd>Included</dd>
                            </div>
                            <div v-for="v in c.product.variations" :key="v.id">
                                <dt>{{ v.name }}</dt>
                                <dd>{{ v.options.map((o) => o.value).join(", ") }}</dd>
                            </div>
                        </dl>

                        <NuxtLink :to="`/creator/marketplace/${c.product.id}`" class="cl-link cl-brief__more">Open campaign page →</NuxtLink>
                    </div>
                </li>
            </ul>
        </section>
    </div>
</template>

<style scoped>
/* Hallmark · genre: modern-minimal (app page) · macrostructure: Index-First (urgency-sorted hairline rows) · theme: existing creator scheme (green + neutral grey, amber for deadlines) · enrichment: none
 * audience: creators with several approved campaigns at once · use: see what needs attention, message the brand, open the brief · tone: utilitarian, calm
 * motion: none beyond colour transitions on controls; disabled under reduced-motion
 * every colour is a --cl-* token; the progress bar shows real campaign dates, nothing invented
 * pre-emit critique: P4 H5 E4 S4 R5 V4 */
.cl-page {
    --cl-ink: oklch(21% 0.034 264.7);
    --cl-ink-2: oklch(44.6% 0.03 256.8);
    --cl-muted: oklch(55.1% 0.027 264.4);
    --cl-rule: oklch(92.8% 0.006 264.5);
    --cl-paper: oklch(100% 0 0);
    --cl-paper-2: oklch(98.5% 0.002 247.8);
    --cl-green-50: oklch(98.2% 0.018 155.8);
    --cl-green-100: oklch(96.2% 0.044 156.7);
    --cl-green-700: oklch(52.7% 0.154 150.07);
    --cl-green-800: oklch(44.8% 0.119 151.3);
    --cl-amber-50: oklch(98.7% 0.022 95.3);
    --cl-amber-500: oklch(76.9% 0.188 70.08);
    --cl-amber-700: oklch(55.5% 0.163 48.4);
    --cl-radius: 14px;
    --cl-radius-sm: 10px;
    --cl-ease: cubic-bezier(0.16, 1, 0.3, 1);

    font-family: "Inter", sans-serif;
    color: var(--cl-ink);
    min-width: 0;
}

/* ── Header ───────────────────────────────────────────── */
.cl-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}
.cl-lede {
    margin: 0.35rem 0 0;
    max-width: 60ch;
    font-size: 0.95rem;
    color: var(--cl-muted);
    font-variant-numeric: tabular-nums;
}
.cl-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 1.5rem 0 0.5rem;
}
.cl-tab {
    min-height: 2.5rem;
    padding: 0 1rem;
    border: 1px solid var(--cl-rule);
    border-radius: 999px;
    background: var(--cl-paper);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cl-ink-2);
    cursor: pointer;
    transition: background-color 0.2s var(--cl-ease), color 0.2s var(--cl-ease), border-color 0.2s var(--cl-ease);
}
.cl-tab:hover {
    background: var(--cl-paper-2);
}
.cl-tab:active {
    background: var(--cl-green-50);
}
.cl-tab.is-on {
    border-color: var(--cl-green-700);
    background: var(--cl-green-700);
    color: var(--cl-paper);
}
.cl-tab:focus-visible,
.cl-btn:focus-visible,
.cl-link:focus-visible {
    outline: 2px solid var(--cl-green-700);
    outline-offset: 2px;
}
.cl-tab__n {
    margin-left: 0.2rem;
    font-variant-numeric: tabular-nums;
    opacity: 0.7;
}

/* ── Groups + rows ────────────────────────────────────── */
.cl-group {
    margin-top: 2rem;
}
.cl-group__title {
    margin: 0 0 0.5rem;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}
.cl-group__n {
    margin-left: 0.4rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--cl-muted);
    font-variant-numeric: tabular-nums;
}
.cl-list {
    margin: 0;
    padding: 0;
    list-style: none;
    border: 1px solid var(--cl-rule);
    border-radius: var(--cl-radius);
    background: var(--cl-paper);
    overflow: hidden;
}
.cl-row + .cl-row {
    border-top: 1px solid var(--cl-rule);
}
.cl-row.is-ended .cl-ident,
.cl-row.is-ended .cl-terms {
    opacity: 0.65;
}
.cl-row__main {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.9rem;
    align-items: center;
    padding: 1rem 1.25rem;
}

.cl-ident {
    display: flex;
    gap: 0.85rem;
    align-items: center;
    min-width: 0;
}
.cl-thumb {
    display: grid;
    place-items: center;
    flex: none;
    width: 3.5rem;
    height: 3.5rem;
    overflow: hidden;
    border: 1px solid var(--cl-rule);
    border-radius: var(--cl-radius-sm);
    background: var(--cl-paper-2);
}
.cl-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.cl-thumb__ph {
    color: var(--cl-muted);
}
.cl-ident__text {
    min-width: 0;
}
.cl-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    overflow-wrap: anywhere;
}
.cl-brand {
    display: flex;
    align-items: center;
    gap: 0.35rem;
    margin: 0.15rem 0 0;
    font-size: 0.85rem;
    color: var(--cl-muted);
}
.cl-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.3rem;
    margin: 0.45rem 0 0;
    padding: 0;
    list-style: none;
}
.cl-tag {
    padding: 0.1rem 0.55rem;
    border: 1px solid var(--cl-rule);
    border-radius: 999px;
    font-size: 0.75rem;
    color: var(--cl-ink-2);
    text-transform: capitalize;
}
.cl-tag--strong {
    border-color: var(--cl-green-100);
    background: var(--cl-green-50);
    color: var(--cl-green-700);
    font-weight: 600;
}

.cl-terms,
.cl-time {
    min-width: 0;
}
.cl-terms__figure {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    color: var(--cl-green-700);
    font-variant-numeric: tabular-nums;
}
.cl-terms__sub,
.cl-time__sub {
    margin: 0.15rem 0 0;
    font-size: 0.8rem;
    color: var(--cl-muted);
    font-variant-numeric: tabular-nums;
}
.cl-time__label {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
}
.cl-time.is-soon .cl-time__label {
    color: var(--cl-amber-700);
}
.cl-bar {
    height: 0.3rem;
    margin: 0.4rem 0 0.1rem;
    overflow: hidden;
    border-radius: 999px;
    background: var(--cl-rule);
}
.cl-bar span {
    display: block;
    height: 100%;
    border-radius: 999px;
    background: var(--cl-green-700);
}
.cl-time.is-soon .cl-bar span {
    background: var(--cl-amber-500);
}

.cl-actions {
    display: flex;
    flex: none;
    flex-wrap: wrap;
    gap: 0.5rem 0.9rem;
    align-items: center;
}
.cl-btn {
    display: inline-flex;
    align-items: center;
    min-height: 2.5rem;
    padding: 0 1rem;
    border: 1px solid var(--cl-green-700);
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cl-green-700);
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.2s var(--cl-ease);
}
.cl-btn:hover {
    background: var(--cl-green-50);
}
.cl-btn:active {
    background: var(--cl-green-100);
}
.cl-link {
    min-height: 2.5rem;
    padding: 0;
    border: 0;
    background: none;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cl-ink-2);
    text-decoration: underline;
    text-underline-offset: 3px;
    white-space: nowrap;
    cursor: pointer;
}
.cl-link:hover {
    color: var(--cl-ink);
}

/* ── Brief ────────────────────────────────────────────── */
.cl-brief {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.25rem;
    margin: 0 1.25rem 1.25rem;
    padding: 1.25rem;
    border-radius: var(--cl-radius-sm);
    background: var(--cl-paper-2);
}
.cl-brief__col {
    display: grid;
    gap: 0.6rem;
    align-content: start;
    min-width: 0;
}
.cl-h {
    margin: 0.4rem 0 0;
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--cl-muted);
}
.cl-h:first-child {
    margin-top: 0;
}
.cl-text {
    margin: 0;
    max-width: 65ch;
    font-size: 0.92rem;
    line-height: 1.6;
    color: var(--cl-ink-2);
    white-space: pre-line;
}
.cl-row-line {
    display: flex;
    flex-wrap: wrap;
    gap: 0.2rem 1rem;
    margin: 0;
    font-size: 0.875rem;
    color: var(--cl-ink-2);
}
.cl-row-line > span {
    min-width: 6.5rem;
    color: var(--cl-muted);
}
.cl-ext {
    color: var(--cl-green-700);
    text-decoration: underline;
    overflow-wrap: anywhere;
}
.cl-brief__facts {
    display: grid;
    gap: 0.6rem;
    align-content: start;
    margin: 0;
}
.cl-brief__facts > div {
    padding-bottom: 0.6rem;
    border-bottom: 1px solid var(--cl-rule);
}
.cl-brief__facts dt {
    font-size: 0.75rem;
    color: var(--cl-muted);
}
.cl-brief__facts dd {
    margin: 0.1rem 0 0;
    font-size: 0.9rem;
    font-weight: 600;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}
.cl-brief__more {
    justify-self: start;
}
.cl-brief__check {
    grid-column: 1 / -1;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid var(--cl-rule);
}

/* ── Empty ────────────────────────────────────────────── */
.cl-empty {
    display: grid;
    gap: 0.4rem;
    justify-items: start;
    margin-top: 1.5rem;
    padding: 2rem 0;
    border-top: 1px solid var(--cl-rule);
    border-bottom: 1px solid var(--cl-rule);
}
.cl-empty__title {
    margin: 0;
    font-size: 1rem;
    font-weight: 700;
}
.cl-empty__text {
    margin: 0 0 0.6rem;
    font-size: 0.9rem;
    color: var(--cl-muted);
}

/* ── Wider screens ────────────────────────────────────── */
@media (min-width: 40rem) {
    .cl-row__main {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    }
    .cl-ident {
        grid-column: 1 / -1;
    }
    .cl-actions {
        grid-column: 1 / -1;
    }
}
@media (min-width: 64rem) {
    .cl-row__main {
        grid-template-columns: minmax(0, 2.4fr) minmax(7rem, 1fr) minmax(9rem, 1.1fr) auto;
        gap: 1.5rem;
    }
    .cl-ident,
    .cl-actions {
        grid-column: auto;
    }
    .cl-brief {
        grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr);
        gap: 2rem;
    }
    .cl-brief__more {
        grid-column: 1 / -1;
    }
}
@media (prefers-reduced-motion: reduce) {
    .cl-tab,
    .cl-btn {
        transition: none;
    }
}
</style>
