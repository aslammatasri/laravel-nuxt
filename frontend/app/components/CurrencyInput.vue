<script setup lang="ts">
// Money field shown as 2,000 or 2,000.50. v-model is a plain numeric string ("2000.5") or ''.
defineOptions({ inheritAttrs: false })

const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    prefix?: string
    invalid?: boolean
    compact?: boolean
  }>(),
  { prefix: 'RM', compact: false },
)
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const MAX_INT_DIGITS = 8

const input = ref<HTMLInputElement | null>(null)
const text = ref('')

function rawToDisplay(raw: string) {
  if (!raw) return ''
  const [int = '', dec] = raw.split('.')
  const grouped = int.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
  return dec === undefined ? grouped : `${grouped}.${dec}`
}

function normalise(value: string) {
  let v = value.replace(/[^\d.]/g, '')
  const firstDot = v.indexOf('.')
  if (firstDot !== -1) v = v.slice(0, firstDot + 1) + v.slice(firstDot + 1).replace(/\./g, '')
  let [int = '', dec] = v.split('.')
  int = int.replace(/^0+(?=\d)/, '').slice(0, MAX_INT_DIGITS)
  if (dec !== undefined) dec = dec.slice(0, 2)
  if (firstDot === 0 && int === '') int = '0'
  return dec === undefined ? int : `${int}.${dec}`
}

watch(
  () => props.modelValue,
  (v) => {
    const raw = v === null || v === undefined || v === '' ? '' : String(v)
    if (normalise(text.value) !== raw) {
      const n = raw && !isNaN(Number(raw)) ? raw : ''
      text.value = rawToDisplay(n)
    }
  },
  { immediate: true },
)

function onInput(e: Event) {
  const el = e.target as HTMLInputElement
  const before = el.value
  const caret = el.selectionStart ?? before.length
  // How many number characters sit left of the caret (commas don't count)
  const sigBefore = before.slice(0, caret).replace(/[^\d.]/g, '').length

  const raw = normalise(before)
  const display = rawToDisplay(raw)
  text.value = display
  el.value = display

  // Put the caret back after the same number of significant characters
  let seen = 0
  let pos = 0
  while (pos < display.length && seen < sigBefore) {
    if (/[\d.]/.test(display[pos]!)) seen++
    pos++
  }
  el.setSelectionRange(pos, pos)

  emit('update:modelValue', raw.endsWith('.') ? raw.slice(0, -1) : raw)
}

function onBlur() {
  const raw = normalise(text.value)
  if (!raw) return
  const [int = '', dec] = raw.split('.')
  const padded = dec === undefined ? int : `${int}.${dec.padEnd(2, '0')}`
  const final = dec === '' ? int : padded
  text.value = rawToDisplay(final)
  emit('update:modelValue', final)
}
</script>

<template>
  <div class="relative">
    <span
      class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
      :class="compact ? 'text-sm' : 'text-sm'"
    >{{ prefix }}</span>
    <input
      v-bind="$attrs"
      ref="input"
      :value="text"
      type="text"
      inputmode="decimal"
      placeholder="0.00"
      autocomplete="off"
      :aria-invalid="invalid ? 'true' : undefined"
      class="w-full border rounded-xl pr-4 text-sm focus:outline-none focus:ring-2"
      :class="[
        compact ? 'pl-9 py-2' : 'pl-11 py-3',
        invalid ? 'border-red-300 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-500',
      ]"
      @input="onInput"
      @blur="onBlur"
    />
  </div>
</template>
