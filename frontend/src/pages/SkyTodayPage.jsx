import { Clock3, CloudMoon, Droplets, Eye, Moon, Sun, Wind } from 'lucide-react'
import PageHero from '../components/PageHero'
import InteractiveSkyMap from '../components/sky/InteractiveSkyMap'
import SectionHeader from '../components/SectionHeader'
import { skyData } from '../data/fallback'

export default function SkyTodayPage() {
  return (
    <>
      <PageHero eyebrow="رصدخانه شخصی شما" title="آسمان امروز" description="وضعیت آسمان، بهترین زمان رصد، ماه، طلوع و غروب و رویدادهای قابل مشاهده امشب را یکجا ببینید." image="/media/stars-vertical-poster.jpg" />
      <section className="content-shell py-10 sm:py-14">
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <MetricCard icon={CloudMoon} title="وضعیت آسمان" value={skyData.condition} detail={`دید افقی ${skyData.visibility} کیلومتر`} />
          <MetricCard icon={Moon} title="فاز ماه" value={`${skyData.moonPhase}، ${skyData.moonIllumination}٪`} detail={`سن ماه: ${skyData.moonAge} روز`} />
          <MetricCard icon={Sun} title="طلوع و غروب" value={`${skyData.sunrise} / ${skyData.sunset}`} detail="طول روز حدود ۱۴ ساعت و ۳۵ دقیقه" />
          <MetricCard icon={Clock3} title="بهترین زمان رصد" value={skyData.bestTime} detail="شرایط مناسب و آلودگی نوری کمتر" />
        </div>
        <div className="mt-10 grid gap-5 xl:grid-cols-[1.65fr_.8fr]">
          <div><SectionHeader eyebrow="نقشه تعاملی" title="آسمان امشب" description="روی صورت‌های فلکی کلیک کنید تا مسیر ستاره‌ها و نام آن‌ها برجسته شود." /><InteractiveSkyMap className="min-h-[500px]" /></div>
          <div className="space-y-5">
            <div className="glass-panel rounded-3xl p-6"><h3 className="card-title">کیفیت رصد</h3><div className="mt-6 grid grid-cols-2 gap-3"><Quality icon={Eye} label="شفافیت" value={`${skyData.transparency} از ۱۰`} /><Quality icon={Eye} label="دید نجومی" value={`${skyData.seeing} از ۱۰`} /><Quality icon={Droplets} label="رطوبت" value={`${skyData.humidity}٪`} /><Quality icon={Wind} label="سرعت باد" value={`${skyData.wind} km/h`} /></div></div>
            <div className="glass-panel rounded-3xl p-6"><h3 className="card-title">رویدادهای امشب</h3><div className="mt-5 space-y-4">{skyData.events.map((event) => <div key={event.title} className="rounded-2xl border border-white/[.07] bg-white/[.025] p-4"><div className="flex items-center justify-between gap-3"><strong className="text-sm text-white">{event.title}</strong><span className="rounded-full bg-blue-500/10 px-2 py-1 text-xs text-blue-300">{event.time}</span></div><p className="mt-2 text-xs leading-6 text-slate-500">{event.detail}</p></div>)}</div></div>
          </div>
        </div>
      </section>
    </>
  )
}

function MetricCard({ icon: Icon, title, value, detail }) {
  return <div className="glass-panel glass-panel-hover rounded-3xl p-5"><div className="flex items-start justify-between"><div><span className="text-xs text-slate-500">{title}</span><strong className="mt-3 block text-xl text-white">{value}</strong><p className="mt-2 text-xs text-slate-500">{detail}</p></div><span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-300"><Icon className="h-5 w-5" /></span></div></div>
}

function Quality({ icon: Icon, label, value }) {
  return <div className="rounded-2xl border border-white/[.07] bg-black/15 p-4"><Icon className="h-5 w-5 text-blue-300" /><span className="mt-3 block text-xs text-slate-500">{label}</span><strong className="mt-1 block text-sm text-white">{value}</strong></div>
}
