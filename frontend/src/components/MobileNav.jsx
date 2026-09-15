import { CalendarDays, Home, Search, Telescope, Newspaper, Sparkles, Clapperboard } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { useSiteSettings } from '../context/SiteSettingsContext'

const icons = {
  calendar: CalendarDays,
  home: Home,
  search: Search,
  sparkles: Sparkles,
  telescope: Telescope,
  newspaper: Newspaper,
  clapperboard: Clapperboard
}

const requiredMobileItems = [
  { label: 'رویدادها', path: '/events', url: '/events', external: false, target: '', icon: 'calendar' },
  { label: 'جستجو', path: '/search', url: '/search', external: false, target: '', icon: 'search' }
]

export default function MobileNav() {
  const { settings } = useSiteSettings()
  const items = withRequiredLinks((settings.menus.mobile || []).filter((item) => !item.external))

  return (
    <nav className="mobile-nav-bar md:hidden" aria-label="پیمایش سریع موبایل" style={{ '--mobile-nav-count': items.length }}>
      {items.map(({ label, path, url, icon }) => {
        const Icon = icons[icon] || Home
        return (
          <NavLink key={path || url} to={path || url} className={({ isActive }) => `mobile-nav-link ${isActive ? 'mobile-nav-link-active' : ''}`.trim()}>
            <Icon className="h-5 w-5 shrink-0" />
            <span className="mobile-nav-label">{label}</span>
          </NavLink>
        )
      })}
    </nav>
  )
}

function withRequiredLinks(items) {
  const existing = new Set(items.map((item) => item.path || item.url))
  return [
    ...items,
    ...requiredMobileItems.filter((item) => !existing.has(item.path))
  ]
}
