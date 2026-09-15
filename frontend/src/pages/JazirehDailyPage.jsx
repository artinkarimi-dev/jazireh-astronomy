import { ArrowLeft, ImageIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { useEffect, useState } from 'react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { formatDate } from '../lib/utils'

export default function JazirehDailyPage() {
  const [items, setItems] = useState([])
  const [loading, setLoading] = useState(true)

  usePageMeta('جزیره دیلی', 'آرشیو پست‌های کامیونیتی جزیره درباره عکس روز ناسا و روایت‌های نجومی منتشرشده در یوتیوب.')

  useEffect(() => {
    let active = true
    api.get('/api/jazireh-daily')
      .then((response) => {
        if (active) setItems(Array.isArray(response.data) ? response.data : [])
      })
      .catch(() => {
        if (active) setItems([])
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => { active = false }
  }, [])

  return (
    <>
      <PageHero eyebrow="جزیره دیلی" title="عکس روز ناسا در جزیره" description="این بخش آرشیو پست‌های خودِ Jazireh از کامیونیتی یوتیوب است؛ نه جایگزین صفحه رسمی NASA APOD.">
        <Link to="/apod" className="secondary-btn">صفحه APOD <ArrowLeft className="h-4 w-4" /></Link>
      </PageHero>

      <section className="content-shell section-space">
        {loading ? (
          <div className="surface-card p-8 sm:p-10"><div className="flex min-h-[320px] items-center justify-center"><div className="h-9 w-9 animate-spin rounded-full border-2 border-white/10 border-t-amber-300" /></div></div>
        ) : !items.length ? (
          <div className="surface-card p-8 text-center sm:p-10">
            <div className="flex min-h-[280px] flex-col items-center justify-center">
              <span className="eyebrow">جزیره دیلی</span>
              <h2 className="mt-4 text-2xl font-black text-white">هنوز پستی منتشر نشده است</h2>
              <p className="mt-3 max-w-2xl text-sm leading-8 text-slate-400">به محض اینکه همگام‌سازی کامیونیتی یوتیوب یا ورود دستی وردپرس محتوا را ثبت کند، این آرشیو در همین صفحه نمایش داده می‌شود.</p>
            </div>
          </div>
        ) : (
          <div className="daily-grid">
            {items.map((item) => {
              const image = item.media?.thumbnail || item.images?.[0]?.url || youtubeThumbnailFromUrl(item.sourceUrl)
              const isShort = item.media?.type === 'youtube-short' || item.sourceUrl?.includes('/shorts/')
              return (
                <article key={item.id} className="surface-card daily-card overflow-hidden">
                  <Link to={`/jazireh-daily/${item.slug}`} className="group block h-full">
                    <div className={`daily-card-media ${isShort ? 'daily-card-media-short' : ''}`}>
                      <DailyCardImage image={image} title={item.title} />
                    </div>
                    <div className="p-5 sm:p-6">
                      <span className="eyebrow">{formatDate(item.publishedAt)}</span>
                      <h2 className="mt-3 line-clamp-2 text-xl font-black leading-[1.65] text-white">{item.title}</h2>
                      <p className="mt-4 line-clamp-4 text-sm leading-8 text-slate-400">{item.excerpt}</p>
                      <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                        <span className="secondary-btn">مطالعه جزئیات <ArrowLeft className="h-4 w-4" /></span>
                        {item.sourceUrl && <span className="text-xs text-slate-500">منبع یوتیوب</span>}
                      </div>
                    </div>
                  </Link>
                </article>
              )
            })}
          </div>
        )}
      </section>
    </>
  )
}

function youtubeThumbnailFromUrl(url = '') {
  const value = String(url)
  const patterns = [
    /youtu\.be\/([A-Za-z0-9_-]{6,})/,
    /youtube\.com\/shorts\/([A-Za-z0-9_-]{6,})/,
    /youtube\.com\/embed\/([A-Za-z0-9_-]{6,})/,
    /[?&]v=([A-Za-z0-9_-]{6,})/,
  ]
  const match = patterns.map((pattern) => value.match(pattern)?.[1]).find(Boolean)
  return match ? `https://i.ytimg.com/vi/${encodeURIComponent(match)}/hqdefault.jpg` : ''
}

function DailyCardImage({ image, title }) {
  const [failed, setFailed] = useState(false)

  if (!image || failed) {
    return <div className="daily-media-fallback"><ImageIcon className="h-9 w-9 text-amber-200/80" /><span>رسانه‌ای برای این پست ثبت نشده است</span></div>
  }

  return <img src={image} alt={title} className="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy" decoding="async" referrerPolicy="no-referrer" onError={() => setFailed(true)} />
}
