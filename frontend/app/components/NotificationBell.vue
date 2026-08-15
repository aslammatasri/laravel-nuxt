<script setup lang="ts">
interface NotificationItem {
  id: string
  data: { title: string; message: string; url: string }
  read_at: string | null
  created_at: string
}

const { $api } = useApi()

const open          = ref(false)
const unreadCount    = ref(0)
const notifications  = ref<NotificationItem[]>([])
const loading        = ref(false)
let pollTimer: ReturnType<typeof setInterval> | null = null

async function fetchUnreadCount() {
  try {
    const res = await $api<{ count: number }>('/notifications/unread-count')
    unreadCount.value = res.count
  } catch {
    // ignore poll failures
  }
}

async function fetchNotifications() {
  loading.value = true
  try {
    const res = await $api<{ data: NotificationItem[] }>('/notifications')
    notifications.value = res.data
  } catch {
    notifications.value = []
  } finally {
    loading.value = false
  }
}

async function toggle() {
  open.value = !open.value
  if (open.value) await fetchNotifications()
}

async function selectNotification(item: NotificationItem) {
  if (!item.read_at) {
    try {
      await $api(`/notifications/${item.id}/read`, { method: 'PATCH' })
      item.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch {
      // ignore
    }
  }
  open.value = false
  if (item.data?.url) await navigateTo(item.data.url)
}

async function markAllAsRead() {
  try {
    await $api('/notifications/read-all', { method: 'PATCH' })
    notifications.value.forEach(n => { n.read_at = n.read_at || new Date().toISOString() })
    unreadCount.value = 0
  } catch {
    // ignore
  }
}

function formatRelative(dateStr: string): string {
  const diffMs = Date.now() - new Date(dateStr).getTime()
  const mins   = Math.floor(diffMs / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

onMounted(() => {
  fetchUnreadCount()
  pollTimer = setInterval(fetchUnreadCount, 30000)
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})
</script>

<template>
  <div class="relative">
    <button
      @click="toggle"
      class="relative w-9 h-9 flex items-center justify-center rounded-full text-gray-500 hover:bg-gray-50 hover:text-gray-900 transition-colors"
    >
      <AppIcon name="bell" />
      <span
        v-if="unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold"
      >
        {{ unreadCount > 9 ? '9+' : unreadCount }}
      </span>
    </button>

    <Teleport to="body">
      <div v-if="open" class="fixed inset-0 z-40" @click="open = false" />
    </Teleport>

    <div
      v-if="open"
      class="absolute right-0 mt-2 w-80 bg-white rounded-2xl border border-gray-100 shadow-lg z-50 overflow-hidden"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
        <span class="font-semibold text-gray-900 text-sm">Notifications</span>
        <button
          v-if="unreadCount > 0"
          @click="markAllAsRead"
          class="text-xs text-green-600 hover:underline font-medium"
        >
          Mark all as read
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto">
        <div v-if="loading" class="px-4 py-8 text-center text-sm text-gray-400">Loading…</div>

        <div v-else-if="!notifications.length" class="px-4 py-8 text-center text-sm text-gray-400">
          No notifications yet
        </div>

        <button
          v-for="item in notifications"
          :key="item.id"
          @click="selectNotification(item)"
          class="w-full flex items-start gap-2 text-left px-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50 transition-colors"
        >
          <span
            class="w-2 h-2 rounded-full mt-1.5 shrink-0"
            :class="item.read_at ? 'bg-transparent' : 'bg-green-500'"
          />
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900">{{ item.data.title }}</p>
            <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ item.data.message }}</p>
            <p class="text-[11px] text-gray-400 mt-1">{{ formatRelative(item.created_at) }}</p>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>
