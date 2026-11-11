<script setup lang="ts">
definePageMeta({ middleware: "auth" });

interface Conversation {
    id: number;
    product: { id: number; name: string; image: string | null };
    other: { id: number; name: string };
    unread_count: number;
    last_message: { body: string; mine: boolean; created_at: string } | null;
    last_message_at: string | null;
}
interface Message {
    id: number;
    sender_id: number;
    body: string;
    created_at: string;
}

const authStore = useAuth();
setPageLayout(authStore.isBrand ? "brand" : "creator");

const route = useRoute();
const config = useRuntimeConfig();
const { $api } = useApi();
const unread = useUnreadMessages();

const myId = computed(() => authStore.user?.id);
const accent = computed(() =>
    authStore.isBrand
        ? { bubble: "bg-blue-600", ring: "focus:ring-blue-500", btn: "bg-blue-600 hover:bg-blue-700", dot: "bg-blue-600", active: "bg-blue-50" }
        : { bubble: "bg-green-600", ring: "focus:ring-green-500", btn: "bg-green-600 hover:bg-green-700", dot: "bg-green-600", active: "bg-green-50" },
);

const conversations = ref<Conversation[]>([]);
const loadingList = ref(true);
const selectedId = ref<number | null>(null);
const messages = ref<Message[]>([]);
const loadingThread = ref(false);
const draft = ref("");
const sending = ref(false);
const error = ref<string | null>(null);
const scroller = ref<HTMLElement | null>(null);

const selected = computed(() =>
    conversations.value.find((c) => c.id === selectedId.value) ?? null,
);

async function loadConversations() {
    try {
        const res = await $api<{ data: Conversation[] }>("/conversations");
        conversations.value = res.data;
    } catch {
        // keep previous list on poll failure
    } finally {
        loadingList.value = false;
    }
}

function scrollToBottom() {
    nextTick(() => {
        if (scroller.value) scroller.value.scrollTop = scroller.value.scrollHeight;
    });
}

async function markRead() {
    const conv = selected.value;
    if (!conv || !conv.unread_count) return;
    conv.unread_count = 0;
    try {
        await $api(`/conversations/${conv.id}/read`, { method: "PATCH" });
        unread.refresh();
    } catch {
        // retried on next poll
    }
}

async function openConversation(id: number) {
    selectedId.value = id;
    messages.value = [];
    error.value = null;
    loadingThread.value = true;
    try {
        const res = await $api<{ data: Message[] }>(`/conversations/${id}/messages`);
        messages.value = res.data;
        scrollToBottom();
        await markRead();
    } catch {
        error.value = "Could not load messages";
    } finally {
        loadingThread.value = false;
    }
}

async function pollThread() {
    const id = selectedId.value;
    if (!id) return;
    const last = messages.value[messages.value.length - 1]?.id ?? 0;
    try {
        const res = await $api<{ data: Message[] }>(`/conversations/${id}/messages`, {
            params: { after: last },
        });
        if (id !== selectedId.value || !res.data.length) return;
        const seen = new Set(messages.value.map((m) => m.id));
        const fresh = res.data.filter((m) => !seen.has(m.id));
        if (!fresh.length) return;
        const nearBottom =
            !scroller.value ||
            scroller.value.scrollHeight - scroller.value.scrollTop - scroller.value.clientHeight < 120;
        messages.value.push(...fresh);
        if (nearBottom) scrollToBottom();
        await loadConversations();
        await markRead();
    } catch {
        // ignore poll failures
    }
}

async function send() {
    const body = draft.value.trim();
    if (!body || !selectedId.value || sending.value) return;
    sending.value = true;
    error.value = null;
    try {
        const msg = await $api<Message>(`/conversations/${selectedId.value}/messages`, {
            method: "POST",
            body: { body },
        });
        messages.value.push(msg);
        draft.value = "";
        scrollToBottom();
        loadConversations();
    } catch (err: any) {
        error.value = err?.data?.message || "Failed to send message";
    } finally {
        sending.value = false;
    }
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        send();
    }
}

function back() {
    selectedId.value = null;
    messages.value = [];
}

