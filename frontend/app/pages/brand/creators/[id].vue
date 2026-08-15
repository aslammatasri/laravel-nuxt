<script setup lang="ts">
definePageMeta({
  middleware: 'brand',
  layout:     'brand',
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
}

interface CreatorPortfolio {
  id: number
  name: string
  email: string
  social_accounts: SocialAccount[]
}

const { $api } = useApi()
const route    = useRoute()

const { data: creator } = await useAsyncData(
  `brand-creator-portfolio-${route.params.id}`,
  () => $api<CreatorPortfolio>(`/brand/creators/${route.params.id}/portfolio`).catch(() => null)
)

const youtube = computed(() => creator.value?.social_accounts?.find(a => a.platform === 'youtube') || null)

function avatarColor(name: string): string {
  const colors = [
    'bg-red-400', 'bg-blue-400', 'bg-green-400', 'bg-yellow-400',
    'bg-purple-400', 'bg-pink-400', 'bg-indigo-400', 'bg-teal-400',
  ]
  let hash = 0
  for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash)
  return colors[Math.abs(hash) % colors.length]
}

function formatNumber(n: number | null): string {
  if (n === null || n === undefined) return '—'
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M'
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K'
  return String(n)
}
</script>

<template>
  <div>

    <!-- Not found -->
    <div v-if="!creator" class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400">
      <div class="w-14 h-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
        <AppIcon name="search" class="text-gray-400" />
      </div>
      <p class="font-medium text-gray-600">Creator not found</p>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="flex items-center gap-4 mb-8">
        <div
          class="w-14 h-14 rounded-full flex items-center justify-center text-white text-xl font-bold shrink-0"
          :class="avatarColor(creator.name)"
        >
          {{ creator.name.charAt(0).toUpperCase() }}
        </div>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ creator.name }}</h1>
          <p class="text-gray-500">{{ creator.email }}</p>
        </div>
      </div>

      <!-- No connected accounts -->
      <div v-if="!youtube" class="bg-white rounded-2xl border border-gray-100 px-6 py-16 text-center text-gray-400">
        <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center mx-auto mb-4">
          <AppIcon name="video" class="text-blue-500" />
        </div>
        <p class="font-medium text-gray-600">No social accounts connected yet</p>
        <p class="text-sm mt-1">This creator hasn't linked their YouTube channel</p>
      </div>

      <!-- YouTube stats -->
      <div v-else class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-center gap-3 mb-6">
          <img
            v-if="youtube.thumbnail_url"
            :src="youtube.thumbnail_url"
            class="w-12 h-12 rounded-full"
            alt=""
          />
          <div>
            <h3 class="font-semibold text-gray-900">{{ youtube.title || youtube.handle }}</h3>
            <p class="text-xs text-gray-400">YouTube</p>
          </div>
        </div>

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
    </template>

  </div>
</template>
