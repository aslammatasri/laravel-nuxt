export const PLATFORMS = [
  { value: 'youtube', label: 'YouTube' },
  { value: 'tiktok', label: 'TikTok' },
  { value: 'instagram', label: 'Instagram' },
]

export const CONTENT_TYPES = [
  { value: 'review', label: 'Review' },
  { value: 'unboxing', label: 'Unboxing' },
  { value: 'tutorial', label: 'Tutorial' },
  { value: 'short_video', label: 'Short video' },
  { value: 'lifestyle', label: 'Lifestyle' },
]

export const NICHES = ['beauty', 'fashion', 'food', 'tech', 'lifestyle', 'health', 'home', 'sports']

export const PLATFORM_LABELS: Record<string, string> = Object.fromEntries(PLATFORMS.map((p) => [p.value, p.label]))
export const CONTENT_TYPE_LABELS: Record<string, string> = Object.fromEntries(CONTENT_TYPES.map((c) => [c.value, c.label]))

export interface CampaignExtras {
  platforms: string[]
  content_types: string[]
  posts_required: string
  brief: string
  hashtags: string
  product_url: string
  min_subscribers: string
  target_niches: string[]
  free_sample: boolean
  flat_fee: string
  application_deadline: string
}

export const emptyExtras = (): CampaignExtras => ({
  platforms: [],
  content_types: [],
  posts_required: '1',
  brief: '',
  hashtags: '',
  product_url: '',
  min_subscribers: '',
  target_niches: [],
  free_sample: false,
  flat_fee: '',
  application_deadline: '',
})

/** Build the extra campaign fields from an API product (edit page). */
export function extrasFrom(p: any): CampaignExtras {
  const str = (v: unknown) => (v === null || v === undefined ? '' : String(v))
  return {
    platforms: Array.isArray(p?.platforms) ? [...p.platforms] : [],
    content_types: Array.isArray(p?.content_types) ? [...p.content_types] : [],
    posts_required: str(p?.posts_required ?? 1),
    brief: str(p?.brief),
    hashtags: str(p?.hashtags),
    product_url: str(p?.product_url),
    min_subscribers: str(p?.min_subscribers),
    target_niches: Array.isArray(p?.target_niches) ? [...p.target_niches] : [],
    free_sample: !!p?.free_sample,
    flat_fee: str(p?.flat_fee),
    application_deadline: str(p?.application_deadline).slice(0, 10),
  }
}

// Optional fields that the edit page must be able to clear
const CLEARABLE = ['brief', 'hashtags', 'product_url', 'min_subscribers', 'flat_fee', 'application_deadline', 'max_affiliates']

/** Put a form object into FormData (arrays as key[], booleans as 1/0). */
export function appendFormData(body: FormData, form: Record<string, any>, opts: { clearEmpty?: boolean } = {}) {
  for (const [key, value] of Object.entries(form)) {
    if (Array.isArray(value)) {
      if (!value.length) {
        if (opts.clearEmpty) body.append(key, '')
      } else {
        value.forEach((v) => body.append(`${key}[]`, String(v)))
      }
    } else if (typeof value === 'boolean') {
      body.append(key, value ? '1' : '0')
    } else if (value === '' || value === null || value === undefined) {
      if (opts.clearEmpty && CLEARABLE.includes(key)) body.append(key, '')
    } else {
      body.append(key, String(value))
    }
  }
}

/** Client-side checks for the extra fields. Returns field -> message. */
export function validateExtras(f: CampaignExtras, campaignEnd: string): Record<string, string> {
  const e: Record<string, string> = {}

  if (f.product_url.trim() && !/^https?:\/\/\S+\.\S+/i.test(f.product_url.trim()))
    e.product_url = 'Enter a full link, for example https://shop.example.com/product.'

  const posts = Number(f.posts_required)
  if (!Number.isInteger(posts) || posts < 1 || posts > 20)
    e.posts_required = 'Use a number from 1 to 20.'

  if (f.min_subscribers !== '' && (!Number.isInteger(Number(f.min_subscribers)) || Number(f.min_subscribers) < 0))
    e.min_subscribers = 'Use a whole number, or leave empty for no minimum.'

  if (f.flat_fee !== '' && Number(f.flat_fee) < 0) e.flat_fee = 'Flat fee cannot be negative.'

  if (f.application_deadline && campaignEnd && f.application_deadline > campaignEnd.slice(0, 10))
    e.application_deadline = 'Applications must close on or before the campaign end date.'

  if (f.brief.length > 3000) e.brief = 'The brief is too long (max 3000 characters).'

  return e
}
