<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const authStore = useAuth()
const { $api }  = useApi()

setPageLayout(authStore.isBrand ? 'brand' : 'creator')

const nameForm = reactive({
  name: authStore.user?.name || '',
})

const passwordForm = reactive({
  current_password:      '',
  password:              '',
  password_confirmation: '',
})

const nameLoading   = ref(false)
const passLoading   = ref(false)
const nameSuccess   = ref('')
const passSuccess   = ref('')
const nameError     = ref('')
const passError     = ref('')
const showCurrent   = ref(false)
const showNew       = ref(false)
const showConfirm   = ref(false)

const passwordStrength = computed(() => {
  const p = passwordForm.password
  if (!p) return null
  if (p.length < 8) return { level: 0, label: 'Too short', barClass: 'bg-red-500', textClass: 'text-red-500' }
  let score = 0
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  if (score <= 1) return { level: 1, label: 'Weak', barClass: 'bg-red-500', textClass: 'text-red-500' }
  if (score === 2) return { level: 2, label: 'Fair', barClass: 'bg-amber-500', textClass: 'text-amber-500' }
  if (score === 3) return { level: 3, label: 'Good', barClass: 'bg-green-500', textClass: 'text-green-600' }
  return { level: 4, label: 'Strong', barClass: 'bg-green-600', textClass: 'text-green-700' }
})

async function handleUpdateName() {
  nameLoading.value  = true
  nameError.value    = ''
  nameSuccess.value  = ''
  try {
    const res = await $api<{ message: string; user: { name: string } }>('/profile/name', {
      method: 'PUT',
      body: { name: nameForm.name },
    })
    authStore.user!.name = res.user.name
    nameSuccess.value = 'Name updated successfully'
  } catch (err: any) {
    nameError.value = err?.data?.message || 'Failed to update name'
  } finally {
    nameLoading.value = false
  }
}

async function handleUpdatePassword() {
  passLoading.value  = true
  passError.value    = ''
  passSuccess.value  = ''
  try {
    await $api('/profile/password', {
      method: 'PUT',
      body: {
        current_password:      passwordForm.current_password,
        password:              passwordForm.password,
        password_confirmation: passwordForm.password_confirmation,
      },
    })
    passSuccess.value = 'Password updated successfully'
    passwordForm.current_password         = ''
    passwordForm.password                 = ''
    passwordForm.password_confirmation    = ''
  } catch (err: any) {
    passError.value = err?.data?.message || 'Failed to update password'
  } finally {
    passLoading.value = false
  }
}

const accent = computed(() => authStore.isBrand
  ? { text: 'text-blue-600', bg: 'bg-blue-100', ring: 'focus:ring-blue-400', btn: 'bg-blue-600 hover:bg-blue-700', chip: 'bg-blue-50 text-blue-700' }
  : { text: 'text-green-600', bg: 'bg-green-100', ring: 'focus:ring-green-400', btn: 'bg-green-500 hover:bg-green-600', chip: 'bg-green-50 text-green-700' }
)
</script>

