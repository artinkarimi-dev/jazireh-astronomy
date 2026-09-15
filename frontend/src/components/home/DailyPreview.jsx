import { ArrowLeft, ArrowUpLeft, ImageIcon } from 'lucide-react'
import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { formatDate } from '../../lib/utils'

export default function DailyPreview({ item = null, content = {} }) {
  const media = item?.media || {}
  const image = media.thumbnail || item?.images?.[0]?.url || youtubeThumbnailFromUrl(item?.sourceUrl)
  const isShort = media.type === 'youtube-short' || item?.sourceUrl?.includes('/shorts/')
  const [imageFailed, setImageFailed] = useState(false)
  const displayImage = image && !imageFailed ? image : ''
  const usingFallback = !image || imageFailed

  useEffect(() => {
    setImageFailed(false)
  }, [image])

  return (
    <article className="surface-card home-daily-card accent-yellow p-5 sm:p-6">
      <div className="grid h-full gap-5 lg:grid-cols-[minmax(0,.95fr)_minmax(260px,.75fr)]">
        <div className="flex min-h-[220px] flex-col justify-between">
          <div>
            <span className="eyebrow">{content.daily_eyebrow || 'جزیره دیلی'}</span>
            <h3 className="mt-3 text-2xl font-black leading-[1.5] text-white">{content.daily_title || 'عکس روز ناسا در جزیره'}</h3>
            <p className="mt-4 text-sm leading-8 text-slate-400">{item?.excerpt || 'پست‌های منتخب کامیونیتی یوتیوب جزیره بعد از همگام‌سازی در وردپرس اینجا نمایش داده می‌شوند.'}</p>
          </div>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link to={item ? `/jazireh-daily/${item.slug}` : '/jazireh-daily'} className="primary-btn">{content.daily_cta || 'مشاهده جزیره دیلی'} <ArrowLeft className="h-4 w-4" /></Link>
            {item?.sourceUrl && <a href={item.sourceUrl} target="_blank" rel="noopener noreferrer" className="secondary-btn">پست اصلی <ArrowUpLeft className="h-4 w-4" /></a>}
          </div>
        </div>
        <div className={`daily-preview-visual ${isShort && !usingFallback ? 'daily-preview-short' : ''}`}>
          {displayImage ? <img src={displayImage} alt={item?.title || 'جزیره دیلی'} className="h-full w-full object-cover" loading="lazy" decoding="async" referrerPolicy="no-referrer" onError={() => setImageFailed(true)} /> : <div className="daily-media-fallback"><ImageIcon className="h-9 w-9 text-amber-200/80" /><span>رسانه‌ای برای این پست ثبت نشده است</span></div>}
          <div className="daily-preview-overlay">
            {usingFallback && item ? <span className="mb-2 inline-flex rounded-full border border-amber-300/20 bg-amber-300/[.1] px-2.5 py-1 text-[10px] font-bold text-amber-100">رسانه تاییدشده ثبت نشده</span> : null}
            <strong className="line-clamp-2 text-base leading-8 text-white">{item?.title || 'جزیره دیلی'}</strong>
            <span className="mt-2 block text-xs text-slate-300">{item?.publishedAt ? formatDate(item.publishedAt) : 'همگام‌سازی دوره‌ای از یوتیوب کامیونیتی'}</span>
          </div>
        </div>
      </div>
    </article>
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
