import { Home, Telescope, Newspaper, Image, Youtube, Radar, Sparkles } from 'lucide-react'
import { NavLink } from 'react-router-dom'

const items = [
  { label: 'خانه', path: '/', icon: Home },
  { label: 'آسمان امروز', path: '/sky', icon: Sparkles },
  { label: 'کاوش فضا', path: '/explore', icon: Telescope },
  { label: 'اخبار', path: '/news', icon: Newspaper },
  { label: 'عکس روز', path: '/apod', icon: Image },
  { label: 'ویدیوها', path: '/videos', icon: Youtube },
  { label: 'رادار', path: '/radar', icon: Radar }
]

export default function Sidebar() {
  return (
    <aside className="fixed right-0 top-[74px] z-30 hidden h-[calc(100vh-74px)] w-[96px] border-l border-white/[.08] bg-space-950/55 backdrop-blur-xl lg:flex lg:flex-col lg:items-center lg:justify-between lg:py-5">
      <nav className="flex w-full flex-col items-center gap-1 px-2">
        {items.map(({ label, path, icon: Icon }) => (
          <NavLink key={path} to={path} className={({ isActive }) => `group flex w-full flex-col items-center gap-1.5 rounded-2xl px-2 py-3 text-[10px] transition ${isActive ? 'bg-blue-500/10 text-blue-300' : 'text-slate-500 hover:bg-white/[.04] hover:text-slate-200'}`}>
            <Icon className="h-5 w-5 transition group-hover:-translate-y-0.5" />
            <span>{label}</span>
          </NavLink>
        ))}
      </nav>
      <div className="h-1.5 w-1.5 rounded-full bg-blue-300 shadow-[0_0_16px_rgba(96,165,250,.9)]" />
    </aside>
  )
}
