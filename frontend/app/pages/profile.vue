<script setup lang="ts">
definePageMeta({
  middleware: 'auth',
})

const authStore = useAuth()
const { $api }  = useApi()

setPageLayout(authStore.isAdmin ? 'admin' : authStore.isBrand ? 'brand' : 'creator')

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

const avatarLoading = ref(false)
const avatarError   = ref('')

async function handleAvatarChange(e: Event) {
  const input = e.target as HTMLInputElement
  const file  = input.files?.[0]
  input.value = ''
  if (!file) return

  avatarError.value = ''
  const { accepted, problems } = checkImageFiles([file])
  if (!accepted.length) {
    avatarError.value = problems[0] || 'That file cannot be used.'
    return
  }

  avatarLoading.value = true
  try {
    const body = new FormData()
    body.append('avatar', file)
    const res = await $api<{ user: { avatar: string | null } }>('/profile/avatar', { method: 'POST', body })
    authStore.user!.avatar = res.user.avatar
  } catch (err: any) {
    const parsed = parseApiError(err, 'Could not upload your photo.')
    avatarError.value = parsed.fields.avatar || parsed.message
  } finally {
    avatarLoading.value = false
  }
}

async function handleRemoveAvatar() {
  avatarLoading.value = true
  avatarError.value   = ''
  try {
    await $api('/profile/avatar', { method: 'DELETE' })
    authStore.user!.avatar = null
  } catch (err: any) {
    avatarError.value = parseApiError(err, 'Could not remove your photo.').message
  } finally {
    avatarLoading.value = false
  }
}

const nameUnchanged = computed(() => nameForm.name.trim() === (authStore.user?.name || ''))

const passwordStrength = computed(() => {
  const p = passwordForm.password
  if (!p) return null
  if (p.length < 8) return { level: 0, label: 'Too short', tone: 'bad' }
  let score = 0
  if (p.length >= 12) score++
  if (/[A-Z]/.test(p)) score++
  if (/[0-9]/.test(p)) score++
  if (/[^A-Za-z0-9]/.test(p)) score++
  if (score <= 1) return { level: 1, label: 'Weak', tone: 'bad' }
  if (score === 2) return { level: 2, label: 'Fair', tone: 'fair' }
  if (score === 3) return { level: 3, label: 'Good', tone: 'good' }
  return { level: 4, label: 'Strong', tone: 'strong' }
})

const passwordChecks = computed(() => {
  const p = passwordForm.password
  return [
    { label: '8+ characters', ok: p.length >= 8 },
    { label: '12+ characters', ok: p.length >= 12 },
    { label: 'Uppercase letter', ok: /[A-Z]/.test(p) },
    { label: 'Number', ok: /[0-9]/.test(p) },
    { label: 'Symbol', ok: /[^A-Za-z0-9]/.test(p) },
  ]
})

