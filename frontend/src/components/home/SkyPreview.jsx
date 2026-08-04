import { ArrowLeft, Radio } from 'lucide-react'
import { Link } from 'react-router-dom'
import { skyData } from '../../data/fallback'

export default function SkyPreview() {
  return (
    <article className="glass-panel glass-panel-hover panel-noise flex min-h-[410px] flex-col rounded-3xl p-5">
      <div className="flex items-start justify-between"><div><h3 className="card-title">آسمان امروز</h3><p className="mt-1 text-xs text-slate-500">نقشه زنده آسمان در موقعیت شما</p></div><span className="flex items-center gap-1 rounded-full bg-blue-500/10 px-2 py-1 text-[10px] text-blue-300"><Radio className="h-3 w-3" /> زنده</span></div>
      <div className="relative mt-4 flex-1 overflow-hidden rounded-2xl border border-white/[.08] bg-black/25">
        <img src="/media/sky-map.jpg" alt="نقشه آسمان" className="h-full w-full object-cover opacity-90" />
        <div className="absolute inset-0 bg-gradient-to-t from-space-950/90 via-transparent to-transparent" />
        <div className="absolute bottom-4 right-4"><p className="font-bold text-white">{skyData.location}</p><p className="mt-1 text-xs text-slate-300">بهترین زمان: {skyData.bestTime}</p></div>
      </div>
      <Link to="/sky" className="primary-btn mt-4 w-full">مشاهده کامل آسمان <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
