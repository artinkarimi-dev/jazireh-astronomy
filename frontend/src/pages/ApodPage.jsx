import { Camera, ChevronLeft, ChevronRight } from 'lucide-react'
import { useState } from 'react'
import { AnimatePresence, motion } from 'framer-motion'
import PageHero from '../components/PageHero'
import { apodItems } from '../data/fallback'

export default function ApodPage() {
  const [index, setIndex] = useState(0)
  const item = apodItems[index]
  const change = (direction) => setIndex((index + direction + apodItems.length) % apodItems.length)
  return (
    <>
      <PageHero eyebrow="Astronomy Picture of the Day" title="عکس روز ناسا" description="ترجمه فارسی تصویر نجومی روز، همراه با توضیح علمی روشن و منبع تصویر." image="/media/nebula.jpg" />
      <section className="content-shell py-10 sm:py-14">
        <div className="glass-panel overflow-hidden rounded-[2rem]">
          <AnimatePresence mode="wait"><motion.div key={item.id} initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: .35 }} className="grid xl:grid-cols-[1.35fr_.75fr]"><div className="relative min-h-[400px] xl:min-h-[650px]"><img src={item.image} alt={item.title} className="absolute inset-0 h-full w-full object-cover" /><div className="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent" /><div className="absolute bottom-5 left-5 flex gap-2"><button onClick={() => change(-1)} className="icon-button bg-black/50"><ChevronRight className="h-5 w-5" /></button><button onClick={() => change(1)} className="icon-button bg-black/50"><ChevronLeft className="h-5 w-5" /></button></div></div><div className="flex flex-col justify-center p-7 sm:p-10"><span className="flex items-center gap-2 text-xs font-semibold text-blue-300"><Camera className="h-4 w-4" />{item.date}</span><h2 className="mt-5 text-3xl font-black leading-[1.5] text-white">{item.title}</h2><p className="mt-5 text-sm font-medium leading-8 text-slate-300">{item.excerpt}</p><p className="mt-5 text-sm leading-8 text-slate-500">{item.content}</p><div className="mt-7 border-t border-white/[.08] pt-5 text-xs text-slate-500">اعتبار تصویر: {item.photographer}</div></div></motion.div></AnimatePresence>
        </div>
      </section>
    </>
  )
}
