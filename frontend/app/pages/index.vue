<script setup lang="ts">
const authStore = useAuth()
const menuOpen = ref(false)

const avatars = [
  { letter: 'A', color: 'bg-orange-400' },
  { letter: 'N', color: 'bg-emerald-700' },
  { letter: 'E', color: 'bg-stone-500' },
  { letter: 'R', color: 'bg-rose-700' },
  { letter: 'F', color: 'bg-teal-500' },
]

const stats = [
  { value: '2,400+', label: 'Active Creators' },
  { value: '380+', label: 'Malaysian Brands' },
  { value: 'RM 1.2M', label: 'Commissions Paid' },
]

const features = [
  { icon: 'dollar', title: 'High Commission Rates', desc: 'Find products offering up to 30% commission across every category.' },
  { icon: 'shield-check', title: 'Verified Malaysian Brands', desc: 'Every brand is SSM-verified — no scams, just real opportunities.' },
  { icon: 'chart-bar', title: 'Real Channel Stats', desc: 'Connect your YouTube channel so brands see your real reach.' },
  { icon: 'credit-card', title: 'Track Your Earnings', desc: 'See your commissions and monthly growth in one dashboard.' },
  { icon: 'link', title: 'Direct Brand Connection', desc: 'Message brands directly and build lasting partnerships.' },
  { icon: 'clock', title: 'Apply in Minutes', desc: 'Write your pitch and submit an application in under 5 minutes.' },
  { icon: 'chat', title: 'Affiliate Concierge', desc: 'Our team helps you get matched with the right brands, faster.' },
]

const steps = [
  { num: '01', icon: 'user-plus', title: 'Create your profile' },
  { num: '02', icon: 'cart', title: 'Browse & apply' },
  { num: '03', icon: 'applications', title: 'Review applications' },
  { num: '04', icon: 'megaphone', title: 'Promote & earn' },
]

const creatorChecklist = ['Join as a creator for free', 'Showcase your real channel stats', 'Apply to commission products']
const brandChecklist = ['List your products in minutes', 'Connect directly with creators', 'Only pay for real results']
</script>

