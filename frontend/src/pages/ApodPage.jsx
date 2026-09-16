import { Camera, ChevronLeft, ChevronRight, ExternalLink, Play } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { getApodDisplay } from '../lib/apodLocalization'
import { normalizeApodVideo } from '../lib/apodVideo'

export default function ApodPage() {
  const initialItems = useMemo(() => [], [])
  const [items, setItems] = useState(initialItems)
  const [index, setIndex] = useState(0)
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState(false)

  useEffect(() => {
    let active = true
    setLoading(true)
    setLoadError(false)
    api.get('/api/apod?limit=20').then((response) => {
      if (!active || !response.data?.length) return
      const blockedLocalImages = ['/media/galaxy.jpg', '/media/nebula.jpg', '/media/saturn.jpg', '/media/hero-planet.jpg']
      const usableItems = response.data.filter((entry) => {
        if (entry.mediaType === 'video') return Boolean(entry.mediaUrl || entry.sourceUrl)
        return entry.image && !blockedLocalImages.includes(entry.image)
      })
      if (usableItems.length) { setItems(usableItems); setIndex(0) }
    }).catch(() => { if (active) setLoadError(true) })
      .finally(() => { if (active) setLoading(false) })
    return () => { active = false }
  }, [])

  const item = items[index] || null
  const display = getApodDisplay(item)
  const isVideo = item?.mediaType === 'video' && (item.mediaUrl || item.sourceUrl)
  const change = (direction) => setIndex((current) => (current + direction + items.length) % items.length)
  usePageMeta(display.title || 'عکس روز ناسا', display.summary || 'تصویر نجومی روز ناسا در جزیره نجوم.')

  return (
    <>
      <PageHero eyebrow="Astronomy Picture of the Day" title="عکس روز ناسا" description="تصویرهای منتخب نجومی با محتوای فارسیِ آماده‌شده در جزیره؛ اگر ترجمه آماده نباشد، متن اصلی NASA با برچسب شفاف نمایش داده می‌شود." />
      <section className="content-shell section-space">
        {!item ? (
          <div className="surface-card flex min-h-[360px] flex-col items-center justify-center p-8 text-center">
            <Camera className="h-10 w-10 text-slate-600" />
            <h2 className="mt-4 text-2xl font-black text-white">{loading ? 'در حال دریافت APOD' : loadError ? 'دریافت APOD ممکن نبود' : 'APOD معتبری برای نمایش وجود ندارد'}</h2>
            <p className="mt-3 max-w-xl text-sm leading-8 text-slate-500">
              {loading ? 'درخواست از API داخلی جزیره در حال انجام است.' : 'برای جلوگیری از نمایش تصویر آرشیوی به‌جای داده روز، فقط داده معتبر ذخیره‌شده در WordPress یا دریافت‌شده از NASA نمایش داده می‌شود.'}
            </p>
          </div>
        ) : <div className="apod-layout">
          <div className="surface-card apod-panel p-3 sm:p-4">
            <div className="apod-image-frame relative">
              {isVideo ? (
                <ApodVideo item={item} display={display} />
              ) : (
                <ApodImage item={item} display={display} />
              )}
              {items.length > 1 && <div className="absolute bottom-4 left-4 flex gap-2"><button onClick={() => change(-1)} className="icon-button bg-black/70" aria-label="تصویر قبلی"><ChevronRight className="h-5 w-5" /></button><button onClick={() => change(1)} className="icon-button bg-black/70" aria-label="تصویر بعدی"><ChevronLeft className="h-5 w-5" /></button></div>}
            </div>
          </div>

          <aside className="surface-card apod-panel flex flex-col justify-center p-6 sm:p-8">
            <span className="eyebrow"><Camera className="h-4 w-4" />{item.date}</span>
            {item.isFallback ? <span className="mt-3 inline-flex w-fit rounded-full border border-amber-300/20 bg-amber-300/[.08] px-3 py-1 text-xs text-amber-100">داده پشتیبان</span> : null}
            {!display.hasPersianEditorial && display.warning ? <span className="mt-3 inline-flex w-fit rounded-full border border-sky-300/20 bg-sky-300/[.08] px-3 py-1 text-xs font-bold leading-6 text-sky-100">{display.warning}</span> : null}
            <h2 className="mt-4 text-[clamp(1.8rem,4vw,2.4rem)] font-black leading-[1.35] text-white">{display.title}</h2>
            {display.summary && <p className="mt-5 text-sm font-medium leading-8 text-slate-300">{display.summary}</p>}
            {display.content && display.content !== display.summary ? <p className="mt-4 text-sm leading-8 text-slate-500">{display.content}</p> : null}
            {display.hasPersianEditorial && display.titleOriginal ? (
              <details className="mt-5 rounded-2xl border border-white/[.08] bg-white/[.025] p-4 text-sm text-slate-400">
                <summary className="cursor-pointer font-bold text-slate-300">عنوان و متن اصلی NASA</summary>
                <h3 className="mt-4 text-left text-base font-bold leading-7 text-white" dir="ltr">{display.titleOriginal}</h3>
                {display.contentOriginal ? <p className="mt-3 text-left leading-7 text-slate-500" dir="ltr">{display.contentOriginal}</p> : null}
              </details>
            ) : null}
            {item.displayWarning ? <p className="mt-4 text-xs leading-6 text-amber-100">{item.displayWarning}</p> : null}
            <div className="mt-7 border-t border-white/[.08] pt-5 text-xs leading-6 text-slate-500">
              <Attribution item={item} />
            </div>
          </aside>
        </div>}
      </section>
    </>
  )
}

