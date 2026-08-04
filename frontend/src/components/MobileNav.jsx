import { Home, Telescope, Newspaper, Sparkles, MoreHorizontal } from 'lucide-react'
import { NavLink } from 'react-router-dom'

const items = [
  { label: 'خانه', path: '/', icon: Home },
  { label: 'آسمان', path: '/sky', icon: Sparkles },
  { label: 'کاوش', path: '/explore', icon: Telescope },
  { label: 'اخبار', path: '/news', icon: Newspaper },
  { label: 'بیشتر', path: '/apod', icon: MoreHorizontal }
]

export default function MobileNav() {
  return (
    <nav className="fixed inset-x-3 bottom-3 z-50 flex h-16 items-center justify-around rounded-2xl border border-white/10 bg-space-950/90 px-2 shadow-panel backdrop-blur-2xl lg:hidden">
      {items.map(({ label, path, icon: Icon }) => <NavLink key={path} to={path} className={({ isActive }) => `flex min-w-[54px] flex-col items-center gap-1 rounded-xl py-2 text-[10px] ${isActive ? 'text-blue-300' : 'text-slate-500'}`}><Icon className="h-5 w-5" /><span>{label}</span></NavLink>)}
    </nav>
  )
}
