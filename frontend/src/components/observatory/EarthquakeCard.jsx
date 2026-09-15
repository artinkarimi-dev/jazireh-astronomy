import { Activity, Waves } from 'lucide-react'
import ObservatoryCard from './ObservatoryCard'

export default function EarthquakeCard({ widget, loading = false }) {
  const data = widget?.data
  const earthquakes = Array.isArray(data?.earthquakes) ? data.earthquakes.slice(0, 4) : []

  return (
    <ObservatoryCard
      eyebrow="Earthquakes"
      title="زمین‌لرزه‌های اخیر"
      description="آخرین رخدادهای ثبت‌شده در فید جهانی USGS."
      status={loading ? 'stale' : widget?.status}
      message={loading ? 'در حال دریافت' : widget?.message}
      updatedAt={widget?.updatedAt}
      source={widget?.source || data?.source}
      sourceUrl={widget?.sourceUrl || data?.sourceUrl}
      className="accent-orange"
    >
      {earthquakes.length ? (
        <div className="space-y-3">
          {earthquakes.map((item) => (
            <a
              key={item.id || `${item.place}-${item.time}`}
              href={item.detailUrl || data?.sourceUrl || widget?.sourceUrl}
              target="_blank"
              rel="noreferrer"
              className="block rounded-2xl border border-white/[.06] bg-white/[.025] p-3 transition hover:border-orange-200/25 hover:bg-white/[.045]"
            >
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <strong className="line-clamp-2 text-sm leading-6 text-white">{item.place || 'مکان نامشخص'}</strong>
                  <div className="mt-2 flex flex-wrap gap-2 text-[11px] text-slate-500">
                    <span>{formatDateTime(item.time)}</span>
                    <span>عمق {formatDepth(item.depth)}</span>
                  </div>
                </div>
                <Magnitude value={item.magnitude} />
              </div>
            </a>
          ))}
        </div>
      ) : (
        <EmptyState label={loading ? 'در حال دریافت فید زمین‌لرزه‌ها' : 'رخدادی برای نمایش در دسترس نیست'} />
      )}

      <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-white/[.06] bg-white/[.025] p-3 text-xs text-slate-500">
        <span className="flex items-center gap-2"><Activity className="h-4 w-4 text-orange-200" />{formatCount(data?.count)}</span>
        <span>{formatDateTime(data?.generatedAt)}</span>
      </div>
    </ObservatoryCard>
  )
}

function Magnitude({ value }) {
  const label = typeof value === 'number' ? value.toLocaleString('fa-IR', { maximumFractionDigits: 1 }) : '—'
  return <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl border border-orange-300/20 bg-orange-300/10 text-sm font-black text-orange-100">{label}</span>
}

function EmptyState({ label }) {
  return <div className="flex min-h-[220px] items-center justify-center rounded-2xl border border-white/[.06] bg-white/[.025] text-center text-xs font-bold leading-6 text-slate-500"><div><Waves className="mx-auto mb-3 h-7 w-7 text-orange-200" />{label}</div></div>
}

function formatDepth(value) {
  if (typeof value !== 'number') return '—'
  return `${value.toLocaleString('fa-IR', { maximumFractionDigits: 1 })} km`
}

function formatCount(value) {
  if (typeof value !== 'number') return '— رخداد'
  return `${value.toLocaleString('fa-IR')} رخداد امروز`
}

function formatDateTime(value) {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat('fa-IR', { hour: '2-digit', minute: '2-digit', month: 'short', day: 'numeric' }).format(new Date(value))
  } catch {
    return '—'
  }
}
