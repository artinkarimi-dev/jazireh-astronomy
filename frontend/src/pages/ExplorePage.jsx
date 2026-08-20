import { useEffect, useMemo, useState } from 'react'
import { Info, MousePointer2, Orbit } from 'lucide-react'
import PageHero from '../components/PageHero'
import SolarSystemExplorer from '../components/space/SolarSystemExplorer'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { celestialObjects as fallbackObjects } from '../data/fallback'

const filters = [{ label: 'همه', value: 'همه' }, { label: 'سنگی', value: 'سنگی' }, { label: 'غول گازی', value: 'گازی' }, { label: 'غول یخی', value: 'یخی' }]

export default function ExplorePage() {
  const [objects, setObjects] = useState(fallbackObjects)
  const [selected, setSelected] = useState(fallbackObjects.find((item) => item.slug === 'earth') || fallbackObjects[0])
  const [filter, setFilter] = useState('همه')
  usePageMeta('کاوش فضا', 'سیاره‌های منظومه شمسی را در تجربه تعاملی فارسی جزیره کاوش کنید.')

  useEffect(() => {
    let active = true
    api.get('/api/objects').then((response) => {
      if (!active || !response.data?.length) return
      setObjects(response.data)
      setSelected(response.data.find((item) => item.slug === 'earth') || response.data[0])
    }).catch(() => {})
    return () => { active = false }
  }, [])

  const filtered = useMemo(() => filter === 'همه' ? objects : objects.filter((item) => item.type.includes(filter)), [filter, objects])
  useEffect(() => {
    if (selected && !filtered.some((item) => item.id === selected.id)) setSelected(filtered[0] || null)
  }, [filtered, selected])

  return (
    <>
      <PageHero eyebrow="سفر در منظومه شمسی" title="سیاره‌ها را از نزدیک بشناسید" description="سیاره موردنظر را انتخاب کنید و ویژگی‌ها، زمان گردش و واقعیت‌های علمی آن را بخوانید.">
        <span className="secondary-btn"><MousePointer2 className="h-4 w-4" />انتخاب سیاره</span>
        <span className="secondary-btn"><Orbit className="h-4 w-4" />نمای مداری</span>
      </PageHero>

      <section className="content-shell section-space">
        <div className="mb-5 flex flex-wrap gap-2">{filters.map((item) => <button type="button" key={item.value} onClick={() => setFilter(item.value)} className={filter === item.value ? 'primary-btn whitespace-nowrap' : 'secondary-btn whitespace-nowrap'}>{item.label}</button>)}</div>
        <div className="explore-layout">
          <div className="surface-card explore-panel p-3 sm:p-4">
            <SolarSystemExplorer planets={filtered} selected={selected} onSelect={setSelected} />
          </div>

          <aside className="surface-card explore-panel p-5 sm:p-7">
            {selected ? (
              <div className="flex h-full flex-col">
                <div>
                  <span className="eyebrow">{selected.type}</span>
                  <h2 className="mt-3 text-3xl font-black text-white sm:text-4xl">{selected.name}</h2>
                  <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">{Object.entries(selected.stats || {}).map(([key, value]) => <div key={key} className="metric-tile"><span className="text-[10px] text-slate-500">{{ diameter: 'قطر', day: 'طول روز', year: 'طول سال', moons: 'قمرها' }[key] || key}</span><strong className="mt-2 block text-xs text-white">{value}</strong></div>)}</div>
                </div>
                <div className="mt-7">
                  <h3 className="flex items-center gap-2 font-bold text-white"><Info className="h-4 w-4 text-amber-300" />واقعیت‌های جالب</h3>
                  <ul className="mt-4 space-y-3">{(selected.facts || []).map((fact) => <li key={fact} className="flex gap-3 text-sm leading-7 text-slate-300"><span className="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gradient-to-r from-amber-300 to-orange-400" />{fact}</li>)}</ul>
                </div>
                <p className="mt-auto border-t border-white/[.07] pt-5 text-xs leading-6 text-slate-600">فاصله‌ها و اندازه‌ها در این نمایش برای آموزش ساده‌سازی شده‌اند.</p>
              </div>
            ) : <div className="flex h-full items-center justify-center text-sm text-slate-400">یک سیاره را انتخاب کنید.</div>}
          </aside>
        </div>
      </section>
    </>
  )
}
