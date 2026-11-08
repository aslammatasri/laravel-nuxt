<script setup lang="ts">
definePageMeta({
    middleware: "creator",
    layout: "creator",
});

interface Product {
    id: number;
    name: string;
    image: string | null;
    images: string[];
    description: string;
    price: string | number;
    category: string;
    commission_rate: string | number;
    commission_type: string;
    brand: { name: string };
}

const config = useRuntimeConfig();
interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
}

const { $api } = useApi();

const filters = reactive({
    search: "",
    category: "",
    commission_type: "",
    min_commission: "",
    max_commission: "",
    min_price: "",
    max_price: "",
    sort: "commission_rate",
});

const showFilters = ref(false);

const { data: products, refresh } = await useAsyncData("marketplace", () =>
    $api<Paginated<Product>>("/creator/marketplace", { params: filters }).catch(
        () => null,
    ),
);

const { data: categories } = await useAsyncData("marketplace-categories", () =>
    $api<string[]>("/creator/marketplace/categories").catch(() => []),
);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;
watch(
    () => filters.search,
    () => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => refresh(), 300);
    },
);

watch(
    () => [
        filters.category,
        filters.commission_type,
        filters.min_commission,
        filters.max_commission,
        filters.min_price,
        filters.max_price,
        filters.sort,
    ],
    () => refresh(),
);

function clearFilters() {
    filters.category = "";
    filters.commission_type = "";
    filters.min_commission = "";
    filters.max_commission = "";
    filters.min_price = "";
    filters.max_price = "";
    filters.sort = "commission_rate";
}

const sortLabels: Record<string, string> = {
    commission_rate: "Highest commission",
    created_at: "Newest",
    price: "Lowest price",
    name: "Name A-Z",
};

type FilterKey =
    | "category"
    | "commission_type"
    | "min_commission"
    | "max_commission"
    | "min_price"
    | "max_price"
    | "sort";

const activeChips = computed(() => {
    const chips: { key: FilterKey; label: string }[] = [];
    if (filters.commission_type)
        chips.push({
            key: "commission_type",
            label:
                filters.commission_type === "percentage"
                    ? "Percentage commission"
                    : "Fixed commission",
        });
    if (filters.min_commission)
        chips.push({ key: "min_commission", label: `Commission from ${filters.min_commission}` });
    if (filters.max_commission)
        chips.push({ key: "max_commission", label: `Commission up to ${filters.max_commission}` });
    if (filters.min_price)
        chips.push({ key: "min_price", label: `Price from RM ${filters.min_price}` });
    if (filters.max_price)
        chips.push({ key: "max_price", label: `Price up to RM ${filters.max_price}` });
    if (filters.sort !== "commission_rate")
        chips.push({ key: "sort", label: `Sorted: ${sortLabels[filters.sort] ?? filters.sort}` });
    return chips;
});

function removeChip(key: FilterKey) {
    filters[key] = key === "sort" ? "commission_rate" : "";
}

const advancedCount = computed(
    () =>
        [
            filters.commission_type,
            filters.min_commission,
            filters.max_commission,
            filters.min_price,
            filters.max_price,
        ].filter(Boolean).length,
);

const hasActiveFilters = computed(
    () => !!filters.category || activeChips.value.length > 0 || !!filters.search,
);

function resetAll() {
    clearFilters();
    filters.search = "";
}

function formatCommission(product: Product): string {
    const rate = product.commission_rate;
    if (product.commission_type === "percentage") return `${rate}%`;
    return `RM ${rate}`;
}

const commissionTier = (rate: number | string) => {
    const num = Number(rate);
    if (num >= 20) return "high";
    if (num >= 10) return "mid";
    return "low";
};
</script>

