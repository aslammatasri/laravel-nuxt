<script setup lang="ts">
definePageMeta({ middleware: "brand", layout: "brand" });

interface Review {
    id: number;
    rating: number;
    body: string | null;
    created_at: string;
    campaign: string | null;
    creator: { id: number | null; name: string | null; avatar?: string | null };
}
interface Payload {
    average: number | null;
    count: number;
    distribution: Record<string, number>;
    data: Review[];
}

const { $api } = useApi();
const { data } = await useAsyncData("brand-reviews", () => $api<Payload>("/brand/reviews").catch(() => null));

const reviews = computed(() => data.value?.data ?? []);
const count = computed(() => data.value?.count ?? 0);
const average = computed(() => data.value?.average ?? null);
const bars = computed(() =>
    [5, 4, 3, 2, 1].map((s) => {
        const n = data.value?.distribution?.[String(s)] ?? 0;
        return { star: s, n, pct: count.value ? Math.round((n / count.value) * 100) : 0 };
    }),
);

const filter = ref<number | null>(null);
const visible = computed(() => (filter.value ? reviews.value.filter((r) => r.rating === filter.value) : reviews.value));

const date = (d: string) =>
    new Date(d).toLocaleDateString("en-MY", { timeZone: "Asia/Kuala_Lumpur", day: "numeric", month: "short", year: "numeric" });
</script>

<template>
    <div class="rv-page">
        <header>
            <h1 class="rv-title">Reviews</h1>
            <p class="rv-lede">
                <template v-if="count">What creators wrote after working with you. Other creators can read these on your public brand page.</template>
                <template v-else>Creators can review you once their deliverables are approved or you have paid them. Reviews appear here.</template>
            </p>
        </header>

        <template v-if="count">
            <!-- Summary -->
            <section class="rv-summary" aria-label="Rating summary">
                <div class="rv-avg">
                    <p class="rv-avg__n">{{ average?.toFixed(1) }}</p>
                    <StarRating :model-value="average ?? 0" readonly size="md" />
                    <p class="rv-avg__c">{{ count }} review{{ count === 1 ? "" : "s" }}</p>
                </div>
                <ul class="rv-bars">
                    <li v-for="b in bars" :key="b.star">
                        <button
                            type="button"
                            class="rv-bar"
                            :class="{ 'is-on': filter === b.star }"
                            :aria-pressed="filter === b.star"
                            :disabled="!b.n"
                            @click="filter = filter === b.star ? null : b.star"
                        >
                            <span class="rv-bar__label">{{ b.star }} star{{ b.star === 1 ? "" : "s" }}</span>
                            <span class="rv-bar__track" aria-hidden="true"><span :style="{ width: b.pct + '%' }" /></span>
                            <span class="rv-bar__n">{{ b.n }}</span>
                        </button>
                    </li>
                </ul>
            </section>

            <p v-if="filter" class="rv-filter">
                Showing {{ visible.length }} {{ filter }}-star review{{ visible.length === 1 ? "" : "s" }}.
                <button type="button" class="rv-link" @click="filter = null">Show all</button>
            </p>

            <!-- List -->
            <ul class="rv-list">
                <li v-for="r in visible" :key="r.id" class="rv-item">
                    <div class="rv-item__top">
                        <Avatar :src="r.creator.avatar" :name="r.creator.name" size="md" />
                        <div class="rv-item__who">
                            <p class="rv-item__name">{{ r.creator.name }}</p>
                            <p class="rv-item__meta">
                                <template v-if="r.campaign">{{ r.campaign }} · </template>{{ date(r.created_at) }}
                            </p>
                        </div>
                        <StarRating :model-value="r.rating" readonly size="sm" />
                    </div>
                    <p v-if="r.body" class="rv-item__body">{{ r.body }}</p>
                    <p v-else class="rv-item__none">Rated without a written review.</p>
                    <div v-if="r.creator.id" class="rv-item__foot">
                        <NuxtLink :to="`/brand/creators/${r.creator.id}`" class="rv-link">View creator portfolio</NuxtLink>
                    </div>
                </li>
            </ul>
        </template>

        <div v-else class="rv-empty">
            <p class="rv-empty__title">No reviews yet</p>
            <p class="rv-empty__text">Approve deliverables and pay on time. Creators are then prompted to leave a review.</p>
            <NuxtLink to="/brand/creators" class="rv-btn">Go to creators</NuxtLink>
        </div>
    </div>
</template>

<style scoped>
/* Hallmark · genre: modern-minimal (app page) · macrostructure: Stat-Led (rating figure leads, distribution supports, list follows) · theme: existing brand scheme (blue + neutral grey, amber stars) · enrichment: none
 * audience: brands checking what creators think of working with them · use: read feedback, see the rating split, open a creator · tone: utilitarian, calm
 * every figure is real data (average, count, per-star counts); no invented benchmarks
 * pre-emit critique: P4 H4 E4 S4 R5 V4 */