// ── Open a chat from ?application=ID (entry buttons) or ?c=ID ──
async function openFromQuery() {
    const appId = Number(route.query.application);
    const convId = Number(route.query.c);
    if (appId) {
        try {
            const res = await $api<{ id: number }>("/conversations", {
                method: "POST",
                body: { application_id: appId },
            });
            await loadConversations();
            await openConversation(res.id);
        } catch (err: any) {
            error.value = err?.data?.message || "Could not open this chat";
        }
    } else if (convId) {
        await openConversation(convId);
    }
}

// ── Formatting ─────────────────────────────────────────────────
function relative(d: string | null) {
    if (!d) return "";
    const mins = Math.floor((Date.now() - new Date(d).getTime()) / 60000);
    if (mins < 1) return "now";
    if (mins < 60) return `${mins}m`;
    const h = Math.floor(mins / 60);
    if (h < 24) return `${h}h`;
    return `${Math.floor(h / 24)}d`;
}
function time(d: string) {
    return new Date(d).toLocaleTimeString("en-MY", { hour: "numeric", minute: "2-digit" });
}
function dayLabel(d: string) {
    return new Date(d).toLocaleDateString("en-MY", { weekday: "short", day: "numeric", month: "short" });
}
function showDay(i: number) {
    if (i === 0) return true;
    return dayLabel(messages.value[i]!.created_at) !== dayLabel(messages.value[i - 1]!.created_at);
}

// ── Polling: list 10s, open thread 5s, only while tab visible ──
// Timers start synchronously and are guarded by `disposed`: setPageLayout can
// remount this page, and a timer created after unmount would leak and keep
// polling on every page (and 401 once the session ends).
const token = useCookie("auth_token");
let disposed = false;
let listTimer: ReturnType<typeof setInterval> | null = null;
let threadTimer: ReturnType<typeof setInterval> | null = null;

const canPoll = () =>
    !disposed && !!token.value && document.visibilityState === "visible";

onMounted(() => {
    listTimer = setInterval(() => {
        if (canPoll()) loadConversations();
    }, 10000);
    threadTimer = setInterval(() => {
        if (canPoll()) pollThread();
    }, 5000);

    (async () => {
        await loadConversations();
        if (!disposed) await openFromQuery();
    })();
});

onBeforeUnmount(() => {
    disposed = true;
    if (listTimer) clearInterval(listTimer);
    if (threadTimer) clearInterval(threadTimer);
});
</script>

