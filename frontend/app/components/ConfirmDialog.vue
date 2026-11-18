<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        message?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        loading?: boolean;
        error?: string | null;
        danger?: boolean;
    }>(),
    { confirmLabel: "Confirm", cancelLabel: "Cancel", danger: false },
);
const emit = defineEmits<{ (e: "confirm"): void; (e: "cancel"): void }>();

const cancelBtn = ref<HTMLButtonElement | null>(null);

function onKey(e: KeyboardEvent) {
    if (e.key === "Escape" && !props.loading) emit("cancel");
}
watch(
    () => props.open,
    (v) => {
        if (!import.meta.client) return;
        if (v) {
            window.addEventListener("keydown", onKey);
            nextTick(() => cancelBtn.value?.focus()); // safe default: cancel, not delete
        } else {
            window.removeEventListener("keydown", onKey);
        }
    },
);
onBeforeUnmount(() => import.meta.client && window.removeEventListener("keydown", onKey));
</script>

<template>
    <Teleport to="body">
        <Transition name="cd">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-[2px]" @click="!loading && emit('cancel')" />
                <div
                    role="alertdialog"
                    aria-modal="true"
                    aria-labelledby="cd-title"
                    :aria-describedby="message ? 'cd-msg' : undefined"
                    class="cd-panel relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                >
                    <div
                        class="w-11 h-11 rounded-full flex items-center justify-center mb-4"
                        :class="danger ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600'"
                    >
                        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <template v-if="danger">
                                <path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14M10 11v5m4-5v5" />
                            </template>
                            <template v-else>
                                <circle cx="12" cy="12" r="9" /><path d="M12 8v4m0 4h.01" />
                            </template>
                        </svg>
                    </div>

                    <h2 id="cd-title" class="text-lg font-semibold text-gray-900">{{ title }}</h2>
                    <div v-if="message || $slots.default" id="cd-msg" class="mt-1.5 text-sm text-gray-500 leading-relaxed">
                        <slot>{{ message }}</slot>
                    </div>
                    <p v-if="error" class="mt-3 text-sm text-red-600" role="alert">{{ error }}</p>

                    <div class="mt-6 flex justify-end gap-2.5">
                        <button
                            ref="cancelBtn"
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium text-gray-700 border border-gray-200 hover:bg-gray-50 transition-colors disabled:opacity-50"
                            :disabled="loading"
                            @click="emit('cancel')"
                        >
                            {{ cancelLabel }}
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2.5 rounded-xl text-sm font-medium text-white transition-colors disabled:opacity-60"
                            :class="danger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
                            :disabled="loading"
                            @click="emit('confirm')"
                        >
                            {{ loading ? "Working…" : confirmLabel }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.cd-enter-active,
.cd-leave-active {
    transition: opacity 0.15s ease;
}
.cd-enter-active .cd-panel,
.cd-leave-active .cd-panel {
    transition: transform 0.15s ease;
}
.cd-enter-from,
.cd-leave-to {
    opacity: 0;
}
.cd-enter-from .cd-panel,
.cd-leave-to .cd-panel {
    transform: translateY(8px) scale(0.98);
}
@media (prefers-reduced-motion: reduce) {
    .cd-enter-active,
    .cd-leave-active,
    .cd-enter-active .cd-panel,
    .cd-leave-active .cd-panel {
        transition: none;
    }
}
</style>
