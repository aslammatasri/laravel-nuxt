import { defineStore } from 'pinia'
import { useApi } from '~/composables/useApi'

interface User {
  id: number
  name: string
  email: string
  role: 'creator' | 'brand' | 'admin'
}

export const useAuthStore = defineStore('auth', () => {
  // ── State ──────────────────────────────────────────────────────
  const user         = ref<User | null>(null)
  const token        = useCookie('auth_token')
  const isLoading    = ref(false)
  const error        = ref<string | null>(null)

  // ── Getters ────────────────────────────────────────────────────
  const isLoggedIn   = computed(() => !!token.value)
  const isCreator    = computed(() => user.value?.role === 'creator')
  const isBrand      = computed(() => user.value?.role === 'brand')
  const isAdmin      = computed(() => user.value?.role === 'admin')

  // ── Actions ────────────────────────────────────────────────────
  async function register(payload: {
    name: string
    email: string
    password: string
    password_confirmation: string
    role: 'creator' | 'brand'
  }) {
    isLoading.value = true
    error.value     = null

    try {
      const { $api } = useApi()
      const response  = await $api<{ user: User; token: string }>('/auth/register', {
        method: 'POST',
        body: payload,
      })

      token.value = response.token
      user.value  = response.user

      // Redirect based on role
      await navigateTo(response.user.role === 'brand' ? '/brand/dashboard' : '/creator/dashboard')
    } catch (err: any) {
      error.value = err?.data?.message || 'Registration failed'
    } finally {
      isLoading.value = false
    }
  }

  async function login(payload: { email: string; password: string }) {
    isLoading.value = true
    error.value     = null

    try {
      const { $api } = useApi()
      const response  = await $api<{ user: User; token: string }>('/auth/login', {
        method: 'POST',
        body: payload,
      })

      token.value = response.token
      user.value  = response.user

      // Redirect based on role
      await navigateTo(response.user.role === 'brand' ? '/brand/dashboard' : '/creator/dashboard')
    } catch (err: any) {
      error.value = err?.data?.message || 'Invalid credentials'
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      const { $api } = useApi()
      await $api('/auth/logout', { method: 'POST' })
    } catch {}  finally {
      token.value = null
      user.value  = null
      await navigateTo('/auth/login')
    }
  }

  async function fetchUser() {
    if (!token.value) return

    try {
      const { $api } = useApi()
      user.value      = await $api<User>('/auth/me')
    } catch {
      token.value = null
      user.value  = null
    }
  }

  return {
    user, token, isLoading, error,
    isLoggedIn, isCreator, isBrand, isAdmin,
    register, login, logout, fetchUser,
  }
})