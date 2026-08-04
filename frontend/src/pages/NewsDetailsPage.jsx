import { ArrowRight, Clock3, Share2 } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { newsItems } from '../data/fallback'

export default function NewsDetailsPage() {
  const { slug } = useParams()
  const item = newsItems.find((n) => n.slug === slug) || newsItems[0]
  return (
    <article className="content-shell max-w-5xl py-14 sm:py-20">
      <Link to="/news" className="secondary-btn"><ArrowRight className="h-4 w-4" /> بازگشت به اخبار</Link>
      <div className="mt-8"><span className="eyebrow">{item.category}</span><h1 className="mt-4 text-3xl font-black leading-[1.5] text-white sm:text-5xl">{item.title}</h1><div className="mt-5 flex flex-wrap gap-4 text-xs text-slate-500"><span>{item.publishedAt}</span><span className="flex items-center gap-1"><Clock3 className="h-3.5 w-3.5" />{item.readingTime}</span><button className="flex items-center gap-1 text-blue-300"><Share2 className="h-3.5 w-3.5" />اشتراک‌گذاری</button></div></div>
      <img src={item.image} alt={item.title} className="mt-8 h-[320px] w-full rounded-3xl object-cover shadow-panel sm:h-[520px]" />
      <div className="mx-auto mt-10 max-w-3xl"><p className="text-lg font-medium leading-9 text-slate-200">{item.excerpt}</p><div className="mt-7 space-y-6 text-base leading-9 text-slate-400"><p>{item.content}</p><p>در تحلیل چنین خبرهایی باید میان مشاهده مستقیم، مدل علمی و نتیجه‌گیری احتمالی تفاوت گذاشت. داده‌های تازه معمولاً پس از بررسی گروه‌های مستقل و مقایسه با رصدهای دیگر اعتبار بیشتری پیدا می‌کنند.</p></div></div>
    </article>
  )
}
