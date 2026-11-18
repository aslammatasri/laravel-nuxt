<script setup lang="ts">
export interface MyReview {
    id: number;
    rating: number;
    body: string | null;
}

const props = defineProps<{ collabId: number; brandName: string | null; review: MyReview | null }>();
const emit = defineEmits<{ (e: "update:review", v: MyReview | null): void }>();

const { $api } = useApi();
const MAX = 1000;

const editing = ref(!props.review);
const rating = ref(props.review?.rating ?? 0);
const body = ref(props.review?.body ?? "");
const saving = ref(false);
const error = ref("");

watch(
    () => props.review,
    (r) => {
        rating.value = r?.rating ?? 0;
        body.value = r?.body ?? "";
    },
);

async function save() {
    if (!rating.value) {
        error.value = "Pick a star rating first.";
        return;
    }
    saving.value = true;
    error.value = "";
    try {
        const res = await $api<{ data: MyReview }>(`/creator/collaborations/${props.collabId}/review`, {
            method: "PUT",
            body: { rating: rating.value, body: body.value },
        });
        emit("update:review", res.data);
        editing.value = false;
    } catch (e) {
        const d = (e as { data?: { message?: string } })?.data;
        error.value = d?.message ?? "Could not save your review. Try again.";
    } finally {
        saving.value = false;
    }
}
async function remove() {
    saving.value = true;
    error.value = "";
    try {
        await $api(`/creator/collaborations/${props.collabId}/review`, { method: "DELETE" });
        emit("update:review", null);
        rating.value = 0;
        body.value = "";
        editing.value = true;
    } catch {
        error.value = "Could not remove your review. Try again.";
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="rf">
        <h4 class="rf-h">Your review of {{ brandName || "this brand" }}</h4>

        <div v-if="review && !editing" class="rf-done">
            <StarRating :model-value="review.rating" readonly size="md" />
            <p v-if="review.body" class="rf-body">{{ review.body }}</p>
            <p class="rf-note">Other creators can see this on the brand's page.</p>
            <div class="rf-actions">
                <button type="button" class="rf-link" @click="editing = true">Edit</button>
                <button type="button" class="rf-link rf-link--bad" :disabled="saving" @click="remove">Remove</button>
            </div>
        </div>

        <form v-else class="rf-form" @submit.prevent="save">
            <p class="rf-lede">How was working with them? Your experience helps other creators decide.</p>
            <StarRating v-model="rating" size="lg" />
            <label class="sr-only" :for="`rf-body-${collabId}`">Your experience</label>
            <textarea
                :id="`rf-body-${collabId}`"
                v-model="body"
                rows="4"
                :maxlength="MAX"
                class="rf-input"
                placeholder="Optional. e.g. Clear brief, quick replies, paid within a week of approval."
            />
            <p v-if="error" class="rf-err" role="alert">{{ error }}</p>
            <div class="rf-actions">
                <button type="submit" class="rf-btn" :disabled="saving">{{ saving ? "Saving…" : review ? "Save changes" : "Post review" }}</button>
                <button v-if="review" type="button" class="rf-link" :disabled="saving" @click="editing = false">Cancel</button>
                <span class="rf-count">{{ body.length }} / {{ MAX }}</span>
            </div>
        </form>
    </div>
</template>

<style scoped>
.rf { --rf-ink: oklch(21% 0.034 264.7); --rf-muted: oklch(55.1% 0.027 264.4); --rf-rule: oklch(92.8% 0.006 264.5); --rf-green: oklch(52.7% 0.154 150.07); --rf-green-d: oklch(44.8% 0.119 151.3); --rf-red: oklch(50% 0.19 25); min-width: 0; }
.rf-h { margin: 0 0 0.5rem; font-size: 0.8rem; font-weight: 700; color: var(--rf-muted); }
.rf-lede { margin: 0 0 0.6rem; font-size: 0.875rem; color: var(--rf-muted); }
.rf-form { display: grid; gap: 0.6rem; justify-items: start; }
.rf-input { width: 100%; padding: 0.65rem 0.8rem; border: 1px solid var(--rf-rule); border-radius: 10px; background: oklch(100% 0 0); font: inherit; font-size: 0.9rem; line-height: 1.5; color: var(--rf-ink); resize: vertical; }
.rf-input:focus-visible { outline: 3px solid var(--rf-green); outline-offset: 1px; }
.rf-err { margin: 0; font-size: 0.85rem; color: var(--rf-red); }
.rf-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.9rem; }
.rf-btn { min-height: 2.5rem; padding: 0 1.1rem; border: 0; border-radius: 999px; background: var(--rf-green); color: oklch(100% 0 0); font: inherit; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: background-color 0.15s; }
.rf-btn:hover { background: var(--rf-green-d); }
.rf-btn:disabled { opacity: 0.6; cursor: not-allowed; }
.rf-btn:focus-visible, .rf-link:focus-visible { outline: 3px solid var(--rf-green); outline-offset: 2px; }
.rf-link { min-height: 2.5rem; padding: 0; border: 0; background: none; font: inherit; font-size: 0.875rem; font-weight: 600; color: var(--rf-green-d); text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
.rf-link--bad { color: var(--rf-red); }
.rf-link:disabled { opacity: 0.6; cursor: not-allowed; }
.rf-count { font-size: 0.78rem; color: var(--rf-muted); font-variant-numeric: tabular-nums; }
.rf-done { display: grid; gap: 0.4rem; justify-items: start; }
.rf-body { margin: 0; max-width: 60ch; font-size: 0.9rem; line-height: 1.6; color: var(--rf-ink); white-space: pre-line; overflow-wrap: anywhere; }
.rf-note { margin: 0; font-size: 0.78rem; color: var(--rf-muted); }
@media (prefers-reduced-motion: reduce) { .rf-btn { transition: none; } }
</style>
