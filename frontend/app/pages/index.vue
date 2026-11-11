<script setup lang="ts">
const authStore = useAuth()

const headline = ['Turn', 'your', 'content', 'into', 'a', 'revenue', 'stream']
const accentWord = 'revenue'

const niches = ['Beauty', 'Food', 'Fitness', 'Tech', 'Travel', 'Gaming', 'Lifestyle', 'Parenting', 'Fashion', 'Education']

const features = [
  { title: 'High commission rates', desc: 'Find products offering up to 30% commission across every category.' },
  { title: 'Verified Malaysian brands', desc: 'Every brand is SSM-verified. No scams, just real opportunities.' },
  { title: 'Real channel stats', desc: 'Connect your channel so brands see your real reach.' },
  { title: 'Track your earnings', desc: 'See your commissions and monthly growth in one dashboard.' },
  { title: 'Direct brand connection', desc: 'Message brands directly and build lasting partnerships.' },
  { title: 'Apply in minutes', desc: 'Write your pitch and submit an application in under 5 minutes.' },
  { title: 'Affiliate concierge', desc: 'Our team helps you get matched with the right brands, faster.' },
]

const steps = [
  { num: '01', title: 'Create your profile', desc: 'Creators add their channel. Brands add their company.' },
  { num: '02', title: 'Browse and apply', desc: 'Creators pick products and send a short pitch.' },
  { num: '03', title: 'Review applications', desc: 'Brands compare creators by real channel stats.' },
  { num: '04', title: 'Promote and earn', desc: 'Share your link. Commissions land in your dashboard.' },
]

const creatorChecklist = ['Join as a creator for free', 'Showcase your real channel stats', 'Apply to commission products']
const brandChecklist = ['List your products in minutes', 'Connect directly with creators', 'Only pay for real results']

const dashboardTo = computed(() => roleHome(authStore.user?.role))

/* ---- Command palette (⌘K) ---- */
type Command = { label: string; hint: string; run: () => void }

const paletteOpen = ref(false)
const query = ref('')
const activeIndex = ref(0)
const searchInput = ref<HTMLInputElement | null>(null)
const pillButton = ref<HTMLButtonElement | null>(null)

function jumpTo(id: string) {
  const el = document.getElementById(id)
  if (!el) return
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' })
}

const commands = computed<Command[]>(() => {
  const list: Command[] = [
    { label: 'For creators', hint: 'Jump to', run: () => jumpTo('creators') },
    { label: 'For brands', hint: 'Jump to', run: () => jumpTo('brands') },
    { label: 'How it works', hint: 'Jump to', run: () => jumpTo('process') },
  ]
  if (authStore.isLoggedIn) {
    list.push({ label: 'Go to dashboard', hint: 'Open', run: () => navigateTo(dashboardTo.value) })
  } else {
    list.push(
      { label: 'Log in', hint: 'Open', run: () => navigateTo('/auth/login') },
      { label: 'Join as a creator', hint: 'Sign up', run: () => navigateTo('/auth/register-type?role=creator') },
      { label: 'List your products', hint: 'Sign up', run: () => navigateTo('/auth/register-type?role=brand') },
    )
  }
  return list
})

const results = computed(() => {
  const q = query.value.trim().toLowerCase()
  return q ? commands.value.filter((c) => c.label.toLowerCase().includes(q)) : commands.value
})

watch(query, () => { activeIndex.value = 0 })

function openPalette() {
  paletteOpen.value = true
  query.value = ''
  activeIndex.value = 0
  nextTick(() => searchInput.value?.focus())
}
function closePalette() {
  paletteOpen.value = false
  nextTick(() => pillButton.value?.focus())
}
function runCommand(cmd?: Command) {
  if (!cmd) return
  paletteOpen.value = false
  cmd.run()
}
function onInputKey(e: KeyboardEvent) {
  const n = results.value.length
  if (e.key === 'ArrowDown') { e.preventDefault(); if (n) activeIndex.value = (activeIndex.value + 1) % n }
  else if (e.key === 'ArrowUp') { e.preventDefault(); if (n) activeIndex.value = (activeIndex.value - 1 + n) % n }
  else if (e.key === 'Enter') { e.preventDefault(); runCommand(results.value[activeIndex.value]) }
}
function onGlobalKey(e: KeyboardEvent) {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    paletteOpen.value ? closePalette() : openPalette()
  } else if (e.key === 'Escape' && paletteOpen.value) {
    closePalette()
  }
}
watch(paletteOpen, (open) => {
  if (import.meta.client) document.body.style.overflow = open ? 'hidden' : ''
})
onMounted(() => window.addEventListener('keydown', onGlobalKey))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onGlobalKey)
  if (import.meta.client) document.body.style.overflow = ''
})
</script>

