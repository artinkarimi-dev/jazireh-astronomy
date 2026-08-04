import { Orbit } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function Logo({ compact = false }) {
  return (
    <Link to="/" className="group flex items-center gap-3" aria-label="جزیره نجوم">
      <div className="relative flex h-11 w-11 items-center justify-center rounded-full border border-blue-300/30 bg-blue-500/5 shadow-[0_0_30px_rgba(96,165,250,.12)]">
        <Orbit className="h-7 w-7 text-blue-200 transition group-hover:rotate-12" />
        <span className="absolute h-2 w-2 translate-x-3 -translate-y-3 rounded-full bg-white shadow-[0_0_14px_white]" />
      </div>
      {!compact && (
        <div className="leading-tight">
          <strong className="block text-xl font-black tracking-tight text-white">جزیره</strong>
          <span className="text-[10px] font-semibold tracking-[.36em] text-blue-300">نجوم</span>
        </div>
      )}
    </Link>
  )
}
