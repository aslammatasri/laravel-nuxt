<script setup lang="ts">
definePageMeta({
    middleware: "creator",
    layout: "creator",
});

const { $api } = useApi();
const route = useRoute();
const router = useRouter();

const productId = route.params.id;
const pitch = ref("");
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref(false);
const activeSlide = ref(0);

function formatDate(dateStr: string | null): string {
    if (!dateStr) return "-";
    return new Date(dateStr).toLocaleDateString("en-MY", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

interface VariationOption {
    value: string;
    price: number;
}
interface Variation {
    id: number;
    name: string;
    options: VariationOption[];
}
interface Product {
    id: number;
    name: string;
    image: string | null;
    images: string[];
    description: string;
    price: number;
    category: string;
    commission_rate: string;
    commission_type: string;
    campaign_end: string;
    max_affiliates: number;
    spots_left: number;
    brand: { name: string; avatar?: string | null; brand_profile?: { is_verified: boolean } | null };
    variations: Variation[];
    my_application_status: 'pending' | 'approved' | 'rejected' | null;
    my_subscribers?: number;
    platforms?: string[] | null;
    content_types?: string[] | null;
    posts_required?: number | null;
    brief?: string | null;
    hashtags?: string | null;
    product_url?: string | null;
    min_subscribers?: number | null;
    target_niches?: string[] | null;
    free_sample?: boolean;
    flat_fee?: string | number | null;
    application_deadline?: string | null;
}

const config = useRuntimeConfig();

const { data: product } = await useAsyncData(
    `marketplace-product-${productId}`,
    () => $api<Product>(`/creator/marketplace/${productId}`).catch(() => null),
);

const hasActiveApplication = computed(() =>
    ['pending', 'approved'].includes(product.value?.my_application_status ?? ''),
);

const hasBrief = computed(() => {
    const p = product.value;
    return !!(p?.platforms?.length || p?.content_types?.length || p?.brief || p?.hashtags || p?.product_url);
});
const hasRequirements = computed(() => {
    const p = product.value;
    return !!(p?.min_subscribers || p?.target_niches?.length);
});
const meetsMinimum = computed(() => {
    const min = product.value?.min_subscribers;
    return !min || (product.value?.my_subscribers ?? 0) >= min;
});
const compact = (n: number) => new Intl.NumberFormat("en", { notation: "compact" }).format(n);
const deadlineClosed = computed(() => {
    const d = product.value?.application_deadline;
    return !!d && new Date(d).getTime() + 86400000 < Date.now();
});

const money = (n: number) =>
    "RM " + n.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const isPercent = computed(() => product.value?.commission_type === "percentage");
const rateNumber = computed(() => Number(product.value?.commission_rate ?? 0));
const figure = computed(() =>
    isPercent.value
        ? { prefix: "", num: String(rateNumber.value), unit: "%" }
        : { prefix: "RM", num: rateNumber.value.toLocaleString("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 }), unit: "" },
);
const offerHeadline = computed(() =>
    isPercent.value ? "commission on every sale you drive." : "paid for every sale you drive.",
);
const earnExample = computed(() => {
    const price = Number(product.value?.price ?? 0);
    if (!isPercent.value || !price || !rateNumber.value) return "";
    return `On a ${money(price)} sale you earn ${money((price * rateNumber.value) / 100)}.`;
});
const hasFlatFee = computed(() => Number(product.value?.flat_fee ?? 0) > 0);

function daysUntil(d: string | null | undefined) {
    if (!d) return null;
    const ms = new Date(d).getTime() - new Date().setHours(0, 0, 0, 0);
    return Math.ceil(ms / 86400000);
}
function countdown(d: string | null | undefined) {
    const n = daysUntil(d);
    if (n === null) return "";
    if (n < 0) return "Closed";
    if (n === 0) return "Today";
    return `${n} day${n === 1 ? "" : "s"} left`;
}

const stats = computed(() => {
    const p = product.value;
    if (!p) return [];
    const list: { key: string; label: string; value: string; sub?: string; pct?: number | null }[] = [
        { key: "price", label: "Product price", value: money(Number(p.price ?? 0)) },
        { key: "ends", label: "Campaign ends", value: formatDate(p.campaign_end), sub: countdown(p.campaign_end) },
    ];
    if (p.application_deadline)
        list.push({ key: "deadline", label: "Apply by", value: formatDate(p.application_deadline), sub: countdown(p.application_deadline) });
    if (p.posts_required)
        list.push({ key: "posts", label: "Per creator", value: `${p.posts_required} post${p.posts_required === 1 ? "" : "s"}` });
    list.push({
        key: "spots",
        label: "Spots left",
        value: p.spots_left == null ? "Unlimited" : String(p.spots_left),
        pct: spotsTakenPct.value,
    });
    return list;
});

const commissionLabel = computed(() => {
    if (!product.value) return "";
    return `${product.value.commission_rate}${
        product.value.commission_type === "percentage" ? "%" : " RM"
    }`;
});

const imageCount = computed(() => product.value?.images?.length ?? 0);

function prevSlide() {
    activeSlide.value =
        activeSlide.value > 0 ? activeSlide.value - 1 : imageCount.value - 1;
}
function nextSlide() {
    activeSlide.value =
        activeSlide.value < imageCount.value - 1 ? activeSlide.value + 1 : 0;
}

const spotsTakenPct = computed(() => {
    const p = product.value;
    if (!p || p.spots_left == null || !p.max_affiliates) return null;
    const taken = p.max_affiliates - p.spots_left;
    return Math.min(100, Math.max(0, Math.round((taken / p.max_affiliates) * 100)));
});

async function handleApply() {
    if (!pitch.value.trim()) {
        error.value = "Please write a pitch message";
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        await $api(`/creator/products/${productId}/apply`, {
            method: "POST",
            body: { pitch_message: pitch.value },
        });
        success.value = true;
    } catch (err: any) {
        error.value = err?.data?.message || "Failed to submit application";
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="pd-page">
        <NuxtLink to="/creator/marketplace" class="pd-back">
            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 10H5m4-4-4 4 4 4" stroke-linecap="round" stroke-linejoin="round" /></svg>
            Back to marketplace
        </NuxtLink>

        <div v-if="product" class="pd-grid">
            <!-- Left: product -->
            <div class="pd-main">
                <!-- Offer -->
                <header class="pd-hero">
                    <p class="pd-meta">
                        <span class="pd-cat">{{ product.category }}</span>
                        <span v-if="product.brand?.name" class="pd-brand pd-brand--row">
                            <Avatar :src="product.brand.avatar" :name="product.brand.name" size="xs" />
                            by {{ product.brand.name }}
                            <span v-if="product.brand.brand_profile?.is_verified" class="pd-verified" title="Verified brand">✓ Verified</span>
                        </span>
                    </p>
                    <h1 class="pd-title">{{ product.name }}</h1>

                    <div class="pd-offer">
                        <p class="pd-figure" :aria-label="`${figure.prefix} ${figure.num}${figure.unit} commission`">
                            <span v-if="figure.prefix" class="pd-figure__unit">{{ figure.prefix }}</span>
                            <span class="pd-figure__num">{{ figure.num }}</span>
                            <span v-if="figure.unit" class="pd-figure__unit">{{ figure.unit }}</span>
                        </p>
                        <div class="pd-offer__words">
                            <p class="pd-offer__headline">{{ offerHeadline }}</p>
                            <p v-if="earnExample" class="pd-offer__sub">{{ earnExample }}</p>
                            <ul v-if="hasFlatFee || product.free_sample" class="pd-chips" aria-label="Extra perks">
                                <li v-if="hasFlatFee" class="pd-chip pd-chip--strong">+ {{ money(Number(product.flat_fee)) }} flat fee</li>
                                <li v-if="product.free_sample" class="pd-chip pd-chip--strong">Free sample included</li>
                            </ul>
                        </div>
                    </div>

                    <a v-if="!success && !hasActiveApplication && !deadlineClosed" href="#apply" class="pd-jump">
                        Apply to join
                        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 4v11m-4-4 4 4 4-4" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </a>

                    <p v-if="product.description" class="pd-desc">{{ product.description }}</p>
                </header>

                <!-- Gallery -->
                <section class="pd-gallery" aria-label="Product images">
                    <div class="pd-stage">
                        <template v-if="imageCount">
                            <img
                                v-for="(img, i) in product.images"
                                :key="img"
                                :src="`${config.public.storageBase}/${img}`"
                                :alt="i === activeSlide ? product.name : ''"
                                class="pd-stage__img"
                                :class="{ 'is-active': i === activeSlide }"
                            />
                            <template v-if="imageCount > 1">
                                <button type="button" class="pd-nav pd-nav--prev" aria-label="Previous image" @click="prevSlide">
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m12 5-5 5 5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </button>
                                <button type="button" class="pd-nav pd-nav--next" aria-label="Next image" @click="nextSlide">
                                    <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </button>
                                <p class="pd-count" aria-live="polite">{{ activeSlide + 1 }} / {{ imageCount }}</p>
                            </template>
                        </template>
                        <div v-else class="pd-stage__ph" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
                                <path d="M4 8.5 12 4l8 4.5v7L12 20l-8-4.5v-7Z" stroke-linejoin="round" /><path d="m4 8.5 8 4.5 8-4.5M12 13v7" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <ul v-if="imageCount > 1" class="pd-thumbs">
                        <li v-for="(img, i) in product.images" :key="img">
                            <button
                                type="button"
                                class="pd-thumb"
                                :class="{ 'is-active': i === activeSlide }"
                                :aria-label="`Show image ${i + 1}`"
                                :aria-current="i === activeSlide"
                                @click="activeSlide = i"
                            >
                                <img :src="`${config.public.storageBase}/${img}`" alt="" />
                            </button>
                        </li>
                    </ul>
                </section>

                <!-- At a glance -->
                <dl class="pd-stats">
                    <div v-for="st in stats" :key="st.key" class="pd-stat">
                        <dt>{{ st.label }}</dt>
                        <dd>{{ st.value }}</dd>
                        <p v-if="st.sub" class="pd-stat__sub">{{ st.sub }}</p>
                        <div v-if="st.pct !== undefined && st.pct !== null" class="pd-spots__track" role="img" :aria-label="`${st.pct}% of spots taken`">
                            <div class="pd-spots__fill" :style="{ width: st.pct + '%' }"></div>
                        </div>
                    </div>
                </dl>

                <!-- Brief document -->
                <article v-if="hasBrief || hasRequirements || product.variations?.length" class="pd-doc">
                    <section v-if="hasBrief" class="pd-doc__sec">
                        <h2 class="pd-h2">What you will do</h2>
                        <ul v-if="product.platforms?.length || product.content_types?.length || product.posts_required" class="pd-chips">
                            <li v-for="p in product.platforms" :key="p" class="pd-chip pd-chip--strong">{{ PLATFORM_LABELS[p] || p }}</li>
                            <li v-for="c in product.content_types" :key="c" class="pd-chip">{{ CONTENT_TYPE_LABELS[c] || c }}</li>
                            <li v-if="product.posts_required" class="pd-chip">
                                {{ product.posts_required }} post{{ product.posts_required === 1 ? "" : "s" }}
                            </li>
                        </ul>
                        <p v-if="product.brief" class="pd-brief__text">{{ product.brief }}</p>
                        <p v-if="product.hashtags" class="pd-brief__row"><span>Hashtags</span> {{ product.hashtags }}</p>
                        <p v-if="product.product_url" class="pd-brief__row">
                            <span>Product page</span>
                            <a :href="product.product_url" target="_blank" rel="noopener noreferrer" class="pd-link">{{ product.product_url }} ↗</a>
                        </p>
                    </section>

                    <section v-if="hasRequirements" class="pd-doc__sec">
                        <h2 class="pd-h2">Who can apply</h2>
                        <p
                            v-if="product.min_subscribers"
                            class="pd-qualify"
                            :class="meetsMinimum ? 'is-ok' : 'is-low'"
                            role="status"
                        >
                            <span aria-hidden="true">{{ meetsMinimum ? "✓" : "!" }}</span>
                            <span>
                                {{
                                    meetsMinimum
                                        ? `You meet the ${product.min_subscribers.toLocaleString()} subscriber minimum (you have ${compact(product.my_subscribers ?? 0)}).`
                                        : `This campaign asks for ${product.min_subscribers.toLocaleString()} subscribers. You have ${compact(product.my_subscribers ?? 0)}.`
                                }}
                            </span>
                        </p>
                        <ul v-if="product.target_niches?.length" class="pd-chips">
                            <li v-for="n in product.target_niches" :key="n" class="pd-chip">{{ n }}</li>
                        </ul>
                    </section>

                    <section v-if="product.variations?.length" class="pd-doc__sec">
                        <h2 class="pd-h2">Variations</h2>
                        <div v-for="variation in product.variations" :key="variation.id" class="pd-var">
                            <p class="pd-var__name">{{ variation.name }}</p>
                            <ul class="pd-var__opts">
                                <li v-for="option in variation.options" :key="option.value" class="pd-opt">
                                    {{ option.value }}
                                    <span class="pd-opt__price">RM {{ option.price.toFixed(2) }}</span>
                                </li>
                            </ul>
                        </div>
                    </section>
                </article>
            </div>

            <!-- Right: facts + apply -->
            <aside class="pd-side">
                <div id="apply" class="pd-card">
                    <!-- Success -->
                    <div v-if="success" class="pd-success">
                        <span class="pd-success__icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 10.5 3.5 3.5L15 6.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </span>
                        <h2 class="pd-success__title">Application submitted</h2>
                        <p class="pd-success__text">
                            The brand will review your application and get back to you soon.
                        </p>
                        <NuxtLink to="/creator/dashboard#applications" class="pd-btn">View my applications</NuxtLink>
                        <NuxtLink to="/creator/marketplace" class="pd-btn pd-btn--ghost">Browse more</NuxtLink>
                    </div>

                    <!-- Already applied (pending / approved) -->
                    <div v-else-if="hasActiveApplication" class="pd-success">
                        <span class="pd-success__icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 10.5 3.5 3.5L15 6.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                        </span>
                        <h2 class="pd-success__title">
                            {{ product?.my_application_status === "approved" ? "Application approved" : "Application pending" }}
                        </h2>
                        <p class="pd-success__text">
                            {{
                                product?.my_application_status === "approved"
                                    ? "You are approved to join this campaign."
                                    : "You already applied. The brand is reviewing your application."
                            }}
                        </p>
                        <NuxtLink to="/creator/dashboard#applications" class="pd-btn">View my applications</NuxtLink>
                    </div>

                    <!-- Applications closed -->
                    <div v-else-if="deadlineClosed" class="pd-success">
                        <h2 class="pd-success__title">Applications closed</h2>
                        <p class="pd-success__text">The brand stopped accepting applications on {{ formatDate(product?.application_deadline ?? null) }}.</p>
                        <NuxtLink to="/creator/marketplace" class="pd-btn pd-btn--ghost">Browse more</NuxtLink>
                    </div>

                    <!-- Apply -->
                    <form v-else class="pd-form" @submit.prevent="handleApply">
                        <h2 class="pd-h2">Apply to promote</h2>
                        <p class="pd-form__hint">
                            Tell the brand why you are the right creator for this product.
                        </p>

                        <p v-if="product?.my_application_status === 'rejected'" class="pd-form__hint">
                            Your previous application was rejected. You can apply again.
                        </p>

                        <div v-if="error" class="pd-error" role="alert">{{ error }}</div>

                        <label class="pd-label" for="pitch">
                            Your pitch message
                            <span class="pd-label__note">min 20 characters</span>
                        </label>
                        <textarea
                            id="pitch"
                            v-model="pitch"
                            rows="6"
                            placeholder="e.g. I am a beauty creator with 50k followers mostly Malaysian females aged 18-25. I have promoted similar skincare products before and achieved great conversion rates. I would love to collaborate with your brand..."
                            class="pd-textarea"
                        />
                        <p class="pd-count-note" :class="{ 'is-ok': pitch.trim().length >= 20 }">
                            {{ pitch.length }} characters
                        </p>

                        <button type="submit" :disabled="loading" class="pd-btn pd-btn--block">
                            {{ loading ? "Submitting..." : "Submit application" }}
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        <!-- Not found -->
        <div v-else class="pd-missing">
            <p class="pd-missing__title">Campaign not found</p>
            <p class="pd-missing__text">It may have been removed or the campaign has closed.</p>
            <NuxtLink to="/creator/marketplace" class="pd-btn">Back to marketplace</NuxtLink>
        </div>
    </div>
</template>

<style scoped>
/* ── Offer hero (Stat-Led) ───────────────────────────────── */
.pd-hero {
    display: grid;
    gap: 1rem;
    min-width: 0;
}
.pd-offer {
    display: grid;
    gap: 0.5rem 1.5rem;
    align-items: end;
    padding: 1.25rem 0;
    border-top: 1px solid var(--pd-rule);
    border-bottom: 1px solid var(--pd-rule);
}
.pd-figure {
    margin: 0;
    display: flex;
    align-items: baseline;
    gap: 0.15em;
    font-variant-numeric: tabular-nums;
    line-height: 0.9;
    color: var(--pd-green-700);
}
.pd-figure__num {
    font-size: clamp(3.5rem, 11vw, 5.5rem);
    font-weight: 800;
    letter-spacing: -0.05em;
}
.pd-figure__unit {
    font-size: clamp(1.5rem, 4vw, 2.25rem);
    font-weight: 700;
    letter-spacing: -0.02em;
}
.pd-offer__words {
    min-width: 0;
}
.pd-offer__headline {
    margin: 0;
    font-size: 1.15rem;
    font-weight: 700;
    letter-spacing: -0.015em;
    line-height: 1.3;
}
.pd-offer__sub {
    margin: 0.25rem 0 0;
    font-size: 0.9rem;
    color: var(--pd-muted);
    font-variant-numeric: tabular-nums;
}
.pd-jump {
    justify-self: start;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 2.5rem;
    padding: 0 1rem;
    border: 1px solid var(--pd-green-700);
    border-radius: 999px;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--pd-green-700);
    text-decoration: none;
    white-space: nowrap;
    transition: background-color 0.2s var(--pd-ease), color 0.2s var(--pd-ease);
}
.pd-jump svg {
    width: 1rem;
    height: 1rem;
}
.pd-jump:hover {
    background: var(--pd-green-50);
}
.pd-jump:active {
    background: var(--pd-green-100);
}
.pd-jump:focus-visible {
    outline: 2px solid var(--pd-green-700);
    outline-offset: 2px;
}
.pd-desc {
    max-width: 62ch;
}

/* ── At-a-glance stats: hairline blocks, no cards ───────── */
.pd-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
    margin: 0;
    border-top: 1px solid var(--pd-rule);
    border-bottom: 1px solid var(--pd-rule);
}
.pd-stat {
    min-width: 0;
    padding: 0.9rem 1rem;
    border-left: 1px solid var(--pd-rule);
}
.pd-stat:first-child {
    padding-left: 0;
    border-left: 0;
}
.pd-stat dt {
    font-size: 0.75rem;
    color: var(--pd-muted);
}
.pd-stat dd {
    margin: 0.2rem 0 0;
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
    overflow-wrap: anywhere;
}
.pd-stat__sub {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: var(--pd-muted);
}

/* ── Brief document: one card, hairline-divided sections ── */
.pd-doc {
    border: 1px solid var(--pd-rule);
    border-radius: var(--pd-radius);
    background: var(--pd-paper);
}
.pd-doc__sec {
    display: grid;
    gap: 0.75rem;
    padding: 1.25rem 1.5rem;
}
.pd-doc__sec + .pd-doc__sec {
    border-top: 1px solid var(--pd-rule);
}
.pd-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin: 0;
    padding: 0;
    list-style: none;
}
.pd-chip {
    padding: 0.25rem 0.7rem;
    border: 1px solid var(--pd-rule);
    border-radius: 999px;
    font-size: 0.8rem;
    color: var(--pd-ink-2);
    text-transform: capitalize;
}
.pd-chip--strong {
    border-color: var(--pd-green-100);
    background: var(--pd-green-50);
    color: var(--pd-green-700);
    font-weight: 600;
    text-transform: none;
}
.pd-brief__text {
    margin: 0;
    max-width: 65ch;
    font-size: 0.95rem;
    line-height: 1.65;
    color: var(--pd-ink-2);
    white-space: pre-line;
}
.pd-brief__row {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem 1rem;
    margin: 0;
    font-size: 0.9rem;
    color: var(--pd-ink-2);
}
.pd-brief__row > span {
    min-width: 7rem;
    color: var(--pd-muted);
}
.pd-link {
    color: var(--pd-green-700);
    text-decoration: underline;
    overflow-wrap: anywhere;
}
.pd-qualify {
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
    margin: 0;
    padding: 0.7rem 0.9rem;
    border: 1px solid;
    border-radius: var(--pd-radius-sm);
    font-size: 0.9rem;
    line-height: 1.45;
}
.pd-qualify.is-ok {
    border-color: var(--pd-green-100);
    background: var(--pd-green-50);
    color: var(--pd-green-800);
}
.pd-qualify.is-low {
    border-color: var(--pd-amber-200);
    background: var(--pd-amber-50);
    color: var(--pd-amber-700);
}
.pd-qualify > span:first-child {
    font-weight: 800;
}
.pd-brand--row {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.pd-verified {
    margin-left: 0.35rem;
    padding: 0.05rem 0.45rem;
    border-radius: 9999px;
    background: var(--pd-green-50);
    color: var(--pd-green-700);
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

/* Hallmark · genre: modern-minimal (app page) · macrostructure: Stat-Led (commission figure leads; sticky apply panel right) · theme: existing creator scheme (green + neutral grey, amber for caution) · enrichment: none
 * audience: creators deciding whether a campaign is worth applying to · use: see what they earn, what they must do, whether they qualify, then apply · tone: utilitarian, calm
 * motion: gallery crossfade + chip hover only (opacity / colour), disabled under reduced-motion; no number counter (cut)
 * every colour is a --pd-* token; the only figures shown are the campaign's own data
 * pre-emit critique: P4 H5 E4 S5 R5 V4 */

.pd-page {
    --pd-ink: oklch(21% 0.034 264.7);
    --pd-ink-2: oklch(44.6% 0.03 256.8);
    --pd-muted: oklch(55.1% 0.027 264.4);
    --pd-faint: oklch(70.7% 0.022 261.3);
    --pd-rule: oklch(92.8% 0.006 264.5);
    --pd-rule-soft: oklch(96.7% 0.003 264.5);
    --pd-paper: oklch(100% 0 0);
    --pd-paper-2: oklch(98.5% 0.002 247.8);
    --pd-paper-3: oklch(96.7% 0.003 264.5);
    --pd-green: oklch(72.3% 0.219 149.6);
    --pd-green-600: oklch(62.7% 0.194 149.2);
    --pd-green-700: oklch(52.7% 0.154 150.07);
    --pd-green-800: oklch(44.8% 0.119 151.3);
    --pd-green-50: oklch(98.2% 0.018 155.8);
    --pd-green-100: oklch(96.2% 0.044 156.7);
    --pd-amber-700: oklch(55.5% 0.163 48.4);
    --pd-amber-50: oklch(98.7% 0.022 95.3);
    --pd-amber-200: oklch(92.4% 0.12 95.7);
    --pd-red: oklch(57.7% 0.245 27.3);
    --pd-red-50: oklch(97.1% 0.013 17.4);
    --pd-radius: 14px;
    --pd-radius-sm: 10px;
    --pd-ease: cubic-bezier(0.16, 1, 0.3, 1);

    font-family: "Inter", sans-serif;
    color: var(--pd-ink);
    min-width: 0;
}

.pd-page button,
.pd-page textarea {
    font-family: inherit;
}
.pd-page a:focus-visible,
.pd-page button:focus-visible {
    outline: 2px solid var(--pd-green);
    outline-offset: 2px;
}

.pd-back {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    margin-bottom: 1.25rem;
    font-size: 0.85rem;
    color: var(--pd-muted);
    text-decoration: none;
}
.pd-back svg {
    width: 1rem;
    height: 1rem;
}
.pd-back:hover {
    color: var(--pd-ink);
}

/* ── Layout ───────────────────────────────────────────────── */
.pd-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 1.5rem;
    align-items: start;
}
.pd-main {
    display: grid;
    gap: 1.75rem;
    min-width: 0;
}
.pd-side {
    min-width: 0;
}

/* ── Gallery ──────────────────────────────────────────────── */
.pd-gallery {
    display: grid;
    gap: 0.75rem;
}
.pd-stage {
    position: relative;
    aspect-ratio: 16 / 10;
    max-height: 26rem;
    width: 100%;
    overflow: hidden;
    background: var(--pd-paper-3);
    border: 1px solid var(--pd-rule);
    border-radius: var(--pd-radius);
}
.pd-stage__img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.3s var(--pd-ease);
}
.pd-stage__img.is-active {
    opacity: 1;
}
.pd-stage__ph {
    position: absolute;
    inset: 0;
    display: grid;
    place-items: center;
    color: var(--pd-faint);
}
.pd-stage__ph svg {
    width: 3.5rem;
    height: 3.5rem;
}
.pd-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: grid;
    place-items: center;
    width: 2.25rem;
    height: 2.25rem;
    border: 1px solid var(--pd-rule);
    border-radius: 999px;
    background: oklch(100% 0 0 / 0.9);
    color: var(--pd-ink);
    cursor: pointer;
}
.pd-nav svg {
    width: 1rem;
    height: 1rem;
}
.pd-nav:hover {
    background: var(--pd-paper);
}
.pd-nav--prev {
    left: 0.75rem;
}
.pd-nav--next {
    right: 0.75rem;
}
.pd-count {
    position: absolute;
    right: 0.75rem;
    bottom: 0.75rem;
    margin: 0;
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    background: oklch(21% 0.034 264.7 / 0.7);
    color: var(--pd-paper);
    font-size: 0.75rem;
    font-weight: 500;
}
.pd-thumbs {
    display: flex;
    gap: 0.5rem;
    margin: 0;
    padding: 0 0 0.25rem;
    list-style: none;
    overflow-x: auto;
    scrollbar-width: thin;
}
.pd-thumb {
    flex: none;
    width: 4.5rem;
    height: 4.5rem;
    padding: 0;
    overflow: hidden;
    border: 2px solid transparent;
    border-radius: var(--pd-radius-sm);
    background: var(--pd-paper-3);
    cursor: pointer;
    opacity: 0.7;
}
.pd-thumb img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.pd-thumb:hover {
    opacity: 1;
}
.pd-thumb.is-active {
    border-color: var(--pd-green);
    opacity: 1;
}

/* ── Intro ────────────────────────────────────────────────── */
.pd-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.3rem 0.7rem;
    margin: 0;
    font-size: 0.8rem;
    color: var(--pd-muted);
}
.pd-cat {
    padding: 0.15rem 0.6rem;
    border-radius: 999px;
    background: var(--pd-green-50);
    color: var(--pd-green-700);
    font-weight: 600;
    text-transform: capitalize;
}
.pd-title {
    margin: 0.6rem 0 0;
    font-size: clamp(1.5rem, 2.4vw + 0.75rem, 2.1rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    overflow-wrap: anywhere;
}
.pd-desc {
    margin: 0.85rem 0 0;
    max-width: 42rem;
    font-size: 0.95rem;
    line-height: 1.7;
    color: var(--pd-ink-2);
    white-space: pre-line;
}

/* ── Variations ───────────────────────────────────────────── */
.pd-h2 {
    margin: 0;
    font-size: 0.95rem;
    font-weight: 700;
    letter-spacing: -0.01em;
}
.pd-var__name {
    margin: 0 0 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--pd-muted);
}
.pd-var__opts {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    list-style: none;
}
.pd-opt {
    display: inline-flex;
    align-items: baseline;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border: 1px solid var(--pd-rule);
    border-radius: var(--pd-radius-sm);
    background: var(--pd-paper);
    font-size: 0.85rem;
    color: var(--pd-ink);
}
.pd-opt__price {
    font-size: 0.75rem;
    color: var(--pd-muted);
}

/* ── Side card ────────────────────────────────────────────── */
.pd-card {
    padding: 1.25rem;
    border: 1px solid var(--pd-rule);
    border-radius: var(--pd-radius);
    background: var(--pd-paper);
}
.pd-spots {
    margin-top: 0.9rem;
}
.pd-spots__track {
    height: 0.4rem;
    border-radius: 999px;
    background: var(--pd-paper-3);
    overflow: hidden;
}
.pd-spots__fill {
    height: 100%;
    border-radius: 999px;
    background: var(--pd-green);
}
.pd-spots__text {
    margin: 0.4rem 0 0;
    font-size: 0.75rem;
    color: var(--pd-muted);
}

/* ── Form ─────────────────────────────────────────────────── */
.pd-form {
    display: grid;
    gap: 0.5rem;
}
.pd-form__hint {
    margin: 0 0 0.5rem;
    font-size: 0.825rem;
    color: var(--pd-muted);
}
.pd-label {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.25rem 0.75rem;
    font-size: 0.825rem;
    font-weight: 600;
    color: var(--pd-ink-2);
}
.pd-label__note {
    font-weight: 400;
    color: var(--pd-muted);
}
.pd-textarea {
    width: 100%;
    padding: 0.8rem 0.9rem;
    border: 1px solid var(--pd-rule);
    border-radius: var(--pd-radius-sm);
    background: var(--pd-paper-2);
    font-size: 0.875rem;
    line-height: 1.6;
    color: var(--pd-ink);
    resize: vertical;
    min-height: 8rem;
}
.pd-textarea::placeholder {
    color: var(--pd-faint);
}
.pd-textarea:focus {
    border-color: var(--pd-green);
    background: var(--pd-paper);
    outline: 2px solid var(--pd-green);
    outline-offset: 0;
}
.pd-count-note {
    margin: 0;
    font-size: 0.75rem;
    text-align: right;
    color: var(--pd-muted);
}
.pd-count-note.is-ok {
    color: var(--pd-green-700);
}
.pd-error {
    padding: 0.75rem 0.9rem;
    border-radius: var(--pd-radius-sm);
    background: var(--pd-red-50);
    font-size: 0.85rem;
    color: var(--pd-red);
}

.pd-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.8rem 1.25rem;
    border: 1px solid var(--pd-green-700);
    border-radius: var(--pd-radius-sm);
    background: var(--pd-green-700);
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--pd-paper);
    text-decoration: none;
    cursor: pointer;
}
.pd-btn:hover {
    background: var(--pd-green-800);
    border-color: var(--pd-green-800);
}
.pd-btn:active {
    transform: translateY(1px);
}
.pd-btn:disabled {
    background: var(--pd-faint);
    border-color: var(--pd-faint);
    cursor: not-allowed;
}
.pd-btn--block {
    width: 100%;
    margin-top: 0.5rem;
}
.pd-btn--ghost {
    background: var(--pd-paper);
    border-color: var(--pd-rule);
    color: var(--pd-ink-2);
}
.pd-btn--ghost:hover {
    background: var(--pd-paper-2);
    border-color: var(--pd-ink-2);
}