<template>
  <div class="landing">
    <!-- Nav: bordered bar with inline ⌘K pill -->
    <header class="nav">
      <div class="nav__inner">
        <NuxtLink to="/" class="wordmark">Affiliate<span>MY</span></NuxtLink>

        <!-- <button
          ref="pillButton"
          type="button"
          class="searchpill"
          aria-label="Search and jump (Command K)"
          aria-haspopup="dialog"
          @click="openPalette"
        >
          <svg class="searchpill__icon" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
            <circle cx="9" cy="9" r="5.5" /><path d="m13.5 13.5 3.5 3.5" stroke-linecap="round" />
          </svg>
          <span class="searchpill__text">Jump to…</span>
          <span class="searchpill__kbd" aria-hidden="true"><kbd>⌘</kbd><kbd>K</kbd></span>
        </button> -->

        <nav class="nav__right" aria-label="Primary">
          <a href="#creators" class="nav__link">Creators</a>
          <a href="#brands" class="nav__link">Brands</a>
          <template v-if="authStore.isLoggedIn">
            <NuxtLink :to="dashboardTo" class="btn btn--accent btn--sm">Dashboard</NuxtLink>
          </template>
          <template v-else>
            <NuxtLink to="/auth/login" class="nav__link nav__link--login">Log in</NuxtLink>
            <NuxtLink to="/auth/register-type" class="btn btn--accent btn--sm">Get started</NuxtLink>
          </template>
        </nav>
      </div>
    </header>

    <!-- Command palette -->
    <div v-if="paletteOpen" class="cmdk" @keydown.esc="closePalette">
      <div class="cmdk__backdrop" @click="closePalette"></div>
      <div class="cmdk__panel" role="dialog" aria-modal="true" aria-label="Jump to a section or page">
        <div class="cmdk__field">
          <input
            ref="searchInput"
            v-model="query"
            class="cmdk__input"
            type="text"
            placeholder="Jump to a section or page"
            autocomplete="off"
            role="combobox"
            aria-expanded="true"
            aria-controls="cmdk-list"
            :aria-activedescendant="results.length ? `cmdk-opt-${activeIndex}` : undefined"
            @keydown="onInputKey"
          />
          <kbd>esc</kbd>
        </div>
        <ul id="cmdk-list" class="cmdk__list" role="listbox">
          <li
            v-for="(c, i) in results"
            :id="`cmdk-opt-${i}`"
            :key="c.label"
            role="option"
            :aria-selected="i === activeIndex"
            class="cmdk__item"
            :class="{ 'is-active': i === activeIndex }"
            @mousemove="activeIndex = i"
            @click="runCommand(c)"
          >
            <span>{{ c.label }}</span>
            <span class="cmdk__hint">{{ c.hint }}</span>
          </li>
          <li v-if="!results.length" class="cmdk__empty">No match</li>
        </ul>
        <div class="cmdk__foot" aria-hidden="true">
          <span><kbd>↑</kbd><kbd>↓</kbd> move</span><span><kbd>↵</kbd> open</span><span><kbd>esc</kbd> close</span>
        </div>
      </div>
    </div>

    <main>
      <!-- Marquee hero -->
      <section class="hero">
        <h1 class="hero__title" aria-label="Turn your content into a revenue stream">
          <span
            v-for="(w, i) in headline"
            :key="i"
            class="word"
            :class="{ 'word--accent': w === accentWord }"
            :style="{ '--i': i }"
            aria-hidden="true"
          ><span class="word__in">{{ w }}</span></span>
        </h1>

        <div class="ticker" aria-hidden="true">
          <div class="ticker__track">
            <ul class="ticker__set"><li v-for="n in niches" :key="n">{{ n }}</li></ul>
            <ul class="ticker__set ticker__set--dup"><li v-for="n in niches" :key="n + '-dup'">{{ n }}</li></ul>
          </div>
        </div>
      </section>

      <!-- Two doors -->
      <section class="doors wrap">
        <p class="doors__lede">
          AffiliateMY connects Malaysian content creators with local brands.
          Show your real channel stats, apply to high-commission products, and start earning.
        </p>

        <div class="doors__grid">
          <article id="creators" class="door door--creator">
            <h2>For creators</h2>
            <p class="door__desc">Browse high-commission products, connect your channel and start earning.</p>
            <ul class="checks">
              <li v-for="item in creatorChecklist" :key="item"><AppIcon name="check" class="checks__icon" />{{ item }}</li>
            </ul>
            <NuxtLink to="/auth/register-type?role=creator" class="btn btn--accent">Join as creator</NuxtLink>
          </article>

          <article id="brands" class="door door--brand">
            <h2>For brands</h2>
            <p class="door__desc">Find the right creators for your products, backed by real channel stats.</p>
            <ul class="checks">
              <li v-for="item in brandChecklist" :key="item"><AppIcon name="check" class="checks__icon" />{{ item }}</li>
            </ul>
            <NuxtLink to="/auth/register-type?role=brand" class="btn btn--marigold">List your products</NuxtLink>
          </article>
        </div>
      </section>

      <!-- Why: ruled grid -->
      <section class="why wrap">
        <h2 class="why__title">Everything you need to grow your affiliate income</h2>
        <ul class="why__grid">
          <li v-for="f in features" :key="f.title" class="cell">
            <h3>{{ f.title }}</h3>
            <p>{{ f.desc }}</p>
          </li>
        </ul>
      </section>

      <!-- Process: the one dark band -->
      <section id="process" class="band">
        <div class="wrap band__inner">
          <h2>From sign up to first commission</h2>
          <ol class="steps">
            <li v-for="(s, i) in steps" :key="s.num" class="step" :class="i % 2 ? 'step--b' : 'step--a'">
              <span class="step__num">{{ s.num }}</span>
              <h3>{{ s.title }}</h3>
              <p>{{ s.desc }}</p>
            </li>
          </ol>
        </div>
      </section>

      <!-- Close -->
      <section class="close wrap">
        <h2>Start on the side that fits you</h2>
        <div class="close__actions">
          <NuxtLink to="/auth/register-type?role=creator" class="btn btn--accent">I'm a creator</NuxtLink>
          <NuxtLink to="/auth/register-type?role=brand" class="btn btn--marigold">I'm a brand</NuxtLink>
        </div>
      </section>
    </main>

    <footer class="foot wrap">
      <p class="foot__line">Made in Malaysia, for people who make things.</p>
      <div class="foot__meta">
        <span class="wordmark">Affiliate<span>MY</span></span>
        <span>© 2026</span>
      </div>
    </footer>
  </div>
