<script setup lang="ts">
export interface CollabTask {
    id: number;
    title: string;
    type: string;
    due_date: string | null;
    link: string | null;
    done: boolean;
}

const props = defineProps<{ collabId: number; tasks: CollabTask[]; ended?: boolean }>();
const emit = defineEmits<{ (e: "update:tasks", v: CollabTask[]): void }>();

const { $api } = useApi();
const base = computed(() => `/creator/collaborations/${props.collabId}/tasks`);

const TYPES = [
    { value: "video", label: "Video" },
    { value: "live", label: "Live" },
    { value: "photo", label: "Photo" },
    { value: "story", label: "Story" },
    { value: "post", label: "Post" },
    { value: "sample", label: "Sample" },
    { value: "other", label: "Other" },
];

const draft = reactive({ title: "", type: "video", due_date: "" });
const error = ref("");
const busy = ref(false);
const linkOpen = ref<number | null>(null);

const doneCount = computed(() => props.tasks.filter((t) => t.done).length);
const pct = computed(() => (props.tasks.length ? Math.round((doneCount.value / props.tasks.length) * 100) : 0));

function replace(t: CollabTask) {
    emit("update:tasks", props.tasks.map((x) => (x.id === t.id ? t : x)));
}

async function toggle(t: CollabTask) {
    error.value = "";
    const prev = t.done;
    replace({ ...t, done: !prev });
    try {
        const res = await $api<{ data: CollabTask }>(`${base.value}/${t.id}`, { method: "PATCH", body: { done: !prev } });
        replace(res.data);
    } catch {
        replace({ ...t, done: prev });
        error.value = "Could not update task.";
    }
}

async function saveLink(t: CollabTask, link: string) {
    error.value = "";
    try {
        const res = await $api<{ data: CollabTask }>(`${base.value}/${t.id}`, { method: "PATCH", body: { link: link || null } });
        replace(res.data);
        linkOpen.value = null;
    } catch {
        error.value = "Enter a valid URL starting with https://";
    }
}

async function add() {
    if (!draft.title.trim() || busy.value) return;
    busy.value = true;
    error.value = "";
    try {
        const res = await $api<{ data: CollabTask }>(base.value, {
            method: "POST",
            body: { title: draft.title.trim(), type: draft.type, due_date: draft.due_date || null },
        });
        emit("update:tasks", [...props.tasks, res.data]);
        draft.title = "";
        draft.due_date = "";
    } catch {
        error.value = "Could not add task.";
    } finally {
        busy.value = false;
    }
}

async function remove(t: CollabTask) {
    error.value = "";
    try {
        await $api(`${base.value}/${t.id}`, { method: "DELETE" });
        emit("update:tasks", props.tasks.filter((x) => x.id !== t.id));
    } catch {
        error.value = "Could not delete task.";
    }
}

function overdue(t: CollabTask) {
    return !t.done && !props.ended && !!t.due_date && new Date(t.due_date + "T23:59:59") < new Date();
}
function fmt(d: string) {
    return new Date(d).toLocaleDateString("en-MY", { month: "short", day: "numeric" });
}
</script>

<template>
    <section class="ck" aria-label="Deliverables checklist">
        <header class="ck__head">
            <h4 class="ck__title">Checklist</h4>
            <span class="ck__count">{{ doneCount }}/{{ tasks.length }} done</span>
        </header>
        <div class="ck__bar" role="progressbar" :aria-valuenow="pct" aria-valuemin="0" aria-valuemax="100">
            <span :style="{ width: pct + '%' }" />
        </div>

        <ul class="ck__list">
            <li v-for="t in tasks" :key="t.id" class="ck__item" :class="{ 'is-done': t.done }">
                <label class="ck__label">
                    <input type="checkbox" :checked="t.done" @change="toggle(t)" />
                    <span class="ck__text">{{ t.title }}</span>
                </label>
                <span class="ck__type">{{ TYPES.find((x) => x.value === t.type)?.label ?? t.type }}</span>
                <span v-if="t.due_date" class="ck__due" :class="{ 'is-late': overdue(t) }">
                    {{ overdue(t) ? "Overdue · " : "Due " }}{{ fmt(t.due_date) }}
                </span>
                <a v-if="t.link && linkOpen !== t.id" :href="t.link" target="_blank" rel="noopener noreferrer" class="ck__act">View ↗</a>
                <button type="button" class="ck__act" @click="linkOpen = linkOpen === t.id ? null : t.id">
                    {{ t.link ? "Edit link" : "Add link" }}
                </button>
                <button type="button" class="ck__act ck__act--del" :aria-label="`Delete ${t.title}`" @click="remove(t)">Delete</button>

                <form v-if="linkOpen === t.id" class="ck__link" @submit.prevent="saveLink(t, ($event.target as HTMLFormElement).link.value.trim())">
                    <input name="link" type="url" :value="t.link ?? ''" placeholder="https://… link to your published content" aria-label="Content link" />
                    <button type="submit" class="ck__btn">Save</button>
                </form>
            </li>
        </ul>
        <p v-if="!tasks.length" class="ck__empty">No tasks. Add your first deliverable below.</p>

        <form v-if="!ended" class="ck__add" @submit.prevent="add">
            <input v-model="draft.title" type="text" maxlength="120" placeholder="Add task, e.g. Go live on TikTok" aria-label="Task title" />
            <select v-model="draft.type" aria-label="Task type">
                <option v-for="o in TYPES" :key="o.value" :value="o.value">{{ o.label }}</option>
            </select>
            <input v-model="draft.due_date" type="date" aria-label="Due date" />
            <button type="submit" class="ck__btn" :disabled="busy || !draft.title.trim()">Add</button>
        </form>
        <p v-if="error" class="ck__error" role="alert">{{ error }}</p>
    </section>