function Attribution({ item }) {
  const sourceName = item?.sourceName || 'NASA Astronomy Picture of the Day'
  const sourceUrl = safeHttpsUrl(item?.sourceUrl)
  const mediaUrl = safeHttpsUrl(item?.mediaUrl)
  const credit = item?.copyright || item?.credit || item?.photographer || ''

  return (
    <dl className="space-y-3">
      <div>
        <dt className="text-slate-600">منبع</dt>
        <dd className="mt-1 font-bold text-slate-300">
          {sourceUrl ? (
            <a href={sourceUrl} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 text-amber-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/50" aria-label={`مشاهده منبع رسمی APOD: ${sourceName}`}>
              {sourceName}
              <ExternalLink className="h-4 w-4" aria-hidden="true" />
            </a>
          ) : sourceName}
        </dd>
      </div>
      {credit ? (
        <div>
          <dt className="text-slate-600">اعتبار / حق نشر</dt>
          <dd className="mt-1 font-bold text-slate-300">{credit}</dd>
        </div>
      ) : null}
      {mediaUrl && mediaUrl !== sourceUrl ? (
        <div>
          <dt className="text-slate-600">رسانه اصلی</dt>
          <dd className="mt-1">
            <a href={mediaUrl} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 text-amber-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/50" aria-label="مشاهده رسانه اصلی APOD">
              مشاهده رسانه
              <ExternalLink className="h-4 w-4" aria-hidden="true" />
            </a>
          </dd>
        </div>
      ) : null}
    </dl>
  )
}

