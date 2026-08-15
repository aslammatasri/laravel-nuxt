<script setup lang="ts">
definePageMeta({
  middleware: 'creator',
  layout:     'creator',
})

interface SocialAccount {
  id: number
  platform: string
  handle: string
  title: string | null
  thumbnail_url: string | null
  subscriber_count: number | null
  view_count: number | null
  video_count: number | null
  avg_recent_views: number | null
  avg_recent_likes: number | null
  avg_recent_comments: number | null
  engagement_rate: number | null
  last_synced_at: string | null
  sync_error: string | null
}

const { $api } = useApi()

const { data: accounts, refresh } = await useAsyncData(
  'creator-social-accounts',
  () => $api<{ data: SocialAccount[] }>('/creator/social-accounts').catch(() => null)
)

const youtube = computed(() => accounts.value?.data?.find(a => a.platform === 'youtube') || null)

const handleInput = ref('')
const connecting   = ref(false)
const syncing      = ref(false)
const errorMessage = ref('')

async function connect() {
  if (!handleInput.value.trim()) return
  connecting.value = true
  errorMessage.value = ''
  try {
    await $api('/creator/social-accounts/youtube', {
      method: 'POST',
      body: { handle: handleInput.value.trim() },
    })
    handleInput.value = ''
    await refresh()
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Could not connect that channel'
  } finally {
    connecting.value = false
  }
}

async function sync() {
  if (!youtube.value) return
  syncing.value = true
  errorMessage.value = ''
  try {
    await $api(`/creator/social-accounts/${youtube.value.id}/sync`, { method: 'POST' })
    await refresh()
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Could not refresh stats'
  } finally {
    syncing.value = false
  }
}

const confirmingDisconnect = ref(false)
const disconnecting        = ref(false)

async function disconnect() {
  if (!youtube.value) return
  disconnecting.value = true
  errorMessage.value = ''
  try {
    await $api(`/creator/social-accounts/${youtube.value.id}`, { method: 'DELETE' })
    confirmingDisconnect.value = false
    await refresh()
  } catch (err: any) {
    errorMessage.value = err?.data?.message || 'Could not disconnect channel'
  } finally {
    disconnecting.value = false
  }
}

function formatNumber(n: number | null): string {
  if (n === null || n === undefined) return '—'
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return d.toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric' }) + ', ' +
    d.toLocaleTimeString('en-MY', { hour: '2-digit', minute: '2-digit' })
}
</script>

<template>
  <div>

    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Portfolio</h1>
      <p class="text-gray-500 mt-1">Connect your YouTube channel so brands can see your reach</p>
    </div>

    <!-- Connect form -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 mb-6">
      <h2 class="font-semibold text-gray-900 mb-1">YouTube</h2>
      <p class="text-sm text-gray-400 mb-4">Enter your channel handle (e.g. @mkbhd)</p>

      <div class="flex gap-3">
        <input
          v-model="handleInput"
          type="text"
          placeholder="@yourchannel"
          class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
          @keyup.enter="connect"
        />
        <button
          @click="connect"
          :disabled="connecting || !handleInput.trim()"
          class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-colors shrink-0"
        >
          {{ connecting ? 'Connecting…' : youtube ? 'Reconnect' : 'Connect' }}
        </button>
      </div>

      <p v-if="errorMessage" class="text-sm text-red-500 mt-3">{{ errorMessage }}</p>
    </div>

    <!-- Empty state -->
    <div
      v-if="!youtube"
      class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400"
    >
      <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center mx-auto mb-4">
        <AppIcon name="video" class="text-green-500" />
      </div>
      <p class="font-medium text-gray-600">No channel connected yet</p>
      <p class="text-sm mt-1">Connect your YouTube channel above to build your portfolio</p>
    </div>

    <!-- Stats card -->
    <div v-else class="bg-white rounded-2xl border border-gray-100 p-6">
      <div class="flex items-start justify-between mb-6">
        <div class="flex items-center gap-3">
          <img
            v-if="youtube.thumbnail_url"
            :src="youtube.thumbnail_url"
            class="w-12 h-12 rounded-full"
            alt=""
          />
          <div>
            <h3 class="font-semibold text-gray-900">{{ youtube.title || youtube.handle }}</h3>
            <p class="text-xs text-gray-400">Last synced {{ formatDate(youtube.last_synced_at) }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
          <button
            @click="sync"
            :disabled="syncing"
            class="border border-gray-200 text-gray-600 hover:bg-gray-50 px-4 py-2 rounded-xl text-xs font-medium transition-colors"
          >
            {{ syncing ? 'Refreshing…' : 'Refresh' }}
          </button>
          <button
            v-if="!confirmingDisconnect"
            @click="confirmingDisconnect = true"
            class="border border-red-200 text-red-500 hover:bg-red-50 px-4 py-2 rounded-xl text-xs font-medium transition-colors"
          >
            Disconnect
          </button>
          <template v-else>
            <span class="text-xs text-gray-500">Disconnect this channel?</span>
            <button
              @click="disconnect"
              :disabled="disconnecting"
              class="bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white px-3 py-2 rounded-xl text-xs font-medium transition-colors"
            >
              {{ disconnecting ? 'Disconnecting…' : 'Yes, Disconnect' }}
            </button>
            <button
              @click="confirmingDisconnect = false"
              class="text-gray-400 hover:text-gray-600 text-xs px-2 py-2"
            >
              Cancel
            </button>
          </template>
        </div>
      </div>

      <p v-if="youtube.sync_error" class="text-sm text-red-500 mb-4 bg-red-50 rounded-lg p-3">
        {{ youtube.sync_error }}
      </p>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Subscribers</p>
          <p class="text-lg font-bold text-gray-900">{{ formatNumber(youtube.subscriber_count) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Total Views</p>
          <p class="text-lg font-bold text-gray-900">{{ formatNumber(youtube.view_count) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Videos</p>
          <p class="text-lg font-bold text-gray-900">{{ formatNumber(youtube.video_count) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Engagement Rate</p>
          <p class="text-lg font-bold text-green-600">
            {{ youtube.engagement_rate !== null ? youtube.engagement_rate + '%' : '—' }}
          </p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Avg Views / Video</p>
          <p class="text-lg font-bold text-gray-900">{{ formatNumber(youtube.avg_recent_views) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Avg Likes / Video</p>
          <p class="text-lg font-bold text-gray-900">{{ formatNumber(youtube.avg_recent_likes) }}</p>
        </div>
        <div class="bg-gray-50 rounded-xl p-4">
          <p class="text-xs text-gray-400 mb-1">Avg Comments / Video</p>
          <p class="text-lg font-bold text-gray-900">{{ formatNumber(youtube.avg_recent_comments) }}</p>
        </div>
      </div>
    </div>

  </div>
</template>
