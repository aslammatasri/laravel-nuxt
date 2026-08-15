<script setup lang="ts">
definePageMeta({
  middleware: 'creator',
  layout:     'creator',
})

interface Product { id: number; name: string; image: string | null; images: string[]; description: string; price: string | number; category: string; commission_rate: string | number; commission_type: string; brand: { name: string } }

const config = useRuntimeConfig()
interface Paginated<T> { data: T[]; total: number; current_page: number }

const { $api } = useApi()

const filters = reactive({
  search:           '',
  category:         '',
  commission_type:  '',
  min_commission:   '',
  max_commission:   '',
  min_price:        '',
  max_price:        '',
  sort:             'commission_rate',
})

const showFilters = ref(true)

const { data: products, refresh } = await useAsyncData('marketplace',
  () => $api<Paginated<Product>>('/creator/marketplace', { params: filters }).catch(() => null)
)

const { data: categories } = await useAsyncData('marketplace-categories',
  () => $api<string[]>('/creator/marketplace/categories').catch(() => [])
)

let debounceTimer: ReturnType<typeof setTimeout> | null = null
watch(
  () => filters.search,
  () => {
    if (debounceTimer) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => refresh(), 300)
  }
)

watch(
  () => [
    filters.category,
    filters.commission_type,
    filters.min_commission,
    filters.max_commission,
    filters.min_price,
    filters.max_price,
    filters.sort,
  ],
  () => refresh()
)

function clearFilters() {
  filters.category = ''
  filters.commission_type = ''
  filters.min_commission = ''
  filters.max_commission = ''
  filters.min_price = ''
  filters.max_price = ''
  filters.sort = 'commission_rate'
}

function hasActiveFilters(): boolean {
  return !!(
    filters.category ||
    filters.commission_type ||
    filters.min_commission ||
    filters.max_commission ||
    filters.min_price ||
    filters.max_price ||
    filters.sort !== 'commission_rate'
  )
}

function activeFilterCount(): number {
  let count = 0
  if (filters.category) count++
  if (filters.commission_type) count++
  if (filters.min_commission) count++
  if (filters.max_commission) count++
  if (filters.min_price) count++
  if (filters.max_price) count++
  if (filters.sort !== 'commission_rate') count++
  return count
}

function formatCommission(product: Product): string {
  const rate = product.commission_rate
  if (product.commission_type === 'percentage') return `${rate}%`
  return `RM ${rate}`
}

const commissionColor = (rate: number | string) => {
  const num = Number(rate)
  if (num >= 20) return 'text-green-600 bg-green-50'
  if (num >= 10) return 'text-emerald-600 bg-emerald-50'
  return 'text-gray-600 bg-gray-50'
}
</script>

