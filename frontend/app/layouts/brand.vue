<script setup lang="ts">
const route = useRoute()
const { collapsed, toggle } = useSidebar()

const navItems = [
  { label: 'Dashboard',    icon: 'dashboard',    to: '/brand/dashboard'    },
  { label: 'Campaigns',    icon: 'products',     to: '/brand/products'     },
  { label: 'Applications', icon: 'applications', to: '/brand/applications' },
  { label: 'Creators',     icon: 'creators',     to: '/brand/creators'     },
  { label: 'Messages',     icon: 'messages',     to: '/messages'           },
]

const isActive = (path: string) => route.path.startsWith(path)

const unread = useUnreadMessages()
onMounted(() => unread.startPolling())
onUnmounted(() => unread.stopPolling())
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <aside :class="collapsed ? 'w-20' : 'w-64'"
            class="bg-white border-r border-gray-100 flex flex-col fixed h-full z-20 transition-[width] duration-200 overflow-hidden">

      <!-- Logo -->
      <div
                class="py-5 border-b border-gray-100 flex items-center whitespace-nowrap"
                :class="collapsed ? 'justify-center px-2' : 'px-6'"
            >
                <span v-if="collapsed" class="text-xl font-bold text-gray-900">A</span>
                <template v-else>
                    <span class="text-xl font-bold text-gray-900">AffiliateMY</span>
                    <span
                        class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium"
                        >Brand</span
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
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900',
                        collapsed ? 'justify-center px-0' : 'px-4',
                    ]"
        >
          <span class="relative">
            <AppIcon :name="item.icon" />
            <span
              v-if="item.to === '/messages' && unread.count.value > 0"
              class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-red-500 ring-2 ring-white"
            />
          </span>
          <span v-if="!collapsed" class="flex-1">{{ item.label }}</span>
          <span
            v-if="!collapsed && item.to === '/messages' && unread.count.value > 0"
            class="min-w-[20px] h-5 px-1.5 flex items-center justify-center rounded-full bg-red-500 text-white text-[11px] font-bold"
          >
            {{ unread.count.value > 9 ? '9+' : unread.count.value }}
          </span>
        </NuxtLink>
      </nav>

    </aside>

    <!-- Main content -->
    <main
            class="flex-1 transition-[margin] duration-200"
            :class="collapsed ? 'ml-20' : 'ml-64'"
        >
      <header class="sticky top-0 z-10 bg-white border-b border-gray-100 px-8 py-4 flex items-center justify-between gap-3">
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
        <UserMenu accent="blue" />
                </div>
      </header>
      <div class="p-8">
        <slot />
      </div>
    </main>

  </div>
</template>