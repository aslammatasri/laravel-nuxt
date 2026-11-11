export const roleHome = (role?: string | null) =>
  role === 'brand' ? '/brand/dashboard' : role === 'admin' ? '/admin/dashboard' : '/creator/dashboard'
