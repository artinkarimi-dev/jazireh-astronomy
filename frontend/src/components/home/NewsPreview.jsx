import { ArrowLeft } from 'lucide-react'
import { Link } from 'react-router-dom'
import { newsItems } from '../../data/fallback'

export default function NewsPreview() {
  return (
    <article className="glass-panel glass-panel-hover min-h-[410px] rounded-3xl p-5">
      <div><h3 className="card-title">آخرین اخبار</h3><p className="mt-1 text-xs text-slate-500">تازه‌ترین رویدادهای علمی و فضایی</p></div>
      <div className="mt-5 space-y-4">
        {newsItems.slice(0, 3).map((item) => <Link key={item.id} to={`/news/${item.slug}`} className="group flex gap-3"><img src={item.image} alt="" className="h-16 w-20 rounded-xl object-cover opacity-85 transition group-hover:opacity-100" /><div className="min-w-0"><h4 className="line-clamp-2 text-sm font-semibold leading-6 text-slate-200 transition group-hover:text-blue-200">{item.title}</h4><span className="mt-1 block text-[10px] text-slate-600">{item.publishedAt}</span></div></Link>)}
      </div>
      <Link to="/news" className="secondary-btn mt-6 w-full">مشاهده همه اخبار <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
