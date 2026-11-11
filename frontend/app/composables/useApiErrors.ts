export interface ParsedApiError {
  /** One-line message for the top banner */
  message: string
  /** First message per form field (image errors collapse into `images`) */
  fields: Record<string, string>
  /** Every distinct message, for forms that only show a banner */
  all: string[]
}

export function parseApiError(err: any, fallback = 'Something went wrong. Please try again.'): ParsedApiError {
  const status: number | undefined = err?.status ?? err?.response?.status
  const data = err?.data

  if (!status) {
    return {
      message: 'Cannot reach the server. Check your internet connection and try again.',
      fields: {},
      all: [],
    }
  }

  if (status === 413) {
    return {
      message: 'The upload is too large. Remove some images or use smaller ones (max 2 MB each).',
      fields: { images: 'The images are too large in total.' },
      all: [],
    }
  }

  if (status === 422 && data?.errors) {
    const fields: Record<string, string> = {}
    const all: string[] = []

    for (const [key, msgs] of Object.entries<string[]>(data.errors)) {
      const msg = msgs?.[0]
      if (!msg) continue

      const imageMatch = key.match(/^images\.(\d+)$/)
      const field = imageMatch ? 'images' : key
      const text = imageMatch ? `Image ${Number(imageMatch[1]) + 1}: ${msg}` : msg

      if (!fields[field]) fields[field] = text
      if (!all.includes(text)) all.push(text)
    }

    return { message: 'Please fix the highlighted fields.', fields, all }
  }

  if (status === 403) return { message: data?.message || 'You are not allowed to do that.', fields: {}, all: [] }
  if (status >= 500) return { message: 'Something went wrong on our side. Please try again in a moment.', fields: {}, all: [] }

  return { message: data?.message || fallback, fields: {}, all: [] }
}

export const MAX_IMAGE_BYTES = 2 * 1024 * 1024
export const MAX_TOTAL_IMAGE_BYTES = 15 * 1024 * 1024
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp']

const mb = (bytes: number) => (bytes / 1024 / 1024).toFixed(1)

/** Split picked files into accepted ones and human-readable problems. */
export function checkImageFiles(files: File[], existingBytes = 0) {
  const accepted: File[] = []
  const problems: string[] = []
  let total = existingBytes

  for (const f of files) {
    if (!ALLOWED_IMAGE_TYPES.includes(f.type)) {
      problems.push(`"${f.name}" is not a JPG, PNG or WEBP image.`)
    } else if (f.size > MAX_IMAGE_BYTES) {
      problems.push(`"${f.name}" is ${mb(f.size)} MB. Each image must be 2 MB or smaller.`)
    } else if (total + f.size > MAX_TOTAL_IMAGE_BYTES) {
      problems.push(`"${f.name}" was skipped: total image size must stay under ${mb(MAX_TOTAL_IMAGE_BYTES)} MB.`)
    } else {
      accepted.push(f)
      total += f.size
    }
  }

  return { accepted, problems }
}