</template>

<style scoped>
/* Hallmark · genre: modern-minimal · macrostructure: Marquee Hero · theme: Cobalt (+ marigold second accent) · enrichment: none · nav: N13 · footer: Ft5
 * tone: creative studio, minimalist SaaS, playful motion · audience: Malaysian content creators + local brands · use: dual sign-up
 * motion: hero word rise · niche ticker · door tilt (transform/opacity only, ease-out, no overshoot)
 * texture: 40px grid on the page background, added at the user's request (Cobalt otherwise stays textureless)
 * pre-emit critique: P4 H4 E4 S4 R4 V5 */

.landing {
  min-height: 100vh;
  background-color: var(--color-paper);
  background-image:
    linear-gradient(var(--color-grid) 1px, transparent 1px),
    linear-gradient(90deg, var(--color-grid) 1px, transparent 1px);
  background-size: var(--grid-size) var(--grid-size);
  color: var(--color-ink-2);
  font-family: var(--font-body);
  font-size: var(--text-md);
  line-height: 1.6;
  overflow-x: clip;
}

h1, h2, h3 {
  font-family: var(--font-display);
  font-style: normal;
  color: var(--color-ink);
  letter-spacing: -0.03em;
  line-height: 1.05;
  overflow-wrap: anywhere;
  min-width: 0;
  margin: 0;
}
p { margin: 0; }
ul, ol { list-style: none; margin: 0; padding: 0; }
kbd { font-family: var(--font-mono); font-size: var(--text-xs); }

.wrap { max-width: 76rem; margin-inline: auto; padding-inline: var(--space-md); }

