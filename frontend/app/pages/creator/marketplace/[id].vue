<script setup lang="ts">
definePageMeta({
  middleware: 'creator',
  layout:     'creator',
})

const { $api } = useApi()
const route    = useRoute()
const router   = useRouter()

const productId    = route.params.id
const pitch        = ref('')
const loading      = ref(false)
const error        = ref<string | null>(null)
const success      = ref(false)
const activeSlide  = ref(0)

function formatDate(dateStr: string | null): string {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' })
}

interface VariationOption { value: string; price: number }
interface Variation       { id: number; name: string; options: VariationOption[] }
interface Product { id: number; name: string; image: string | null; images: string[]; description: string; price: number; category: string; commission_rate: string; commission_type: string; campaign_end: string; max_affiliates: number; spots_left: number; brand: { name: string }; variations: Variation[] }

const config = useRuntimeConfig()

const { data: product } = await useAsyncData(`marketplace-product-${productId}`,
  () => $api<Product>(`/creator/marketplace/${productId}`).catch(() => null)
)

async function handleApply() {
  if (!pitch.value.trim()) {
    error.value = 'Please write a pitch message'
    return
  }

  loading.value = true
  error.value   = null

  try {
    await $api(`/creator/products/${productId}/apply`, {
      method: 'POST',
      body: { pitch_message: pitch.value },
    })
    success.value = true
  } catch (err: any) {
    error.value = err?.data?.message || 'Failed to submit application'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="max-w-3xl">

    <div class="mb-6">
      <NuxtLink to="/creator/marketplace" class="text-sm text-gray-400 hover:text-gray-600 block mb-2">
        ← Back to marketplace
      </NuxtLink>
    </div>

    <div v-if="product" class="space-y-6">

      <!-- Product details -->
      <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">

        <div class="relative bg-gray-100 h-64 flex items-center justify-center overflow-hidden select-none">
          <template v-if="product.images?.length">
            <img
              v-for="(img, i) in product.images"
              :key="img"
              :src="`${config.public.storageBase}/${img}`"
              :alt="product.name"
              class="absolute inset-0 w-full h-full object-cover transition-opacity duration-300"
              :class="i === activeSlide ? 'opacity-100' : 'opacity-0'"
            />
            <!-- Prev / Next -->
            <button v-if="product.images.length > 1" @click="activeSlide = activeSlide > 0 ? activeSlide - 1 : product.images.length - 1"
              class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full w-8 h-8 flex items-center justify-center shadow text-sm transition-colors">‹</button>
            <button v-if="product.images.length > 1" @click="activeSlide = activeSlide < product.images.length - 1 ? activeSlide + 1 : 0"
              class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white rounded-full w-8 h-8 flex items-center justify-center shadow text-sm transition-colors">›</button>
            <!-- Dots -->
            <div v-if="product.images.length > 1" class="absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1.5">
              <button v-for="(_, i) in product.images" :key="i" @click="activeSlide = i"
                class="w-2 h-2 rounded-full transition-all duration-200"
                :class="i === activeSlide ? 'bg-white w-4' : 'bg-white/50'" />
            </div>
          </template>
          <span v-else class="text-6xl">📦</span>
        </div>

        <div class="p-6">
          <div class="flex items-start justify-between mb-3">
            <div>
              <h1 class="text-xl font-bold text-gray-900">{{ product.name }}</h1>
              <p class="text-sm text-gray-400 capitalize mt-0.5">{{ product.category }}</p>
            </div>
            <span class="text-2xl font-bold text-green-600">
              {{ product.commission_rate }}{{ product.commission_type === 'percentage' ? '%' : ' RM' }}
            </span>
          </div>

          <p class="text-gray-600 text-sm leading-relaxed mb-6">{{ product.description }}</p>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400 mb-1">Price</p>
              <p class="font-semibold text-gray-900 text-sm">RM {{ product.price }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400 mb-1">Commission</p>
              <p class="font-semibold text-green-600 text-sm">
                {{ product.commission_rate }}{{ product.commission_type === 'percentage' ? '%' : ' RM' }}
              </p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400 mb-1">Campaign End</p>
              <p class="font-semibold text-gray-900 text-sm">{{ formatDate(product.campaign_end) }}</p>
            </div>
            <div class="bg-gray-50 rounded-xl p-3 text-center">
              <p class="text-xs text-gray-400 mb-1">Spots Left</p>
              <p class="font-semibold text-gray-900 text-sm">
                {{ product.spots_left ?? 'Unlimited' }}
              </p>
            </div>
          </div>

          <!-- Variations -->
          <div v-if="product.variations?.length" class="mt-6 pt-6 border-t border-gray-100">
            <h2 class="text-sm font-semibold text-gray-700 mb-3">Variations</h2>
            <div class="space-y-4">
              <div v-for="variation in product.variations" :key="variation.id">
                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-2">{{ variation.name }}</p>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="option in variation.options"
                    :key="option.value"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-700"
                  >
                    {{ option.value }}
                    <span class="text-gray-400 text-xs">· RM {{ option.price.toFixed(2) }}</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Apply form -->
      <div class="bg-white rounded-2xl border border-gray-100 p-6">

        <!-- Success state -->
        <div v-if="success" class="text-center py-8">
          <p class="text-5xl mb-4">🎉</p>
          <h3 class="text-lg font-bold text-gray-900 mb-2">Application Submitted!</h3>
          <p class="text-gray-500 text-sm mb-6">
            The brand will review your application and get back to you soon.
          </p>
          <div class="flex gap-3 justify-center">
            <NuxtLink
              to="/creator/applications"
              class="bg-green-500 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-green-600"
            >
              View My Applications
            </NuxtLink>
            <NuxtLink
              to="/creator/marketplace"
              class="border border-gray-200 text-gray-600 px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-50"
            >
              Browse More
            </NuxtLink>
          </div>
        </div>

        <!-- Apply form -->
        <div v-else>
          <h2 class="font-semibold text-gray-900 mb-1">Apply to Promote</h2>
          <p class="text-sm text-gray-400 mb-5">
            Tell the brand why you are the right creator for this product
          </p>

          <div v-if="error" class="bg-red-50 text-red-600 text-sm rounded-xl p-4 mb-5">
            {{ error }}
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Your Pitch Message
              <span class="text-gray-400 font-normal">(min 20 characters)</span>
            </label>
            <textarea
              v-model="pitch"
              rows="5"
              placeholder="e.g. I am a beauty creator with 50k followers mostly Malaysian females aged 18-25. I have promoted similar skincare products before and achieved great conversion rates. I would love to collaborate with your brand..."
              class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 resize-none"
            />
            <p class="text-xs text-gray-400 mt-1 text-right">{{ pitch.length }} characters</p>
          </div>

          <button
            @click="handleApply"
            :disabled="loading"
            class="w-full bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white font-medium rounded-xl py-3 text-sm transition-colors"
          >
            {{ loading ? 'Submitting...' : 'Submit Application' }}
          </button>
        </div>

      </div>
    </div>

  </div>
</template>