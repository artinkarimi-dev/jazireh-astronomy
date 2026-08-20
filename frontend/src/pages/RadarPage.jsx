import { Info, Satellite, Signal, Star } from 'lucide-react'
import PageHero from '../components/PageHero'
import SkyRadar from '../components/radar/SkyRadar'
import usePageMeta from '../hooks/usePageMeta'

const tracked = [
  { name: 'ایستگاه فضایی بین‌المللی', code: 'ISS', altitude: '۴۱۷ کیلومتر', status: 'در محدوده نمایش', icon: Satellite },
  { name: 'تلسکوپ فضایی هابل', code: 'HST', altitude: '۵۳۵ کیلومتر', status: 'مسیر شبیه‌سازی‌شده', icon: Satellite },
  { name: 'مشتری', code: 'Jupiter', altitude: '۳۲ درجه', status: 'قابل مشاهده', icon: Star },
  { name: 'ماهواره هواشناسی', code: 'NOAA 18', altitude: '۸۵۴ کیلومتر', status: 'مسیر شبیه‌سازی‌شده', icon: Signal }
]

export default function RadarPage() {
  usePageMeta('شبیه‌ساز رادار آسمان', 'نمایش تعاملی و آموزشی اجرام و ماهواره‌ها در رادار شبیه‌سازی‌شده جزیره نجوم.')
  return (
    <>
      <PageHero eyebrow="رادار آسمان" title="مسیر اجرام را دنبال کنید" description="نمایی آموزشی برای آشنایی با موقعیت اجرام، گذر ماهواره‌ها و مسیرهای آسمانی." />
      <section className="content-shell section-space">
        <div className="mb-5 flex items-start gap-3 rounded-2xl border border-sky-300/15 bg-sky-400/[.05] p-4 text-sm leading-7 text-slate-400"><Info className="mt-1 h-4 w-4 shrink-0 text-sky-300" />این بخش شبیه‌ساز آموزشی است و برای رهگیری زنده باید به سرویس داده مداری متصل شود.</div>
        <div className="radar-layout">
          <div className="surface-card radar-panel flex items-center justify-center p-4 sm:p-5"><SkyRadar /></div>
          <aside className="surface-card radar-panel p-5 sm:p-6"><h2 className="card-title">اجرام در محدوده</h2><p className="mt-1 text-xs text-slate-500">موقعیت‌های نمایش‌داده‌شده آموزشی هستند</p><div className="mt-6 space-y-3">{tracked.map(({ name, code, altitude, status, icon: Icon }) => <div key={code} className="rounded-2xl border border-white/[.07] bg-white/[.025] p-4"><div className="flex flex-wrap items-center gap-3"><span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-400/10 text-sky-200"><Icon className="h-5 w-5" /></span><div className="min-w-0 flex-1"><strong className="block text-sm text-white">{name}</strong><span className="mt-1 block text-xs text-slate-500">{code}</span></div><span className="text-xs text-slate-500">{altitude}</span></div><div className="mt-3 border-t border-white/[.06] pt-3 text-xs text-slate-600">{status}</div></div>)}</div></aside>
        </div>
      </section>
    </>
  )
}
