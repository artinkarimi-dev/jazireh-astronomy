import { Link } from 'react-router-dom'
import { siteConfig } from '../config/site'
import { useSiteSettings } from '../context/SiteSettingsContext'

export default function Logo({ compact = false, className = '' }) {
  const { settings } = useSiteSettings()
  const logoUrl = settings.identity.logo?.url || siteConfig.brand.logo
  const logoAlt = settings.identity.logo?.alt || settings.identity.name || 'Jazireh'
  const siteName = settings.identity.name || siteConfig.name
  const tagline = settings.identity.tagline || 'نجوم و علم'

  return (
    <Link to="/" className={`group inline-flex min-w-0 items-center gap-3 ${className}`} aria-label="بازگشت به صفحه اصلی جزیره">
      <span className="brand-logo-shell">
        <img src={logoUrl} alt={logoAlt} className="h-full w-full rounded-full object-cover" width="50" height="50" />
      </span>
      {!compact && (
        <span className="logo-wordmark min-w-0 leading-none">
          <strong className="block truncate text-lg font-black tracking-tight text-white sm:text-xl">{siteName}</strong>
          <span className="mt-1 hidden truncate text-[10px] font-bold tracking-[.25em] text-amber-300 sm:block">{tagline}</span>
        </span>
      )}
    </Link>
  )
}