<template>
    <div class="mp-page">
        <!-- Header -->
        <header class="mp-head">
            <div>
                <h1 class="mp-title">Marketplace</h1>
                <p class="mp-subtitle">
                    Find products to promote and earn commissions
                </p>
            </div>
            <p v-if="products?.total" class="mp-total">
                <strong>{{ products.total }}</strong>
                product{{ products.total === 1 ? "" : "s" }}
            </p>
        </header>

        <!-- Toolbar -->
        <div class="mp-toolbar">
            <label class="mp-search">
                <svg class="mp-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <circle cx="9" cy="9" r="5.5" /><path d="m13.5 13.5 3.5 3.5" stroke-linecap="round" />
                </svg>
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Search products by name or description"
                    class="mp-search__input"
                    aria-label="Search products"
                />
                <button
                    v-if="filters.search"
                    type="button"
                    class="mp-search__clear"
                    aria-label="Clear search"
                    @click="filters.search = ''"
                >
                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8" stroke-linecap="round" /></svg>
                </button>
            </label>

            <label class="mp-sort">
                <span class="mp-sort__label">Sort</span>
                <select v-model="filters.sort" class="mp-select">
                    <option value="commission_rate">Highest commission</option>
                    <option value="created_at">Newest</option>
                    <option value="price">Lowest price</option>
                    <option value="name">Name A-Z</option>
                </select>
            </label>

            <button
                type="button"
                class="mp-filter-btn"
                :class="{ 'is-on': showFilters || advancedCount }"
                :aria-expanded="showFilters"
                aria-controls="mp-advanced"
                @click="showFilters = !showFilters"
            >
                <svg class="mp-icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="M3 5.5h14M6 10h8M8.5 14.5h3" stroke-linecap="round" />
                </svg>
                Filters
                <span v-if="advancedCount" class="mp-badge">{{ advancedCount }}</span>
            </button>
        </div>

        <!-- Category pills -->
        <div v-if="categories?.length" class="mp-cats" role="group" aria-label="Category">
            <button
                type="button"
                class="mp-cat"
                :class="{ 'is-on': !filters.category }"
                :aria-pressed="!filters.category"
                @click="filters.category = ''"
            >
                All
            </button>
            <button
                v-for="cat in categories"
                :key="cat"
                type="button"
                class="mp-cat"
                :class="{ 'is-on': filters.category === cat }"
                :aria-pressed="filters.category === cat"
                @click="filters.category = cat"
            >
                {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
            </button>
        </div>

        <!-- Advanced filters -->
        <Transition name="mp-slide">
            <div v-if="showFilters" id="mp-advanced" class="mp-adv">
                <div class="mp-adv__grid">
                    <label class="mp-field">
                        <span class="mp-field__label">Commission type</span>
                        <select v-model="filters.commission_type" class="mp-select mp-select--field">
                            <option value="">All types</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed (RM)</option>
                        </select>
                    </label>
                    <label class="mp-field">
                        <span class="mp-field__label">Min commission</span>
                        <input v-model="filters.min_commission" type="number" min="0" placeholder="0" class="mp-input" />
                    </label>
                    <label class="mp-field">
                        <span class="mp-field__label">Max commission</span>
                        <input v-model="filters.max_commission" type="number" min="0" placeholder="100" class="mp-input" />
                    </label>
                    <label class="mp-field">
                        <span class="mp-field__label">Min price (RM)</span>
                        <input v-model="filters.min_price" type="number" min="0" placeholder="0" class="mp-input" />
                    </label>
                    <label class="mp-field">
                        <span class="mp-field__label">Max price (RM)</span>
                        <input v-model="filters.max_price" type="number" min="0" placeholder="1000" class="mp-input" />
                    </label>
                </div>
            </div>
        </Transition>

        <!-- Active filters -->
        <div v-if="activeChips.length || hasActiveFilters" class="mp-meta">
            <ul class="mp-chips">
                <li v-for="chip in activeChips" :key="chip.key">
                    <button type="button" class="mp-chip" @click="removeChip(chip.key)">
                        {{ chip.label }}
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 8 8M14 6l-8 8" stroke-linecap="round" /></svg>
                        <span class="sr-only">Remove filter</span>
                    </button>
                </li>
            </ul>
            <button v-if="hasActiveFilters" type="button" class="mp-clear" @click="resetAll">
                Clear all
            </button>
        </div>

        <!-- Empty state -->
        <div v-if="!products?.data?.length" class="mp-empty">
            <svg class="mp-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round" /><path d="m4 8.5 8 4.5 8-4.5M12 13v7" stroke-linejoin="round" />
            </svg>
            <p class="mp-empty__title">No products found</p>
            <p class="mp-empty__desc">Try a different search or loosen your filters.</p>
            <button v-if="hasActiveFilters" type="button" class="mp-empty__btn" @click="resetAll">
                Clear all filters
            </button>
        </div>

        <!-- Products -->
        <ul v-else class="mp-grid">
            <li v-for="product in products?.data" :key="product.id" class="mp-card">
                <div class="mp-card__media">
                    <img
                        v-if="product.images?.[0]"
                        :src="`${config.public.storageBase}/${product.images[0]}`"
                        :alt="product.name"
                        class="mp-card__img"
                        loading="lazy"
                    />
                    <div v-else class="mp-card__ph" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                            <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round" /><path d="m4 8.5 8 4.5 8-4.5M12 13v7" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>

                <div class="mp-card__body">
                    <p class="mp-card__meta">
                        <span class="mp-card__cat">{{ product.category }}</span>
                        <span class="mp-card__brand">by {{ product.brand?.name }}</span>
                    </p>
                    <h3 class="mp-card__name">
                        <NuxtLink :to="`/creator/marketplace/${product.id}`" class="mp-card__link">
                            {{ product.name }}
                        </NuxtLink>
                    </h3>
                    <p class="mp-card__desc">{{ product.description }}</p>

                    <dl class="mp-card__stats">
                        <div>
                            <dt>Commission</dt>
                            <dd class="mp-card__rate" :data-tier="commissionTier(product.commission_rate)">
                                {{ formatCommission(product) }}
                            </dd>
                        </div>
                        <div>
                            <dt>Price</dt>
                            <dd class="mp-card__price">RM {{ product.price }}</dd>
                        </div>
                    </dl>

                    <span class="mp-card__cta" aria-hidden="true">
                        View &amp; apply
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 10h11m-4-4 4 4-4 4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                </div>
            </li>
        </ul>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

/* Hallmark · genre: modern-minimal (app page) · macrostructure: Catalogue · theme: existing creator-app scheme (warm neutrals, rose accent, green commission) · enrichment: none
 * audience: creators browsing products to promote · use: find a product and open it · tone: utilitarian, calm
 * motion: image zoom + CTA arrow nudge on hover (transform only); reduced-motion removes both
 * colours are the page's original values lifted into tokens; text-bearing fills use a slightly deeper rose (#e0204b) and green-700 to reach AA contrast
 * pre-emit critique: P4 H4 E4 S4 R5 V4 */

.mp-page {
    --mp-ink: #1a1a18;
    --mp-ink-2: #5f5f5b;
    --mp-muted: #6f6f6b;
    --mp-faint: #b5b5b1;
    --mp-rule: #e5e5e3;
    --mp-rule-soft: #f0f0ee;
    --mp-paper: #ffffff;
    --mp-paper-2: #fafafa;
    --mp-paper-3: #f5f5f3;
    --mp-accent: #fe2c55;
    --mp-accent-strong: #e0204b;
    --mp-accent-soft: rgba(254, 44, 85, 0.08);
    --mp-green-ink: #15803d;
    --mp-green-soft: #f0fdf4;
    --mp-emerald-ink: #047857;
    --mp-emerald-soft: #ecfdf5;
    --mp-radius: 12px;
    --mp-radius-sm: 8px;
    --mp-ease: cubic-bezier(0.16, 1, 0.3, 1);

    font-family: "Inter", sans-serif;
    color: var(--mp-ink);
    min-width: 0;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
}

button,
select,
input {
    font-family: inherit;
}
button:focus-visible,
select:focus-visible,
input:focus-visible,
a:focus-visible {
    outline: 2px solid var(--mp-accent);
    outline-offset: 2px;
}

.mp-icon {
    width: 1.05rem;
    height: 1.05rem;
    flex: none;
}

/* ── Header ───────────────────────────────────────────────── */
.mp-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.mp-title {
    font-size: 1.75rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
}
.mp-subtitle {
    margin-top: 0.3rem;
    font-size: 0.9rem;
    color: var(--mp-muted);
}
.mp-total {
    font-size: 0.85rem;
    color: var(--mp-muted);
    white-space: nowrap;
}
.mp-total strong {
    color: var(--mp-ink);
    font-weight: 700;
}

/* ── Toolbar ──────────────────────────────────────────────── */
.mp-toolbar {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.6rem;
}
.mp-search {
    position: relative;
    display: flex;
    align-items: center;
    min-width: 0;
    background: var(--mp-paper);
    border: 1px solid var(--mp-rule);
    border-radius: var(--mp-radius);
    color: var(--mp-muted);
    padding-left: 0.9rem;
}
.mp-search:focus-within {
    border-color: var(--mp-accent);
}
.mp-search__input {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    padding: 0.8rem 0.75rem;
    font-size: 0.9rem;
    color: var(--mp-ink);
}
.mp-search__input:focus-visible {
    outline: none;
}
.mp-search__input::placeholder {
    color: var(--mp-faint);
}
.mp-search__clear {
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    margin-right: 0.25rem;
    border: 0;
    background: transparent;
    color: var(--mp-muted);
    cursor: pointer;
    border-radius: var(--mp-radius-sm);
}
.mp-search__clear svg {
    width: 0.9rem;
    height: 0.9rem;
}
.mp-search__clear:hover {
    color: var(--mp-ink);
    background: var(--mp-paper-3);
}

.mp-sort {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: var(--mp-paper);
    border: 1px solid var(--mp-rule);
    border-radius: var(--mp-radius);
    padding-left: 0.9rem;
}
.mp-sort:focus-within {
    border-color: var(--mp-accent);
}
.mp-sort__label {
    font-size: 0.8rem;
    color: var(--mp-muted);
}
.mp-select {
    flex: 1;
    min-width: 0;
    border: 0;
    background: transparent;
    padding: 0.8rem 0.9rem 0.8rem 0;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--mp-ink);
    cursor: pointer;
}
.mp-select:focus-visible {
    outline: none;
}

