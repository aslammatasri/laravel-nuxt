<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

interface Product {
    id: number;
    name: string;
    image: string | null;
    images: string[];
    status: string;
    flat_fee?: string | number | null;
    free_sample?: boolean;
    category: string;
    description: string;
    price: number;
    campaign_end?: string | null;
    pending_applications_count?: number;
}
interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
}

const config = useRuntimeConfig();
const { $api } = useApi();

const { data: products, refresh } = await useAsyncData("products", () =>
    $api<Paginated<Product>>("/brand/products").catch(() => null),
);

// Active but past its end date (before the daily job closes it)
const hasEnded = (p: Product) =>
    p.status === "active" &&
    !!p.campaign_end &&
    new Date(p.campaign_end).getTime() < Date.now();

async function setStatus(product: Product, status: "active" | "paused") {
    await $api(`/brand/products/${product.id}`, { method: "PUT", body: { status } });
    await refresh();
}

// Pausing hides the campaign from creators, so it asks first. Activating is harmless and runs directly.
const toPause = ref<Product | null>(null);
const pausing = ref(false);
const pauseError = ref<string | null>(null);

function onToggle(product: Product) {
    if (product.status === "active") {
        pauseError.value = null;
        toPause.value = product;
    } else {
        setStatus(product, "active");
    }
}
async function confirmPause() {
    if (!toPause.value) return;
    pausing.value = true;
    pauseError.value = null;
    try {
        await setStatus(toPause.value, "paused");
        toPause.value = null;
    } catch (e) {
        pauseError.value =
            (e as { data?: { message?: string } })?.data?.message ?? "Could not pause this campaign. Try again.";
    } finally {
        pausing.value = false;
    }
}

const toDelete = ref<Product | null>(null);
const deleting = ref(false);
const deleteError = ref<string | null>(null);

function askDelete(product: Product) {
    deleteError.value = null;
    toDelete.value = product;
}
async function confirmDelete() {
    if (!toDelete.value) return;
    deleting.value = true;
    deleteError.value = null;
    try {
        await $api(`/brand/products/${toDelete.value.id}`, { method: "DELETE" });
        toDelete.value = null;
        await refresh();
    } catch (e) {
        deleteError.value =
            (e as { data?: { message?: string } })?.data?.message ?? "Could not delete this campaign. Try again.";
    } finally {
        deleting.value = false;
    }
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Campaigns</h1>
                <p class="text-gray-500 mt-1">Manage your creator campaigns</p>
            </div>
            <NuxtLink
                to="/brand/products/create"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                + Create campaign
            </NuxtLink>
        </div>

        <!-- Empty state -->
        <div
            v-if="!products?.data?.length"
            class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400"
        >
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
                <AppIcon name="products" class="text-blue-500" />
            </div>
            <p class="font-medium text-gray-600">No campaigns yet</p>
            <p class="text-sm mt-1">
                Create your first campaign to start finding creators
            </p>
            <NuxtLink
                to="/brand/products/create"
                class="inline-block mt-4 bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-medium hover:bg-blue-700"
            >
                Create campaign
            </NuxtLink>
        </div>

        <!-- Products grid -->
        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"
        >
            <div
                v-for="product in products?.data"
                :key="product.id"
                class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-sm transition-shadow"
            >
                <!-- Product image -->
                <div class="h-40 overflow-hidden bg-gray-100">
                    <img
                        v-if="product.images?.[0]"
                        :src="`${config.public.storageBase}/${product.images[0]}`"
                        :alt="product.name"
                        class="w-full h-full object-cover"
                    />
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center"
                    >
                        <AppIcon name="products" class="w-10 h-10 text-gray-300" />
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex items-start justify-between mb-2">
                        <h3
                            class="font-semibold text-gray-900 text-sm leading-tight"
                        >
                            <NuxtLink :to="`/brand/products/${product.id}`" class="hover:underline">
                                {{ product.name }}
                            </NuxtLink>
                        </h3>
                        <span
                            class="text-xs px-2 py-0.5 rounded-full ml-2 shrink-0"
                            :class="{
                                'bg-green-100 text-green-700':
                                    product.status === 'active' && !hasEnded(product),
                                'bg-yellow-100 text-yellow-700':
                                    product.status === 'paused',
                                'bg-gray-100 text-gray-500':
                                    product.status === 'closed' || hasEnded(product),
                            }"
                        >
                            {{ hasEnded(product) ? "ended" : product.status }}
                        </span>
                    </div>

                    <NuxtLink
                        v-if="product.pending_applications_count"
                        :to="`/brand/products/${product.id}`"
                        class="inline-block text-xs px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-700 font-medium mb-3 hover:bg-yellow-200 transition-colors"
                    >
                        {{ product.pending_applications_count }} pending
                    </NuxtLink>

                    <p class="text-gray-400 text-xs mb-3 line-clamp-2">
                        {{ product.description }}
                    </p>

                    <div
                        class="flex items-center justify-between text-xs text-gray-500 mb-4"
                    >
                        <span>{{ product.price != null ? `RM ${product.price}` : "No price" }}</span>
                        <span class="font-medium text-green-600">
                            {{ paymentLabel(product) }}
                            {{ paymentSub(product) }}
                        </span>
                    </div>

                    <div class="flex gap-2">
                        <NuxtLink
                            :to="`/brand/products/${product.id}/edit`"
                            class="flex-1 text-center border border-gray-200 text-gray-600 px-3 py-2 rounded-xl text-xs hover:bg-gray-50 transition-colors"
                        >
                            Edit
                        </NuxtLink>
                        <button
                            v-if="product.status !== 'closed'"
                            @click="onToggle(product)"
                            class="flex-1 text-center border border-gray-200 text-gray-600 px-3 py-2 rounded-xl text-xs hover:bg-gray-50 transition-colors"
                        >
                            {{ product.status === "active" ? "Pause" : "Activate" }}
                        </button>
                        <button
                            @click="askDelete(product)"
                            class="flex-1 text-center border border-red-100 text-red-500 px-3 py-2 rounded-xl text-xs hover:bg-red-50 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog
            :open="!!toPause"
            title="Pause this campaign?"
            confirm-label="Pause campaign"
            :loading="pausing"
            :error="pauseError"
            @confirm="confirmPause"
            @cancel="toPause = null"
        >
            <p>
                <strong class="text-gray-800">{{ toPause?.name }}</strong> will be hidden from the marketplace and creators can no longer apply.
            </p>
            <p class="mt-2">Creators already approved keep their collaboration. You can activate it again any time.</p>
        </ConfirmDialog>

        <ConfirmDialog
            :open="!!toDelete"
            danger
            title="Delete this campaign?"
            confirm-label="Delete campaign"
            :loading="deleting"
            :error="deleteError"
            @confirm="confirmDelete"
            @cancel="toDelete = null"
        >
            <p>
                <strong class="text-gray-800">{{ toDelete?.name }}</strong> and everything attached to it will be permanently removed: applications, creator checklists and messages. This cannot be undone.
            </p>
            <p v-if="toDelete?.pending_applications_count" class="mt-2 text-amber-700">
                {{ toDelete.pending_applications_count }} pending {{ toDelete.pending_applications_count === 1 ? "application" : "applications" }} will be deleted too.
            </p>
            <p class="mt-2">To stop new applications but keep the history, use Pause instead.</p>
        </ConfirmDialog>
    </div>
</template>