const matchState = computed(() => {
  if (!passwordForm.password_confirmation) return null
  return passwordForm.password === passwordForm.password_confirmation ? 'match' : 'mismatch'
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
  <div class="st-page" :data-role="authStore.isBrand ? 'brand' : 'creator'">

    <NuxtLink
      :to="authStore.isBrand ? '/brand/dashboard' : '/creator/dashboard'"
      class="st-back"
    >
      <AppIcon name="dashboard" class="st-back__icon" />
      Back to dashboard
    </NuxtLink>

    <header class="st-head">
      <h1 class="st-title">Account settings</h1>
      <p class="st-sub">Manage your profile and security preferences</p>
    </header>

    <div class="st-grid">

      <!-- Identity -->
      <aside class="st-side">
        <div class="st-id">
          <div class="st-avatar-wrap">
            <Avatar
              :src="authStore.user?.avatar"
              :name="authStore.user?.name"
              size="xl"
              :tone="authStore.isAdmin ? 'violet' : authStore.isBrand ? 'blue' : 'green'"
            />
          </div>
          <div class="st-avatar-actions">
            <label class="st-avatar-btn" :class="{ 'is-busy': avatarLoading }">
              {{ avatarLoading ? 'Uploading…' : authStore.user?.avatar ? 'Change photo' : 'Upload photo' }}
              <input
                type="file"
                accept="image/png,image/jpeg,image/webp"
                class="st-avatar-input"
                :disabled="avatarLoading"
                @change="handleAvatarChange"
              />
            </label>
            <button
              v-if="authStore.user?.avatar"
              type="button"
              class="st-avatar-remove"
              :disabled="avatarLoading"
              @click="handleRemoveAvatar"
            >
              Remove
            </button>
          </div>
          <p v-if="avatarError" class="st-avatar-error" role="alert">{{ avatarError }}</p>
          <p v-else class="st-avatar-hint">JPG, PNG or WEBP, up to 2 MB</p>
          <p class="st-id__name">{{ authStore.user?.name }}</p>
          <p class="st-id__email">{{ authStore.user?.email }}</p>
          <span class="st-chip">{{ authStore.isBrand ? 'Brand account' : 'Creator account' }}</span>
        </div>
      </aside>

      <div class="st-main">

        <!-- Profile -->
        <section class="st-card" aria-labelledby="st-profile">
          <header class="st-card__head">
            <h2 id="st-profile" class="st-card__title">Profile</h2>
            <p class="st-card__desc">How your name appears across AffiliateMY.</p>
          </header>

          <form class="st-form" @submit.prevent="handleUpdateName">
            <Transition name="st-fade">
              <p v-if="nameSuccess" class="st-alert st-alert--ok" role="status">{{ nameSuccess }}</p>
            </Transition>
            <Transition name="st-fade">
              <p v-if="nameError" class="st-alert st-alert--bad" role="alert">{{ nameError }}</p>
            </Transition>

            <label class="st-label" for="st-name">Full name</label>
            <input
              id="st-name"
              v-model="nameForm.name"
              type="text"
              placeholder="Your name"
              autocomplete="name"
              class="st-input"
              @input="nameSuccess = ''; nameError = ''"
            />

            <div class="st-actions">
              <button
                type="submit"
                :disabled="nameLoading || !nameForm.name.trim() || nameUnchanged"
                class="st-btn"
              >
                <span v-if="nameLoading" class="st-spin" aria-hidden="true" />
                <AppIcon v-else name="check" class="st-btn__icon" />
                {{ nameLoading ? 'Saving…' : 'Save changes' }}
              </button>
              <span class="st-hint">{{ nameForm.name.length }} characters</span>
            </div>
          </form>
        </section>

        <!-- Password -->
        <section class="st-card" aria-labelledby="st-password">
          <header class="st-card__head">
            <h2 id="st-password" class="st-card__title">Password</h2>
            <p class="st-card__desc">Use a strong password you don't use anywhere else.</p>
          </header>

          <form class="st-form" @submit.prevent="handleUpdatePassword">
            <Transition name="st-fade">
              <p v-if="passSuccess" class="st-alert st-alert--ok" role="status">{{ passSuccess }}</p>
            </Transition>
            <Transition name="st-fade">
              <p v-if="passError" class="st-alert st-alert--bad" role="alert">{{ passError }}</p>
            </Transition>

            <label class="st-label" for="st-current">Current password</label>
            <div class="st-field">
              <input
                id="st-current"
                v-model="passwordForm.current_password"
                :type="showCurrent ? 'text' : 'password'"
                placeholder="Enter current password"
                autocomplete="current-password"
                class="st-input st-input--toggle"
                @input="passSuccess = ''; passError = ''"
              />
              <button
                type="button"
                class="st-eye"
                :aria-label="showCurrent ? 'Hide current password' : 'Show current password'"
                :aria-pressed="showCurrent"
                @click="showCurrent = !showCurrent"
              >
                <AppIcon :name="showCurrent ? 'eye-slash' : 'eye'" class="st-eye__icon" />
              </button>
            </div>

            <div class="st-pair">
              <div>
                <label class="st-label" for="st-new">New password</label>
                <div class="st-field">
                  <input
                    id="st-new"
                    v-model="passwordForm.password"
                    :type="showNew ? 'text' : 'password'"
                    placeholder="Min 8 characters"
                    autocomplete="new-password"
                    class="st-input st-input--toggle"
                    @input="passSuccess = ''; passError = ''"
                  />
                  <button
                    type="button"
                    class="st-eye"
                    :aria-label="showNew ? 'Hide new password' : 'Show new password'"
                    :aria-pressed="showNew"
                    @click="showNew = !showNew"
                  >
                    <AppIcon :name="showNew ? 'eye-slash' : 'eye'" class="st-eye__icon" />
                  </button>
                </div>
              </div>

              <div>
                <label class="st-label" for="st-confirm">Confirm new password</label>
                <div class="st-field">
                  <input
                    id="st-confirm"
                    v-model="passwordForm.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    placeholder="Repeat new password"
                    autocomplete="new-password"
                    class="st-input st-input--toggle"
                    :aria-invalid="matchState === 'mismatch'"
                    @input="passSuccess = ''; passError = ''"
                  />
                  <button
                    type="button"
                    class="st-eye"
                    :aria-label="showConfirm ? 'Hide confirmation' : 'Show confirmation'"
                    :aria-pressed="showConfirm"
                    @click="showConfirm = !showConfirm"
                  >
                    <AppIcon :name="showConfirm ? 'eye-slash' : 'eye'" class="st-eye__icon" />
                  </button>
                </div>
                <p v-if="matchState" class="st-match" :class="`is-${matchState}`" aria-live="polite">
                  {{ matchState === 'match' ? 'Passwords match' : "Passwords don't match yet" }}
                </p>
              </div>
            </div>

            <div v-if="passwordStrength" class="st-strength">
              <div class="st-strength__row">
                <div class="st-meter" aria-hidden="true">
                  <span
                    v-for="n in 4"
                    :key="n"
                    class="st-meter__seg"
                    :class="[n <= Math.max(passwordStrength.level, 1) ? `is-on is-${passwordStrength.tone}` : '']"
                  />
                </div>
                <span class="st-strength__label" :class="`is-${passwordStrength.tone}`">
                  {{ passwordStrength.label }}
                </span>
              </div>
              <ul class="st-checks" aria-label="Password requirements">
                <li v-for="c in passwordChecks" :key="c.label" class="st-check" :class="{ 'is-ok': c.ok }">
                  <AppIcon v-if="c.ok" name="check" class="st-check__icon" />
                  <span v-else class="st-check__dot" aria-hidden="true" />
                  {{ c.label }}
                </li>
              </ul>
            </div>

            <div class="st-actions">
              <button type="submit" :disabled="passLoading" class="st-btn">
                <span v-if="passLoading" class="st-spin" aria-hidden="true" />
                <AppIcon v-else name="lock" class="st-btn__icon" />
                {{ passLoading ? 'Updating…' : 'Update password' }}
              </button>
            </div>
          </form>
        </section>

      </div>
    </div>
  </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap");

/* Hallmark · genre: modern-minimal (app page) · macrostructure: Split Studio (sticky identity card + form column) · theme: existing role scheme (creator green, brand blue, gray neutrals) · enrichment: none
 * audience: signed-in creators and brands · use: change name, change password · tone: utilitarian, calm
 * motion: alert fade (opacity/transform) + spinner; reduced-motion removes both transitions
 * colours are the page's original Tailwind values as OKLCH tokens; filled buttons use green-700 / blue-600 so white text passes AA
 * pre-emit critique: P4 H4 E4 S4 R5 V4 */

.st-page {
  --st-ink: oklch(21% 0.034 264.7);
  --st-ink-2: oklch(44.6% 0.03 256.8);
  --st-muted: oklch(55.1% 0.027 264.4);
  --st-faint: oklch(70.7% 0.022 261.3);
  --st-rule: oklch(92.8% 0.006 264.5);
  --st-rule-soft: oklch(96.7% 0.003 264.5);
  --st-paper: oklch(100% 0 0);
  --st-paper-2: oklch(98.5% 0.002 247.8);
  --st-red: oklch(63.7% 0.237 25.3);
  --st-red-ink: oklch(57.7% 0.245 27.3);
  --st-red-soft: oklch(97.1% 0.013 17.4);
  --st-amber: oklch(76.9% 0.188 70.1);
  --st-amber-ink: oklch(55.5% 0.163 48.4);
  --st-radius: 14px;
  --st-radius-sm: 10px;
  --st-ease: cubic-bezier(0.16, 1, 0.3, 1);

  /* creator (default) */
  --st-accent: oklch(72.3% 0.219 149.6);
  --st-accent-fill: oklch(52.7% 0.154 150.07);
  --st-accent-fill-hover: oklch(44.8% 0.119 151.3);
  --st-accent-ink: oklch(52.7% 0.154 150.07);
  --st-accent-soft: oklch(98.2% 0.018 155.8);
  --st-accent-tint: oklch(96.2% 0.044 156.7);
  --st-good: oklch(72.3% 0.219 149.6);
  --st-strong: oklch(62.7% 0.194 149.2);

  font-family: "Inter", sans-serif;
  color: var(--st-ink);
  max-width: 62rem;
  margin-inline: auto;
  min-width: 0;
}
.st-page[data-role="brand"] {
  --st-accent: oklch(62.3% 0.214 259.8);
  --st-accent-fill: oklch(54.6% 0.245 262.9);
  --st-accent-fill-hover: oklch(48.8% 0.243 264.4);
  --st-accent-ink: oklch(48.8% 0.243 264.4);
  --st-accent-soft: oklch(97% 0.014 254.6);
  --st-accent-tint: oklch(93.2% 0.032 255.6);
}

.st-page button,
.st-page input { font-family: inherit; }
.st-page a:focus-visible,
.st-page button:focus-visible {
  outline: 2px solid var(--st-accent);
  outline-offset: 2px;
}

/* ── Header ───────────────────────────────────────────────── */
.st-back {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: 1.25rem;
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--st-muted);
  text-decoration: none;
}
.st-back:hover { color: var(--st-ink); }
.st-back__icon { width: 1rem; height: 1rem; }

.st-head { margin-bottom: 1.75rem; }
.st-title {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.15;
}
.st-sub {
  margin: 0.35rem 0 0;
  font-size: 0.9rem;
  color: var(--st-muted);
}

/* ── Layout ───────────────────────────────────────────────── */
.st-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 1.25rem;
  align-items: start;
}
.st-main { display: grid; gap: 1.25rem; min-width: 0; }
.st-side { min-width: 0; }

