import { resolveAssetPath } from '../lib/utils'

export const siteConfig = {
  name: 'جزیره',
  fullName: 'جزیره نجوم',
  description: 'رسانه‌ای فارسی برای روایت علمی نجوم، فضا و شگفتی‌های جهان.',
  youtube: {
    url: 'https://www.youtube.com/@Jazireh',
    handle: '@Jazireh',
    label: 'کانال یوتیوب جزیره'
  },
  developer: {
    name: 'آرتین کریمی',
    role: 'طراحی و توسعه وب',
    url: import.meta.env.VITE_DEVELOPER_URL || 'https://github.com/artinkarimi-dev'
  },
  brand: {
    logo: resolveAssetPath('/brand/jazireh-logo.jpg'),
    logoFallback: resolveAssetPath('/brand/jazireh-logo.webp')
  }
}

export function getPersianYear() {
  return new Intl.DateTimeFormat('fa-IR-u-ca-persian', { year: 'numeric' }).format(new Date())
}