<template>
    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
            <p class="text-gray-500 mt-1">
                Chat with {{ authStore.isBrand ? "creators" : "brands" }} you collaborate with
            </p>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden flex h-[calc(100vh-14rem)] min-h-[420px]">
            <!-- Conversation list -->
            <aside
                class="w-full md:w-80 md:border-r border-gray-100 flex-col shrink-0"
                :class="selectedId ? 'hidden md:flex' : 'flex'"
            >
                <div v-if="loadingList" class="p-6 text-sm text-gray-400">Loading…</div>

                <div v-else-if="!conversations.length" class="p-8 text-center text-gray-400 my-auto">
                    <div class="w-14 h-14 rounded-full bg-gray-50 flex items-center justify-center mx-auto mb-4">
                        <AppIcon name="messages" class="text-gray-400" />
                    </div>
                    <p class="font-medium text-gray-600">No conversations yet</p>
                    <p class="text-sm mt-1">
                        A chat opens when an application is approved.
                    </p>
                </div>

                <ul v-else class="overflow-y-auto divide-y divide-gray-50">
                    <li v-for="c in conversations" :key="c.id">
                        <button
                            @click="openConversation(c.id)"
                            class="w-full text-left px-4 py-3 flex gap-3 hover:bg-gray-50 transition-colors"
                            :class="c.id === selectedId ? accent.active : ''"
                        >
                            <div class="w-11 h-11 rounded-xl bg-gray-100 overflow-hidden shrink-0 flex items-center justify-center">
                                <img
                                    v-if="c.product.image"
                                    :src="`${config.public.storageBase}/${c.product.image}`"
                                    :alt="c.product.name"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-sm font-bold text-gray-500">
                                    {{ c.other.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-baseline justify-between gap-2">
                                    <p class="text-sm truncate" :class="c.unread_count ? 'font-semibold text-gray-900' : 'font-medium text-gray-800'">
                                        {{ c.other.name }}
                                    </p>
                                    <span class="text-[11px] text-gray-400 shrink-0">{{ relative(c.last_message_at) }}</span>
                                </div>
                                <p class="text-xs text-gray-400 truncate">{{ c.product.name }}</p>
                                <div class="flex items-center justify-between gap-2 mt-0.5">
                                    <p class="text-xs truncate" :class="c.unread_count ? 'text-gray-800' : 'text-gray-500'">
                                        <template v-if="c.last_message">
                                            {{ c.last_message.mine ? "You: " : "" }}{{ c.last_message.body }}
                                        </template>
                                        <template v-else>No messages yet</template>
                                    </p>
                                    <span
                                        v-if="c.unread_count"
                                        class="min-w-[18px] h-[18px] px-1 rounded-full text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                        :class="accent.dot"
                                    >
                                        {{ c.unread_count > 9 ? "9+" : c.unread_count }}
                                    </span>
                                </div>
                            </div>
                        </button>
                    </li>
                </ul>
            </aside>

            <!-- Thread -->
            <section class="flex-1 min-w-0 flex-col" :class="selectedId ? 'flex' : 'hidden md:flex'">
                <div v-if="!selected" class="m-auto text-center text-gray-400 px-6">
                    <p class="font-medium text-gray-600">Select a conversation</p>
                    <p class="text-sm mt-1">Pick a chat on the left to start messaging.</p>
                    <p v-if="error" class="text-sm text-red-500 mt-3">{{ error }}</p>
                </div>

                <template v-else>
                    <header class="px-4 py-3 border-b border-gray-100 flex items-center gap-3">
                        <button class="md:hidden p-1 -ml-1 text-gray-500" @click="back" aria-label="Back to conversations">←</button>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ selected.other.name }}</p>
                            <NuxtLink
                                :to="authStore.isBrand ? `/brand/products/${selected.product.id}` : `/creator/marketplace/${selected.product.id}`"
                                class="text-xs text-gray-500 hover:underline truncate block"
                            >
                                {{ selected.product.name }}
                            </NuxtLink>
                        </div>
                    </header>

                    <div ref="scroller" class="flex-1 overflow-y-auto px-4 py-4 space-y-1 bg-gray-50">
                        <p v-if="loadingThread" class="text-sm text-gray-400 text-center">Loading…</p>
                        <p v-else-if="!messages.length" class="text-sm text-gray-400 text-center py-8">
                            No messages yet. Say hello 👋
                        </p>

                        <template v-for="(m, i) in messages" :key="m.id">
                            <p v-if="showDay(i)" class="text-[11px] text-gray-400 text-center py-2">
                                {{ dayLabel(m.created_at) }}
                            </p>
                            <div class="flex" :class="m.sender_id === myId ? 'justify-end' : 'justify-start'">
                                <div
                                    class="max-w-[80%] md:max-w-[65%] px-3.5 py-2 rounded-2xl text-sm whitespace-pre-wrap break-words"
                                    :class="
                                        m.sender_id === myId
                                            ? `${accent.bubble} text-white rounded-br-md`
                                            : 'bg-white border border-gray-100 text-gray-800 rounded-bl-md'
                                    "
                                >
                                    {{ m.body }}
                                    <span
                                        class="block text-[10px] mt-0.5 text-right"
                                        :class="m.sender_id === myId ? 'text-white/70' : 'text-gray-400'"
                                    >
                                        {{ time(m.created_at) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <form class="border-t border-gray-100 p-3 flex items-end gap-2" @submit.prevent="send">
                        <textarea
                            v-model="draft"
                            rows="1"
                            maxlength="2000"
                            placeholder="Write a message…  (Enter to send, Shift+Enter for new line)"
                            class="flex-1 resize-none border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 max-h-32"
                            :class="accent.ring"
                            @keydown="onKeydown"
                        />
                        <button
                            type="submit"
                            :disabled="!draft.trim() || sending"
                            class="text-white px-4 py-2.5 rounded-xl text-sm font-medium disabled:opacity-50 transition-colors"
                            :class="accent.btn"
                        >
                            Send
                        </button>
                    </form>
                    <p v-if="error" class="px-4 pb-2 text-xs text-red-500">{{ error }}</p>
                </template>
            </section>
        </div>
    </div>
</template>
