import { useEffect, useMemo, useState } from 'react'
import { ArrowLeft, Clock3, Search, Newspaper, X } from 'lucide-react'
import { Link } from 'react-router-dom'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { formatDate, normalizePersianText } from '../lib/utils'

export default function NewsPage() {
  const [items, setItems] = useState([])
  const [loadError, setLoadError] = useState(false)
  const [query, setQuery] = useState('')
  const [category, setCategory] = useState('همه')
  usePageMeta('اخبار علمی', 'تازه‌ترین خبرها و روایت‌های فارسی از نجوم، ماموریت‌های فضایی و پژوهش‌های علمی در جزیره.')

  useEffect(() => {
    let active = true
    setLoadError(false)
    api.get('/api/news')
      .then((response) => { if (active && Array.isArray(response.data)) setItems(response.data) })
      .catch(() => { if (active) setLoadError(true) })
    return () => { active = false }
  }, [])

  const categories = useMemo(() => ['همه', ...new Set(items.map((item) => item.category).filter(Boolean))], [items])
  const normalizedQuery = normalizePersianText(query)
  const filtered = useMemo(() => items.filter((item) => (category === 'همه' || item.category === category) && normalizePersianText(`${item.title} ${item.excerpt || ''} ${item.category || ''} ${item.author || ''} ${item.sourceName || ''}`).includes(normalizedQuery)), [category, items, normalizedQuery])

  return (
    <>
      <PageHero eyebrow="روایت‌های علمی جزیره" title="اخبار علمی" description="تازه‌ترین خبرهای نجوم، ماموریت‌های فضایی، سیاره‌های فراخورشیدی و پژوهش‌های کیهانی را دنبال کنید." />
      <section className="content-shell section-space">
        <div className="surface-card news-filter-bar mb-7 flex flex-col gap-4 p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
          <div className={`search-field w-full lg:max-w-md ${query ? 'has-clear' : ''}`}>
            <input value={query} onChange={(event) => setQuery(event.target.value)} className="input-field search-input" placeholder="جستجو در خبرها..." />
            <Search className="search-icon h-4 w-4" />
            {query ? (
              <button type="button" onClick={() => setQuery('')} className="search-input-clear" aria-label="پاک کردن جستجو">
                <X className="h-4 w-4" />
              </button>
            ) : null}
          </div>
          <div className="news-filter-list flex min-w-0 flex-wrap gap-2 overflow-x-auto pb-1 custom-scrollbar lg:justify-end">{categories.map((item) => <button type="button" key={item} onClick={() => setCategory(item)} className={category === item ? 'primary-btn whitespace-nowrap' : 'secondary-btn whitespace-nowrap'}>{item}</button>)}</div>
        </div>
        <div className="mb-5 text-xs text-slate-600">{filtered.length} خبر</div>

        {filtered.length ? <div className="news-grid">{filtered.map((item, index) => (
          <article key={item.id} className="surface-card news-card accent-orange p-5 sm:p-6">
            <div className="flex items-start justify-between gap-3"><span className="event-category bg-orange-400/10 text-orange-200">{item.category}</span><span className="shrink-0 text-4xl font-black text-white/[.035] sm:text-5xl">{String(index + 1).padStart(2, '0')}</span></div>
            <h2 className="mt-5 text-xl font-black leading-8 text-white">{item.title}</h2>
            <p className="mt-3 line-clamp-3 text-sm leading-7 text-slate-400">{item.excerpt}</p>
            <div className="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-white/[.07] pt-4 text-xs leading-6 text-slate-500"><span className="flex min-w-0 items-center gap-1"><Clock3 className="h-3.5 w-3.5 shrink-0" />{item.readingTime || '۵ دقیقه'}</span>{item.sourceName || item.author ? <span className="truncate">{item.sourceName || item.author}</span> : null}<span className="state-pill">{formatDate(item.publishedAt)}</span><Link to={`/news/${item.slug}`} className="flex items-center gap-1 text-amber-200">ادامه <ArrowLeft className="h-3.5 w-3.5" /></Link></div>
          </article>
        ))}</div> : <div className="surface-card flex min-h-[280px] flex-col items-center justify-center p-8 text-center"><Newspaper className="h-10 w-10 text-slate-600" /><h2 className="mt-4 text-xl font-bold text-white">{loadError ? 'دریافت خبرها ممکن نبود' : 'خبری پیدا نشد'}</h2><p className="mt-3 max-w-lg text-sm leading-7 text-slate-500">{loadError ? 'برای جلوگیری از نمایش خبر جعلی، این صفحه فقط محتوای معتبر WordPress را نشان می‌دهد.' : 'هنوز محتوای منتشرشده‌ای مطابق این فیلتر وجود ندارد.'}</p></div>}
      </section>
    </>
  )
}
