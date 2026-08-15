export default defineNuxtRouteMiddleware(() => {
  const auth = useAuth()

  if (!auth.isLoggedIn) {
    return navigateTo('/auth/login')
  }

  if (auth.user && !auth.isBrand) {
    return navigateTo('/creator/dashboard')
  }
})