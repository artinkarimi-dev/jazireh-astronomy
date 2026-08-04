import { ArrowLeft, Orbit } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function ExplorePreview() {
  return (
    <article className="glass-panel glass-panel-hover panel-noise flex min-h-[410px] flex-col rounded-3xl p-5">
      <div><h3 className="card-title">کاوش فضا</h3><p className="mt-1 text-xs text-slate-500">سفر سه‌بعدی در منظومه شمسی</p></div>
      <div className="relative mt-4 flex-1 overflow-hidden rounded-2xl border border-white/[.08] bg-black/30">
        <img src="/media/saturn.jpg" alt="زحل" className="h-full w-full object-cover transition duration-700 hover:scale-105" />
        <div className="absolute inset-0 bg-gradient-to-t from-space-950/95 via-transparent to-transparent" />
        <div className="absolute bottom-4 right-4"><div className="flex items-center gap-2 text-blue-200"><Orbit className="h-4 w-4" /><span className="text-xs">تعامل با موس و لمس</span></div><strong className="mt-2 block text-2xl text-white">+۱۰٬۰۰۰ جرم آسمانی</strong></div>
      </div>
      <Link to="/explore" className="primary-btn mt-4 w-full">شروع کاوش <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