.mp-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.8rem 1rem;
    background: var(--mp-paper);
    border: 1px solid var(--mp-rule);
    border-radius: var(--mp-radius);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--mp-ink-2);
    cursor: pointer;
    white-space: nowrap;
}
.mp-filter-btn:hover {
    border-color: var(--mp-ink);
}
.mp-filter-btn.is-on {
    border-color: var(--mp-accent);
    color: var(--mp-ink);
}
.mp-badge {
    display: inline-grid;
    place-items: center;
    min-width: 1.15rem;
    height: 1.15rem;
    padding: 0 0.3rem;
    border-radius: 999px;
    background: var(--mp-accent-strong);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
}

/* ── Category pills ───────────────────────────────────────── */
.mp-cats {
    display: flex;
    gap: 0.4rem;
    margin-top: 0.9rem;
    padding-bottom: 0.25rem;
    overflow-x: auto;
    scrollbar-width: none;
}
.mp-cats::-webkit-scrollbar {
    display: none;
}
.mp-cat {
    flex: none;
    padding: 0.45rem 0.95rem;
    background: var(--mp-paper);
    border: 1px solid var(--mp-rule);
    border-radius: 999px;
    font-size: 0.825rem;
    font-weight: 500;
    color: var(--mp-ink-2);
    white-space: nowrap;
    cursor: pointer;
}
.mp-cat:hover {
    border-color: var(--mp-ink);
    color: var(--mp-ink);
}
.mp-cat.is-on {
    background: var(--mp-ink);
    border-color: var(--mp-ink);
    color: #fff;
}