/* ── Identity ─────────────────────────────────────────────── */
.st-id {
  display: grid;
  justify-items: center;
  gap: 0.2rem;
  padding: 1.75rem 1.25rem;
  text-align: center;
  background: var(--st-paper);
  border: 1px solid var(--st-rule);
  border-radius: var(--st-radius);
}
.st-avatar-wrap {
  margin-bottom: 0.6rem;
}
.st-avatar-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.35rem;
}
.st-avatar-btn {
  position: relative;
  display: inline-block;
  padding: 0.35rem 0.8rem;
  border: 1px solid var(--st-rule, #e5e7eb);
  border-radius: 999px;
  background: #fff;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--st-accent-ink);
  cursor: pointer;
  transition: background 0.15s;
}
.st-avatar-btn:hover {
  background: var(--st-accent-tint);
}
.st-avatar-btn:focus-within {
  outline: 2px solid var(--st-accent-ink);
  outline-offset: 2px;
}
.st-avatar-btn.is-busy {
  opacity: 0.6;
  cursor: progress;
}
.st-avatar-input {
  position: absolute;
  inset: 0;
  width: 100%;
  opacity: 0;
  cursor: pointer;
}
.st-avatar-remove {
  border: 0;
  background: none;
  font-size: 0.78rem;
  font-weight: 600;
  color: #dc2626;
  cursor: pointer;
}
.st-avatar-remove:disabled {
  opacity: 0.5;
}
.st-avatar-hint,
.st-avatar-error {
  margin: 0 0 0.6rem;
  font-size: 0.72rem;
}
.st-avatar-hint {
  color: #9ca3af;
}
.st-avatar-error {
  color: #dc2626;
}
.st-id__name {
  margin: 0;
  max-width: 100%;
  font-size: 1.05rem;
  font-weight: 700;
  letter-spacing: -0.01em;
  overflow-wrap: anywhere;
}
.st-id__email {
  margin: 0;
  max-width: 100%;
  font-size: 0.825rem;
  color: var(--st-muted);
  overflow-wrap: anywhere;
}
.st-chip {
  margin-top: 0.6rem;
  padding: 0.2rem 0.75rem;
  border-radius: 999px;
  background: var(--st-accent-soft);
  color: var(--st-accent-ink);
  font-size: 0.75rem;
  font-weight: 600;
}

/* ── Cards ────────────────────────────────────────────────── */
.st-card {
  background: var(--st-paper);
  border: 1px solid var(--st-rule);
  border-radius: var(--st-radius);
}
.st-card__head {
  padding: 1.25rem 1.5rem 1rem;
  border-bottom: 1px solid var(--st-rule-soft);
}
.st-card__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.st-card__desc {
  margin: 0.2rem 0 0;
  font-size: 0.825rem;
  color: var(--st-muted);
}

/* ── Forms ────────────────────────────────────────────────── */
.st-form {
  display: grid;
  gap: 0.5rem;
  padding: 1.25rem 1.5rem 1.5rem;
}
.st-label {
  display: block;
  margin-top: 0.4rem;
  margin-bottom: 0.4rem;
  font-size: 0.825rem;
  font-weight: 600;
  color: var(--st-ink-2);
}
.st-form > .st-label:first-child,
.st-alert + .st-label { margin-top: 0; }

.st-field { position: relative; }
.st-input {
  width: 100%;
  padding: 0.7rem 0.95rem;
  border: 1px solid var(--st-rule);
  border-radius: var(--st-radius-sm);
  background: var(--st-paper-2);
  font-size: 0.9rem;
  color: var(--st-ink);
}
.st-input--toggle { padding-right: 2.9rem; }
.st-input::placeholder { color: var(--st-faint); }
.st-input:hover { border-color: var(--st-faint); }
.st-input:focus {
  border-color: var(--st-accent);
  background: var(--st-paper);
  outline: 2px solid var(--st-accent);
  outline-offset: 0;
}
.st-input[aria-invalid="true"] { border-color: var(--st-red); }

.st-eye {
  position: absolute;
  top: 50%;
  right: 0.3rem;
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  transform: translateY(-50%);
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: var(--st-muted);
  cursor: pointer;
}
.st-eye:hover { color: var(--st-ink); background: var(--st-rule-soft); }
.st-eye__icon { width: 1.1rem; height: 1.1rem; }

.st-pair {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 0.5rem 1rem;
}
.st-pair > div { min-width: 0; }

.st-match { margin: 0.4rem 0 0; font-size: 0.775rem; font-weight: 500; }
.st-match.is-match { color: var(--st-accent-ink); }
.st-match.is-mismatch { color: var(--st-red-ink); }

/* ── Strength ─────────────────────────────────────────────── */
.st-strength { display: grid; gap: 0.7rem; margin-top: 0.5rem; }
.st-strength__row { display: flex; align-items: center; gap: 0.75rem; }
.st-meter { flex: 1; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 0.3rem; }
.st-meter__seg { height: 0.35rem; border-radius: 999px; background: var(--st-rule-soft); }
.st-meter__seg.is-bad { background: var(--st-red); }
.st-meter__seg.is-fair { background: var(--st-amber); }
.st-meter__seg.is-good { background: var(--st-good); }
.st-meter__seg.is-strong { background: var(--st-strong); }
.st-strength__label { flex: none; font-size: 0.775rem; font-weight: 700; }
.st-strength__label.is-bad { color: var(--st-red-ink); }
.st-strength__label.is-fair { color: var(--st-amber-ink); }
.st-strength__label.is-good,
.st-strength__label.is-strong { color: var(--st-accent-ink); }

.st-checks { display: flex; flex-wrap: wrap; gap: 0.4rem 1rem; margin: 0; padding: 0; list-style: none; }
.st-check { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.775rem; color: var(--st-muted); }
.st-check.is-ok { color: var(--st-accent-ink); font-weight: 500; }
.st-check__icon { width: 0.85rem; height: 0.85rem; }
.st-check__dot { width: 0.35rem; height: 0.35rem; margin: 0 0.25rem; border-radius: 999px; background: var(--st-faint); }

/* ── Actions ──────────────────────────────────────────────── */
.st-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.9rem; }
.st-hint { font-size: 0.775rem; color: var(--st-muted); }

