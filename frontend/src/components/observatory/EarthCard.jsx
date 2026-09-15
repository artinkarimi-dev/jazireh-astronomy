import { useEffect, useState } from 'react'
import { ExternalLink, Globe2 } from 'lucide-react'
import ObservatoryCard from './ObservatoryCard'

export default function EarthCard({ widget, loading = false }) {
  const data = widget?.data
  const imageUrl = normalizeImageUrl(data?.image)
  const [imageFailed, setImageFailed] = useState(false)

  useEffect(() => {
    setImageFailed(false)
  }, [imageUrl])

  return (
    <ObservatoryCard
      eyebrow="Earth From Space"
      title="زمین از فضا"
      description="آخرین تصویر رنگ طبیعی زمین از ماموریت DSCOVR."
      status={loading ? 'stale' : widget?.status}
      message={loading ? 'در حال دریافت' : widget?.message}
      updatedAt={widget?.updatedAt}
      source={widget?.source || data?.source}
      sourceUrl={widget?.sourceUrl || data?.sourceUrl}
      className="accent-blue"
      actions={data?.sourceUrl && <ExternalLinkButton href={data.sourceUrl} />}
    >
      {imageUrl && !imageFailed ? (
        <div className="observatory-media-frame observatory-media-square">
          <img
            src={imageUrl}
            alt={data.title || 'Earth From Space'}
            loading="lazy"
            decoding="async"
            referrerPolicy="no-referrer"
            onError={() => setImageFailed(true)}
          />
        </div>
      ) : (
        <EmptyState label={loading ? 'در حال آماده‌سازی تصویر زمین' : 'تصویر زمین در دسترس نیست'} />
      )}

      <strong className="mt-4 line-clamp-2 block text-sm leading-7 text-white">{data?.title || 'Earth From Space'}</strong>
      <p className="mt-2 line-clamp-3 text-xs leading-6 text-slate-500">{data?.caption || 'پس از دریافت داده، توضیح تصویر زمین اینجا نمایش داده می‌شود.'}</p>
      <dl className="mt-4 grid grid-cols-2 gap-3 text-xs">
        <Metric label="زمان ثبت" value={formatDateTime(data?.timestamp)} />
        <Metric label="منبع" value={data?.provider || data?.source || '—'} />
      </dl>
    </ObservatoryCard>
  )
}

function normalizeImageUrl(value) {
  if (!value || typeof value !== 'string') return ''
  return value.trim()
}

function Metric({ label, value }) {
  return <div className="rounded-2xl border border-white/[.06] bg-white/[.025] p-3"><dt className="text-slate-500">{label}</dt><dd className="mt-2 line-clamp-2 font-bold leading-6 text-white">{value}</dd></div>
}

function EmptyState({ label }) {
  return <div className="flex aspect-square items-center justify-center rounded-2xl border border-white/[.06] bg-white/[.025] text-center text-xs font-bold leading-6 text-slate-500"><div><Globe2 className="mx-auto mb-3 h-7 w-7 text-sky-200" />{label}</div></div>
}

function ExternalLinkButton({ href }) {
  return <a className="icon-button" href={href} target="_blank" rel="noreferrer" aria-label="مشاهده منبع"><ExternalLink className="h-4 w-4" /></a>
}

function formatDateTime(value) {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat('fa-IR', { hour: '2-digit', minute: '2-digit', month: 'short', day: 'numeric' }).format(new Date(value))
  } catch {
    return '—'
  }
}
