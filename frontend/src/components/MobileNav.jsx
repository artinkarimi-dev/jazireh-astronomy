import { Home, Telescope, Newspaper, Sparkles, Clapperboard } from 'lucide-react'
import { NavLink } from 'react-router-dom'
import { useSiteSettings } from '../context/SiteSettingsContext'

const icons = {
  home: Home,
  sparkles: Sparkles,
  telescope: Telescope,
  newspaper: Newspaper,
  clapperboard: Clapperboard
}

export default function MobileNav() {
  const { settings } = useSiteSettings()
  const items = (settings.menus.mobile || []).filter((item) => !item.external).slice(0, 5)

  return (
    <nav className="mobile-nav-bar md:hidden" aria-label="پیمایش سریع موبایل">
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
