import { ArrowLeft, Newspaper } from 'lucide-react'
import { Link } from 'react-router-dom'
import { newsItems } from '../../data/fallback'

export default function NewsPreview({ items = newsItems, content = {} }) {
  return (
    <article className="surface-card home-feature-card accent-orange p-5 sm:p-6">
      <div className="flex items-start justify-between"><div><span className="eyebrow">{content.news_eyebrow || 'تازه‌ها'}</span><h3 className="mt-2 card-title">{content.news_title || 'آخرین اخبار علمی'}</h3></div><Newspaper className="h-5 w-5 text-orange-300" /></div>
      <div className="mt-5 flex-1 space-y-3">
        {items.slice(0, 3).map((item, index) => (
          <Link key={item.id} to={`/news/${item.slug}`} className="group flex items-start gap-3 rounded-2xl border border-white/[.06] bg-white/[.018] p-3 transition hover:border-orange-300/20 hover:bg-white/[.035]">
            <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-orange-400/10 text-xs font-black text-orange-200">{index + 1}</span>
            <span className="min-w-0"><strong className="line-clamp-2 text-sm leading-6 text-slate-200 group-hover:text-white">{item.title}</strong><span className="mt-1 block text-[10px] text-slate-600">{item.publishedAt || 'تازه منتشر شده'}</span></span>
          </Link>
        ))}
      </div>
      <Link to="/news" className="secondary-btn mt-5 w-full">{content.news_cta || 'همه خبرها'} <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
