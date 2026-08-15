<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const authStore = useAuth()
const { $api }  = useApi()

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
  if (p.length < 8) return { level: 0, label: 'Too short', color: '#ef4444' }
  let score = 0
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  if (score <= 1) return { level: 1, label: 'Weak', color: '#ef4444' }
  if (score === 2) return { level: 2, label: 'Fair', color: '#f59e0b' }
  if (score === 3) return { level: 3, label: 'Good', color: '#10b981' }
  return { level: 4, label: 'Strong', color: '#059669' }
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
</script>

<template>
  <div class="sp">

    <div class="sp-header">
      <NuxtLink
        :to="authStore.isBrand ? '/brand/dashboard' : '/creator/dashboard'"
        class="sp-back"
      >
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
        Back to dashboard
      </NuxtLink>
      <div>
        <h1 class="sp-title">Account Settings</h1>
        <p class="sp-sub">Manage your profile and security preferences</p>
      </div>
    </div>

    <!-- Profile -->
    <div class="sp-card">
      <div class="sp-card-header">
        <h2 class="sp-card-title">Profile</h2>
      </div>
      <div class="sp-card-body">

        <div class="sp-profile-banner">
          <div class="sp-avatar-wrap">
            <div class="sp-avatar" :class="authStore.isBrand ? 'sp-avatar-brand' : ''">
              {{ authStore.user?.name?.charAt(0).toUpperCase() || '?' }}
            </div>
          </div>
          <div class="sp-profile-info">
            <p class="sp-profile-name">{{ authStore.user?.name }}</p>
            <p class="sp-profile-email">{{ authStore.user?.email }}</p>
            <span class="sp-profile-role" :class="authStore.isBrand ? 'sp-role-brand' : 'sp-role-creator'">
              {{ authStore.isBrand ? 'Brand account' : 'Creator account' }}
            </span>
          </div>
        </div>

        <div class="sp-separator"></div>

        <Transition name="sp-fade">
          <div v-if="nameSuccess" class="sp-success">{{ nameSuccess }}</div>
        </Transition>
        <Transition name="sp-fade">
          <div v-if="nameError" class="sp-error">{{ nameError }}</div>
        </Transition>

        <div class="sp-field">
          <label class="sp-label">Full name</label>
          <input
            v-model="nameForm.name"
            type="text"
            placeholder="Your name"
            class="sp-input"
            @input="nameSuccess = ''; nameError = ''"
          />
        </div>

        <div class="sp-actions">
          <button
            @click="handleUpdateName"
            :disabled="nameLoading || !nameForm.name.trim()"
            class="sp-btn sp-btn-primary"
          >
            <svg v-if="!nameLoading" class="sp-btn-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <span v-if="nameLoading" class="sp-spinner"></span>
            <span v-else>Save changes</span>
          </button>
          <span class="sp-char-count">{{ nameForm.name.length }} characters</span>
        </div>

      </div>
    </div>

    <!-- Password -->
    <div class="sp-card">
      <div class="sp-card-header">
        <h2 class="sp-card-title">Password</h2>
      </div>
      <div class="sp-card-body">

        <Transition name="sp-fade">
          <div v-if="passSuccess" class="sp-success">{{ passSuccess }}</div>
        </Transition>
        <Transition name="sp-fade">
          <div v-if="passError" class="sp-error">{{ passError }}</div>
        </Transition>

        <div class="sp-field">
          <label class="sp-label">Current password</label>
          <div class="sp-input-wrap">
            <input
              v-model="passwordForm.current_password"
              :type="showCurrent ? 'text' : 'password'"
              placeholder="Enter current password"
              class="sp-input sp-input-icon-right"
              @input="passSuccess = ''; passError = ''"
            />
            <button type="button" class="sp-toggle" @click="showCurrent = !showCurrent" tabindex="-1">
              <svg v-if="!showCurrent" class="sp-toggle-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
              <svg v-else class="sp-toggle-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/></svg>
            </button>
          </div>
        </div>

        <div class="sp-field-row">
          <div class="sp-field">
            <label class="sp-label">New password</label>
            <div class="sp-input-wrap">
              <input
                v-model="passwordForm.password"
                :type="showNew ? 'text' : 'password'"
                placeholder="Min 8 characters"
                class="sp-input sp-input-icon-right"
                @input="passSuccess = ''; passError = ''"
              />
              <button type="button" class="sp-toggle" @click="showNew = !showNew" tabindex="-1">
                <svg v-if="!showNew" class="sp-toggle-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                <svg v-else class="sp-toggle-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/></svg>
              </button>
            </div>
          </div>

          <div class="sp-field">
            <label class="sp-label">Confirm new password</label>
            <div class="sp-input-wrap">
              <input
                v-model="passwordForm.password_confirmation"
                :type="showConfirm ? 'text' : 'password'"
                placeholder="Repeat new password"
                class="sp-input sp-input-icon-right"
                @input="passSuccess = ''; passError = ''"
              />
              <button type="button" class="sp-toggle" @click="showConfirm = !showConfirm" tabindex="-1">
                <svg v-if="!showConfirm" class="sp-toggle-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/></svg>
                <svg v-else class="sp-toggle-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/><path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Password strength -->
        <div v-if="passwordStrength" class="sp-strength">
          <div class="sp-strength-bar">
            <div class="sp-strength-fill" :style="{ width: `${(passwordStrength.level / 4) * 100}%`, background: passwordStrength.color }"></div>
          </div>
          <span class="sp-strength-label" :style="{ color: passwordStrength.color }">{{ passwordStrength.label }}</span>
        </div>

        <div class="sp-actions">
          <button @click="handleUpdatePassword" :disabled="passLoading" class="sp-btn sp-btn-primary">
            <svg v-if="!passLoading" class="sp-btn-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
            <span v-if="passLoading" class="sp-spinner"></span>
            <span v-else>Update password</span>
          </button>
        </div>

      </div>
    </div>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