.st-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.7rem 1.25rem;
  border: 0;
  border-radius: var(--st-radius-sm);
  background: var(--st-accent-fill);
  color: var(--st-paper);
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
}
.st-btn:hover { background: var(--st-accent-fill-hover); }
.st-btn:active { transform: translateY(1px); }
.st-btn:disabled { background: var(--st-faint); cursor: not-allowed; }
.st-btn__icon { width: 1rem; height: 1rem; }
.st-spin {
  width: 1rem;
  height: 1rem;
  border: 2px solid oklch(100% 0 0 / 0.35);
  border-top-color: var(--st-paper);
  border-radius: 999px;
  animation: st-spin 0.7s linear infinite;
}
@keyframes st-spin { to { transform: rotate(360deg); } }

/* ── Alerts ───────────────────────────────────────────────── */
.st-alert { margin: 0 0 0.4rem; padding: 0.7rem 0.95rem; border-radius: var(--st-radius-sm); font-size: 0.85rem; font-weight: 500; }
.st-alert--ok { background: var(--st-accent-soft); color: var(--st-accent-ink); }
.st-alert--bad { background: var(--st-red-soft); color: var(--st-red-ink); }

.st-fade-enter-active,
.st-fade-leave-active { transition: opacity 0.2s var(--st-ease), transform 0.2s var(--st-ease); }
.st-fade-enter-from,
.st-fade-leave-to { opacity: 0; transform: translateY(-4px); }

/* ── Wider screens ────────────────────────────────────────── */
@media (min-width: 40rem) {
  .st-pair { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (min-width: 56rem) {
  .st-grid { grid-template-columns: 17rem minmax(0, 1fr); gap: 1.5rem; }
  .st-side { position: sticky; top: 5.5rem; }
}

@media (prefers-reduced-motion: reduce) {
  .st-fade-enter-active,
  .st-fade-leave-active { transition: none; }
  .st-spin { animation-duration: 2s; }
  .st-btn:active { transform: none; }
}
</style>