.rv-page {
    --rv-ink: oklch(21% 0.034 264.7);
    --rv-ink-2: oklch(44.6% 0.03 256.8);
    --rv-muted: oklch(55.1% 0.027 264.4);
    --rv-rule: oklch(92.8% 0.006 264.5);
    --rv-paper: oklch(100% 0 0);
    --rv-paper-2: oklch(98.5% 0.002 247.8);
    --rv-blue: oklch(54.6% 0.245 262.9);
    --rv-blue-d: oklch(48.8% 0.217 264.4);
    --rv-star: oklch(76.9% 0.188 70.08);
    --rv-radius: 14px;

    font-family: "Inter", sans-serif;
    color: var(--rv-ink);
    min-width: 0;
}
.rv-title { margin: 0; font-size: 1.5rem; font-weight: 800; letter-spacing: -0.03em; }
.rv-lede { margin: 0.35rem 0 0; max-width: 62ch; font-size: 0.95rem; color: var(--rv-muted); }

/* Summary */
.rv-summary {
    display: grid;
    grid-template-columns: minmax(0, 14rem) minmax(0, 1fr);
    gap: 2rem;
    align-items: center;
    margin-top: 1.5rem;
    padding: 1.5rem;
    border: 1px solid var(--rv-rule);
    border-radius: var(--rv-radius);
    background: var(--rv-paper);
}
.rv-avg { display: grid; gap: 0.35rem; justify-items: start; }
.rv-avg__n { margin: 0; font-size: 3rem; font-weight: 800; letter-spacing: -0.04em; line-height: 1; font-variant-numeric: tabular-nums; }
.rv-avg__c { margin: 0; font-size: 0.85rem; color: var(--rv-muted); }
.rv-bars { display: grid; gap: 0.15rem; margin: 0; padding: 0; list-style: none; }
.rv-bar {
    display: grid;
    grid-template-columns: 4.25rem minmax(0, 1fr) 2rem;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    min-height: 2.25rem;
    padding: 0 0.5rem;
    border: 0;
    border-radius: 8px;
    background: none;
    font: inherit;
    font-size: 0.8rem;
    color: var(--rv-ink-2);
    text-align: left;
    cursor: pointer;
    transition: background-color 0.15s;
}
.rv-bar:hover:not(:disabled) { background: var(--rv-paper-2); }
.rv-bar:disabled { cursor: default; color: var(--rv-muted); }
.rv-bar.is-on { background: var(--rv-paper-2); font-weight: 600; color: var(--rv-ink); }
.rv-bar:focus-visible, .rv-link:focus-visible, .rv-btn:focus-visible { outline: 3px solid var(--rv-blue); outline-offset: 2px; }
.rv-bar__track { height: 0.5rem; border-radius: 999px; background: var(--rv-rule); overflow: hidden; }
.rv-bar__track > span { display: block; height: 100%; border-radius: 999px; background: var(--rv-star); }
.rv-bar__n { text-align: right; font-variant-numeric: tabular-nums; color: var(--rv-muted); }

.rv-filter { margin: 1rem 0 0; font-size: 0.875rem; color: var(--rv-muted); }

/* List */
.rv-list { display: grid; gap: 0.75rem; margin: 1.25rem 0 0; padding: 0; list-style: none; }
.rv-item { padding: 1.1rem 1.25rem; border: 1px solid var(--rv-rule); border-radius: var(--rv-radius); background: var(--rv-paper); }
.rv-item__top { display: flex; align-items: center; gap: 0.8rem; }
.rv-item__who { flex: 1; min-width: 0; }
.rv-item__name { margin: 0; font-size: 0.925rem; font-weight: 600; overflow-wrap: anywhere; }
.rv-item__meta { margin: 0.1rem 0 0; font-size: 0.8rem; color: var(--rv-muted); }
.rv-item__body { margin: 0.75rem 0 0; max-width: 68ch; font-size: 0.925rem; line-height: 1.6; color: var(--rv-ink-2); white-space: pre-line; overflow-wrap: anywhere; }
.rv-item__none { margin: 0.75rem 0 0; font-size: 0.85rem; color: var(--rv-muted); }
.rv-item__foot { margin-top: 0.6rem; }
.rv-link { display: inline-flex; align-items: center; min-height: 2.25rem; padding: 0; border: 0; background: none; font: inherit; font-size: 0.85rem; font-weight: 600; color: var(--rv-blue-d); text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }

/* Empty */
.rv-empty { margin-top: 1.5rem; padding: 3.5rem 1.5rem; border: 1px solid var(--rv-rule); border-radius: var(--rv-radius); background: var(--rv-paper); text-align: center; }
.rv-empty__title { margin: 0; font-size: 1.05rem; font-weight: 700; }
.rv-empty__text { margin: 0.3rem auto 1.25rem; max-width: 42ch; font-size: 0.9rem; color: var(--rv-muted); }
.rv-btn { display: inline-flex; align-items: center; min-height: 2.5rem; padding: 0 1.1rem; border: 1px solid var(--rv-blue); border-radius: 999px; font-size: 0.875rem; font-weight: 600; color: var(--rv-blue-d); text-decoration: none; }

@media (max-width: 40rem) {
    .rv-summary { grid-template-columns: minmax(0, 1fr); gap: 1rem; padding: 1.25rem; }
    .rv-item { padding: 1rem; }
}
@media (prefers-reduced-motion: reduce) { .rv-bar { transition: none; } }
</style>
