const GIS_SRC = 'https://accounts.google.com/gsi/client'

let scriptPromise: Promise<void> | null = null

function loadGis(): Promise<void> {
  if (scriptPromise) return scriptPromise

  scriptPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = GIS_SRC
    script.async = true
    script.onload = () => resolve()
    script.onerror = () => reject(new Error('Failed to load Google Identity Services'))
    document.head.appendChild(script)
  })

  return scriptPromise
}

export const useGoogleAuth = () => {
  const config = useRuntimeConfig()

  async function connectYoutube(): Promise<{ code: string }> {
    await loadGis()

    return new Promise((resolve, reject) => {
      const client = (window as any).google.accounts.oauth2.initCodeClient({
        client_id: config.public.googleClientId,
        scope: 'https://www.googleapis.com/auth/youtube.readonly email',
        ux_mode: 'popup',
        callback: (response: any) => {
          if (response.error) {
            reject(new Error('Google sign-in was cancelled or failed'))
            return
          }
          resolve({ code: response.code })
        },
      })

      client.requestCode()
    })
  }

  return { connectYoutube }
}
