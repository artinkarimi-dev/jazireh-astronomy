import { Outlet, useLocation } from 'react-router-dom'
import { useEffect } from 'react'
import Header from './Header'
import Sidebar from './Sidebar'
import MobileNav from './MobileNav'
import Footer from './Footer'

export default function Layout() {
  const { pathname } = useLocation()
  useEffect(() => {
    window.scrollTo({ top: 0, behavior: 'auto' })
  }, [pathname])
  return (
    <div className="page-shell" dir="rtl">
      <Header />
      <Sidebar />
      <div className="pt-[74px] lg:pr-[96px]">
        <main><Outlet /></main>
        <Footer />
      </div>
      <MobileNav />
    </div>
  )
}