<template>
  <div class="mp-page">

    <!-- Header row -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="mp-title">Marketplace</h1>
        <p class="mp-subtitle">Find products to promote and earn commissions</p>
      </div>
      <button
        @click="showFilters = !showFilters"
        class="mp-toggle-btn"
        :class="{ 'mp-toggle-active': hasActiveFilters() }"
      >
        <span>🔍</span>
        Filters
        <span v-if="activeFilterCount()" class="mp-filter-badge">{{ activeFilterCount() }}</span>
        <span class="mp-toggle-chevron" :class="{ 'mp-chevron-open': showFilters }">▾</span>
      </button>
    </div>

    <!-- Search -->
    <div class="mp-search-wrap">
      <span class="mp-search-icon">🔍</span>
      <input
        v-model="filters.search"
        type="text"
        placeholder="Search products by name or description..."
        class="mp-search-input"
      />
      <button
        v-if="filters.search"
        @click="filters.search = ''"
        class="mp-search-clear"
      >✕</button>
    </div>

    <!-- Filter panel -->
    <Transition name="mp-slide">
      <div v-if="showFilters" class="mp-filters">
        <div class="mp-filters-grid">

          <div class="mp-filter-group">
            <label class="mp-filter-label">Category</label>
            <select v-model="filters.category" class="mp-select">
              <option value="">All Categories</option>
              <option v-for="cat in (categories || [])" :key="cat" :value="cat">
                {{ cat.charAt(0).toUpperCase() + cat.slice(1) }}
              </option>
            </select>
          </div>

          <div class="mp-filter-group">
            <label class="mp-filter-label">Commission Type</label>
            <select v-model="filters.commission_type" class="mp-select">
              <option value="">All Types</option>
              <option value="percentage">Percentage (%)</option>
              <option value="fixed">Fixed (RM)</option>
            </select>
          </div>

          <div class="mp-filter-group">
            <label class="mp-filter-label">Min Commission</label>
            <input v-model="filters.min_commission" type="number" min="0" placeholder="0" class="mp-input" />
          </div>

          <div class="mp-filter-group">
            <label class="mp-filter-label">Max Commission</label>
            <input v-model="filters.max_commission" type="number" min="0" placeholder="100" class="mp-input" />
          </div>

          <div class="mp-filter-group">
            <label class="mp-filter-label">Min Price (RM)</label>
            <input v-model="filters.min_price" type="number" min="0" placeholder="0" class="mp-input" />
          </div>

          <div class="mp-filter-group">
            <label class="mp-filter-label">Max Price (RM)</label>
            <input v-model="filters.max_price" type="number" min="0" placeholder="1000" class="mp-input" />
          </div>

          <div class="mp-filter-group">
            <label class="mp-filter-label">Sort By</label>
            <select v-model="filters.sort" class="mp-select">
              <option value="commission_rate">Highest Commission</option>
              <option value="created_at">Newest</option>
              <option value="price">Lowest Price</option>
              <option value="name">Name A-Z</option>
            </select>
          </div>

          <div class="mp-filter-group mp-filter-actions">
            <button
              v-if="hasActiveFilters()"
              @click="clearFilters"
              class="mp-clear-btn"
            >
              ✕ Clear all
            </button>
          </div>

        </div>

        <!-- Active filter chips -->
        <div v-if="hasActiveFilters()" class="mp-chips">
          <span v-if="filters.category" class="mp-chip mp-chip-removable">
            {{ filters.category }}
            <button @click="filters.category = ''" class="mp-chip-x">✕</button>
          </span>
          <span v-if="filters.commission_type" class="mp-chip mp-chip-removable">
            {{ filters.commission_type === 'percentage' ? '%' : 'RM' }}
            <button @click="filters.commission_type = ''" class="mp-chip-x">✕</button>
          </span>
          <span v-if="filters.min_commission" class="mp-chip">
            Min comm: {{ filters.min_commission }}
          </span>
          <span v-if="filters.max_commission" class="mp-chip">
            Max comm: {{ filters.max_commission }}
          </span>
          <span v-if="filters.min_price" class="mp-chip">
            Min price: RM {{ filters.min_price }}
          </span>
          <span v-if="filters.max_price" class="mp-chip">
            Max price: RM {{ filters.max_price }}
          </span>
        </div>
      </div>
    </Transition>

    <!-- Results bar -->
    <div class="mp-results-bar">
      <p v-if="products?.data?.length" class="mp-results-count">
        {{ products.total }} product{{ products.total === 1 ? '' : 's' }} found
      </p>
      <div v-else class="mp-results-count">No results</div>
    </div>

    <!-- Empty state -->
    <div v-if="!products?.data?.length" class="mp-empty">
      <div class="mp-empty-icon">🔍</div>
      <p class="mp-empty-title">No products found</p>
      <p class="mp-empty-desc">Try adjusting your filters or search terms</p>
    </div>

    <!-- Products grid -->
    <div v-else class="mp-grid">
      <div
        v-for="product in products?.data"
        :key="product.id"
        class="mp-card"
      >
        <!-- Image -->
        <div class="mp-card-img-wrap">
          <img
            v-if="product.images?.[0]"
            :src="`${config.public.storageBase}/${product.images[0]}`"
            :alt="product.name"
            class="mp-card-img"
          />
          <div v-else class="mp-card-img-placeholder">
            <span class="text-4xl">📦</span>
          </div>
          <!-- Commission badge -->
          <div class="mp-card-badge" :class="commissionColor(product.commission_rate)">
            {{ formatCommission(product) }}
          </div>
        </div>

        <div class="mp-card-body">

          <!-- Category tag -->
          <span class="mp-card-cat">{{ product.category }}</span>

          <!-- Name -->
          <h3 class="mp-card-name">{{ product.name }}</h3>

          <!-- Brand -->
          <p class="mp-card-brand">by {{ product.brand?.name }}</p>

          <!-- Description -->
          <p class="mp-card-desc">{{ product.description }}</p>

          <!-- Footer -->
          <div class="mp-card-footer">
            <div class="mp-card-price">
              <span class="mp-card-price-label">Price</span>
              <span class="mp-card-price-val">RM {{ product.price }}</span>
            </div>
            <NuxtLink
              :to="`/creator/marketplace/${product.id}`"
              class="mp-card-btn"
            >
              View & Apply →
            </NuxtLink>
          </div>

        </div>
      </div>
    </div>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

