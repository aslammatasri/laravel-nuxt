<script setup lang="ts">
definePageMeta({ middleware: "creator", layout: "creator" });

interface Collab {
    id: number;
    approved_at: string;
    pitch_message: string | null;
    state: "active" | "ending_soon" | "ended";
    days_left: number | null;
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
        variations: { id: number; name: string; options: { value: string; price: number | string }[] }[];
    };
}

const config = useRuntimeConfig();
const { $api } = useApi();

const { data } = await useAsyncData("creator-collaborations", () =>
    $api<{ data: Collab[] }>("/creator/collaborations").catch(() => null),
);

const filter = ref<"all" | "active" | "ended">("all");
const expandedId = ref<number | null>(null);

const collabs = computed(() => data.value?.data ?? []);
const counts = computed(() => ({
    all: collabs.value.length,
    active: collabs.value.filter((c) => c.state !== "ended").length,
    ended: collabs.value.filter((c) => c.state === "ended").length,
}));
const visible = computed(() =>
    collabs.value.filter((c) =>
        filter.value === "all"
            ? true
            : filter.value === "ended"
              ? c.state === "ended"
              : c.state !== "ended",
    ),
);

const tabs = [
    { key: "all", label: "All" },
    { key: "active", label: "Active" },
    { key: "ended", label: "Ended" },
] as const;

function stateLabel(c: Collab) {
    if (c.state === "ended") return "Ended";
    if (c.days_left === null) return "Active";
    if (c.state === "ending_soon")
        return c.days_left === 0 ? "Ends today" : `${c.days_left}d left`;
    return "Active";
}
const stateClass = (s: Collab["state"]) =>
    s === "ended"
        ? "bg-gray-100 text-gray-500"
        : s === "ending_soon"
          ? "bg-yellow-100 text-yellow-700"
          : "bg-green-100 text-green-700";

function formatDate(d: string | null) {
    if (!d) return "-";
    return new Date(d).toLocaleDateString("en-MY", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}
function commission(c: Collab) {
    return `${c.product.commission_rate}${c.product.commission_type === "percentage" ? "%" : " RM"}`;
}
</script>

<template>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Collaborations</h1>
            <p class="text-gray-500 mt-1">Products you are approved to promote</p>
        </div>

        <!-- Filter tabs -->
        <div class="flex gap-2 mb-6">
            <button
                v-for="t in tabs"
                :key="t.key"
                @click="filter = t.key"
                class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
                :class="
                    filter === t.key
                        ? 'bg-green-600 text-white'
                        : 'bg-white border border-gray-200 text-gray-500 hover:bg-gray-50'
                "
            >
                {{ t.label }}
                <span class="ml-1 opacity-70">{{ counts[t.key] }}</span>
            </button>
        </div>

        <!-- Empty state -->
        <div
            v-if="!visible.length"
            class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400"
        >
            <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                <AppIcon name="collaborations" class="text-green-500" />
            </div>
            <p class="font-medium text-gray-600">
                {{ collabs.length ? "No collaborations in this filter" : "No collaborations yet" }}
            </p>
            <p class="text-sm mt-1">
                {{ collabs.length ? "Try another filter." : "Once a brand approves your application, it appears here." }}
            </p>
            <NuxtLink
                v-if="!collabs.length"
                to="/creator/marketplace"
                class="inline-block mt-4 bg-green-500 text-white px-5 py-2 rounded-xl text-sm font-medium hover:bg-green-600"
            >
                Browse Marketplace
            </NuxtLink>
        </div>

        <!-- Cards -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div
                v-for="c in visible"
                :key="c.id"
                class="bg-white rounded-2xl border border-gray-100 overflow-hidden"
                :class="{ 'opacity-75': c.state === 'ended' }"
            >
                <div class="p-5 flex gap-4">
                    <div class="w-24 h-24 rounded-xl bg-gray-50 overflow-hidden shrink-0 flex items-center justify-center">
                        <img
                            v-if="c.product.images?.[0]"
                            :src="`${config.public.storageBase}/${c.product.images[0]}`"
                            :alt="c.product.name"
                            class="w-full h-full object-cover"
                        />
                        <AppIcon v-else name="marketplace" class="text-gray-300" />
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <h2 class="font-semibold text-gray-900 truncate">{{ c.product.name }}</h2>
                            <span class="text-xs px-2.5 py-0.5 rounded-full font-medium shrink-0" :class="stateClass(c.state)">
                                {{ stateLabel(c) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5 flex items-center gap-1.5">
                            <Avatar :src="c.product.brand?.avatar" :name="c.product.brand?.name" size="xxs" />
                            by {{ c.product.brand?.name }}
                        </p>

                        <dl class="grid grid-cols-3 gap-3 mt-3 text-xs">
                            <div>
                                <dt class="text-gray-400">Commission</dt>
                                <dd class="font-semibold text-green-600">{{ commission(c) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Approved</dt>
                                <dd class="text-gray-700">{{ formatDate(c.approved_at) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-400">Ends</dt>
                                <dd class="text-gray-700">{{ formatDate(c.product.campaign_end) }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="px-5 pb-4 flex items-center gap-2">
                    <button
                        @click="expandedId = expandedId === c.id ? null : c.id"
                        class="text-xs px-3 py-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition-colors"
                        :aria-expanded="expandedId === c.id"
                    >
                        {{ expandedId === c.id ? "Hide brief" : "View brief" }}
                    </button>
                    <NuxtLink
                        :to="`/messages?application=${c.id}`"
                        class="text-xs px-3 py-1.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-medium transition-colors"
                    >
                        Message brand
                    </NuxtLink>
                    <NuxtLink
                        :to="`/creator/marketplace/${c.product.id}`"
                        class="text-xs px-3 py-1.5 rounded-xl bg-green-50 text-green-700 hover:bg-green-100 font-medium transition-colors"
                    >
                        View product →
                    </NuxtLink>
                </div>

                <!-- Brief -->
                <div v-if="expandedId === c.id" class="border-t border-gray-100 bg-gray-50 px-5 py-4 space-y-4 text-sm">
                    <div v-if="c.product.description">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">About the product</p>
                        <p class="text-gray-700 whitespace-pre-line">{{ c.product.description }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-400">Campaign</p>
                            <p class="text-gray-700">
                                {{ formatDate(c.product.campaign_start) }} – {{ formatDate(c.product.campaign_end) }}
                            </p>
                        </div>
                        <div v-if="c.product.price">
                            <p class="text-xs text-gray-400">Price</p>
                            <p class="text-gray-700">RM {{ c.product.price }}</p>
                        </div>
                    </div>

                    <div v-if="c.product.variations?.length">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Variations</p>
                        <div v-for="v in c.product.variations" :key="v.id" class="mb-1">
                            <span class="font-medium text-gray-700">{{ v.name }}:</span>
                            <span class="text-gray-600">
                                {{ v.options.map((o) => `${o.value} (RM ${o.price})`).join(", ") }}
                            </span>
                        </div>
                    </div>

                    <div v-if="c.pitch_message">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Your pitch</p>
                        <p class="text-gray-600 whitespace-pre-line">{{ c.pitch_message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