function ApodVideo({ item, display }) {
  const video = normalizeApodVideo(item)
  const [activated, setActivated] = useState(false)
  const [failed, setFailed] = useState(false)
  const title = display.title || item?.titleOriginal || 'NASA APOD video'
  const canLoadMedia = activated && !failed && (video.canEmbed || video.canPlayInline)

  useEffect(() => {
    setActivated(false)
    setFailed(false)
  }, [item?.sourceUrl, item?.mediaUrl])

  if (canLoadMedia && video.canEmbed) {
    return (
      <iframe
        src={video.embedUrl}
        title={title}
        className="apod-media-frame"
        loading="lazy"
        referrerPolicy="strict-origin-when-cross-origin"
        allow="fullscreen; picture-in-picture"
        allowFullScreen
        onError={() => setFailed(true)}
      />
    )
  }

  if (canLoadMedia && video.canPlayInline) {
    return (
      <video
        className="apod-media-frame"
        src={video.sourceUrl}
        poster={video.posterUrl || undefined}
        controls
        playsInline
        preload="metadata"
        onError={() => setFailed(true)}
      >
        <a href={video.sourceUrl} target="_blank" rel="noopener noreferrer">مشاهده ویدیو در منبع اصلی</a>
      </video>
    )
  }

  return (
    <div className="flex h-full min-h-[280px] items-center justify-center bg-black p-6 text-center">
      {video.posterUrl ? (
        <img
          src={video.posterUrl}
          alt=""
          className="absolute inset-0 h-full w-full object-cover opacity-50"
          loading="lazy"
          decoding="async"
          onError={(event) => { event.currentTarget.style.display = 'none' }}
        />
      ) : null}
      <div className="relative z-10 max-w-md">
        <span className="eyebrow justify-center">ویدیو APOD</span>
        <h3 className="mt-3 text-xl font-black leading-8 text-white">{title}</h3>
        {video.canEmbed || video.canPlayInline ? (
          <>
            <button
              type="button"
              className="primary-btn mx-auto mt-5"
              onClick={() => setActivated(true)}
              aria-label={`پخش ویدیوی ${title}`}
            >
              <Play className="h-4 w-4 fill-current" aria-hidden="true" />
              پخش ویدیو
            </button>
            <p className="mt-4 text-xs leading-6 text-slate-500">برای حفظ کارایی صفحه، ویدیو پس از انتخاب شما بارگذاری می‌شود.</p>
            {failed && video.sourceUrl ? (
              <a
                className="secondary-btn mx-auto mt-4"
                href={video.sourceUrl}
                target="_blank"
                rel="noopener noreferrer"
                aria-label={`مشاهده ویدیوی ${title} در منبع اصلی`}
              >
                مشاهده ویدیو در منبع اصلی
                <ExternalLink className="h-4 w-4" aria-hidden="true" />
              </a>
            ) : null}
          </>
        ) : video.sourceUrl ? (
          <a
            className="secondary-btn mx-auto mt-5"
            href={video.sourceUrl}
            target="_blank"
            rel="noopener noreferrer"
            aria-label={`مشاهده ویدیوی ${title} در منبع اصلی`}
          >
            مشاهده ویدیو در منبع اصلی
            <ExternalLink className="h-4 w-4" aria-hidden="true" />
          </a>
        ) : (
          <p className="mt-4 text-sm leading-7 text-slate-400">نشانی معتبر ویدیو برای این APOD در دسترس نیست.</p>
        )}
        {failed ? <p className="mt-4 text-xs leading-6 text-amber-100">بارگذاری ویدیو ممکن نبود؛ از پیوند منبع رسمی استفاده کنید.</p> : null}
      </div>
    </div>
  )
}

function safeHttpsUrl(value = '') {
  if (!value || typeof value !== 'string') return ''
  try {
    const url = new URL(value)
    return url.protocol === 'https:' ? url.href : ''
  } catch {
    return ''
  }
}

function ApodImage({ item, display }) {
  const image = item?.image || ''
  const [src, setSrc] = useState(image)
  const usingFallback = item?.isFallback

  useEffect(() => {
    setSrc(image)
  }, [image])

  return (
    <>
      {src ? (
        <img
          src={src}
          onError={() => setSrc('')}
          alt={usingFallback ? 'تصویر APOD با وضعیت داده غیرتازه' : display.title}
          className="h-full w-full object-contain"
          loading="eager"
          decoding="async"
        />
      ) : (
        <div className="flex h-full min-h-[280px] items-center justify-center p-8 text-center text-sm leading-7 text-slate-500">
          تصویر معتبر APOD برای این مورد در دسترس نیست.
        </div>
      )}
      {usingFallback ? <span className="absolute right-4 top-4 rounded-full border border-amber-300/20 bg-black/60 px-3 py-1 text-xs font-bold text-amber-100">تصویر جایگزین</span> : null}
    </>
  )
}