.sp {
  font-family: 'Inter', sans-serif;
  max-width: 680px;
  margin: 0 auto;
}

/* ── Header ─────────────────────────────────────────────────── */
.sp-header {
  margin-bottom: 2rem;
}

.sp-back {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: #64748b;
  text-decoration: none;
  margin-bottom: 0.75rem;
  transition: color 0.15s;
}

.sp-back:hover {
  color: #0f172a;
}

.sp-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.sp-sub {
  font-size: 0.875rem;
  color: #64748b;
  margin-top: 0.3rem;
}

/* ── Cards ──────────────────────────────────────────────────── */
.sp-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 1.25rem;
  overflow: hidden;
}

.sp-card-header {
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.sp-card-title {
  font-size: 0.9rem;
  font-weight: 600;
  color: #0f172a;
  letter-spacing: -0.01em;
}

.sp-card-body {
  padding: 1.5rem 1.75rem;
}

/* ── Profile banner ──────────────────────────────────────────── */
.sp-profile-banner {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  margin-bottom: 1.25rem;
}

.sp-avatar-wrap {
  flex-shrink: 0;
}

.sp-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f43f5e, #fb7185);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  font-weight: 700;
  box-shadow: 0 0 0 3px #fff, 0 0 0 5px rgba(244,63,94,0.15);
}

.sp-avatar-brand {
  background: linear-gradient(135deg, #1e293b, #334155);
  box-shadow: 0 0 0 3px #fff, 0 0 0 5px rgba(30,41,59,0.15);
}

.sp-profile-info {
  flex: 1;
  min-width: 0;
}

.sp-profile-name {
  font-size: 1rem;
  font-weight: 600;
  color: #0f172a;
  line-height: 1.3;
}

.sp-profile-email {
  font-size: 0.825rem;
  color: #94a3b8;
  margin-top: 0.15rem;
  line-height: 1.3;
}

.sp-profile-role {
  display: inline-block;
  margin-top: 0.5rem;
  font-size: 0.725rem;
  font-weight: 600;
  padding: 0.2rem 0.65rem;
  border-radius: 6px;
}

.sp-role-creator {
  color: #e11d48;
  background: #fff1f2;
}

.sp-role-brand {
  color: #475569;
  background: #f1f5f9;
}

/* ── Separator ───────────────────────────────────────────────── */
.sp-separator {
  height: 1px;
  background: #f1f5f9;
  margin-bottom: 1.25rem;
}

/* ── Fields ──────────────────────────────────────────────────── */
.sp-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex: 1;
}

