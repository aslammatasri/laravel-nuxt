<script setup lang="ts">
// Display (readonly) or input (radio group) 1-5 star rating
const props = withDefaults(defineProps<{ modelValue: number; readonly?: boolean; size?: "sm" | "md" | "lg"; name?: string }>(), {
    readonly: false,
    size: "md",
    name: "rating",
});
const emit = defineEmits<{ (e: "update:modelValue", v: number): void }>();

const uid = useId();
const hover = ref(0);
const shown = computed(() => (props.readonly ? props.modelValue : hover.value || props.modelValue));
const LABELS = ["", "Poor", "Fair", "Good", "Very good", "Excellent"];
const path = "M10 1.8l2.4 5 5.4.7-4 3.8 1 5.4L10 14.1 5.2 16.7l1-5.4-4-3.8 5.4-.7L10 1.8z";
</script>

<template>
    <span v-if="readonly" class="sr" :class="`sr--${size}`" role="img" :aria-label="`${modelValue} out of 5`">
        <svg v-for="n in 5" :key="n" viewBox="0 0 20 20" aria-hidden="true" :class="{ 'is-on': n <= Math.round(modelValue) }">
            <path :d="path" />
        </svg>
    </span>

    <fieldset v-else class="sr sr--input" :class="`sr--${size}`" @mouseleave="hover = 0">
        <legend class="sr-only">Rating out of 5</legend>
        <template v-for="n in 5" :key="n">
            <input
                :id="`${uid}-${n}`"
                class="sr-only"
                type="radio"
                :name="`${name}-${uid}`"
                :value="n"
                :checked="modelValue === n"
                @change="emit('update:modelValue', n)"
            />
            <label :for="`${uid}-${n}`" :title="LABELS[n]" @mouseenter="hover = n">
                <span class="sr-only">{{ n }} star{{ n === 1 ? "" : "s" }}, {{ LABELS[n] }}</span>
                <svg viewBox="0 0 20 20" aria-hidden="true" :class="{ 'is-on': n <= shown }"><path :d="path" /></svg>
            </label>
        </template>
    </fieldset>
</template>

<style scoped>
.sr {
    --sr-on: oklch(76.9% 0.188 70.08);
    --sr-off: oklch(90% 0.006 264.5);
    display: inline-flex;
    gap: 0.1rem;
    margin: 0;
    padding: 0;
    border: 0;
    min-width: 0;
}
.sr svg {
    fill: var(--sr-off);
    transition: fill 0.12s;
}
.sr svg.is-on {
    fill: var(--sr-on);
}
.sr--sm svg { width: 0.95rem; height: 0.95rem; }
.sr--md svg { width: 1.2rem; height: 1.2rem; }
.sr--lg svg { width: 2rem; height: 2rem; }
.sr--input label {
    display: inline-flex;
    padding: 0.15rem;
    cursor: pointer;
    border-radius: 6px;
}
.sr--input input:focus-visible + label {
    outline: 3px solid oklch(52.7% 0.154 150.07);
    outline-offset: 1px;
}
@media (prefers-reduced-motion: reduce) {
    .sr svg { transition: none; }
}
</style>
