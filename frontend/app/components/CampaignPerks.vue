<script setup lang="ts">
import type { CampaignExtras } from '~/composables/useCampaignForm'

defineProps<{ form: CampaignExtras; errors: Record<string, string> }>()
</script>

<template>
  <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-5">
    <div>
      <h2 class="font-semibold text-gray-900">Perks</h2>
      <p class="text-sm text-gray-400 mt-0.5">What creators get besides commission.</p>
    </div>

    <div class="flex items-center justify-between gap-4">
      <div>
        <p class="text-sm font-medium text-gray-900">Free sample</p>
        <p class="text-xs text-gray-500 mt-0.5">You send the product to approved creators</p>
      </div>
      <button
        type="button"
        role="switch"
        :aria-checked="form.free_sample"
        aria-label="Free sample"
        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        :class="form.free_sample ? 'bg-blue-600' : 'bg-gray-200'"
        @click="form.free_sample = !form.free_sample"
      >
        <span
          class="inline-block h-5 w-5 rounded-full bg-white shadow transition-transform"
          :class="form.free_sample ? 'translate-x-5' : 'translate-x-0.5'"
        />
      </button>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-1">
        Flat fee per creator <span class="text-gray-400 font-normal">(optional)</span>
      </label>
      <CurrencyInput v-model="form.flat_fee" :invalid="!!errors.flat_fee" />
      <p v-if="errors.flat_fee" class="text-xs text-red-500 mt-1">{{ errors.flat_fee }}</p>
      <p v-else class="text-xs text-gray-400 mt-1">Paid once, on top of commission.</p>
    </div>
  </div>
</template>
