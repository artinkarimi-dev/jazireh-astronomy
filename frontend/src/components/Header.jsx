import { Menu, Search, X, Radio } from 'lucide-react'
import { useState } from 'react'
import { NavLink } from 'react-router-dom'
import { navItems } from '../data/fallback'
import Logo from './Logo'

export default function Header() {
  const [open, setOpen] = useState(false)
  return (
    <header className="fixed inset-x-0 top-0 z-50 border-b border-white/[.08] bg-space-950/70 backdrop-blur-2xl">
      <div className="content-shell flex h-[74px] items-center justify-between gap-4">
        <Logo />
        <nav className="hidden items-center gap-1 xl:flex">
          {navItems.map((item) => (
            <NavLink key={item.path} to={item.path} className={({ isActive }) => `relative rounded-full px-4 py-2 text-sm transition ${isActive ? 'text-white' : 'text-slate-400 hover:text-white'}`}>
              {({ isActive }) => <>{item.label}{isActive && <span className="absolute inset-x-4 -bottom-[15px] h-px bg-blue-300 shadow-[0_0_12px_rgba(96,165,250,.8)]" />}</>}
            </NavLink>
          ))}
        </nav>
        <div className="flex items-center gap-2">
          <button className="icon-button hidden sm:inline-flex" aria-label="جستجو"><Search className="h-4 w-4" /></button>
          <span className="hidden items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-2 text-xs font-bold text-red-300 md:flex"><Radio className="h-3.5 w-3.5 animate-pulse" /> پخش زنده</span>
          <button className="icon-button xl:hidden" onClick={() => setOpen(true)} aria-label="باز کردن منو"><Menu className="h-5 w-5" /></button>
        </div>
      </div>
      {open && (
        <div className="fixed inset-0 z-[80] bg-black/75 backdrop-blur-md xl:hidden" onClick={() => setOpen(false)}>
          <div className="mr-auto flex h-full w-[86%] max-w-sm flex-col border-r border-white/10 bg-space-950 p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between"><Logo /><button className="icon-button" onClick={() => setOpen(false)}><X className="h-5 w-5" /></button></div>
            <nav className="mt-10 flex flex-col gap-2">
              {navItems.map((item) => <NavLink key={item.path} to={item.path} onClick={() => setOpen(false)} className={({ isActive }) => `rounded-2xl px-4 py-3 text-sm font-semibold ${isActive ? 'border border-blue-400/25 bg-blue-500/10 text-blue-200' : 'text-slate-300 hover:bg-white/5'}`}>{item.label}</NavLink>)}
            </nav>
          </div>
        </div>
      )}
    </header>
  )
}