.mp-page {
  font-family: 'Inter', sans-serif;
}

/* ── Header ─────────────────────────────────────────────────── */
.mp-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #1a1a18;
  letter-spacing: -0.02em;
}

.mp-subtitle {
  font-size: 0.9rem;
  color: #888;
  margin-top: 0.15rem;
}

.mp-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  font-weight: 500;
  font-family: 'Inter', sans-serif;
  color: #666;
  background: #fff;
  border: 1px solid #e5e5e3;
  border-radius: 12px;
  padding: 0.55rem 1rem;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.mp-toggle-btn:hover {
  border-color: #FE2C55;
}

.mp-toggle-active {
  border-color: #FE2C55;
  color: #FE2C55;
  background: rgba(254,44,85,0.04);
}

.mp-filter-badge {
  background: #FE2C55;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 700;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.mp-toggle-chevron {
  font-size: 0.7rem;
  transition: transform 0.25s ease;
}

.mp-chevron-open {
  transform: rotate(180deg);
}

/* ── Search ─────────────────────────────────────────────────── */
.mp-search-wrap {
  position: relative;
  margin-bottom: 1rem;
}

.mp-search-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  font-size: 1rem;
  pointer-events: none;
}

.mp-search-input {
  width: 100%;
  border: 1.5px solid #e5e5e3;
  border-radius: 14px;
  padding: 0.85rem 2.5rem 0.85rem 2.75rem;
  font-size: 0.9rem;
  font-family: 'Inter', sans-serif;
  color: #1a1a18;
  background: #fff;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

.mp-search-input:focus {
  border-color: #FE2C55;
  box-shadow: 0 0 0 4px rgba(254,44,85,0.08);
}

.mp-search-input::placeholder {
  color: #bbb;
}

.mp-search-clear {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #bbb;
  cursor: pointer;
  font-size: 0.9rem;
  padding: 0.25rem;
  transition: color 0.2s;
}

.mp-search-clear:hover {
  color: #666;
}

/* ── Filters ────────────────────────────────────────────────── */
.mp-filters {
  background: #fff;
  border: 1px solid #e5e5e3;
  border-radius: 16px;
  padding: 1.25rem;
  margin-bottom: 1rem;
}

.mp-filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 0.75rem;
}

.mp-filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.mp-filter-actions {
  justify-content: flex-end;
}

.mp-filter-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #888;
  letter-spacing: 0.01em;
}

.mp-select,
.mp-input {
  width: 100%;
  border: 1.5px solid #e5e5e3;
  border-radius: 10px;
  padding: 0.55rem 0.75rem;
  font-size: 0.85rem;
  font-family: 'Inter', sans-serif;
  color: #1a1a18;
  background: #fafafa;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}

.mp-select:focus,
.mp-input:focus {
  border-color: #FE2C55;
  background: #fff;
  box-shadow: 0 0 0 3px rgba(254,44,85,0.08);
}

.mp-select::placeholder,
.mp-input::placeholder {
  color: #bbb;
}

.mp-clear-btn {
  font-size: 0.8rem;
  font-weight: 500;
  font-family: 'Inter', sans-serif;
  color: #888;
  background: #f5f5f5;
  border: none;
  border-radius: 10px;
  padding: 0.55rem 1rem;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
  align-self: flex-end;
  white-space: nowrap;
}

.mp-clear-btn:hover {
  background: #fef2f2;
  color: #dc2626;
}

/* Filter animation */
.mp-slide-enter-active,
.mp-slide-leave-active {
  transition: all 0.25s ease;
}

.mp-slide-enter-from,
.mp-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* ── Chips ──────────────────────────────────────────────────── */
.mp-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #f0f0ee;
}