/* ── Advanced filters ─────────────────────────────────────── */
.mp-adv {
    margin-top: 0.9rem;
    padding: 1rem;
    background: var(--mp-paper);
    border: 1px solid var(--mp-rule);
    border-radius: var(--mp-radius);
}
.mp-adv__grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr));
    gap: 0.75rem;
}
.mp-field {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
    min-width: 0;
}
.mp-field__label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--mp-muted);
}
.mp-input,
.mp-select--field {
    width: 100%;
    border: 1px solid var(--mp-rule);
    border-radius: var(--mp-radius-sm);
    background: var(--mp-paper-2);
    padding: 0.6rem 0.75rem;
    font-size: 0.85rem;
    color: var(--mp-ink);
}
.mp-input:focus,
.mp-select--field:focus {
    border-color: var(--mp-accent);
    background: var(--mp-paper);
}
.mp-input:focus-visible,
.mp-select--field:focus-visible {
    outline: none;
}
.mp-input::placeholder {
    color: var(--mp-faint);
}

.mp-slide-enter-active,
.mp-slide-leave-active {
    transition: opacity 0.2s var(--mp-ease), transform 0.2s var(--mp-ease);
}
.mp-slide-enter-from,
.mp-slide-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

/* ── Active filters ───────────────────────────────────────── */
.mp-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem 0.75rem;
    margin-top: 0.9rem;
}
.mp-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    list-style: none;
    margin: 0;
    padding: 0;
}
.mp-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.3rem 0.7rem;
    background: var(--mp-accent-soft);
    border: 0;
    border-radius: 999px;
    font-size: 0.775rem;
    font-weight: 500;
    color: var(--mp-accent-strong);
    cursor: pointer;
}
.mp-chip svg {
    width: 0.7rem;
    height: 0.7rem;
}
.mp-chip:hover {
    background: rgba(254, 44, 85, 0.14);
}
.mp-clear {
    border: 0;
    background: none;
    padding: 0.3rem 0.2rem;
    font-size: 0.8rem;
    font-weight: 500;
    color: var(--mp-muted);
    text-decoration: underline;
    text-underline-offset: 3px;
    cursor: pointer;
}
.mp-clear:hover {
    color: var(--mp-ink);
}

