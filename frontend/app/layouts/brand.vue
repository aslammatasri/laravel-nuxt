<script setup lang="ts">
const route = useRoute()

const navItems = [
  { label: 'Dashboard',    icon: 'dashboard',    to: '/brand/dashboard'    },
  { label: 'Products',     icon: 'products',     to: '/brand/products'     },
  { label: 'Applications', icon: 'applications', to: '/brand/applications' },
  { label: 'Creators',     icon: 'creators',     to: '/brand/creators'     },
  { label: 'Messages',     icon: 'messages',     to: '/messages'           },
]

const isActive = (path: string) => route.path.startsWith(path)
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-white border-r border-gray-100 flex flex-col fixed h-full">

      <!-- Logo -->
      <div class="px-6 py-5 border-b border-gray-100">
        <span class="text-xl font-bold text-gray-900">AffiliateMY</span>
        <span class="ml-2 text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">Brand</span>
      </div>

      <!-- Nav -->
      <nav class="flex-1 px-4 py-4 space-y-1">
        <NuxtLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-colors"
          :class="isActive(item.to)
            ? 'bg-blue-50 text-blue-700'
            : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'"
        >
          <AppIcon :name="item.icon" />
          {{ item.label }}
        </NuxtLink>
      </nav>

    </aside>

    <!-- Main content -->
    <main class="flex-1 ml-64">
      <header class="sticky top-0 z-10 bg-white border-b border-gray-100 px-8 py-4 flex items-center justify-end gap-3">
        <NotificationBell />
        <UserMenu accent="blue" />
      </header>
      <div class="p-8">
        <slot />
      </div>
    </main>

  </div>
</template>