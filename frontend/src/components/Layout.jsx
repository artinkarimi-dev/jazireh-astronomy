import { Outlet, useLocation } from 'react-router-dom'
import { useEffect } from 'react'
import Header from './Header'
import MobileNav from './MobileNav'
import Footer from './Footer'

export default function Layout() {
  const { pathname } = useLocation()

  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'auto' })
  }, [pathname])

  return (
    <div className="page-shell" dir="rtl">
      <a href="#main-content" className="skip-link">رفتن به محتوای اصلی</a>
      <Header />
      <div className="page-surface">
        <main id="main-content" tabIndex="-1" className="min-w-0"><Outlet /></main>
        <Footer />
      </div>
      <MobileNav />
    </div>
  )
}