/* ---- Buttons ---- */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  min-height: 3rem;
  padding: 0 var(--space-lg);
  border-radius: var(--radius-btn);
  border: var(--rule-hair) solid transparent;
  font-family: var(--font-body);
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
  cursor: pointer;
  transition: background-color var(--dur-short) var(--ease-out), transform var(--dur-micro) var(--ease-out);
}
.btn--sm { min-height: 2.25rem; padding: 0 var(--space-sm); }
.btn--accent { background: var(--color-accent); color: var(--color-accent-ink); }
.btn--accent:hover { background: var(--color-accent-hover); }
.btn--marigold { background: var(--color-accent-2); color: var(--color-accent-2-ink); }
.btn--marigold:hover { background: var(--color-accent-2-hover); }
.btn:active { transform: translateY(1px); }
.btn:focus-visible, a:focus-visible, button:focus-visible {
  outline: 2px solid var(--color-focus);
  outline-offset: 2px;
}

/* ---- Nav ---- */
.nav {
  position: sticky;
  top: 0;
  z-index: 30;
  background: color-mix(in oklch, var(--color-paper) 85%, transparent);
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
  border-bottom: var(--rule-hair) solid var(--color-rule);
}
.nav__inner {
  max-width: 76rem;
  margin-inline: auto;
  padding-inline: var(--space-md);
  height: var(--nav-h);
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}
.wordmark {
  font-family: var(--font-display);
  font-weight: 700;
  letter-spacing: -0.03em;
  color: var(--color-ink);
  text-decoration: none;
  white-space: nowrap;
}
.wordmark span { color: var(--color-accent); }
.nav .wordmark { font-size: var(--text-lg); }
.nav__right { margin-left: auto; display: flex; align-items: center; gap: var(--space-md); }
.nav__link { display: none; color: var(--color-muted); font-size: var(--text-sm); text-decoration: none; white-space: nowrap; }
.nav__link:hover { color: var(--color-ink); }

.searchpill {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
  height: 2.25rem;
  padding: 0 var(--space-xs);
  margin-left: var(--space-xs);
  background: var(--color-paper-2);
  border: var(--rule-hair) solid var(--color-rule);
  border-radius: var(--radius-btn);
  color: var(--color-muted);
  font-family: var(--font-body);
  font-size: var(--text-sm);
  cursor: pointer;
  transition: border-color var(--dur-short) var(--ease-out);
}
.searchpill:hover { border-color: var(--color-accent); }
.searchpill__icon { width: 1rem; height: 1rem; flex: none; }
.searchpill__text, .searchpill__kbd { display: none; }
.searchpill__kbd { gap: var(--space-3xs); }
.searchpill__kbd kbd {
  padding: 0 var(--space-2xs);
  border: var(--rule-hair) solid var(--color-rule-2);
  border-radius: 4px;
  background: var(--color-surface);
  color: var(--color-ink-2);
  line-height: 1.5;
}

