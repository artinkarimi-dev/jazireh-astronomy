import { useEffect, useState } from 'react'
import { Camera, ExternalLink } from 'lucide-react'
import ObservatoryCard from './ObservatoryCard'
import { getApodDisplay } from '../../lib/apodLocalization'

export default function ApodCard({ widget, loading = false }) {
  const item = widget?.data?.latest
  const display = getApodDisplay(item)
  const imageUrl = normalizeImageUrl(item?.image)
  const [imageFailed, setImageFailed] = useState(false)
  const displayImage = imageUrl && !imageFailed ? imageUrl : ''
  const usingFallback = !imageUrl || imageFailed

  useEffect(() => {
    setImageFailed(false)
  }, [imageUrl])

  return (
    <ObservatoryCard
      eyebrow="APOD"
      title="تصویر روز ناسا"
      description="تصویر یا ویدیوی منتخب نجومی روز."
      status={loading ? 'stale' : widget?.status}
      message={loading ? 'در حال دریافت' : widget?.message}
      updatedAt={widget?.updatedAt}
      source={widget?.source}
      sourceUrl={widget?.sourceUrl}
      className="accent-yellow"
      actions={item?.sourceUrl && <a className="icon-button" href={item.sourceUrl} target="_blank" rel="noreferrer" aria-label="مشاهده منبع"><ExternalLink className="h-4 w-4" /></a>}
    >
      {displayImage ? (
        <div className="observatory-media-frame observatory-media-video">
          <img
            src={displayImage}
            alt={usingFallback ? 'تصویر APOD با وضعیت داده غیرتازه' : display.title}
            loading="lazy"
            decoding="async"
            referrerPolicy="no-referrer"
            onError={() => setImageFailed(true)}
          />
        </div>
      ) : (
        <div className="flex aspect-video items-center justify-center rounded-2xl border border-white/[.06] bg-white/[.025] text-center text-xs font-bold leading-6 text-slate-500">
          <div><Camera className="mx-auto mb-3 h-7 w-7 text-amber-200" />{loading ? 'در حال آماده‌سازی تصویر روز' : 'تصویر روز در دسترس نیست'}</div>
        </div>
      )}
      {usingFallback && item ? <span className="mt-4 inline-flex w-fit rounded-full border border-amber-300/20 bg-amber-300/[.08] px-2.5 py-1 text-[10px] font-bold leading-5 text-amber-100">تصویر تاییدشده در دسترس نیست</span> : null}
      {!display.hasPersianEditorial && item ? <span className="mt-4 inline-flex w-fit rounded-full border border-sky-300/20 bg-sky-300/[.08] px-2.5 py-1 text-[10px] font-bold leading-5 text-sky-100">متن اصلی NASA</span> : null}
      <strong className="mt-3 line-clamp-2 block text-sm leading-7 text-white">{display.title || 'NASA Astronomy Picture of the Day'}</strong>
      <p className="mt-2 line-clamp-3 text-xs leading-6 text-slate-500">{display.summary || display.content || 'پس از دریافت داده، توضیح تصویر روز نمایش داده می‌شود.'}</p>
    </ObservatoryCard>
  )
}

function normalizeImageUrl(value) {
  if (!value || typeof value !== 'string') return ''
  return value.trim()
}