/* ── Empty ────────────────────────────────────────────────── */
.mp-empty {
    margin-top: 1.5rem;
    padding: 4rem 1.5rem;
    text-align: center;
    background: var(--mp-paper);
    border: 1px dashed var(--mp-rule);
    border-radius: var(--mp-radius);
}
.mp-empty__icon {
    width: 2.5rem;
    height: 2.5rem;
    color: var(--mp-faint);
}
.mp-empty__title {
    margin-top: 0.75rem;
    font-size: 1rem;
    font-weight: 600;
}
.mp-empty__desc {
    margin-top: 0.25rem;
    font-size: 0.85rem;
    color: var(--mp-muted);
}
.mp-empty__btn {
    margin-top: 1.25rem;
    padding: 0.6rem 1.1rem;
    background: var(--mp-ink);
    border: 0;
    border-radius: var(--mp-radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
}

/* ── Grid + cards ─────────────────────────────────────────── */
.mp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(100%, 17rem), 1fr));
    gap: 1.1rem;
    margin: 1.25rem 0 0;
    padding: 0;
    list-style: none;
}
.mp-card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: var(--mp-paper);
    border: 1px solid var(--mp-rule);
    border-radius: var(--mp-radius);
    overflow: hidden;
}
.mp-card:hover {
    border-color: var(--mp-ink-2);
}
.mp-card:focus-within {
    outline: 2px solid var(--mp-accent);
    outline-offset: 2px;
}
.mp-card__media {
    aspect-ratio: 4 / 3;
    overflow: hidden;
    background: var(--mp-paper-3);
    border-bottom: 1px solid var(--mp-rule-soft);
}
.mp-card__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s var(--mp-ease);
}
.mp-card:hover .mp-card__img {
    transform: scale(1.04);
}
.mp-card__ph {
    width: 100%;
    height: 100%;
    display: grid;
    place-items: center;
    color: var(--mp-faint);
}
.mp-card__ph svg {
    width: 2.5rem;
    height: 2.5rem;
}

.mp-card__body {
    display: flex;
    flex: 1;
    flex-direction: column;
    padding: 1rem 1.1rem 1.1rem;
}
.mp-card__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.25rem 0.6rem;
    font-size: 0.75rem;
    color: var(--mp-muted);
}
.mp-card__cat {
    padding: 0.1rem 0.5rem;
    background: var(--mp-paper-3);
    border-radius: 999px;
    font-weight: 600;
    text-transform: capitalize;
    color: var(--mp-ink-2);
}
.mp-card__name {
    margin-top: 0.55rem;
    font-size: 1.025rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    line-height: 1.3;
    overflow-wrap: anywhere;
}
.mp-card__link {
    color: inherit;
    text-decoration: none;
}
.mp-card__link::after {
    content: "";
    position: absolute;
    inset: 0;
}
.mp-card__link:focus-visible {
    outline: none;
}
.mp-card__desc {
    margin-top: 0.35rem;
    font-size: 0.825rem;
    line-height: 1.55;
    color: var(--mp-ink-2);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.mp-card__stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    margin: auto 0 0;
    padding-top: 1rem;
}
.mp-card__stats > div {
    padding: 0.7rem 0 0;
    border-top: 1px solid var(--mp-rule-soft);
}
.mp-card__stats > div + div {
    padding-left: 0.9rem;
    border-left: 1px solid var(--mp-rule-soft);
}
.mp-card__stats dt {
    font-size: 0.7rem;
    font-weight: 500;
    color: var(--mp-muted);
}
.mp-card__stats dd {
    margin: 0.15rem 0 0;
}
.mp-card__rate {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.2;
    color: var(--mp-ink-2);
}
.mp-card__rate[data-tier="high"] {
    color: var(--mp-green-ink);
}
.mp-card__rate[data-tier="mid"] {
    color: var(--mp-emerald-ink);
}
.mp-card__price {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.5;
}

.mp-card__cta {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    margin-top: 1rem;
    padding: 0.65rem 1rem;
    background: var(--mp-accent-strong);
    border-radius: var(--mp-radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    color: #fff;
}
.mp-card__cta svg {
    width: 1rem;
    height: 1rem;
    transition: transform 0.2s var(--mp-ease);
}
.mp-card:hover .mp-card__cta svg {
    transform: translateX(3px);
}

/* ── Wider screens ────────────────────────────────────────── */
@media (min-width: 40rem) {
    .mp-toolbar {
        grid-template-columns: minmax(0, 1fr) auto auto;
    }
    .mp-sort {
        min-width: 15rem;
    }
}

@media (max-width: 40rem) {
    .mp-head {
        align-items: flex-start;
        flex-direction: column;
        gap: 0.5rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .mp-card__img,
    .mp-card__cta svg,
    .mp-slide-enter-active,
    .mp-slide-leave-active {
        transition: none;
    }
    .mp-card:hover .mp-card__img,
    .mp-card:hover .mp-card__cta svg {
        transform: none;
    }
}
</style>
