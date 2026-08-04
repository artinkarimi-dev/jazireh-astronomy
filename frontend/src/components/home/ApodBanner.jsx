import { ArrowLeft, Camera } from 'lucide-react'
import { Link } from 'react-router-dom'
import { apodItems } from '../../data/fallback'

export default function ApodBanner() {
  const item = apodItems[0]
  return (
    <article className="glass-panel panel-noise relative min-h-[300px] overflow-hidden rounded-3xl">
      <img src={item.image} alt={item.title} className="absolute inset-0 h-full w-full object-cover" />
      <div className="absolute inset-0 bg-gradient-to-l from-space-950 via-space-950/75 to-space-950/15" />
      <div className="relative flex min-h-[300px] max-w-xl flex-col justify-center p-7 sm:p-10">
        <span className="flex items-center gap-2 text-xs font-semibold text-blue-300"><Camera className="h-4 w-4" /> تصویر رسمی روز ناسا</span>
        <h3 className="mt-4 text-2xl font-black text-white sm:text-3xl">{item.title}</h3>
        <p className="mt-4 text-sm leading-7 text-slate-300">{item.excerpt}</p>
        <Link to="/apod" className="primary-btn mt-6 w-fit">ادامه مطلب <ArrowLeft className="h-4 w-4" /></Link>
      </div>
    </article>
  )
}
