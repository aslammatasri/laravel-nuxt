export const useSidebar = () => {
  const collapsed = useCookie<boolean>('sidebar-collapsed', {
    default: () => false,
    maxAge: 60 * 60 * 24 * 365,
  })

  const toggle = () => {
    collapsed.value = !collapsed.value
  }

  return { collapsed, toggle }
}