/* ── Success ──────────────────────────────────────────────── */
.pd-success {
    display: grid;
    justify-items: stretch;
    gap: 0.6rem;
    text-align: center;
}
.pd-success__icon {
    justify-self: center;
    display: grid;
    place-items: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 999px;
    background: var(--pd-green-100);
    color: var(--pd-green-700);
}
.pd-success__icon svg {
    width: 1.25rem;
    height: 1.25rem;
}
.pd-success__title {
    margin: 0.25rem 0 0;
    font-size: 1.05rem;
    font-weight: 700;
}
.pd-success__text {
    margin: 0 0 0.5rem;
    font-size: 0.85rem;
    color: var(--pd-muted);
}

/* ── Not found ────────────────────────────────────────────── */
.pd-missing {
    padding: 4rem 1.5rem;
    border: 1px dashed var(--pd-rule);
    border-radius: var(--pd-radius);
    background: var(--pd-paper);
    text-align: center;
}
.pd-missing__title {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 700;
}
.pd-missing__text {
    margin: 0.3rem 0 1.25rem;
    font-size: 0.875rem;
    color: var(--pd-muted);
}

/* ── Wider screens ────────────────────────────────────────── */
@media (min-width: 40rem) {
    .pd-offer {
        grid-template-columns: auto minmax(0, 1fr);
    }
}
@media (min-width: 64rem) {
    .pd-grid {
        grid-template-columns: minmax(0, 1fr) 22rem;
        gap: 2rem;
    }
    .pd-side {
        position: sticky;
        top: 5.5rem;
    }
}

@media (prefers-reduced-motion: reduce) {
    .pd-stage__img {
        transition: none;
    }
    .pd-btn:active {
        transform: none;
    }
}
</style>
