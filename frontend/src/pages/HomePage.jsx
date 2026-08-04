import { motion } from 'framer-motion'
import Hero from '../components/home/Hero'
import SkyPreview from '../components/home/SkyPreview'
import ExplorePreview from '../components/home/ExplorePreview'
import NewsPreview from '../components/home/NewsPreview'
import VideoPreview from '../components/home/VideoPreview'
import ApodBanner from '../components/home/ApodBanner'
import RadarPreview from '../components/home/RadarPreview'
import SectionHeader from '../components/SectionHeader'

export default function HomePage() {
  return (
    <>
      <Hero />
      <section className="content-shell py-10 sm:py-14">
        <SectionHeader eyebrow="مرکز فرماندهی" title="همه چیز برای یک شب نجومی" description="از نقشه آسمان و وضعیت رصد تا سفر تعاملی در فضا؛ تمام بخش‌ها با یک معماری روشن، سریع و مینیمال کنار هم قرار گرفته‌اند." />
        <motion.div initial="hidden" whileInView="show" viewport={{ once: true, margin: '-80px' }} variants={{ hidden: {}, show: { transition: { staggerChildren: .1 } } }} className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {[SkyPreview, ExplorePreview, NewsPreview, VideoPreview].map((Component, index) => <motion.div key={index} variants={{ hidden: { opacity: 0, y: 24 }, show: { opacity: 1, y: 0 } }}><Component /></motion.div>)}
        </motion.div>
        <div className="mt-4 grid gap-4 xl:grid-cols-[1.5fr_.8fr]"><ApodBanner /><RadarPreview /></div>
      </section>
    </>
  )
}
