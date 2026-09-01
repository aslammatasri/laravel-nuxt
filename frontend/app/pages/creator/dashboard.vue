<script setup lang="ts">
definePageMeta({
    middleware: "creator",
    layout: "creator",
});

interface Application {
    id: number;
    status: string;
    product: { name: string };
}
interface Product {
    id: number;
    name: string;
    commission_rate: string;
    commission_type: string;
    category: string;
    price: number;
}

const { $api } = useApi();
const authStore = useAuth();

const { data: applications } = await useAsyncData("creator-applications", () =>
    $api<{ data: Application[] }>("/creator/applications").catch(() => null),
);

const { data: marketplace } = await useAsyncData("creator-marketplace", () =>
    $api<{ data: Product[] }>("/creator/marketplace", {
        params: { per_page: 5 },
    }).catch(() => null),
);

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
                Welcome back, {{ authStore.user?.name }} 👋
            </h1>
            <p class="text-gray-500 mt-1">
                Track your applications and find new products to promote
            </p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
            <div class="bg-white rounded-2xl p-6 border border-gray-100">
                <p class="text-sm text-gray-500 mb-1">Total Applications</p>
                <p class="text-3xl font-bold text-gray-900">
                    {{ totalApplications }}
                </p>
                <NuxtLink
                    to="/creator/applications"
                    class="text-xs text-green-600 hover:underline mt-2 block"
                >
                    View all →
                </NuxtLink>
            </div>

            <div class="bg-yellow-50 rounded-2xl p-6 border border-yellow-100">
                <p class="text-sm text-yellow-700 mb-1">Pending</p>
                <p class="text-3xl font-bold text-yellow-800">
                    {{ pendingApplications }}
                </p>
                <NuxtLink
                    to="/creator/applications?status=pending"
                    class="text-xs text-yellow-700 hover:underline mt-2 block"
                >
                    Check status →
                </NuxtLink>
            </div>

            <div class="bg-green-50 rounded-2xl p-6 border border-green-100">
                <p class="text-sm text-green-700 mb-1">Active Collabs</p>
                <p class="text-3xl font-bold text-green-800">
                    {{ approvedApplications }}
                </p>
                <NuxtLink
                    to="/creator/collaborations"
                    class="text-xs text-green-700 hover:underline mt-2 block"
                >
                    View collabs →
                </NuxtLink>
            </div>
        </div>

        <!-- Latest Products in Marketplace -->
        <div
            class="bg-white rounded-2xl border border-gray-100 overflow-hidden"
        >
            <div
                class="px-6 py-4 border-b border-gray-100 flex items-center justify-between"
            >
                <h2 class="font-semibold text-gray-900">Latest Products</h2>
                <NuxtLink
                    to="/creator/marketplace"
                    class="text-sm text-green-600 hover:underline"
                >
                    Browse all
                </NuxtLink>
            </div>

            <!-- Empty state -->
            <div
                v-if="!marketplace?.data?.length"
                class="px-6 py-12 text-center text-gray-400"
            >
                <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
                    <AppIcon name="marketplace" class="text-green-500" />
                </div>
                <p class="font-medium">No products available yet</p>
                <p class="text-sm mt-1">
                    Check back soon for new products to promote
                </p>
            </div>

            <!-- Products list -->
            <div v-else class="divide-y divide-gray-50">
                <div
                    v-for="product in marketplace?.data?.slice(0, 5)"
                    :key="product.id"
                    class="px-6 py-4 flex items-center justify-between"
                >
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            {{ product.name }}
                        </p>
                        <p class="text-xs text-gray-400 mt-0.5">
                            {{ product.category }} · RM {{ product.price }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-green-600">
                            {{ product.commission_rate
                            }}{{
                                product.commission_type === "percentage"
                                    ? "%"
                                    : " RM"
                            }}
                        </span>
                        <NuxtLink
                            :to="`/creator/marketplace/${product.id}`"
                            class="text-xs bg-green-50 text-green-700 px-3 py-1.5 rounded-xl hover:bg-green-100 transition-colors"
                        >
                            View
                        </NuxtLink>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
