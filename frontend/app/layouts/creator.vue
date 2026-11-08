<script setup lang="ts">
const route = useRoute();
const { collapsed, toggle } = useSidebar();

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
            :class="collapsed ? 'w-20' : 'w-64'"
            class="bg-white border-r border-gray-100 flex flex-col fixed h-full z-20 transition-[width] duration-200 overflow-hidden"
        >
            <!-- Logo -->
            <div
                class="py-5 border-b border-gray-100 flex items-center whitespace-nowrap"
                :class="collapsed ? 'justify-center px-2' : 'px-6'"
            >
                <span v-if="collapsed" class="text-xl font-bold text-gray-900">A</span>
                <template v-else>
                    <span class="text-xl font-bold text-gray-900">AffiliateMY</span>
                    <span
                        class="ml-2 text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium"
                        >Creator</span
                    >
                </template>
            </div>

            <!-- Nav -->
            <nav class="flex-1 py-4 space-y-1" :class="collapsed ? 'px-3' : 'px-4'">
                <NuxtLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 py-2.5 rounded-xl text-sm font-medium transition-colors whitespace-nowrap"
                    :title="collapsed ? item.label : undefined"
                    :class="[
                        isActive(item.to)
                            ? 'bg-green-50 text-green-700'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900',
                        collapsed ? 'justify-center px-0' : 'px-4',
                    ]"
                >
                    <AppIcon :name="item.icon" />
                    <span v-if="!collapsed">{{ item.label }}</span>
                </NuxtLink>
            </nav>

        </aside>

        <!-- Main content -->
        <main
            class="flex-1 transition-[margin] duration-200"
            :class="collapsed ? 'ml-20' : 'ml-64'"
        >
            <header
                class="sticky top-0 z-10 bg-white border-b border-gray-100 px-8 py-4 flex items-center justify-between gap-3"
            >
                <button
                    type="button"
                    class="p-2 rounded-lg text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                    :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                    :aria-expanded="!collapsed"
                    @click="toggle"
                >
                    <AppIcon name="menu" />
                </button>
                <div class="flex items-center gap-3">
                <NotificationBell />
                <UserMenu accent="green" />
                </div>
            </header>
            <div class="p-8">
                <slot />
            </div>
        </main>
    </div>
</template>
