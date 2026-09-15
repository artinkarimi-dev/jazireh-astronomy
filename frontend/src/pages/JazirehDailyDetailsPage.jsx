import { ArrowRight, ArrowUpLeft, ImageIcon, Images } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { formatDate } from '../lib/utils'

export default function JazirehDailyDetailsPage() {
  const { slug } = useParams()
  const [item, setItem] = useState(null)
  const [loading, setLoading] = useState(true)

  usePageMeta(item?.title || 'جزئیات جزیره دیلی', item?.excerpt || 'جزئیات پست جزیره دیلی.')

  useEffect(() => {
    let active = true
    setLoading(true)
    api.get(`/api/jazireh-daily/${slug}`)
      .then((response) => {
        if (active) setItem(response.data || null)
      })
      .catch(() => {
        if (active) setItem(null)
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => { active = false }
  }, [slug])

  if (loading) return <div className="content-shell flex min-h-[65vh] items-center justify-center py-20"><div className="h-9 w-9 animate-spin rounded-full border-2 border-white/10 border-t-amber-300" /></div>
  if (!item) return <section className="content-shell flex min-h-[65vh] flex-col items-center justify-center py-20 text-center"><span className="eyebrow">خطای ۴۰۴</span><h1 className="mt-4 text-3xl font-black text-white">این پست پیدا نشد</h1><Link to="/jazireh-daily" className="primary-btn mt-6"><ArrowRight className="h-4 w-4" />بازگشت به جزیره دیلی</Link></section>

  return (
    <article className="content-shell py-8 sm:py-12 lg:py-16">
      <div className="detail-layout xl:grid-cols-[minmax(0,1fr)_320px]">
        <div className="surface-card p-6 sm:p-8 lg:p-10">
          <Link to="/jazireh-daily" className="secondary-btn"><ArrowRight className="h-4 w-4" />بازگشت</Link>
          <span className="eyebrow mt-8">{item.source === 'youtube-community' ? 'کامیونیتی یوتیوب جزیره' : 'جزیره دیلی'}</span>
          <h1 className="article-title mt-4 font-black text-white">{item.title}</h1>
          <p className="article-lead mt-6 font-medium text-slate-200">{item.excerpt}</p>

          <div className="daily-detail-media-stack mt-8">
            {resolvedMediaItems(item).map((image) => (
              <div key={image.id || image.url || image.type} className="daily-detail-media-frame">
                <DailyDetailImage image={image} title={item.title} />
              </div>
            ))}
          </div>

          <div className="article-content mt-8 border-t border-white/[.07] pt-7 text-base leading-9 text-slate-400">
            {String(item.text || '').split('\n').filter(Boolean).map((paragraph, index) => <p key={`${item.id}-${index}`}>{paragraph}</p>)}
          </div>
        </div>

        <aside className="detail-aside">
          <div className="surface-card p-5">
            <span className="text-xs text-slate-500">تاریخ انتشار منبع</span>
            <strong className="mt-2 block text-sm text-white">{formatDate(item.publishedAt)}</strong>
            {item.publishedLabel && <p className="mt-3 text-xs leading-7 text-slate-500">{item.publishedLabel}</p>}
            {item.sourceUrl && <a href={item.sourceUrl} target="_blank" rel="noopener noreferrer" className="secondary-btn mt-5 w-full">پست اصلی یوتیوب <ArrowUpLeft className="h-4 w-4" /></a>}
          </div>
          <div className="surface-card p-5">
            <div className="flex items-center gap-2 text-amber-200"><Images className="h-4 w-4" /><span className="text-sm font-bold">رسانه</span></div>
            <p className="mt-3 text-sm leading-8 text-slate-400">تصاویر این صفحه پس از همگام‌سازی در رسانه وردپرس نگه‌داری می‌شوند تا نمایش عمومی به بارگذاری مستقیم از کامیونیتی یوتیوب وابسته نباشد.</p>
          </div>
        </aside>
      </div>
    </article>
  )
}

function resolvedMediaItems(item) {
  if (item.images?.length) return item.images
  if (item.media?.thumbnail) return [{ url: item.media.thumbnail, alt: item.title, type: item.media.type }]

  const derived = youtubeThumbnailFromUrl(item.sourceUrl)
  if (derived) return [{ url: derived, alt: item.title, type: item.sourceUrl?.includes('/shorts/') ? 'youtube-short' : 'youtube-video' }]

  return [{ url: '', alt: item.title, type: 'none' }]
}

function DailyDetailImage({ image, title }) {
  const [failed, setFailed] = useState(false)

  if (!image?.url || failed) {
    return <div className="daily-media-fallback min-h-[220px]"><ImageIcon className="h-9 w-9 text-amber-200/80" /><span>رسانه‌ای برای این پست ثبت نشده است</span></div>
  }

  return <img src={image.url} alt={image.alt || title} className={`daily-detail-image ${image.type === 'youtube-short' ? 'daily-detail-image-short' : ''}`} loading="lazy" decoding="async" referrerPolicy="no-referrer" onError={() => setFailed(true)} />
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