</template>

<style scoped>
.ck { display: grid; gap: 0.6rem; min-width: 0; }
.ck__head { display: flex; justify-content: space-between; align-items: baseline; }
.ck__title { margin: 0; font-size: 0.8rem; font-weight: 700; color: oklch(55.1% 0.027 264.4); }
.ck__count { font-size: 0.8rem; font-variant-numeric: tabular-nums; color: oklch(44.6% 0.03 256.8); }
.ck__bar { height: 0.3rem; border-radius: 999px; background: oklch(92.8% 0.006 264.5); overflow: hidden; }
.ck__bar span { display: block; height: 100%; background: oklch(52.7% 0.154 150.07); transition: width 0.3s; }
.ck__list { margin: 0; padding: 0; list-style: none; display: grid; }
.ck__item { display: flex; flex-wrap: wrap; align-items: center; gap: 0.3rem 0.75rem; padding: 0.55rem 0; border-bottom: 1px solid oklch(92.8% 0.006 264.5); }
.ck__label { display: flex; align-items: center; gap: 0.6rem; flex: 1 1 14rem; min-width: 0; cursor: pointer; }
.ck__label input { width: 1.1rem; height: 1.1rem; accent-color: oklch(52.7% 0.154 150.07); }
.ck__text { font-size: 0.9rem; overflow-wrap: anywhere; }
.ck__item.is-done .ck__text { text-decoration: line-through; color: oklch(55.1% 0.027 264.4); }
.ck__type { font-size: 0.75rem; padding: 0.1rem 0.55rem; border: 1px solid oklch(92.8% 0.006 264.5); border-radius: 999px; color: oklch(44.6% 0.03 256.8); }
.ck__due { font-size: 0.8rem; color: oklch(55.1% 0.027 264.4); font-variant-numeric: tabular-nums; }
.ck__due.is-late { color: oklch(55.5% 0.163 48.4); font-weight: 600; }
.ck__act { padding: 0.25rem 0; border: 0; background: none; font-size: 0.8rem; font-weight: 600; color: oklch(44.6% 0.03 256.8); text-decoration: underline; text-underline-offset: 3px; cursor: pointer; }
.ck__act--del { color: oklch(55.5% 0.163 48.4); }
.ck__link, .ck__add { display: flex; flex-wrap: wrap; gap: 0.5rem; flex: 1 1 100%; }
.ck__link input, .ck__add input[type="text"] { flex: 1 1 12rem; }
.ck input:not([type="checkbox"]), .ck select { min-height: 2.5rem; padding: 0 0.7rem; border: 1px solid oklch(92.8% 0.006 264.5); border-radius: 10px; background: #fff; font: inherit; font-size: 0.875rem; }
.ck__btn { min-height: 2.5rem; padding: 0 1rem; border: 1px solid oklch(52.7% 0.154 150.07); border-radius: 999px; background: oklch(52.7% 0.154 150.07); color: #fff; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
.ck__btn:disabled { opacity: 0.5; cursor: not-allowed; }
.ck__empty, .ck__error { margin: 0; font-size: 0.85rem; color: oklch(55.1% 0.027 264.4); }
.ck__error { color: oklch(55.5% 0.163 48.4); }
.ck input:focus-visible, .ck select:focus-visible, .ck button:focus-visible { outline: 2px solid oklch(52.7% 0.154 150.07); outline-offset: 2px; }
@media (prefers-reduced-motion: reduce) { .ck__bar span { transition: none; } }
</style>
