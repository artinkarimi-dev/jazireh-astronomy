import { useEffect, useState } from 'react'
import { Clock3, CloudMoon, Droplets, Eye, Moon, Sun, Wind } from 'lucide-react'
import PageHero from '../components/PageHero'
import InteractiveSkyMap from '../components/sky/InteractiveSkyMap'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { skyData as fallbackSky } from '../data/fallback'

export default function SkyTodayPage() {
  const [sky, setSky] = useState(fallbackSky)
  usePageMeta('آسمان امروز', 'وضعیت آسمان، ماه، زمان مناسب رصد و رویدادهای قابل مشاهده در جزیره نجوم.')

  useEffect(() => {
    let active = true
    api.get('/api/sky/today').then((response) => { if (active && response.data) setSky({ ...fallbackSky, ...response.data }) }).catch(() => {})
    return () => { active = false }
  }, [])

  return (
    <>
      <PageHero eyebrow="راهنمای رصد جزیره" title="آسمان امروز" description="وضعیت آسمان، فاز ماه، زمان مناسب رصد و مهم‌ترین رویدادهای امشب را یکجا ببینید." />

      <section className="content-shell section-space">
        <div className="sky-metrics-grid">
          <MetricCard icon={CloudMoon} title="وضعیت آسمان" value={sky.condition} detail={`دید افقی ${sky.visibility} کیلومتر`} accent="blue" />
          <MetricCard icon={Moon} title="فاز ماه" value={`${sky.moonPhase}، ${sky.moonIllumination}٪`} detail={`سن ماه: ${sky.moonAge} روز`} accent="purple" />
          <MetricCard icon={Sun} title="طلوع و غروب" value={`${sky.sunrise} / ${sky.sunset}`} detail="زمان محلی" accent="orange" />
          <MetricCard icon={Clock3} title="بهترین زمان رصد" value={sky.bestTime} detail="برآورد براساس شرایط آسمان" accent="yellow" />
        </div>

        <div className="sky-main-grid mt-5">
          <div className="surface-card sky-main-panel p-4 sm:p-5">
            <div className="mb-4 flex items-center justify-between"><div><span className="eyebrow">نقشه آسمان</span><h2 className="mt-2 card-title">صورت‌های فلکی امشب</h2></div><span className="text-xs text-slate-600">برای انتخاب کلیک کنید</span></div>
            <InteractiveSkyMap className="h-[320px] sm:h-[420px] lg:h-[520px]" />
          </div>

          <div className="surface-card sky-main-panel p-5 sm:p-6">
            <h3 className="card-title">شرایط رصد</h3>
            <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
              <Quality icon={Eye} label="شفافیت" value={`${sky.transparency} از ۱۰`} />
              <Quality icon={Eye} label="دید نجومی" value={`${sky.seeing} از ۱۰`} />
              <Quality icon={Droplets} label="رطوبت" value={`${sky.humidity}٪`} />
              <Quality icon={Wind} label="سرعت باد" value={`${sky.wind} km/h`} />
            </div>
            <h3 className="mt-7 card-title">رویدادهای امشب</h3>
            <div className="mt-4 space-y-3">{(sky.events || []).map((event) => <div key={`${event.title}-${event.time}`} className="rounded-2xl border border-white/[.06] bg-white/[.02] p-4"><div className="flex items-center justify-between gap-3"><strong className="text-sm text-white">{event.title}</strong><span className="text-xs text-amber-200">{event.time}</span></div><p className="mt-2 text-xs leading-6 text-slate-500">{event.detail}</p></div>)}</div>
          </div>
        </div>
      </section>
    </>
  )
}

function MetricCard({ icon: Icon, title, value, detail, accent }) {
  return <div className={`surface-card sky-metric-card accent-${accent} p-5`}><div className="flex items-start justify-between gap-4"><div className="min-w-0"><span className="text-xs text-slate-500">{title}</span><strong className="mt-3 block text-lg leading-8 text-white sm:text-xl">{value}</strong><p className="mt-2 text-xs leading-6 text-slate-500">{detail}</p></div><span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/[.04]"><Icon className="h-5 w-5" /></span></div></div>
}
function Quality({ icon: Icon, label, value }) { return <div className="metric-tile"><Icon className="h-5 w-5 text-sky-300" /><span className="mt-3 block text-xs text-slate-500">{label}</span><strong className="mt-1 block text-sm text-white">{value}</strong></div> }
