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
    commission_rate: string;
    commission_type: string;
    category: string;
    description: string;
    price: number;
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

async function deleteProduct(id: number) {
    if (!confirm("Are you sure you want to delete this product?")) return;

    await $api(`/brand/products/${id}`, { method: "DELETE" });
    await refresh();
}
</script>

<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Products</h1>
                <p class="text-gray-500 mt-1">Manage your affiliate products</p>
            </div>
            <NuxtLink
                to="/brand/products/create"
                class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700 transition-colors"
            >
                + Add Product
            </NuxtLink>
        </div>

        <!-- Empty state -->
        <div
            v-if="!products?.data?.length"
            class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400"
        >
            <p class="text-4xl mb-3">📦</p>
            <p class="font-medium text-gray-600">No products yet</p>
            <p class="text-sm mt-1">
                Add your first product to start finding creators
            </p>
            <NuxtLink
                to="/brand/products/create"
                class="inline-block mt-4 bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-medium hover:bg-blue-700"
            >
                Add Product
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
                        class="w-full h-full flex items-center justify-center text-4xl"
                    >
                        📦
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex items-start justify-between mb-2">
                        <h3
                            class="font-semibold text-gray-900 text-sm leading-tight"
                        >
                            {{ product.name }}
                        </h3>
                        <span
                            class="text-xs px-2 py-0.5 rounded-full ml-2 shrink-0"
                            :class="{
                                'bg-green-100 text-green-700':
                                    product.status === 'active',
                                'bg-gray-100 text-gray-500':
                                    product.status !== 'active',
                            }"
                        >
                            {{ product.status }}
                        </span>
                    </div>

                    <p class="text-gray-400 text-xs mb-3 line-clamp-2">
                        {{ product.description }}
                    </p>

                    <div
                        class="flex items-center justify-between text-xs text-gray-500 mb-4"
                    >
                        <span>RM {{ product.price }}</span>
                        <span class="font-medium text-green-600">
                            {{ product.commission_rate
                            }}{{
                                product.commission_type === "percentage"
                                    ? "%"
                                    : " RM"
                            }}
                            commission
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
                            @click="deleteProduct(product.id)"
                            class="flex-1 text-center border border-red-100 text-red-500 px-3 py-2 rounded-xl text-xs hover:bg-red-50 transition-colors"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
