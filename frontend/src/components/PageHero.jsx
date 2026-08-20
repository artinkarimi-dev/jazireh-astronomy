import { Sparkles } from 'lucide-react'

export default function PageHero({ eyebrow, title, description, children, aside }) {
  return (
    <section className="content-shell pt-5 sm:pt-7 lg:pt-9">
      <div className="page-intro p-5 sm:p-7 lg:p-10">
        <div className={`relative grid items-center gap-7 ${aside ? 'lg:grid-cols-[minmax(0,1.3fr)_minmax(270px,.7fr)]' : ''}`}>
          <div className="reveal-up">
            <span className="eyebrow"><Sparkles className="h-4 w-4" />{eyebrow}</span>
            <h1 className="mt-4 max-w-4xl text-[clamp(1.9rem,5vw,3rem)] font-black leading-[1.24] text-white">{title}</h1>
            <p className="mt-4 max-w-3xl text-sm leading-8 text-slate-300 sm:text-base lg:text-lg">{description}</p>
            {children && <div className="hero-actions-row mt-6">{children}</div>}
          </div>
          {aside && <div>{aside}</div>}
        </div>
      </div>
    </section>
  )
}
