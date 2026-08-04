import { useState } from 'react'
import { Info, MousePointer2, Rotate3D, Search, X } from 'lucide-react'
import { motion, AnimatePresence } from 'framer-motion'
import PageHero from '../components/PageHero'
import SolarSystemScene from '../components/space/SolarSystemScene'
import { celestialObjects } from '../data/fallback'

export default function ExplorePage() {
  const [selected, setSelected] = useState(celestialObjects[3])
  const [filter, setFilter] = useState('همه')
  const filtered = filter === 'همه' ? celestialObjects : celestialObjects.filter((p) => p.type.includes(filter))
  return (
    <>
      <PageHero eyebrow="تجربه تعاملی سه‌بعدی" title="کاوش فضا" description="با موس یا لمس در منظومه شمسی حرکت کنید، روی هر سیاره کلیک کنید و واقعیت‌های علمی و مشخصات آن را ببینید." image="/media/hero-planet.jpg">
        <div className="flex flex-wrap gap-3 text-xs text-slate-300"><span className="secondary-btn"><MousePointer2 className="h-4 w-4" /> کلیک برای انتخاب</span><span className="secondary-btn"><Rotate3D className="h-4 w-4" /> کشیدن برای چرخش</span></div>
      </PageHero>
      <section className="content-shell py-10">
        <div className="mb-5 flex flex-wrap gap-2">{['همه','سنگی','گازی','یخی'].map((item) => <button key={item} onClick={() => setFilter(item)} className={filter === item ? 'primary-btn' : 'secondary-btn'}>{item}</button>)}</div>
        <div className="relative min-h-[690px] overflow-hidden rounded-3xl border border-white/10 bg-black shadow-panel">
          <div className="absolute inset-0"><SolarSystemScene planets={filtered} selected={selected} onSelect={setSelected} /></div>
          <div className="pointer-events-none absolute right-5 top-5 z-10 flex items-center gap-2 rounded-xl border border-white/10 bg-space-950/70 px-3 py-2 text-xs text-slate-300 backdrop-blur"><Search className="h-4 w-4 text-blue-300" /> برای دیدن اطلاعات، یک سیاره را انتخاب کنید</div>
          <AnimatePresence>
            {selected && <motion.aside initial={{ opacity: 0, x: -30 }} animate={{ opacity: 1, x: 0 }} exit={{ opacity: 0, x: -30 }} className="absolute bottom-4 left-4 right-4 z-20 max-h-[70%] overflow-auto rounded-3xl border border-white/10 bg-space-950/88 p-5 shadow-panel backdrop-blur-2xl sm:right-auto sm:top-4 sm:w-[390px] custom-scrollbar">
              <button onClick={() => setSelected(null)} className="icon-button absolute left-4 top-4"><X className="h-4 w-4" /></button>
              <span className="eyebrow">{selected.type}</span><h2 className="mt-2 text-3xl font-black text-white">{selected.name}</h2>
              <div className="mt-5 grid grid-cols-2 gap-2">{Object.entries(selected.stats).map(([key, value]) => <div key={key} className="rounded-2xl border border-white/[.07] bg-white/[.03] p-3"><span className="text-[10px] text-slate-500">{{ diameter:'قطر', day:'طول روز', year:'طول سال', moons:'قمرها' }[key]}</span><strong className="mt-1 block text-xs text-white">{value}</strong></div>)}</div>
              <h3 className="mt-6 flex items-center gap-2 font-bold text-white"><Info className="h-4 w-4 text-blue-300" /> واقعیت‌های جالب</h3>
              <ul className="mt-4 space-y-3">{selected.facts.map((fact) => <li key={fact} className="flex gap-3 text-sm leading-7 text-slate-300"><span className="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-300 shadow-[0_0_10px_rgba(96,165,250,.8)]" />{fact}</li>)}</ul>
            </motion.aside>}
          </AnimatePresence>
        </div>
      </section>
    </>
  )
}
