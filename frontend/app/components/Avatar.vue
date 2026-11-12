<script setup lang="ts">
// Profile photo with an initial-letter fallback.
// `src` is the stored path (e.g. "avatars/abc.jpg"), the same way product images are stored.
const props = withDefaults(
  defineProps<{
    src?: string | null
    name?: string | null
    size?: 'xxs' | 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    /** 'auto' picks a colour from the name; the rest match the role themes */
    tone?: 'auto' | 'blue' | 'green' | 'violet' | 'gray'
  }>(),
  { size: 'md', tone: 'auto' },
)

const config = useRuntimeConfig()
const failed = ref(false)
watch(() => props.src, () => (failed.value = false))

const url = computed(() =>
  props.src && !failed.value ? `${config.public.storageBase}/${props.src}` : null,
)
const initial = computed(() => (props.name || '?').trim().charAt(0).toUpperCase() || '?')

const sizes: Record<string, string> = {
  xxs: 'w-5 h-5 text-[10px]',
  xs: 'w-7 h-7 text-xs',
  sm: 'w-9 h-9 text-sm',
  md: 'w-11 h-11 text-sm',
  lg: 'w-14 h-14 text-xl',
  xl: 'w-24 h-24 text-3xl',
}

const palette = [
  'bg-red-400 text-white',
  'bg-blue-400 text-white',
  'bg-green-400 text-white',
  'bg-yellow-400 text-white',
  'bg-purple-400 text-white',
  'bg-pink-400 text-white',
  'bg-indigo-400 text-white',
  'bg-teal-400 text-white',
]
const tones: Record<string, string> = {
  blue: 'bg-blue-100 text-blue-700',
  green: 'bg-green-100 text-green-700',
  violet: 'bg-violet-100 text-violet-700',
  gray: 'bg-gray-100 text-gray-600',
}

const toneClass = computed(() => {
  if (props.tone !== 'auto') return tones[props.tone]
  let hash = 0
  const n = props.name || ''
  for (let i = 0; i < n.length; i++) hash = n.charCodeAt(i) + ((hash << 5) - hash)
  return palette[Math.abs(hash) % palette.length]
})
</script>

<template>
  <span
    class="inline-flex items-center justify-center rounded-full overflow-hidden shrink-0 font-bold select-none"
    :class="[sizes[size], url ? 'bg-gray-100' : toneClass]"
  >
    <img
      v-if="url"
      :src="url"
      :alt="name ? `${name}'s photo` : 'Profile photo'"
      class="w-full h-full object-cover"
      loading="lazy"
      @error="failed = true"
    />
    <span v-else aria-hidden="true">{{ initial }}</span>
  </span>
</template>
