<script setup lang="ts">
const authStore = useAuthStore();
const route = useRoute();

const navItems = [
    { label: "Dashboard", icon: "dashboard", to: "/creator/dashboard" },
    { label: "Marketplace", icon: "marketplace", to: "/creator/marketplace" },
    {
        label: "Applications",
        icon: "applications",
        to: "/creator/applications",
    },
    {
        label: "Collaborations",
        icon: "collaborations",
        to: "/creator/collaborations",
    },
    { label: "Earnings", icon: "earnings", to: "/creator/earnings" },
    { label: "Portfolio", icon: "portfolio", to: "/creator/portfolio" },
    { label: "Messages", icon: "messages", to: "/messages" },
];

const isActive = (path: string) => route.path.startsWith(path);
</script>

<template>
    <div class="min-h-screen bg-gray-50 flex">
        <!-- Sidebar -->
        <aside
            class="w-64 bg-white border-r border-gray-100 flex flex-col fixed h-full"
        >
            <!-- Logo -->
            <div class="px-6 py-5 border-b border-gray-100">
                <span class="text-xl font-bold text-gray-900">AffiliateMY</span>
                <span
                    class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium"
                    >Creator</span
                >
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-4 py-4 space-y-1">
                <NuxtLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
                    :class="
                        isActive(item.to)
                            ? 'bg-green-50 text-green-700'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'
                    "
                >
                    <AppIcon :name="item.icon" />
                    {{ item.label }}
                </NuxtLink>
            </nav>

            <!-- User -->
            <div class="px-4 py-4 border-t border-gray-100">
                <div class="flex items-center gap-3 px-4 py-2">
                    <div
                        class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-sm font-bold"
                    >
                        {{ authStore.user?.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ authStore.user?.name }}
                        </p>
                        <p class="text-xs text-gray-400 truncate">
                            {{ authStore.user?.email }}
                        </p>
                    </div>
                </div>
                <NuxtLink
                    to="/profile"
                    class="w-full mt-1 flex items-center gap-3 px-4 py-2 text-sm text-gray-500 hover:bg-gray-50 rounded-xl transition-colors"
                >
                    <AppIcon name="settings" /> Settings
                </NuxtLink>
                <button
                    @click="authStore.logout()"
                    class="w-full mt-1 text-left px-4 py-2 text-sm text-red-500 hover:bg-red-50 rounded-xl transition-colors"
                >
                    Logout
                </button>
            </div>
        </aside>

        <!-- Main content -->
        <main class="flex-1 ml-64">
            <header
                class="sticky top-0 z-10 bg-white border-b border-gray-100 px-8 py-4 flex justify-end"
            >
                <NotificationBell />
            </header>
            <div class="p-8">
                <slot />
            </div>
        </main>
    </div>
</template>
