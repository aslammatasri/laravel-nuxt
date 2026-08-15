export const useApi = () => {
  const config      = useRuntimeConfig()
  const tokenCookie = useCookie('auth_token')

  const $api = $fetch.create({
    baseURL: config.public.apiBase,

    onRequest({ options }) {
      const headers = new Headers(options.headers as HeadersInit)
      headers.set('Accept', 'application/json')
      if (tokenCookie.value) {
        headers.set('Authorization', `Bearer ${tokenCookie.value}`)
      }
      options.headers = headers
    },

    onResponseError({ response }) {
      // Auto redirect to login if token expired
      if (response.status === 401) {
        tokenCookie.value = null
        navigateTo('/auth/login')
      }
    },
  })

  return { $api }
}