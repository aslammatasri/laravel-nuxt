export default defineNuxtRouteMiddleware(() => {
  const auth = useAuth()

  if (!auth.isLoggedIn) {
    return navigateTo('/auth/login')
  }

  if (auth.user && !auth.isCreator) {
    return navigateTo(roleHome(auth.user.role))
  }
})
