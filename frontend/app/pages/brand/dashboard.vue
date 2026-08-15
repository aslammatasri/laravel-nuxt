<script setup lang="ts">
definePageMeta({
    middleware: "brand",
    layout: "brand",
});

interface Product {
    id: number;
    name: string;
    status: string;
    commission_rate: string;
}
interface Application {
    id: number;
    status: string;
    creator: { name: string };
    product: Product;
}
interface Paginated<T> {
    data: T[];
    total: number;
    current_page: number;
}

const { $api } = useApi();
const authStore = useAuth();

// ── Fetch stats ────────────────────────────────────────────────
const { data: products } = await useAsyncData("brand-products", () =>
    $api<Paginated<Product>>("/brand/products").catch(() => null),
);

const { data: applications } = await useAsyncData("brand-applications", () =>
    $api<Paginated<Application>>("/brand/applications").catch(() => null),
);

// ── Computed stats ─────────────────────────────────────────────
const totalProducts = computed(() => products.value?.data?.length ?? 0);
const totalApplications = computed(() => applications.value?.data?.length ?? 0);
const pendingApplications = computed(
    () =>
        applications.value?.data?.filter((a: any) => a.status === "pending")
            .length ?? 0,
);
const approvedApplications = computed(
    () =>
        applications.value?.data?.filter((a: any) => a.status === "approved")
            .length ?? 0,
);
</script>

<template>
    <div>
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">
                Good morning, {{ authStore.user?.name }} 👋
            </h1>
            <p class="text-gray-500 mt-1">
                Here's what's happening with your campaigns
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Total Products</p>
                <p class="text-3xl font-bold text-gray-900">
                    {{ totalProducts }}
                </p>
                <NuxtLink
                    to="/brand/products"
                    class="text-xs text-blue-600 hover:underline mt-2 block"
                >
                    Manage products →
                </NuxtLink>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Total Applications</p>
                <p class="text-3xl font-bold text-gray-900">
                    {{ totalApplications }}
                </p>
                <NuxtLink
                    to="/brand/applications"
                    class="text-xs text-blue-600 hover:underline mt-2 block"
                >
                    View all →
                </NuxtLink>
            </div>

            <div
                class="bg-white rounded-2xl p-6 border border-yellow-100 bg-yellow-50"
            >
                <p class="text-sm text-yellow-700 mb-1">Pending Review</p>
                <p class="text-3xl font-bold text-yellow-800">
                    {{ pendingApplications }}
                </p>
                <NuxtLink
                    to="/brand/applications?status=pending"
                    class="text-xs text-yellow-700 hover:underline mt-2 block"
                >
                    Review now →
                </NuxtLink>
            </div>

            <div
                class="bg-white rounded-2xl p-6 border border-green-100 bg-green-50"
            >
                <p class="text-sm text-green-700 mb-1">Active Creators</p>
                <p class="text-3xl font-bold text-green-800">
                    {{ approvedApplications }}
                </p>
                <NuxtLink
                    to="/brand/applications?status=approved"
                    class="text-xs text-green-700 hover:underline mt-2 block"
                >
                    View creators →
                </NuxtLink>
            </div>
        </div>

        <!-- Recent Applications -->
        <div
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden"
        >
            <div
                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
            >
                <h2 class="font-semibold text-gray-900">Recent Applications</h2>
                <NuxtLink
                    to="/brand/applications"
                    class="text-sm text-blue-600 hover:underline"
                >
                    View all
                </NuxtLink>
            </div>

            <!-- Empty state -->
            <div
                v-if="!applications?.data?.length"
                class="px-6 py-12 text-center text-gray-400"
            >
                <p class="text-4xl mb-3">📋</p>
                <p class="font-medium">No applications yet</p>
                <p class="text-sm mt-1">
                    Applications will appear here when creators apply to your
                    products
                </p>
            </div>

            <!-- Applications list -->
            <div v-else class="divide-y divide-gray-50">
                <div
                    v-for="app in applications?.data?.slice(0, 5)"
                    :key="app.id"
                    class="px-6 py-4 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 text-sm font-bold"
                        >
                            {{ app.creator?.name?.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">
                                {{ app.creator?.name }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ app.product?.name }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="text-xs px-3 py-1 rounded-full font-medium"
                        :class="{
                            'bg-yellow-100 text-yellow-700':
                                app.status === 'pending',
                            'bg-green-100 text-green-700':
                                app.status === 'approved',
                            'bg-red-100 text-red-700':
                                app.status === 'rejected',
                        }"
                    >
                        {{ app.status }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