.mp-chip {
  font-size: 0.75rem;
  font-weight: 500;
  color: #666;
  background: #f5f5f5;
  padding: 0.25rem 0.65rem;
  border-radius: 100px;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
}

.mp-chip-removable {
  background: rgba(254,44,85,0.08);
  color: #FE2C55;
}

.mp-chip-x {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  font-size: 0.7rem;
  padding: 0;
  line-height: 1;
  opacity: 0.6;
  transition: opacity 0.2s;
}

.mp-chip-x:hover {
  opacity: 1;
}

/* ── Results bar ────────────────────────────────────────────── */
.mp-results-bar {
  margin-bottom: 1.25rem;
}

.mp-results-count {
  font-size: 0.85rem;
  color: #888;
}

/* ── Empty state ────────────────────────────────────────────── */
.mp-empty {
  text-align: center;
  padding: 4rem 2rem;
}

.mp-empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.mp-empty-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #666;
  margin-bottom: 0.3rem;
}

.mp-empty-desc {
  font-size: 0.85rem;
  color: #aaa;
}

/* ── Product grid ──────────────────────────────────────────── */
.mp-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.25rem;
}

.mp-card {
  background: #fff;
  border: 1px solid #e5e5e3;
  border-radius: 18px;
  overflow: hidden;
  transition: transform 0.25s ease, box-shadow 0.3s ease;
}

.mp-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.06);
}

/* Image */
.mp-card-img-wrap {
  position: relative;
  height: 180px;
  overflow: hidden;
  background: #f5f5f5;
}

.mp-card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.4s ease;
}

.mp-card:hover .mp-card-img {
  transform: scale(1.05);
}

.mp-card-img-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #f5f5f5, #eaeaea);
}

/* Commission badge on image */
.mp-card-badge {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  font-size: 0.8rem;
  font-weight: 700;
  padding: 0.3rem 0.7rem;
  border-radius: 10px;
  backdrop-filter: blur(4px);
}

/* Body */
.mp-card-body {
  padding: 1.1rem 1.25rem 1.25rem;
}

.mp-card-cat {
  display: inline-block;
  font-size: 0.7rem;
  font-weight: 600;
  color: #888;
  background: #f5f5f5;
  padding: 0.15rem 0.55rem;
  border-radius: 100px;
  text-transform: capitalize;
  margin-bottom: 0.4rem;
}

.mp-card-name {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a18;
  line-height: 1.3;
  margin-bottom: 0.15rem;
  letter-spacing: -0.01em;
}

.mp-card-brand {
  font-size: 0.8rem;
  color: #999;
  margin-bottom: 0.6rem;
}

.mp-card-desc {
  font-size: 0.825rem;
  color: #888;
  line-height: 1.55;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  margin-bottom: 1rem;
}

/* Footer */
.mp-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.mp-card-price {
  display: flex;
  flex-direction: column;
}

.mp-card-price-label {
  font-size: 0.7rem;
  color: #bbb;
  font-weight: 500;
}

.mp-card-price-val {
  font-size: 1rem;
  font-weight: 700;
  color: #1a1a18;
}

.mp-card-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  background: #FE2C55;
  color: #fff;
  font-size: 0.8rem;
  font-weight: 600;
  font-family: 'Inter', sans-serif;
  padding: 0.6rem 1.1rem;
  border-radius: 12px;
  text-decoration: none;
  white-space: nowrap;
  transition: transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 2px 12px rgba(254,44,85,0.2);
}

.mp-card-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(254,44,85,0.35);
}

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 768px) {
  .mp-grid {
    grid-template-columns: 1fr;
  }

  .mp-filters-grid {
    grid-template-columns: 1fr 1fr;
  }

  .mp-filter-actions {
    grid-column: span 2;
    flex-direction: row;
  }
}

@media (max-width: 480px) {
  .mp-filters-grid {
    grid-template-columns: 1fr;
  }

  .mp-filter-actions {
    grid-column: span 1;
  }
}
</style>