<template>
  <div class="max-w-2xl mx-auto">

    <NuxtLink
      :to="authStore.isBrand ? '/brand/dashboard' : '/creator/dashboard'"
      class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-400 hover:text-gray-600 transition-colors mb-4"
    >
      <AppIcon name="dashboard" class="w-4 h-4" />
      Back to dashboard
    </NuxtLink>

    <h1 class="text-2xl font-bold text-gray-900">Account Settings</h1>
    <p class="text-gray-500 mt-1 mb-8">Manage your profile and security preferences</p>

    <!-- Profile -->
    <div class="bg-white rounded-2xl border border-gray-100 mb-6">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-900">Profile</h2>
      </div>

      <div class="p-6">
        <div class="flex items-center gap-4 pb-6 mb-6 border-b border-gray-100">
          <div
            class="w-14 h-14 rounded-full flex items-center justify-center text-lg font-bold shrink-0"
            :class="[accent.bg, accent.text]"
          >
            {{ authStore.user?.name?.charAt(0).toUpperCase() || '?' }}
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-gray-900 truncate">{{ authStore.user?.name }}</p>
            <p class="text-sm text-gray-400 truncate">{{ authStore.user?.email }}</p>
            <span class="inline-block mt-1.5 text-xs font-semibold px-2.5 py-0.5 rounded-full" :class="accent.chip">
              {{ authStore.isBrand ? 'Brand account' : 'Creator account' }}
            </span>
          </div>
        </div>

        <Transition name="fade">
          <p v-if="nameSuccess" class="text-sm font-medium text-green-700 bg-green-50 rounded-xl px-4 py-3 mb-4">
            {{ nameSuccess }}
          </p>
        </Transition>
        <Transition name="fade">
          <p v-if="nameError" class="text-sm font-medium text-red-600 bg-red-50 rounded-xl px-4 py-3 mb-4">
            {{ nameError }}
          </p>
        </Transition>

        <label class="block text-sm font-medium text-gray-700 mb-2">Full name</label>
        <input
          v-model="nameForm.name"
          type="text"
          placeholder="Your name"
          class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2"
          :class="accent.ring"
          @input="nameSuccess = ''; nameError = ''"
        />

        <div class="flex items-center gap-3 mt-4">
          <button
            @click="handleUpdateName"
            :disabled="nameLoading || !nameForm.name.trim()"
            class="inline-flex items-center gap-2 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            :class="accent.btn"
          >
            <span v-if="nameLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            <AppIcon v-else name="check" class="w-4 h-4" />
            {{ nameLoading ? 'Saving…' : 'Save changes' }}
          </button>
          <span class="text-xs text-gray-400">{{ nameForm.name.length }} characters</span>
        </div>
      </div>
    </div>

    <!-- Password -->
    <div class="bg-white rounded-2xl border border-gray-100">
      <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-900">Password</h2>
      </div>

      <div class="p-6">
        <Transition name="fade">
          <p v-if="passSuccess" class="text-sm font-medium text-green-700 bg-green-50 rounded-xl px-4 py-3 mb-4">
            {{ passSuccess }}
          </p>
        </Transition>
        <Transition name="fade">
          <p v-if="passError" class="text-sm font-medium text-red-600 bg-red-50 rounded-xl px-4 py-3 mb-4">
            {{ passError }}
          </p>
        </Transition>

        <label class="block text-sm font-medium text-gray-700 mb-2">Current password</label>
        <div class="relative mb-4">
          <input
            v-model="passwordForm.current_password"
            :type="showCurrent ? 'text' : 'password'"
            placeholder="Enter current password"
            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2"
            :class="accent.ring"
            @input="passSuccess = ''; passError = ''"
          />
          <button
            type="button"
            tabindex="-1"
            @click="showCurrent = !showCurrent"
            class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <AppIcon :name="showCurrent ? 'eye-slash' : 'eye'" class="w-4 h-4" />
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">New password</label>
            <div class="relative">
              <input
                v-model="passwordForm.password"
                :type="showNew ? 'text' : 'password'"
                placeholder="Min 8 characters"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2"
                :class="accent.ring"
                @input="passSuccess = ''; passError = ''"
              />
              <button
                type="button"
                tabindex="-1"
                @click="showNew = !showNew"
                class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <AppIcon :name="showNew ? 'eye-slash' : 'eye'" class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Confirm new password</label>
            <div class="relative">
              <input
                v-model="passwordForm.password_confirmation"
                :type="showConfirm ? 'text' : 'password'"
                placeholder="Repeat new password"
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2"
                :class="accent.ring"
                @input="passSuccess = ''; passError = ''"
              />
              <button
                type="button"
                tabindex="-1"
                @click="showConfirm = !showConfirm"
                class="absolute right-1 top-1/2 -translate-y-1/2 p-2 text-gray-400 hover:text-gray-600 transition-colors"
              >
                <AppIcon :name="showConfirm ? 'eye-slash' : 'eye'" class="w-4 h-4" />
              </button>
            </div>
          </div>
        </div>

        <div v-if="passwordStrength" class="flex items-center gap-3 mt-3">
          <div class="flex-1 h-1.5 bg-gray-100 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-all duration-300"
              :class="passwordStrength.barClass"
              :style="{ width: `${(passwordStrength.level / 4) * 100}%` }"
            />
          </div>
          <span class="text-xs font-semibold shrink-0" :class="passwordStrength.textClass">
            {{ passwordStrength.label }}
          </span>
        </div>

        <button
          @click="handleUpdatePassword"
          :disabled="passLoading"
          class="inline-flex items-center gap-2 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed mt-4"
          :class="accent.btn"
        >
          <span v-if="passLoading" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
          <AppIcon v-else name="lock" class="w-4 h-4" />
          {{ passLoading ? 'Updating…' : 'Update password' }}
        </button>
      </div>
    </div>

  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: all 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