/* ---- Command palette ---- */
.cmdk { position: fixed; inset: 0; z-index: 60; }
.cmdk__backdrop { position: absolute; inset: 0; background: oklch(24% 0.02 258 / 0.4); }
.cmdk__panel {
  position: absolute;
  top: 12vh;
  left: 50%;
  transform: translateX(-50%);
  width: min(34rem, calc(100vw - 2rem));
  background: var(--color-surface);
  border: var(--rule-hair) solid var(--color-rule-2);
  border-radius: var(--radius-card);
  overflow: hidden;
  animation: cmdk-in var(--dur-short) var(--ease-out);
}
.cmdk__field { display: flex; align-items: center; gap: var(--space-sm); padding: var(--space-sm); border-bottom: var(--rule-hair) solid var(--color-rule); }
.cmdk__field kbd { color: var(--color-muted); }
.cmdk__input {
  flex: 1;
  min-width: 0;
  border: 0;
  background: transparent;
  color: var(--color-ink);
  font-family: var(--font-mono);
  font-size: var(--text-sm);
}
.cmdk__input:focus { outline: none; }
.cmdk__list { max-height: 18rem; overflow-y: auto; padding: var(--space-2xs); }
.cmdk__item {
  display: flex;
  justify-content: space-between;
  gap: var(--space-sm);
  padding: var(--space-xs);
  border-radius: var(--radius-btn);
  color: var(--color-ink);
  font-size: var(--text-sm);
  cursor: pointer;
}
.cmdk__item.is-active { background: var(--color-accent-soft); }
.cmdk__hint { font-family: var(--font-mono); font-size: var(--text-xs); color: var(--color-muted); }
.cmdk__empty { padding: var(--space-sm); color: var(--color-muted); font-size: var(--text-sm); }
.cmdk__foot {
  display: flex;
  gap: var(--space-md);
  padding: var(--space-xs) var(--space-sm);
  border-top: var(--rule-hair) solid var(--color-rule);
  color: var(--color-muted);
  font-size: var(--text-xs);
}
@keyframes cmdk-in { from { opacity: 0; transform: translateX(-50%) translateY(-6px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }

/* ---- Marquee hero ---- */
.hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  grid-template-rows: 1fr auto;
  min-height: calc(100svh - var(--nav-h));
  overflow-x: clip;
}
.hero__title {
  align-self: center;
  padding: var(--space-xl) var(--space-md);
  max-width: 76rem;
  margin-inline: auto;
  width: 100%;
  min-width: 0;
  font-size: var(--text-marquee);
  font-weight: 600;
  line-height: 0.95;
  letter-spacing: -0.045em;
}
.word { display: inline-block; overflow: hidden; vertical-align: top; padding-bottom: 0.08em; margin-right: 0.22em; }
.word__in { display: inline-block; animation: word-rise var(--dur-long) var(--ease-out) both; animation-delay: calc(var(--i) * 70ms + 80ms); }
.word--accent .word__in {
  color: var(--color-accent);
  background: linear-gradient(var(--color-accent-2), var(--color-accent-2)) 0 92% / 100% 0.12em no-repeat;
}
@keyframes word-rise { from { transform: translateY(105%); } to { transform: translateY(0); } }

/* ---- Ticker ---- */
.ticker {
  min-width: 0;
  border-block: var(--rule-hair) solid var(--color-rule);
  background: var(--color-paper-2);
  overflow: clip;
}
.ticker__track { display: flex; width: max-content; animation: ticker 48s linear infinite; }
.ticker__track:hover { animation-play-state: paused; }
.ticker__set { display: flex; flex: none; }
.ticker__set li {
  padding: var(--space-xs) var(--space-md);
  font-family: var(--font-mono);
  font-size: var(--text-sm);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-ink-2);
  white-space: nowrap;
}
.ticker__set li::after { content: "✦"; margin-left: var(--space-md); color: var(--color-accent); }
.ticker__set:nth-child(even) li::after { color: var(--color-accent-2); }
@keyframes ticker { from { transform: translateX(0); } to { transform: translateX(-50%); } }

/* ---- Doors ---- */
.doors { padding-block: var(--space-2xl); display: grid; gap: var(--space-xl); }
.doors__lede { font-size: var(--text-lg); line-height: 1.5; color: var(--color-ink-2); max-width: 40rem; }
.doors__grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: var(--space-md); }
.door {
  display: grid;
  gap: var(--space-sm);
  align-content: start;
  justify-items: start;
  padding: var(--space-lg);
  border: var(--rule-hair) solid var(--color-rule);
  border-radius: var(--radius-card);
  scroll-margin-top: calc(var(--nav-h) + var(--space-md));
  transition: transform var(--dur-short) var(--ease-out), border-color var(--dur-short) var(--ease-out);
}
.door h2 { font-size: var(--text-2xl); font-weight: 600; }
.door__desc { color: var(--color-ink-2); max-width: 28rem; }
.door .btn { margin-top: var(--space-xs); }
.door--creator { background: var(--color-accent-soft); border-color: var(--color-accent-line); }
.door--brand { background: var(--color-accent-2-soft); border-color: var(--color-accent-2-line); }
@media (hover: hover) {
  .door--creator:hover { transform: translateY(-4px) rotate(-0.6deg); border-color: var(--color-accent); }
  .door--brand:hover { transform: translateY(-4px) rotate(0.6deg); border-color: var(--color-accent-2-hover); }
}

.checks { display: grid; gap: var(--space-2xs); }
.checks li { display: flex; align-items: center; gap: var(--space-xs); font-size: var(--text-sm); color: var(--color-ink); }
.checks__icon { width: 1rem; height: 1rem; flex: none; }
.door--creator .checks__icon { color: var(--color-accent); }
.door--brand .checks__icon { color: var(--color-ink); }

