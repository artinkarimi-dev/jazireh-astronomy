import { useEffect, useMemo, useState } from 'react'
import { ArrowLeft, Clock3, Search } from 'lucide-react'
import { Link } from 'react-router-dom'
import { motion } from 'framer-motion'
import PageHero from '../components/PageHero'
import { newsItems as fallback } from '../data/fallback'
import { api } from '../lib/api'

export default function NewsPage() {
  const [items, setItems] = useState(fallback)
  const [query, setQuery] = useState('')
  const [category, setCategory] = useState('همه')
  useEffect(() => { api.get('/api/news').then((res) => setItems(res.data?.length ? res.data : fallback)).catch(() => {}) }, [])
  const categories = ['همه', ...new Set(items.map((n) => n.category))]
  const filtered = useMemo(() => items.filter((item) => (category === 'همه' || item.category === category) && item.title.includes(query)), [items, query, category])
  return (
    <>
      <PageHero eyebrow="تازه‌ترین کشفیات" title="اخبار علمی" description="روایت روشن، خلاصه و فارسی از رویدادهای مهم نجوم، ماموریت‌های فضایی و پژوهش‌های کیهانی." image="/media/galaxy.jpg" />
      <section className="content-shell py-10 sm:py-14">
        <div className="glass-panel mb-8 flex flex-col gap-4 rounded-3xl p-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="relative w-full sm:max-w-sm"><Search className="absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" /><input value={query} onChange={(e) => setQuery(e.target.value)} className="input-field pr-11" placeholder="جستجو در خبرها..." /></div>
          <div className="flex gap-2 overflow-x-auto pb-1 custom-scrollbar">{categories.map((item) => <button key={item} onClick={() => setCategory(item)} className={category === item ? 'primary-btn whitespace-nowrap' : 'secondary-btn whitespace-nowrap'}>{item}</button>)}</div>
        </div>
        <div className="grid gap-5 md:grid-cols-2 xl:grid-cols-3">{filtered.map((item, i) => <motion.article key={item.id} initial={{ opacity: 0, y: 20 }} whileInView={{ opacity: 1, y: 0 }} viewport={{ once: true }} transition={{ delay: i * .05 }} className="glass-panel glass-panel-hover overflow-hidden rounded-3xl"><Link to={`/news/${item.slug}`}><div className="relative h-56 overflow-hidden"><img src={item.image} alt={item.title} className="h-full w-full object-cover transition duration-700 hover:scale-105" /><div className="absolute inset-0 bg-gradient-to-t from-space-950/90 via-transparent to-transparent" /><span className="absolute bottom-4 right-4 rounded-full bg-blue-500/15 px-3 py-1 text-xs text-blue-200 backdrop-blur">{item.category}</span></div><div className="p-5"><h2 className="text-xl font-bold leading-8 text-white">{item.title}</h2><p className="mt-3 line-clamp-3 text-sm leading-7 text-slate-400">{item.excerpt}</p><div className="mt-5 flex items-center justify-between border-t border-white/[.07] pt-4 text-xs text-slate-500"><span className="flex items-center gap-1"><Clock3 className="h-3.5 w-3.5" />{item.readingTime}</span><span className="flex items-center gap-1 text-blue-300">ادامه خبر <ArrowLeft className="h-3.5 w-3.5" /></span></div></div></Link></motion.article>)}</div>
      </section>
    </>
  )
}
