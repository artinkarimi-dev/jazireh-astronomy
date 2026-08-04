import { Satellite, Signal, Star } from 'lucide-react'
import PageHero from '../components/PageHero'
import SkyRadar from '../components/radar/SkyRadar'

const tracked = [
  { name: 'ایستگاه فضایی بین‌المللی', code: 'ISS', altitude: '۴۱۷ کیلومتر', status: 'بالای افق', icon: Satellite },
  { name: 'تلسکوپ فضایی هابل', code: 'HST', altitude: '۵۳۵ کیلومتر', status: 'در حال عبور', icon: Satellite },
  { name: 'مشتری', code: 'Jupiter', altitude: '۳۲ درجه', status: 'قابل مشاهده', icon: Star },
  { name: 'ماهواره هواشناسی', code: 'NOAA 18', altitude: '۸۵۴ کیلومتر', status: 'بالای افق', icon: Signal }
]

export default function RadarPage() {
  return (
    <>
      <PageHero eyebrow="ردیابی زنده" title="رادار آسمان" description="نمایشی تعاملی برای ردیابی ماهواره‌ها، اجرام درخشان و گذرهای قابل مشاهده در آسمان امشب." image="/media/radar.jpg" />
      <section className="content-shell py-10 sm:py-14"><div className="grid gap-6 xl:grid-cols-[1.2fr_.8fr]"><div className="glass-panel flex min-h-[600px] items-center justify-center rounded-3xl p-6"><SkyRadar /></div><div className="glass-panel rounded-3xl p-6"><div className="flex items-center justify-between"><div><h2 className="card-title">اجرام در حال رهگیری</h2><p className="mt-1 text-xs text-slate-500">به‌روزرسانی شبیه‌سازی‌شده هر ۳۰ ثانیه</p></div><span className="h-2.5 w-2.5 animate-pulse rounded-full bg-emerald-400 shadow-[0_0_16px_rgba(52,211,153,.8)]" /></div><div className="mt-6 space-y-3">{tracked.map(({ name, code, altitude, status, icon: Icon }) => <div key={code} className="rounded-2xl border border-white/[.07] bg-white/[.025] p-4"><div className="flex items-center gap-3"><span className="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/10 text-blue-300"><Icon className="h-5 w-5" /></span><div><strong className="text-sm text-white">{name}</strong><span className="mt-1 block text-xs text-slate-500">{code}</span></div></div><div className="mt-4 flex items-center justify-between border-t border-white/[.06] pt-3 text-xs"><span className="text-slate-500">{altitude}</span><span className="text-emerald-300">{status}</span></div></div>)}</div></div></div></section>
    </>
  )
}
