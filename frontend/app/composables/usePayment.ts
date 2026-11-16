// How a campaign pays the creator: one cash payment per creator, a free product, or both.
interface PaidProduct {
  flat_fee?: string | number | null
  free_sample?: boolean | null
}

export const hasPayment = (p: PaidProduct) => Number(p.flat_fee) > 0

export const paymentLabel = (p: PaidProduct) =>
  hasPayment(p)
    ? 'RM ' + Number(p.flat_fee).toLocaleString('en-MY', { maximumFractionDigits: 2 })
    : p.free_sample
      ? 'Free product'
      : '-'

export const paymentSub = (p: PaidProduct) =>
  hasPayment(p) ? (p.free_sample ? 'per creator + free sample' : 'per creator') : p.free_sample ? 'product only' : ''
