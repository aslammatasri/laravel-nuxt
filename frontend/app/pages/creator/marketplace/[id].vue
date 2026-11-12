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
}

const config = useRuntimeConfig();

const { data: product } = await useAsyncData(
    `marketplace-product-${productId}`,
    () => $api<Product>(`/creator/marketplace/${productId}`).catch(() => null),
);

const hasActiveApplication = computed(() =>
    ['pending', 'approved'].includes(product.value?.my_application_status ?? ''),
);

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

                <!-- Intro -->
                <section class="pd-intro">
                    <p class="pd-meta">
                        <span class="pd-cat">{{ product.category }}</span>
                        <span v-if="product.brand?.name" class="pd-brand pd-brand--row">
                            <Avatar :src="product.brand.avatar" :name="product.brand.name" size="xs" />
                            by {{ product.brand.name }}
                            <span v-if="product.brand.brand_profile?.is_verified" class="pd-verified" title="Verified brand">✓ Verified</span>
                        </span>
                    </p>
                    <h1 class="pd-title">{{ product.name }}</h1>
                    <p class="pd-desc">{{ product.description }}</p>
                </section>

                <!-- Variations -->
                <section v-if="product.variations?.length" class="pd-vars">
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
            </div>

            <!-- Right: facts + apply -->
            <aside class="pd-side">
                <div class="pd-card">
                    <div class="pd-rate">
                        <p class="pd-rate__label">Commission</p>
                        <p class="pd-rate__value">{{ commissionLabel }}</p>
                    </div>

                    <dl class="pd-facts">
                        <div>
                            <dt>Price</dt>
                            <dd>RM {{ product.price }}</dd>
                        </div>
                        <div>
                            <dt>Campaign ends</dt>
                            <dd>{{ formatDate(product.campaign_end) }}</dd>
                        </div>
                        <div>
                            <dt>Spots left</dt>
                            <dd>{{ product.spots_left ?? "Unlimited" }}</dd>
                        </div>
                    </dl>

                    <div v-if="spotsTakenPct !== null" class="pd-spots">
                        <div class="pd-spots__track" role="img" :aria-label="`${spotsTakenPct}% of spots taken`">
                            <div class="pd-spots__fill" :style="{ width: spotsTakenPct + '%' }"></div>
                        </div>
                        <p class="pd-spots__text">{{ spotsTakenPct }}% of spots taken</p>
                    </div>

                    <hr class="pd-rule" />

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
                                    ? "You are approved to promote this product."
                                    : "You already applied. The brand is reviewing your application."
                            }}
                        </p>
                        <NuxtLink to="/creator/dashboard#applications" class="pd-btn">View my applications</NuxtLink>
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
            <p class="pd-missing__title">Product not found</p>
            <p class="pd-missing__text">It may have been removed or the campaign has closed.</p>
            <NuxtLink to="/creator/marketplace" class="pd-btn">Back to marketplace</NuxtLink>
        </div>
    </div>
</template>

<style scoped>
.pd-brand--row {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}
.pd-verified {
    margin-left: 0.35rem;
    padding: 0.05rem 0.45rem;
    border-radius: 9999px;
    background: #f0fdf4;
    color: #15803d;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

/* Hallmark · genre: modern-minimal (app page) · macrostructure: Split Studio (content left, sticky apply panel right) · theme: existing creator scheme (Tailwind green + gray, red errors) · enrichment: none
 * audience: creators deciding whether to apply · use: read the offer, then submit a pitch · tone: utilitarian, calm
 * motion: gallery crossfade only (opacity), disabled under reduced-motion
 * colours are the page's original Tailwind values as OKLCH tokens; the filled button uses green-700 so white text passes AA
 * pre-emit critique: P4 H4 E4 S4 R5 V4 */

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
    aspect-ratio: 4 / 3;
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
.pd-vars {
    padding-top: 1.5rem;
    border-top: 1px solid var(--pd-rule);
    display: grid;
    gap: 1rem;
}
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
.pd-rate {
    padding: 1rem;
    border-radius: var(--pd-radius-sm);
    background: var(--pd-green-50);
}
.pd-rate__label {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--pd-green-700);
}
.pd-rate__value {
    margin: 0.15rem 0 0;
    font-size: 2rem;
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.15;
    color: var(--pd-green-700);
}
.pd-facts {
    margin: 1rem 0 0;
}
.pd-facts > div {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 0;
    border-bottom: 1px solid var(--pd-rule-soft);
    font-size: 0.875rem;
}
.pd-facts dt {
    color: var(--pd-muted);
}
.pd-facts dd {
    margin: 0;
    font-weight: 600;
    text-align: right;
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
.pd-rule {
    margin: 1.25rem 0;
    border: 0;
    border-top: 1px solid var(--pd-rule);
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
