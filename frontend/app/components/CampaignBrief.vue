<script setup lang="ts">
import type { CampaignExtras } from '~/composables/useCampaignForm'

const props = defineProps<{ form: CampaignExtras; errors: Record<string, string> }>()

function toggle(key: 'platforms' | 'content_types' | 'target_niches', value: string) {
  const list = props.form[key]
  // Replace the array so parent watchers see the change
  props.form[key] = list.includes(value) ? list.filter((v) => v !== value) : [...list, value]
  delete props.errors[key]
}

const input = 'w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500'
const invalid = 'border-red-300! focus:ring-red-400!'
</script>

<template>
  <div class="space-y-5">
    <!-- What the creator must do -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
      <div>
        <h2 class="font-semibold text-gray-900">Campaign brief</h2>
        <p class="text-sm text-gray-400 mt-0.5">What creators will do. Clear briefs get better applications.</p>
      </div>

      <div>
        <p class="block text-sm font-medium text-gray-700 mb-2">Platforms</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="p in PLATFORMS"
            :key="p.value"
            type="button"
            :aria-pressed="form.platforms.includes(p.value)"
            class="px-4 py-2 rounded-xl text-sm font-medium border transition-colors"
            :class="form.platforms.includes(p.value) ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            @click="toggle('platforms', p.value)"
          >
            {{ p.label }}
          </button>
        </div>
        <p v-if="errors.platforms" class="text-xs text-red-500 mt-1">{{ errors.platforms }}</p>
      </div>

      <div>
        <p class="block text-sm font-medium text-gray-700 mb-2">Content type</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="c in CONTENT_TYPES"
            :key="c.value"
            type="button"
            :aria-pressed="form.content_types.includes(c.value)"
            class="px-3.5 py-1.5 rounded-full text-sm font-medium border transition-colors"
            :class="form.content_types.includes(c.value) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            @click="toggle('content_types', c.value)"
          >
            {{ c.label }}
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="posts_required">Posts per creator</label>
          <input id="posts_required" v-model="form.posts_required" type="number" min="1" max="20" :aria-invalid="errors.posts_required ? 'true' : undefined" :class="[input, { [invalid]: errors.posts_required }]" />
          <p v-if="errors.posts_required" class="text-xs text-red-500 mt-1">{{ errors.posts_required }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1" for="hashtags">
            Hashtags <span class="text-gray-400 font-normal">(optional)</span>
          </label>
          <input id="hashtags" v-model="form.hashtags" type="text" maxlength="255" placeholder="#skintific #spf50" :class="input" />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="brief">
          Key messages, do's and don'ts <span class="text-gray-400 font-normal">(optional)</span>
        </label>
        <textarea id="brief" v-model="form.brief" rows="5" maxlength="3000" placeholder="e.g. Show the texture on camera. Mention SPF50 and no white cast. Do not compare with other brands." :aria-invalid="errors.brief ? 'true' : undefined" :class="[input, 'resize-none', { [invalid]: errors.brief }]" />
        <p class="text-xs text-gray-400 mt-1 text-right">{{ form.brief.length }}/3000</p>
        <p v-if="errors.brief" class="text-xs text-red-500">{{ errors.brief }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="product_url">
          Product link <span class="text-gray-400 font-normal">(optional)</span>
        </label>
        <input id="product_url" v-model="form.product_url" type="url" maxlength="500" placeholder="https://shop.example.com/product" :aria-invalid="errors.product_url ? 'true' : undefined" :class="[input, { [invalid]: errors.product_url }]" />
        <p v-if="errors.product_url" class="text-xs text-red-500 mt-1">{{ errors.product_url }}</p>
      </div>
    </div>

    <!-- Who can apply -->
    <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
      <div>
        <h2 class="font-semibold text-gray-900">Creator requirements</h2>
        <p class="text-sm text-gray-400 mt-0.5">Creators see these before they apply. Leave empty to welcome everyone.</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="min_subscribers">Minimum subscribers / followers</label>
        <input id="min_subscribers" v-model="form.min_subscribers" type="number" min="0" placeholder="No minimum" :aria-invalid="errors.min_subscribers ? 'true' : undefined" :class="[input, { [invalid]: errors.min_subscribers }]" />
        <p v-if="errors.min_subscribers" class="text-xs text-red-500 mt-1">{{ errors.min_subscribers }}</p>
      </div>

      <div>
        <p class="block text-sm font-medium text-gray-700 mb-2">Creator niches</p>
        <div class="flex flex-wrap gap-2">
          <button
            v-for="n in NICHES"
            :key="n"
            type="button"
            :aria-pressed="form.target_niches.includes(n)"
            class="px-3.5 py-1.5 rounded-full text-sm font-medium border capitalize transition-colors"
            :class="form.target_niches.includes(n) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
            @click="toggle('target_niches', n)"
          >
            {{ n }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
