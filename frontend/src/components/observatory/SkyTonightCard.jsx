import { Clock3, CloudMoon, Sunrise, Sunset } from 'lucide-react'
import ObservatoryCard from './ObservatoryCard'

export default function SkyTonightCard({ widget, loading = false }) {
  const data = widget?.data
  const highlight = data?.visibleHighlights?.[0]

  return (
    <ObservatoryCard
      eyebrow="Sky Tonight"
      title="آسمان امشب"
      description="خلاصه محاسباتی رصد امشب برای تهران."
      status={loading ? 'stale' : widget?.status}
      message={loading ? 'در حال محاسبه' : widget?.message}
      updatedAt={widget?.updatedAt}
      source={widget?.source}
      className="accent-blue"
    >
      <dl className="grid grid-cols-2 gap-3 text-xs">
        <Metric icon={Sunrise} label="طلوع" value={data?.sunrise?.localTime || '—'} />
        <Metric icon={Sunset} label="غروب" value={data?.sunset?.localTime || '—'} />
        <Metric icon={Clock3} label="بازه رصد" value={data?.bestObservationWindow?.labelFa || '—'} wide />
      </dl>

      <div className="mt-4 rounded-2xl border border-white/[.06] bg-white/[.025] p-4">
        <div className="flex items-center gap-3">
          <CloudMoon className="h-5 w-5 text-sky-200" />
          <strong className="text-sm text-white">{highlight?.title || (loading ? 'در حال آماده‌سازی' : 'هایلایت امشب نامشخص است')}</strong>
        </div>
        <p className="mt-3 text-xs leading-6 text-slate-500">{highlight?.detail || 'پس از دریافت داده، پیشنهاد رصدی امشب اینجا نمایش داده می‌شود.'}</p>
      </div>
    </ObservatoryCard>
  )
}

function Metric({ icon: Icon, label, value, wide }) {
  return <div className={`rounded-2xl border border-white/[.06] bg-white/[.025] p-3 ${wide ? 'col-span-2' : ''}`}><Icon className="h-4 w-4 text-sky-300" /><dt className="mt-3 text-slate-500">{label}</dt><dd className="mt-2 font-bold leading-6 text-white">{value}</dd></div>
}