/* ---- Why: ruled grid ---- */
.why { padding-block: var(--space-2xl); }
.why__title { font-size: var(--text-2xl); font-weight: 600; max-width: 22ch; }
.why__grid {
  margin-top: var(--space-xl);
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: var(--rule-hair);
  background: var(--color-rule);
  border: var(--rule-hair) solid var(--color-rule);
  border-radius: var(--radius-card);
  overflow: clip;
}
.cell { background: var(--color-paper); padding: var(--space-md); transition: background-color var(--dur-short) var(--ease-out); }
.cell:hover { background: var(--color-accent-soft); }
.cell:nth-child(even):hover { background: var(--color-accent-2-soft); }
.cell h3 { font-size: var(--text-lg); font-weight: 600; letter-spacing: -0.02em; }
.cell p { margin-top: var(--space-2xs); font-size: var(--text-sm); color: var(--color-muted); max-width: 30rem; }

/* ---- Process: dark graphite band ---- */
.band { background: var(--color-graphite); color: var(--color-graphite-ink); scroll-margin-top: var(--nav-h); }
.band__inner { padding-block: var(--space-2xl); }
.band h2 { color: var(--color-graphite-ink); font-size: var(--text-2xl); font-weight: 600; max-width: 20ch; }
.steps { margin-top: var(--space-xl); display: grid; grid-template-columns: minmax(0, 1fr); gap: var(--space-lg); }
.step { padding-top: var(--space-md); border-top: var(--rule-hair) solid var(--color-graphite-rule); }
.step__num { font-family: var(--font-mono); font-size: var(--text-sm); }
.step--a .step__num { color: var(--color-accent-line); }
.step--b .step__num { color: var(--color-accent-2); }
.step h3 { margin-top: var(--space-xs); color: var(--color-graphite-ink); font-size: var(--text-lg); font-weight: 600; letter-spacing: -0.02em; }
.step p { margin-top: var(--space-2xs); font-size: var(--text-sm); color: var(--color-graphite-muted); }

/* ---- Close ---- */
.close { padding-block: var(--space-3xl); }
.close h2 { font-size: var(--text-marquee); font-weight: 600; letter-spacing: -0.045em; line-height: 0.95; max-width: 12ch; }
.close__actions { margin-top: var(--space-lg); display: flex; flex-wrap: wrap; gap: var(--space-xs); }

/* ---- Footer: statement ---- */
.foot { padding-block: var(--space-xl) var(--space-lg); display: grid; gap: var(--space-lg); }
.foot__line { font-family: var(--font-display); font-size: clamp(1.5rem, 4vw, 2.75rem); line-height: 1.05; letter-spacing: -0.03em; color: var(--color-ink); max-width: 26ch; }
.foot__meta { display: flex; justify-content: space-between; align-items: baseline; padding-top: var(--space-sm); border-top: var(--rule-hair) solid var(--color-rule); font-family: var(--font-mono); font-size: var(--text-xs); color: var(--color-muted); }

/* ---- Wider than phone ---- */
@media (min-width: 40rem) {
  .wrap, .nav__inner { padding-inline: var(--space-lg); }
  .nav .wordmark { font-size: var(--text-xl); }
  .hero__title { padding-inline: var(--space-lg); }
  .searchpill { min-width: 11rem; margin-left: var(--space-md); }
  .searchpill__text { display: inline; }
  .searchpill__kbd { display: inline-flex; margin-left: auto; }
  .nav__link--login { display: inline; }
  .why__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .steps { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (min-width: 56rem) {
  .nav__link { display: inline; }
  .doors__grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--space-lg); }
  .door { padding: var(--space-xl); }
  .why__grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .cell { padding: var(--space-lg); }
  .cell:first-child { grid-column: span 2; }
  .cell:last-child { grid-column: span 2; }
  .steps { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}

@media (prefers-reduced-motion: reduce) {
  .word__in, .cmdk__panel { animation: none; }
  .ticker__track { animation: none; width: auto; flex-wrap: wrap; }
  .ticker__set--dup { display: none; }
  .door, .cell, .btn, .searchpill { transition: none; }
  .door--creator:hover, .door--brand:hover { transform: none; }
  .btn:active { transform: none; }
}
</style>
