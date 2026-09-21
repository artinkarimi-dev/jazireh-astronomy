import { useEffect, useState } from 'react'
import { ExternalLink, Sun } from 'lucide-react'
import ObservatoryCard from './ObservatoryCard'
import { getSunNowViewModel } from './sunNowModel'

export default function SunNowCard({ widget, fallbackSun = null, loading = false }) {
  const view = getSunNowViewModel(widget, fallbackSun)
  const data = view.data
  const imageCandidates = view.imageCandidates
  const primaryImage = imageCandidates[0] || ''
  const secondaryImage = imageCandidates[1] || ''
  const [imageIndex, setImageIndex] = useState(0)
  const imageUrl = imageCandidates[imageIndex] || ''

  useEffect(() => {
    setImageIndex(0)
  }, [primaryImage, secondaryImage])

  return (
    <ObservatoryCard
      eyebrow="Sun Now"
      title="خورشید اکنون"
      description="آخرین تصویر خورشیدی ثبت‌شده از منبع علمی."
      status={loading ? 'stale' : view.status}
      message={loading ? 'در حال دریافت' : view.message}
      updatedAt={view.updatedAt}
      source={view.source}
      sourceUrl={view.sourceUrl}
      className="accent-orange"
      actions={view.sourceUrl && <ExternalLinkButton href={view.sourceUrl} />}
    >
      {imageUrl ? (
        <div className="observatory-media-frame observatory-media-square">
          <img
            src={imageUrl}
            alt={`تصویر خورشید ${data?.wavelength || ''}`}
            loading="lazy"
            decoding="async"
            referrerPolicy="no-referrer"
            onError={() => setImageIndex((current) => current + 1)}
          />
        </div>
      ) : (
        <EmptyState icon={Sun} label={loading ? 'در حال آماده‌سازی تصویر خورشید' : 'تصویر خورشید در دسترس نیست'} />
      )}
      <dl className="mt-4 grid grid-cols-2 gap-3 text-xs">
        <Metric label="طول موج" value={data?.wavelength || '—'} />
        <Metric label="زمان ثبت" value={formatDateTime(data?.observedAt)} />
      </dl>
    </ObservatoryCard>
  )
}

function Metric({ label, value }) {
  return <div className="rounded-2xl border border-white/[.06] bg-white/[.025] p-3"><dt className="text-slate-500">{label}</dt><dd className="mt-2 font-bold text-white">{value}</dd></div>
}

function EmptyState({ icon: Icon, label }) {
  return <div className="flex aspect-square items-center justify-center rounded-2xl border border-white/[.06] bg-white/[.025] text-center text-xs font-bold leading-6 text-slate-500"><div><Icon className="mx-auto mb-3 h-7 w-7 text-orange-200" />{label}</div></div>
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
