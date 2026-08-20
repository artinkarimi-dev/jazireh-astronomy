import { Menu, X, Youtube } from 'lucide-react'
import { useEffect, useState } from 'react'
import { NavLink, useLocation } from 'react-router-dom'
import { useSiteSettings } from '../context/SiteSettingsContext'
import Logo from './Logo'

function HeaderNavItem({ item, className, activeClassName = '' }) {
  if (item.external) {
    return (
      <a
        href={item.url}
        target={item.target || '_self'}
        rel={item.target === '_blank' ? 'noopener noreferrer' : undefined}
        className={className}
      >
        {item.label}
      </a>
    )
  }

  return (
    <NavLink to={item.path || item.url} className={({ isActive }) => `${className} ${isActive ? activeClassName : ''}`.trim()}>
      {item.label}
    </NavLink>
  )
}

export default function Header() {
  const [open, setOpen] = useState(false)
  const { pathname } = useLocation()
  const { settings } = useSiteSettings()
  const menuItems = settings.menus.primary || []
  const youtube = settings.social.youtube

  useEffect(() => setOpen(false), [pathname])
  useEffect(() => {
    const body = document.body
    const previousOverflow = body.style.overflow
    if (open) body.style.overflow = 'hidden'
    return () => { body.style.overflow = previousOverflow }
  }, [open])
  useEffect(() => {
    if (!open) return undefined
    const onKeyDown = (event) => {
      if (event.key === 'Escape') setOpen(false)
    }
    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [open])

  return (
    <header className="site-header fixed inset-x-0 top-0 z-50">
      <div className="content-shell flex h-[74px] items-center justify-between gap-3 sm:gap-4 lg:h-[82px]">
        <Logo />

        <nav className="hidden items-center gap-1 xl:flex" aria-label="پیمایش اصلی">
          {menuItems.map((item) => (
            <HeaderNavItem key={`${item.label}-${item.path || item.url}`} item={item} className="nav-link" activeClassName="nav-link-active" />
          ))}
        </nav>

        <div className="flex items-center gap-2">
          <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="youtube-cta hidden sm:inline-flex">
            <Youtube className="h-4 w-4 fill-current" />
            {youtube.label}
          </a>
          <button
            type="button"
            className="icon-button xl:hidden"
            onClick={() => setOpen(true)}
            aria-label="باز کردن منو"
            aria-expanded={open}
            aria-controls="mobile-menu-drawer"
          >
            <Menu className="h-5 w-5" />
          </button>
        </div>
      </div>

      {open && (
        <div className="mobile-drawer-shell xl:hidden" onClick={() => setOpen(false)}>
          <div
            id="mobile-menu-drawer"
            className="mobile-drawer-panel"
            onClick={(event) => event.stopPropagation()}
            role="dialog"
            aria-modal="true"
            aria-label="پیمایش موبایل"
          >
            <div className="flex items-center justify-between gap-3">
              <Logo compact />
              <button type="button" className="icon-button" onClick={() => setOpen(false)} aria-label="بستن منو">
                <X className="h-5 w-5" />
              </button>
            </div>
            <nav className="mt-7 flex flex-col gap-2">
              {menuItems.map((item) => (
                <HeaderNavItem
                  key={`${item.label}-${item.path || item.url}`}
                  item={item}
                  className="mobile-menu-link"
                  activeClassName="mobile-menu-link-active"
                />
              ))}
            </nav>
            <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="youtube-cta mt-6 w-full py-3"><Youtube className="h-5 w-5 fill-current" />{youtube.handle}</a>
          </div>
        </div>
      )}
    </header>
  )
}