.sp-field-row {
  display: flex;
  gap: 1rem;
}

.sp-label {
  font-size: 0.825rem;
  font-weight: 600;
  color: #334155;
}

.sp-input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.sp-input {
  width: 100%;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  font-size: 0.875rem;
  font-family: 'Inter', sans-serif;
  color: #0f172a;
  background: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
  outline: none;
  box-sizing: border-box;
}

.sp-input-icon-right {
  padding-right: 2.5rem;
}

.sp-input:focus {
  border-color: #0f172a;
  box-shadow: 0 0 0 3px rgba(15,23,42,0.06);
}

.sp-input::placeholder {
  color: #cbd5e1;
}

.sp-toggle {
  position: absolute;
  right: 0.5rem;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.3rem;
  border-radius: 6px;
  color: #94a3b8;
  transition: color 0.15s, background 0.15s;
  display: flex;
}

.sp-toggle:hover {
  color: #475569;
  background: #f1f5f9;
}

.sp-toggle-icon {
  width: 18px;
  height: 18px;
}

/* ── Strength bar ────────────────────────────────────────────── */
.sp-strength {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.25rem;
}

.sp-strength-bar {
  flex: 1;
  height: 4px;
  background: #f1f5f9;
  border-radius: 2px;
  overflow: hidden;
}

.sp-strength-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.4s ease, background 0.4s ease;
}

.sp-strength-label {
  font-size: 0.75rem;
  font-weight: 600;
  flex-shrink: 0;
}

/* ── Actions ──────────────────────────────────────────────────── */
.sp-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.sp-char-count {
  font-size: 0.775rem;
  color: #94a3b8;
}

/* ── Buttons ──────────────────────────────────────────────────── */
.sp-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  font-weight: 600;
  font-size: 0.85rem;
  font-family: 'Inter', sans-serif;
  padding: 0.55rem 1.1rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s, opacity 0.15s, box-shadow 0.15s;
}

.sp-btn-primary {
  background: #0f172a;
  color: #fff;
}

.sp-btn-primary:hover:not(:disabled) {
  background: #1e293b;
  box-shadow: 0 2px 8px rgba(15,23,42,0.15);
}

.sp-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.sp-btn-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

/* ── Feedback ─────────────────────────────────────────────────── */
.sp-success {
  font-size: 0.825rem;
  font-weight: 500;
  color: #059669;
  background: #ecfdf5;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

.sp-error {
  font-size: 0.825rem;
  font-weight: 500;
  color: #dc2626;
  background: #fef2f2;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  margin-bottom: 1rem;
}

/* ── Transitions ──────────────────────────────────────────────── */
.sp-fade-enter-active,
.sp-fade-leave-active {
  transition: all 0.25s ease;
}

.sp-fade-enter-from,
.sp-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

/* ── Spinner ──────────────────────────────────────────────────── */
.sp-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spSpin 0.6s linear infinite;
}

@keyframes spSpin {
  to { transform: rotate(360deg); }
}

/* ── Responsive ───────────────────────────────────────────────── */
@media (max-width: 640px) {
  .sp-profile-banner {
    flex-direction: column;
    align-items: flex-start;
  }

  .sp-field-row {
    flex-direction: column;
    gap: 1rem;
  }

  .sp-card-body {
    padding: 1.25rem 1.25rem;
  }

  .sp-card-header {
    padding: 1rem 1.25rem;
  }
}
</style>
