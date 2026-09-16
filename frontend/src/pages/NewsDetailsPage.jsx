import { useEffect, useState } from 'react'
import { ArrowRight, Check, Clock3, ExternalLink, PenLine, Share2, UserRound } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { formatDate } from '../lib/utils'

export default function NewsDetailsPage() {
  const { slug } = useParams()
  const [item, setItem] = useState(null)
  const [loading, setLoading] = useState(true)
  const [copied, setCopied] = useState(false)
  usePageMeta(item?.title || 'جزئیات خبر', item?.excerpt || 'جزئیات یک خبر علمی در جزیره نجوم.')

  useEffect(() => {
    let active = true
    setLoading(true)
    api.get(`/api/news/${encodeURIComponent(slug)}`).then((response) => { if (active && response.data) setItem(response.data) }).catch(() => { if (active) setItem(null) }).finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [slug])

  const share = async () => {
    try { if (navigator.share) await navigator.share({ title: item.title, text: item.excerpt, url: window.location.href }); else { await navigator.clipboard.writeText(window.location.href); setCopied(true); window.setTimeout(() => setCopied(false), 2200) } } catch { setCopied(false) }
  }

  if (loading) return <div className="content-shell flex min-h-[65vh] items-center justify-center py-20"><div className="h-9 w-9 animate-spin rounded-full border-2 border-white/10 border-t-amber-300" /></div>
  if (!item) return <section className="content-shell flex min-h-[65vh] flex-col items-center justify-center py-20 text-center"><span className="eyebrow">خطای ۴۰۴</span><h1 className="mt-4 text-3xl font-black text-white">این خبر پیدا نشد</h1><Link to="/news" className="primary-btn mt-6"><ArrowRight className="h-4 w-4" />بازگشت به اخبار</Link></section>

  const updatedDate = item.updatedAt && item.updatedAt !== item.publishedAt ? formatDate(item.updatedAt) : ''

  return (
    <article className="content-shell py-8 sm:py-12 lg:py-16">
      <div className="detail-layout xl:grid-cols-[minmax(0,1fr)_320px]">
        <div className="surface-card p-6 sm:p-8 lg:p-10">
          <Link to="/news" className="secondary-btn"><ArrowRight className="h-4 w-4" />بازگشت</Link>
          <span className="eyebrow mt-8">{item.category}</span>
          <h1 className="article-title mt-4 font-black text-white">{item.title}</h1>
          <p className="article-lead mt-6 font-medium text-slate-200">{item.excerpt}</p>
          {item.image ? (
            <figure className="news-detail-figure mt-8">
              <img src={item.image} alt={item.imageAlt || item.title} />
              {item.imageCredit ? <figcaption>اعتبار تصویر: {item.imageCredit}</figcaption> : null}
            </figure>
          ) : null}
          <div className="article-content mt-8 border-t border-white/[.07] pt-7 text-base leading-9 text-slate-400" dangerouslySetInnerHTML={{ __html: item.content?.includes('<') ? item.content : String(item.content || '').replace(/\n/g, '<br>') }} />
        </div>
        <aside className="detail-aside">
          <div className="surface-card p-5">
            <span className="text-xs text-slate-500">تاریخ انتشار</span>
            <strong className="mt-2 block text-sm text-white">{formatDate(item.publishedAt)}</strong>
            {updatedDate ? <div className="mt-3 text-xs leading-6 text-slate-500">آخرین به‌روزرسانی: {updatedDate}</div> : null}
            <div className="mt-4 flex items-center gap-2 text-xs text-slate-500"><Clock3 className="h-4 w-4" />{item.readingTime || '۵ دقیقه'}</div>
            {item.author ? <div className="mt-3 flex items-center gap-2 text-xs text-slate-500"><UserRound className="h-4 w-4" />نویسنده: {item.author}</div> : null}
            {item.translator ? <div className="mt-3 flex items-center gap-2 text-xs text-slate-500"><PenLine className="h-4 w-4" />مترجم / تنظیم: {item.translator}</div> : null}
            {item.sourceName || item.sourceUrl ? (
              <a href={item.sourceUrl || undefined} target={item.sourceUrl ? '_blank' : undefined} rel={item.sourceUrl ? 'noopener noreferrer' : undefined} className={`mt-5 flex items-center justify-between gap-3 rounded-2xl border border-white/[.08] bg-white/[.03] px-4 py-3 text-xs text-slate-300 ${item.sourceUrl ? 'transition hover:border-amber-200/30 hover:text-white' : 'pointer-events-none'}`}>
                <span>منبع: {item.sourceName || 'لینک منبع'}</span>
                {item.sourceUrl ? <ExternalLink className="h-4 w-4 shrink-0" /> : null}
              </a>
            ) : null}
            {item.imageCredit ? <div className="mt-4 rounded-2xl border border-white/[.08] bg-white/[.025] px-4 py-3 text-xs leading-6 text-slate-500">اعتبار تصویر: {item.imageCredit}</div> : null}
            <button onClick={share} className="secondary-btn mt-5 w-full">{copied ? <Check className="h-4 w-4" /> : <Share2 className="h-4 w-4" />}{copied ? 'لینک کپی شد' : 'اشتراک‌گذاری'}</button>
          </div>
          <div className="surface-card p-5 text-sm leading-8 text-slate-400">برای ارزیابی خبرهای علمی باید میان داده مشاهده‌شده، مدل پژوهشی و نتیجه‌گیری احتمالی تفاوت گذاشت و به منبع اصلی مراجعه کرد.</div>
        </aside>
      </div>
    </article>
  )
}
