import { motion } from 'framer-motion'

export default function PageHero({ eyebrow, title, description, image = '/media/hero-planet.jpg', children }) {
  return (
    <section className="relative overflow-hidden border-b border-white/[.08]">
      <div className="absolute inset-0"><img src={image} alt="" className="h-full w-full object-cover opacity-45" /><div className="absolute inset-0 bg-gradient-to-l from-space-950 via-space-950/75 to-space-950/20" /></div>
      <div className="content-shell relative flex min-h-[330px] items-center py-16 sm:min-h-[390px]">
        <motion.div initial={{ opacity: 0, y: 18 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: .7 }} className="max-w-3xl">
          <span className="eyebrow">{eyebrow}</span>
          <h1 className="mt-4 text-4xl font-black leading-tight text-white sm:text-5xl lg:text-6xl">{title}</h1>
          <p className="mt-5 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">{description}</p>
          {children && <div className="mt-7">{children}</div>}
        </motion.div>
      </div>
    </section>
  )
}