<template>
  <div class="bg-white">

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-gray-100">
      <div class="max-w-6xl mx-auto px-5 sm:px-8 h-16 flex items-center justify-between">
        <NuxtLink to="/" class="text-lg font-extrabold text-gray-900">
          Affiliate<span class="text-indigo-600">MY</span>
        </NuxtLink>

        <!-- Desktop nav actions -->
        <div class="hidden sm:flex items-center gap-3">
          <template v-if="authStore.isLoggedIn">
            <NuxtLink
              :to="authStore.isBrand ? '/brand/dashboard' : '/creator/dashboard'"
              class="rounded-xl bg-indigo-600 text-white text-sm font-semibold px-5 py-2"
            >
              Go to Dashboard
            </NuxtLink>
          </template>
          <template v-else>
            <NuxtLink to="/auth/login" class="rounded-xl border border-gray-200 text-gray-700 text-sm font-semibold px-5 py-2">
              Log in
            </NuxtLink>
            <NuxtLink to="/auth/register-type" class="rounded-xl bg-indigo-600 text-white text-sm font-semibold px-5 py-2">
              Get Started
            </NuxtLink>
          </template>
        </div>

        <!-- Mobile hamburger -->
        <button
          @click="menuOpen = !menuOpen"
          class="p-2 -mr-2 text-gray-700 sm:hidden"
          aria-label="Toggle menu"
        >
          <AppIcon name="menu" class="w-6 h-6" />
        </button>
      </div>

      <div v-if="menuOpen" class="sm:hidden border-t border-gray-100 px-5 py-4 flex flex-col gap-2 bg-white">
        <template v-if="authStore.isLoggedIn">
          <NuxtLink
            :to="authStore.isBrand ? '/brand/dashboard' : '/creator/dashboard'"
            class="text-center rounded-xl bg-indigo-600 text-white text-sm font-semibold py-2.5"
          >
            Go to Dashboard
          </NuxtLink>
        </template>
        <template v-else>
          <NuxtLink to="/auth/login" class="text-center rounded-xl border border-gray-200 text-gray-700 text-sm font-semibold py-2.5">
            Log in
          </NuxtLink>
          <NuxtLink to="/auth/register-type" class="text-center rounded-xl bg-indigo-600 text-white text-sm font-semibold py-2.5">
            Get Started
          </NuxtLink>
        </template>
      </div>
    </header>

    <!-- Hero -->
    <section class="relative overflow-hidden bg-gradient-to-b from-indigo-50 via-slate-50 to-white">
      <div class="relative max-w-3xl mx-auto px-6 pt-16 pb-14 sm:pt-24 sm:pb-20 text-center">
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight text-gray-900 leading-[1.1]">
          Turn your content into
          <span class="block bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
            a revenue stream
          </span>
        </h1>

        <p class="mt-5 text-sm sm:text-lg text-gray-500 leading-relaxed max-w-xl mx-auto">
          AffiliateMY connects Malaysian creators with local brands.
          Showcase your YouTube channel stats, apply to high-commission products, and start earning.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
          <NuxtLink
            to="/auth/register-type?role=creator"
            class="w-full sm:w-auto text-center rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm sm:text-base font-semibold py-3 px-8 shadow-lg shadow-indigo-200"
          >
            Start as Creator →
          </NuxtLink>
          <NuxtLink
            to="/auth/register-type?role=brand"
            class="w-full sm:w-auto text-center rounded-xl border border-indigo-200 text-indigo-600 text-sm sm:text-base font-semibold py-3 px-8"
          >
            List Your Products
          </NuxtLink>
        </div>

        <div class="mt-8 flex flex-col items-center gap-2">
          <div class="flex -space-x-2">
            <span
              v-for="a in avatars"
              :key="a.letter"
              class="w-8 h-8 rounded-full ring-2 ring-white flex items-center justify-center text-xs font-bold text-white"
              :class="a.color"
            >
              {{ a.letter }}
            </span>
          </div>
          <p class="text-xs text-gray-500">
            Join <strong class="text-gray-900">2,400+</strong> creators already earning
          </p>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="border-b border-gray-100">
      <div class="max-w-3xl mx-auto px-6 py-8 sm:py-10 grid grid-cols-3 divide-x divide-gray-100 text-center">
        <div v-for="s in stats" :key="s.label">
          <p class="text-xl sm:text-3xl font-extrabold text-gray-900">{{ s.value }}</p>
          <p class="text-xs sm:text-sm text-gray-500 mt-1">{{ s.label }}</p>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="max-w-5xl mx-auto px-6 py-14 sm:py-20">
      <p class="text-center text-xs font-semibold tracking-widest text-gray-400 uppercase">Why AffiliateMY</p>
      <h2 class="mt-2 text-center text-2xl sm:text-4xl font-extrabold text-gray-900 leading-tight max-w-2xl mx-auto">
        Everything you need to grow your affiliate income
      </h2>

      <div class="mt-8 sm:mt-12 grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
        <div
          v-for="(f, i) in features"
          :key="f.title"
          class="rounded-2xl bg-gray-50 p-4 sm:p-6"
          :class="{ 'col-span-2 sm:col-span-3': i === features.length - 1 }"
        >
          <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center mb-3">
            <AppIcon :name="f.icon" class="w-4.5 h-4.5" />
          </div>
          <p class="text-sm sm:text-base font-bold text-gray-900">{{ f.title }}</p>
          <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-relaxed">{{ f.desc }}</p>
        </div>
      </div>
    </section>

    <!-- Simple Process -->
    <section class="bg-gray-50 py-14 sm:py-20">
      <div class="max-w-4xl mx-auto px-6">
        <p class="text-center text-xs font-semibold tracking-widest text-gray-400 uppercase">Simple Process</p>
        <h2 class="mt-2 text-center text-2xl sm:text-4xl font-extrabold text-gray-900 leading-tight">
          From sign up to first commission
        </h2>

        <div class="mt-8 sm:mt-12 grid grid-cols-4 gap-3 sm:gap-6 text-center">
          <div v-for="s in steps" :key="s.num">
            <div class="w-11 h-11 sm:w-14 sm:h-14 mx-auto rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
              <AppIcon :name="s.icon" class="w-5 h-5 sm:w-6 sm:h-6" />
            </div>
            <p class="mt-2 text-lg sm:text-2xl font-extrabold text-gray-300">{{ s.num }}</p>
            <p class="text-xs sm:text-sm font-semibold text-gray-900 leading-tight">{{ s.title }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Dual CTA -->
    <section class="max-w-4xl mx-auto px-6 py-14 sm:py-20 grid gap-4 sm:gap-6 sm:grid-cols-2">
      <div class="rounded-3xl border border-gray-100 p-6 sm:p-8">
        <div class="w-11 h-11 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
          <AppIcon name="video" class="w-5 h-5" />
        </div>
        <h3 class="mt-4 text-lg font-extrabold text-gray-900">For Creators</h3>
        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
          Browse high-commission products, connect your YouTube channel and start earning.
        </p>
        <ul class="mt-4 space-y-2">
          <li v-for="item in creatorChecklist" :key="item" class="flex items-center gap-2 text-sm text-gray-700">
            <AppIcon name="check" class="w-4 h-4 text-indigo-600" />
            {{ item }}
          </li>
        </ul>
        <NuxtLink
          to="/auth/register-type?role=creator"
          class="mt-5 block text-center rounded-xl bg-gradient-to-r from-gray-900 to-gray-700 text-white text-sm font-semibold py-3"
        >
          Join as Creator →
        </NuxtLink>
      </div>

      <div class="rounded-3xl border border-gray-100 p-6 sm:p-8">
        <div class="w-11 h-11 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
          <AppIcon name="creators" class="w-5 h-5" />
        </div>
        <h3 class="mt-4 text-lg font-extrabold text-gray-900">For Brands</h3>
        <p class="mt-2 text-sm text-gray-500 leading-relaxed">
          Find the right creators for your products, backed by real channel stats.
        </p>
        <ul class="mt-4 space-y-2">
          <li v-for="item in brandChecklist" :key="item" class="flex items-center gap-2 text-sm text-gray-700">
            <AppIcon name="check" class="w-4 h-4 text-gray-700" />
            {{ item }}
          </li>
        </ul>
        <NuxtLink
          to="/auth/register-type?role=brand"
          class="mt-5 block text-center rounded-xl bg-gray-900 text-white text-sm font-semibold py-3"
        >
          List Your Products →
        </NuxtLink>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-gray-100 py-8 sm:py-10 text-center text-xs sm:text-sm text-gray-400">
      © 2026 AffiliateMY.
    </footer>

  </div>
</template>
