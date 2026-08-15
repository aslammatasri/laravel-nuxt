<script setup lang="ts">
const authStore = useAuth()

const features = [
  {
    icon: '💰',
    title: 'High Commission Rates',
    desc: 'Find products offering up to 30% commission. Filter by rate, category and niche to find your perfect match.',
  },
  {
    icon: '🏪',
    title: 'Verified Malaysian Brands',
    desc: 'All brands are SSM-verified Malaysian businesses. No scams, no fake products — just real opportunities.',
  },
  {
    icon: '📺',
    title: 'Real Channel Stats',
    desc: 'Connect your YouTube channel and let brands see your real subscribers, views and engagement — no more screenshotting analytics.',
  },
  {
    icon: '📊',
    title: 'Track Your Earnings',
    desc: 'Log your sales, track commissions and see your monthly earnings growth — all in one clean dashboard.',
  },
  {
    icon: '🤝',
    title: 'Direct Brand Connection',
    desc: 'Message brands directly, negotiate terms and build long-term affiliate partnerships.',
  },
  {
    icon: '⚡',
    title: 'Apply in Minutes',
    desc: 'Browse products, write your pitch and submit your application in under 5 minutes. No complicated forms.',
  },
]

const steps = [
  {
    num: '01',
    role: 'Creator',
    title: 'Create your profile',
    desc: 'Sign up as a creator, tell us your niche and connect your YouTube channel to build your portfolio.',
    color: 'accent',
  },
  {
    num: '02',
    role: 'Creator',
    title: 'Browse & apply',
    desc: 'Find products that match your content style. Write a pitch and apply in minutes.',
    color: 'accent',
  },
  {
    num: '03',
    role: 'Brand',
    title: 'Review applications',
    desc: 'Brands review creator profiles, real channel stats and pitches, then approve the best fit.',
    color: 'neutral',
  },
  {
    num: '04',
    role: 'Creator',
    title: 'Promote & earn',
    desc: 'Get your unique tracking link, start promoting and watch your commissions grow.',
    color: 'accent',
  },
]

const stats = [
  { value: '2,400+', label: 'Active Creators' },
  { value: '380+',   label: 'Malaysian Brands' },
  { value: 'RM 1.2M', label: 'Commissions Paid' },
  { value: '18%',    label: 'Avg Commission Rate' },
]
</script>

