import { ArrowLeft, CloudMoon, Droplets, Gauge, Wind } from 'lucide-react'
import { motion } from 'framer-motion'
import { Link } from 'react-router-dom'
import { skyData } from '../../data/fallback'

export default function Hero() {
  return (
    <section className="relative min-h-[640px] overflow-hidden border-b border-white/[.08] sm:min-h-[700px] lg:min-h-[650px]">
      <div className="absolute inset-0">
        <video className="h-full w-full object-cover object-center opacity-50" autoPlay muted loop playsInline poster="/media/hero-earth-poster.jpg">
          <source src="/media/hero-earth.mp4" type="video/mp4" />
        </video>
        <div className="absolute inset-0 bg-gradient-to-l from-space-950 via-space-950/75 to-space-950/10" />
        <div className="absolute inset-0 bg-gradient-to-t from-space-950 via-transparent to-space-950/30" />
      </div>
      <div className="content-shell relative grid min-h-[640px] items-center gap-10 py-14 sm:min-h-[700px] lg:min-h-[650px] lg:grid-cols-[1.1fr_.9fr]">
        <motion.div initial={{ opacity: 0, x: 30 }} animate={{ opacity: 1, x: 0 }} transition={{ duration: .8 }} className="max-w-2xl">
          <span className="eyebrow">به جزیره نجوم خوش آمدید</span>
          <h1 className="mt-5 text-5xl font-black leading-[1.16] sm:text-6xl lg:text-7xl">
            <span className="text-gradient">کشف کنید.</span><br />یاد بگیرید. کاوش کنید.
          </h1>
          <p className="mt-6 max-w-xl text-base leading-8 text-slate-300 sm:text-lg">هر شب یک آسمان تازه؛ از وضعیت رصد و رویدادهای امشب تا سفر تعاملی میان سیاره‌ها، اخبار علمی و تصویر روز ناسا.</p>
          <div className="mt-8 flex flex-wrap gap-3">
            <Link to="/explore" className="primary-btn px-6 py-3">کاوش جهان <ArrowLeft className="h-4 w-4" /></Link>
            <Link to="/sky" className="secondary-btn px-6 py-3">آسمان امشب</Link>
          </div>
        </motion.div>

        <motion.div initial={{ opacity: 0, y: 24 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: .8, delay: .15 }} className="lg:justify-self-end">
          <div className="glass-panel panel-noise w-full max-w-sm rounded-3xl p-5 sm:p-6">
            <div className="flex items-center justify-between"><div><span className="text-xs font-semibold text-slate-500">وضعیت آسمان زنده</span><p className="mt-1 text-sm text-slate-300">{skyData.location}</p></div><CloudMoon className="h-9 w-9 text-blue-300" /></div>
            <div className="mt-7 flex items-end justify-between"><div><div className="text-6xl font-light text-white">{skyData.temperature}°</div><p className="mt-2 text-sm text-slate-400">{skyData.condition}</p></div><span className="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-xs text-emerald-300">مناسب رصد</span></div>
            <div className="mt-7 grid grid-cols-3 divide-x divide-x-reverse divide-white/10 border-t border-white/10 pt-5">
              <Metric icon={Droplets} label="رطوبت" value={`${skyData.humidity}٪`} />
              <Metric icon={Wind} label="باد" value={`${skyData.wind} km/h`} />
              <Metric icon={Gauge} label="فشار" value={`${skyData.pressure}`} />
            </div>
          </div>
        </motion.div>
      </div>
    </section>
  )
}

function Metric({ icon: Icon, label, value }) {
  return <div className="flex flex-col items-center gap-1 px-2 text-center"><Icon className="h-4 w-4 text-blue-300" /><span className="text-[10px] text-slate-500">{label}</span><strong className="text-xs text-white">{value}</strong></div>
}
