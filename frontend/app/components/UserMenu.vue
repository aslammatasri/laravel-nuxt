<script setup lang="ts">
const props = defineProps<{ accent?: 'blue' | 'green' }>()
const accentClasses = props.accent === 'green'
  ? 'bg-green-100 text-green-700'
  : 'bg-blue-100 text-blue-700'

const authStore = useAuthStore()
const open = ref(false)
const root = ref<HTMLElement | null>(null)

function toggle() {
  open.value = !open.value
}

function handleClickOutside(e: MouseEvent) {
  if (open.value && root.value && !root.value.contains(e.target as Node)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div ref="root" class="relative">
    <button
      @click="toggle"
      class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold transition-opacity hover:opacity-80"
      :class="accentClasses"
    >
      {{ authStore.user?.name?.charAt(0).toUpperCase() }}
    </button>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-64 bg-white rounded-2xl border border-gray-100 shadow-lg z-50 overflow-hidden"
    >
      <div class="px-4 py-3 border-b border-gray-100">
        <p class="text-sm font-medium text-gray-900 truncate">{{ authStore.user?.name }}</p>
        <p class="text-xs text-gray-400 truncate">{{ authStore.user?.email }}</p>
      </div>
      <NuxtLink
        to="/profile"
        @click="open = false"
        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-500 hover:bg-gray-50 transition-colors"
      >
        <AppIcon name="settings" /> Settings
      </NuxtLink>
      <button
        @click="authStore.logout()"
        class="w-full text-left flex items-center px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors"
      >
        Logout
      </button>
    </div>
  </div>
</template>
