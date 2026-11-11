// Shared unread-message count, polled while a layout is mounted.
export const useUnreadMessages = () => {
  const { $api } = useApi()
  const count = useState<number>('unread-messages', () => 0)

  const token = useCookie('auth_token')

  async function refresh() {
    if (!token.value) return
    try {
      const res = await $api<{ count: number }>('/conversations/unread-count')
      count.value = res.count
    } catch {
      // ignore poll failures
    }
  }

  let timer: ReturnType<typeof setInterval> | null = null

  function startPolling() {
    stopPolling()
    refresh()
    timer = setInterval(() => {
      if (token.value && document.visibilityState === 'visible') refresh()
    }, 15000)
  }

  function stopPolling() {
    if (timer) clearInterval(timer)
    timer = null
  }

  return { count, refresh, startPolling, stopPolling }
}