<template>
  <div class="affiliatemy-landing">

    <!-- ── Navbar ─────────────────────────────────────────────── -->
    <nav class="nav">
      <div class="nav-inner">
        <span class="logo">AffiliateMY</span>
        <div class="nav-links">
          <a href="#features">Features</a>
          <a href="#how-it-works">How It Works</a>
          <a href="#stats">Stats</a>
        </div>
        <div class="nav-cta">
          <NuxtLink v-if="authStore.isLoggedIn" :to="authStore.isBrand ? '/brand/dashboard' : '/creator/dashboard'" class="btn-nav-solid">
            Go to Dashboard
          </NuxtLink>
          <template v-else>
            <NuxtLink to="/auth/login" class="btn-nav-ghost">Log in</NuxtLink>
            <NuxtLink to="/auth/register-type" class="btn-nav-solid">Get Started</NuxtLink>
          </template>
        </div>
      </div>
    </nav>

    <!-- ── Hero ──────────────────────────────────────────────── -->
    <section class="hero">
      <div class="hero-bg-grid"></div>
      <div class="hero-blob hero-blob-1"></div>
      <div class="hero-blob hero-blob-2"></div>

      <div class="hero-inner">
        <div class="hero-badge">🇲🇾 Malaysia's Creator-Brand Marketplace</div>

        <h1 class="hero-title">
          Turn your content<br/>
          into a <span class="hero-accent">revenue stream</span>
        </h1>

        <p class="hero-sub">
          AffiliateMY connects Malaysian creators with local brands.<br/>
          Showcase your YouTube channel stats, apply to high-commission products, and start earning
          — with TikTok, Instagram and more platforms on the way.
        </p>

        <div class="hero-actions">
          <NuxtLink to="/auth/register-type?role=creator" class="btn-hero-primary">
            Start as Creator
            <span class="btn-arrow">→</span>
          </NuxtLink>
          <NuxtLink to="/auth/register-type?role=brand" class="btn-hero-secondary">
            List Your Products
          </NuxtLink>
        </div>

        <div class="hero-proof">
          <div class="proof-avatars">
            <div class="avatar av1">A</div>
            <div class="avatar av2">N</div>
            <div class="avatar av3">S</div>
            <div class="avatar av4">R</div>
            <div class="avatar av5">F</div>
          </div>
          <span class="proof-text">Join <strong>2,400+</strong> creators already earning</span>
        </div>
      </div>

      <!-- Hero card floating -->
      <div class="hero-card-wrap">
        <div class="hero-card">
          <div class="card-header">
            <span class="card-dot"></span>
            <span class="card-label">YouTube channel connected</span>
          </div>
          <div class="card-product">
            <div class="card-product-img">📺</div>
            <div>
              <p class="card-product-name">Aiman Reviews</p>
              <p class="card-product-brand">143K subscribers</p>
            </div>
            <span class="card-commission">3.4% eng.</span>
          </div>
          <div class="card-stat-row">
            <div class="card-stat">
              <p class="card-stat-val">2.1M</p>
              <p class="card-stat-lbl">Avg views</p>
            </div>
            <div class="card-stat">
              <p class="card-stat-val">143K</p>
              <p class="card-stat-lbl">Subscribers</p>
            </div>
            <div class="card-stat">
              <p class="card-stat-val">RM 847</p>
              <p class="card-stat-lbl">Earned</p>
            </div>
          </div>
          <div class="card-bar-wrap">
            <div class="card-bar-label">
              <span>Profile completeness</span>
              <span>84%</span>
            </div>
            <div class="card-bar-track">
              <div class="card-bar-fill" style="width: 84%"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Stats ─────────────────────────────────────────────── -->
    <section class="stats-strip" id="stats">
      <div class="stats-inner">
        <div v-for="stat in stats" :key="stat.label" class="stat-item">
          <span class="stat-value">{{ stat.value }}</span>
          <span class="stat-label">{{ stat.label }}</span>
        </div>
      </div>
    </section>

    <!-- ── Features ──────────────────────────────────────────── -->
    <section class="features" id="features">
      <div class="section-inner">
        <div class="section-tag">Why AffiliateMY</div>
        <h2 class="section-title">Everything you need to<br/><span class="section-accent">grow your affiliate income</span></h2>
        <p class="section-sub">No more digging through WhatsApp groups or DM-ing brands manually. AffiliateMY gives you a professional platform to find, apply and manage all your affiliate deals.</p>

        <div class="features-grid">
          <div v-for="feature in features" :key="feature.title" class="feature-card">
            <div class="feature-icon">{{ feature.icon }}</div>
            <h3 class="feature-title">{{ feature.title }}</h3>
            <p class="feature-desc">{{ feature.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── How It Works ───────────────────────────────────────── -->
    <section class="how" id="how-it-works">
      <div class="section-inner">
        <div class="section-tag">Simple Process</div>
        <h2 class="section-title">From sign up to<br/><span class="section-accent">first commission</span></h2>

        <div class="steps-grid">
          <div v-for="step in steps" :key="step.num" class="step-card" :class="`step-${step.color}`">
            <div class="step-num">{{ step.num }}</div>
            <span class="step-role" :class="`role-${step.color}`">{{ step.role }}</span>
            <h3 class="step-title">{{ step.title }}</h3>
            <p class="step-desc">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ── Two Sided CTA ─────────────────────────────────────── -->
    <section class="dual-cta">
      <div class="section-inner">
        <div class="dual-cta-grid">

          <div class="cta-card cta-creator">
            <div class="cta-emoji">🎥</div>
            <h3 class="cta-title">For Creators</h3>
            <p class="cta-desc">Browse hundreds of Malaysian products with high commissions. Connect your YouTube channel and start promoting and earning today.</p>
            <ul class="cta-list">
              <li>✓ Free to join</li>
              <li>✓ No minimum followers</li>
              <li>✓ Weekly commission payouts</li>
            </ul>
            <NuxtLink to="/auth/register-type?role=creator" class="cta-btn cta-btn-primary">
              Join as Creator →
            </NuxtLink>
          </div>

          <div class="cta-card cta-brand">
            <div class="cta-emoji">🏪</div>
            <h3 class="cta-title">For Brands</h3>
            <p class="cta-desc">Find the right creators for your products, backed by real channel stats. Set your commission and only pay for results.</p>
            <ul class="cta-list">
              <li>✓ Only pay per sale</li>
              <li>✓ Full creator profiles & real stats</li>
              <li>✓ Campaign analytics</li>
            </ul>
            <NuxtLink to="/auth/register-type?role=brand" class="cta-btn cta-btn-neutral">
              List Your Products →
            </NuxtLink>
          </div>

        </div>
      </div>
    </section>

    <!-- ── Footer ────────────────────────────────────────────── -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-brand">
          <span class="logo">AffiliateMY</span>
          <p class="footer-tagline">Malaysia's Creator-Brand Marketplace</p>
        </div>
        <div class="footer-links">
          <NuxtLink to="/auth/login">Login</NuxtLink>
          <NuxtLink to="/auth/register-type">Sign Up</NuxtLink>
          <a href="#features">Features</a>
          <a href="#how-it-works">How It Works</a>
        </div>
        <p class="footer-copy">© 2026 AffiliateMY. Made with ❤️ in Malaysia.</p>
      </div>
    </footer>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

/* Neutral, platform-agnostic palette: slate + a single indigo accent */

.affiliatemy-landing {
  font-family: 'Inter', sans-serif;
  background: #f8f7f4;
  color: #1a1a18;
  min-height: 100vh;
}

/* ── Nav ──────────────────────────────────────────────────── */
.nav {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(248, 247, 244, 0.9);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(0,0,0,0.06);
}
.nav-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  height: 64px;
  display: flex;
  align-items: center;
  gap: 2rem;
}
.logo {
  font-family: 'Inter', sans-serif;
  font-weight: 800;
  font-size: 1.25rem;
  color: #1a1a18;
  letter-spacing: -0.02em;
}
.nav-links {
  display: flex;
  gap: 1.75rem;
  flex: 1;
}
.nav-links a {
  font-size: 0.875rem;
  color: #666;
  text-decoration: none;
  transition: color 0.2s;
}
.nav-links a:hover { color: #4f46e5; }
.nav-cta { display: flex; gap: 0.75rem; align-items: center; }
.btn-nav-ghost {
  font-size: 0.875rem;
  color: #444;
  text-decoration: none;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  transition: background 0.2s;
}
.btn-nav-ghost:hover { background: rgba(79,70,229,0.06); }
.btn-nav-solid {
  font-size: 0.875rem;
  font-weight: 500;
  color: #fff;
  background: #4f46e5;
  text-decoration: none;
  padding: 0.5rem 1.25rem;
  border-radius: 10px;
  transition: opacity 0.2s;
}
.btn-nav-solid:hover { opacity: 0.85; }

/* ── Hero ──────────────────────────────────────────────────── */
.hero {
  position: relative;
  min-height: 92vh;
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: center;
  gap: 4rem;
  max-width: 1200px;
  margin: 0 auto;
  padding: 5rem 2rem 4rem;
  overflow: hidden;
}
.hero-bg-grid {
  position: absolute;
  inset: 0;
  background-image: linear-gradient(rgba(0,0,0,0.04) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(0,0,0,0.04) 1px, transparent 1px);
  background-size: 40px 40px;
  pointer-events: none;
}
.hero-blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.hero-blob-1 {
  width: 500px; height: 500px;
  background: rgba(79, 70, 229, 0.1);
  top: -100px; right: -100px;
}
.hero-blob-2 {
  width: 300px; height: 300px;
  background: rgba(71, 85, 105, 0.08);
  bottom: 0; left: 100px;
}
.hero-inner { position: relative; z-index: 1; }
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #fff;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 100px;
  padding: 0.35rem 1rem;
  font-size: 0.8rem;
  font-weight: 500;
  color: #444;
  margin-bottom: 1.75rem;
  animation: fadeUp 0.6s ease both;
}
.hero-title {
  font-family: 'Inter', sans-serif;
  font-size: clamp(2.5rem, 5vw, 3.75rem);
  font-weight: 800;
  line-height: 1.08;
  letter-spacing: -0.03em;
  color: #1a1a18;
  margin-bottom: 1.5rem;
  animation: fadeUp 0.6s ease 0.1s both;
}
.hero-accent {
  color: #4f46e5;
  position: relative;
}
.hero-accent::after {
  content: '';
  position: absolute;
  bottom: 2px;
  left: 0;
  width: 100%;
  height: 3px;
  background: #4f46e5;
  border-radius: 2px;
  opacity: 0.3;
}
.hero-sub {
  font-size: 1.05rem;
  color: #666;
  line-height: 1.7;
  margin-bottom: 2.25rem;
  font-weight: 300;
  animation: fadeUp 0.6s ease 0.2s both;
}
.hero-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 2.5rem;
  animation: fadeUp 0.6s ease 0.3s both;
}
.btn-hero-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #4f46e5;
  color: #fff;
  text-decoration: none;
  padding: 0.85rem 1.75rem;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 500;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 20px rgba(79, 70, 229, 0.35);
}
.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 28px rgba(79, 70, 229, 0.5);
}
.btn-arrow { transition: transform 0.2s; }
.btn-hero-primary:hover .btn-arrow { transform: translateX(3px); }
.btn-hero-secondary {
  display: inline-flex;
  align-items: center;
  background: #fff;
  color: #1a1a18;
  text-decoration: none;
  padding: 0.85rem 1.75rem;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 500;
  border: 1px solid rgba(0,0,0,0.1);
  transition: border-color 0.2s, background 0.2s;
}
.btn-hero-secondary:hover { border-color: rgba(0,0,0,0.2); background: #f0f0ee; }
.hero-proof {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  animation: fadeUp 0.6s ease 0.4s both;
}
.proof-avatars { display: flex; }
.avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  border: 2px solid #f8f7f4;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 700; color: #fff;
  margin-left: -8px;
}
.avatar:first-child { margin-left: 0; }
.av1 { background: #4f46e5; }
.av2 { background: #0d9488; }
.av3 { background: #475569; }
.av4 { background: #d97706; }
.av5 { background: #7c3aed; }
.proof-text { font-size: 0.85rem; color: #666; }
.proof-text strong { color: #1a1a18; }

/* Hero card */
.hero-card-wrap {
  position: relative;
  z-index: 1;
  animation: fadeUp 0.7s ease 0.2s both;
}
.hero-card {
  background: #fff;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 20px 60px rgba(0,0,0,0.08);
  max-width: 380px;
  margin-left: auto;
}
.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}
.card-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79,70,229,0.2);
}
.card-label { font-size: 0.8rem; font-weight: 500; color: #666; }
.card-product {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f8f7f4;
  border-radius: 12px;
  margin-bottom: 1.25rem;
}
.card-product-img { font-size: 1.75rem; }
.card-product-name { font-size: 0.875rem; font-weight: 600; color: #1a1a18; }
.card-product-brand { font-size: 0.75rem; color: #999; margin-top: 2px; }
.card-commission {
  margin-left: auto;
  background: rgba(79,70,229,0.1);
  color: #4f46e5;
  font-size: 0.875rem;
  font-weight: 700;
  padding: 0.3rem 0.6rem;
  border-radius: 8px;
}
.card-stat-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}
.card-stat {
  background: #f8f7f4;
  border-radius: 10px;
  padding: 0.75rem 0.5rem;
  text-align: center;
}
.card-stat-val { font-size: 0.9rem; font-weight: 700; color: #1a1a18; }
.card-stat-lbl { font-size: 0.7rem; color: #999; margin-top: 2px; }
.card-bar-wrap {}
.card-bar-label {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #999;
  margin-bottom: 0.5rem;
}
.card-bar-track {
  height: 6px;
  background: #f0f0ee;
  border-radius: 3px;
  overflow: hidden;
}
.card-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #4f46e5, #818cf8);
  border-radius: 3px;
  transition: width 1s ease;
}

/* ── Stats Strip ────────────────────────────────────────────── */
.stats-strip {
  background: #0f172a;
  padding: 2.5rem 0;
}
.stats-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2rem;
  text-align: center;
}
.stat-item { display: flex; flex-direction: column; gap: 0.25rem; }
.stat-value {
  font-family: 'Inter', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  color: #818cf8;
  letter-spacing: -0.02em;
}
.stat-label { font-size: 0.85rem; color: #94a3b8; }

/* ── Sections shared ───────────────────────────────────────── */
.section-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 5rem 2rem;
  text-align: center;
}
.section-tag {
  display: inline-block;
  background: rgba(79,70,229,0.08);
  color: #4f46e5;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.3rem 0.9rem;
  border-radius: 100px;
  margin-bottom: 1.25rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
.section-title {
  font-family: 'Inter', sans-serif;
  font-size: clamp(1.75rem, 3.5vw, 2.75rem);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.15;
  color: #1a1a18;
  margin-bottom: 1rem;
}
.section-accent { color: #4f46e5; }
.section-sub {
  font-size: 1rem;
  color: #666;
  line-height: 1.7;
  max-width: 600px;
  margin: 0 auto 3.5rem;
  font-weight: 300;
}

/* ── Features ───────────────────────────────────────────────── */
.features { background: #fff; }
.features-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
  text-align: left;
}
.feature-card {
  background: #f8f7f4;
  border-radius: 18px;
  padding: 1.75rem;
  transition: transform 0.2s, box-shadow 0.2s;
}
.feature-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 40px rgba(79,70,229,0.08);
}
.feature-icon { font-size: 1.75rem; margin-bottom: 1rem; }
.feature-title {
  font-family: 'Inter', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a18;
  margin-bottom: 0.5rem;
}
.feature-desc { font-size: 0.875rem; color: #666; line-height: 1.65; font-weight: 300; }

/* ── How It Works ───────────────────────────────────────────── */
.how { background: #f8f7f4; }
.steps-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.5rem;
  text-align: left;
}
.step-card {
  background: #fff;
  border-radius: 18px;
  padding: 1.75rem;
  border: 1px solid rgba(0,0,0,0.06);
  transition: transform 0.2s;
}
.step-card:hover { transform: translateY(-3px); }
.step-num {
  font-family: 'Inter', sans-serif;
  font-size: 2.5rem;
  font-weight: 800;
  line-height: 1;
  margin-bottom: 0.75rem;
  color: #e5e5e3;
}
.step-accent .step-num  { color: rgba(79,70,229,0.25); }
.step-neutral .step-num { color: rgba(71,85,105,0.3); }
.step-role {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 100px;
  margin-bottom: 0.75rem;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}
.role-accent  { background: rgba(79,70,229,0.08); color: #4f46e5; }
.role-neutral { background: rgba(71,85,105,0.1); color: #475569; }
.step-title {
  font-family: 'Inter', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a18;
  margin-bottom: 0.5rem;
}
.step-desc { font-size: 0.875rem; color: #666; line-height: 1.65; font-weight: 300; }

/* ── Dual CTA ───────────────────────────────────────────────── */
.dual-cta { background: #fff; }
.dual-cta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  text-align: left;
}
.cta-card {
  border-radius: 24px;
  padding: 2.5rem;
  border: 1px solid rgba(0,0,0,0.06);
}
.cta-creator { background: rgba(79,70,229,0.04); }
.cta-brand   { background: rgba(71,85,105,0.05); }
.cta-emoji { font-size: 2.5rem; margin-bottom: 1rem; }
.cta-title {
  font-family: 'Inter', sans-serif;
  font-size: 1.5rem;
  font-weight: 800;
  color: #1a1a18;
  margin-bottom: 0.75rem;
  letter-spacing: -0.02em;
}
.cta-desc { font-size: 0.95rem; color: #666; line-height: 1.65; margin-bottom: 1.25rem; font-weight: 300; }
.cta-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}
.cta-list li { font-size: 0.875rem; color: #444; font-weight: 500; }
.cta-btn {
  display: inline-block;
  text-decoration: none;
  padding: 0.85rem 1.75rem;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 500;
  transition: transform 0.2s, box-shadow 0.2s;
}
.cta-btn:hover { transform: translateY(-2px); }
.cta-btn-primary {
  background: #4f46e5;
  color: #fff;
  box-shadow: 0 4px 16px rgba(79,70,229,0.3);
}
.cta-btn-primary:hover { box-shadow: 0 8px 24px rgba(79,70,229,0.45); }
.cta-btn-neutral {
  background: #0f172a;
  color: #fff;
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
}
.cta-btn-neutral:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.3); }

/* ── Footer ─────────────────────────────────────────────────── */
.footer { background: #0f172a; padding: 3rem 0; }
.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1.5rem;
}
.footer-brand { display: flex; flex-direction: column; gap: 0.25rem; }
.footer-brand .logo { color: #fff; }
.footer-tagline { font-size: 0.8rem; color: #94a3b8; }
.footer-links { display: flex; gap: 1.5rem; }
.footer-links a {
  font-size: 0.875rem;
  color: #94a3b8;
  text-decoration: none;
  transition: color 0.2s;
}
.footer-links a:hover { color: #818cf8; }
.footer-copy { font-size: 0.8rem; color: #64748b; }

/* ── Animations ─────────────────────────────────────────────── */
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 768px) {
  .hero { grid-template-columns: 1fr; padding: 3rem 1.5rem; }
  .hero-card-wrap { display: none; }
  .stats-inner { grid-template-columns: repeat(2, 1fr); }
  .features-grid { grid-template-columns: 1fr; }
  .steps-grid { grid-template-columns: repeat(2, 1fr); }
  .dual-cta-grid { grid-template-columns: 1fr; }
  .nav-links { display: none; }
  .footer-inner { flex-direction: column; text-align: center; }
}
</style>
