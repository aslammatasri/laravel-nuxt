<script setup lang="ts">
// Date field that always shows dd/mm/yyyy. v-model is an ISO date (yyyy-mm-dd) or ''.
defineOptions({ inheritAttrs: false })

const props = defineProps<{ modelValue?: string | null; invalid?: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const text = ref('')
const picker = ref<HTMLInputElement | null>(null)

function isoToDisplay(v?: string | null) {
  const m = (v ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/)
  return m ? `${m[3]}/${m[2]}/${m[1]}` : ''
}

function displayToIso(v: string) {
  const m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/)
  if (!m) return ''
  const [, dd, mm, yyyy] = m
  const d = new Date(Number(yyyy), Number(mm) - 1, Number(dd))
  const valid =
    d.getFullYear() === Number(yyyy) && d.getMonth() === Number(mm) - 1 && d.getDate() === Number(dd)
  return valid ? `${yyyy}-${mm}-${dd}` : ''
}

watch(
  () => props.modelValue,
  (v) => {
    // Don't overwrite what the user is typing
    if (displayToIso(text.value) !== (v ?? '').slice(0, 10)) text.value = isoToDisplay(v)
  },
  { immediate: true },
)

function onInput(e: Event) {
  const digits = (e.target as HTMLInputElement).value.replace(/\D/g, '').slice(0, 8)
  let out = digits
  if (digits.length > 4) out = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`
  else if (digits.length > 2) out = `${digits.slice(0, 2)}/${digits.slice(2)}`
  text.value = out
  ;(e.target as HTMLInputElement).value = out
  emit('update:modelValue', displayToIso(out))
}

function openPicker() {
  const el = picker.value
  if (!el) return
  el.value = displayToIso(text.value)
  if (typeof el.showPicker === 'function') el.showPicker()
  else el.click()
}

function onPick(e: Event) {
  const iso = (e.target as HTMLInputElement).value
  text.value = isoToDisplay(iso)
  emit('update:modelValue', iso)
}
</script>

<template>
  <div class="relative">
    <input
      v-bind="$attrs"
      :value="text"
      type="text"
      inputmode="numeric"
      placeholder="dd/mm/yyyy"
      maxlength="10"
      autocomplete="off"
      :aria-invalid="invalid ? 'true' : undefined"
      class="w-full border rounded-xl pl-4 pr-11 py-3 text-sm focus:outline-none focus:ring-2"
      :class="invalid ? 'border-red-300 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-500'"
      @input="onInput"
    />
    <button
      type="button"
      class="absolute right-2 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-gray-700"
      aria-label="Open calendar"
      @click="openPicker"
    >
      <svg viewBox="0 0 20 20" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.6">
        <rect x="3" y="4.5" width="14" height="12" rx="2" />
        <path d="M3 8h14M7 3v3M13 3v3" stroke-linecap="round" />
      </svg>
    </button>
    <input
      ref="picker"
      type="date"
      tabindex="-1"
      aria-hidden="true"
      class="absolute right-3 bottom-0 w-0 h-0 opacity-0 pointer-events-none"
      @change="onPick"
    />
  </div>
</template>
