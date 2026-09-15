import { ArrowLeft, CloudMoon, Play, Youtube } from 'lucide-react'
import { useEffect, useState } from 'react'
import { NavLink } from 'react-router-dom'
import { siteConfig } from '../../config/site'
import { useSiteSettings } from '../../context/SiteSettingsContext'
import { skyData } from '../../data/fallback'
import { resolveAssetPath } from '../../lib/utils'

export default function Hero({ sky = skyData }) {
  const { settings } = useSiteSettings()
  const [preferStaticHero, setPreferStaticHero] = useState(false)
  const hero = settings.homepage.hero
  const youtube = settings.social.youtube
  const logoUrl = settings.identity.logo?.url || siteConfig.brand.logo
  const heroImage = resolveAssetPath(hero.media?.imageUrl || '/media/home-hero-stars.jpg')
  const heroVideo = resolveAssetPath(hero.media?.videoUrl || '/media/home-hero-stars.mp4')
  const isImageHero = hero.media?.type === 'image' || !heroVideo || preferStaticHero

  useEffect(() => {
    if (typeof window === 'undefined' || !window.matchMedia) return undefined
    const mediaQuery = window.matchMedia('(max-width: 767px), (prefers-reduced-motion: reduce)')
    const applyPreference = () => {
      const saveData = navigator.connection?.saveData === true
      setPreferStaticHero(mediaQuery.matches || saveData)
    }
    applyPreference()
    mediaQuery.addEventListener?.('change', applyPreference)
    return () => mediaQuery.removeEventListener?.('change', applyPreference)
  }, [])

  return (
    <section className="home-hero relative overflow-hidden">
      {isImageHero ? (
        <img src={heroImage} alt={hero.title} className="home-hero-video" fetchpriority="high" />
      ) : (
        <video className="home-hero-video" autoPlay muted loop playsInline preload="metadata" poster={heroImage}>
          <source src={heroVideo} type="video/mp4" />
        </video>
      )}
      <div className="home-hero-overlay" />
      <div className="home-hero-glow" />

      <div className="content-shell relative grid min-h-[clamp(32rem,86svh,42rem)] items-center gap-7 py-10 sm:gap-8 sm:py-12 lg:grid-cols-[minmax(0,1.28fr)_minmax(18rem,.72fr)] lg:py-16 xl:gap-12">
        <div className="reveal-up max-w-4xl">
          <span className="hero-kicker">{hero.kicker}</span>
          <h1 className="mt-5 max-w-[13ch] text-[clamp(2.2rem,7vw,4.6rem)] font-black leading-[1.08] text-white">
            {hero.title}
            <span className="hero-gradient-text"> {hero.highlight}</span>
          </h1>
          <p className="mt-5 max-w-2xl text-[0.98rem] leading-8 text-slate-200 sm:text-lg sm:leading-9">
            {hero.description}
          </p>
          <div className="hero-actions-row mt-8">
            <HeroAction action={hero.primaryAction} variant="primary" />
            <HeroAction action={hero.secondaryAction} variant="secondary" />
            <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="youtube-cta"><Youtube className="h-4 w-4 fill-current" />{youtube.label}</a>
          </div>
        </div>

        <div className="reveal-side grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
          <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="hero-side-card hero-side-youtube group">
            <div className="flex items-center gap-4">
              <img src={logoUrl} alt="لوگوی جزیره" className="h-16 w-16 shrink-0 rounded-[22px] border border-amber-300/25 object-cover sm:h-20 sm:w-20" width="80" height="80" loading="lazy" decoding="async" />
              <div className="min-w-0">
                <span className="text-xs font-bold text-red-200">کانال رسمی یوتیوب</span>
                <strong className="mt-2 block text-xl font-black text-white sm:text-2xl">{youtube.handle}</strong>
                <span className="mt-2 block text-sm leading-7 text-slate-300">مستند، آموزش و روایت‌های علمی جزیره</span>
              </div>
              <span className="mr-auto hidden h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-500 text-white transition group-hover:scale-105 sm:flex"><Play className="mr-0.5 h-5 w-5 fill-current" /></span>
            </div>
          </a>

          <div className="hero-side-card hero-side-sky">
            <div className="flex items-start justify-between gap-4">
              <div>
                <span className="text-xs text-slate-400">وضعیت ثبت‌شده آسمان</span>
                <strong className="mt-2 block text-lg font-black leading-8 text-white sm:text-xl">{sky.location}</strong>
              </div>
              <CloudMoon className="h-8 w-8 text-sky-300" />
            </div>
            <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
              <HeroMetric label="هوا" value={sky.temperature === null || sky.temperature === undefined ? 'ناموجود' : `${sky.temperature}°`} accent="orange" />
              <HeroMetric label="وضعیت" value={sky.condition} accent="blue" />
              <HeroMetric label="رصد" value={sky.bestTime || 'ناموجود'} accent="purple" />
            </div>
            {sky.displayWarning ? <p className="mt-3 text-xs leading-6 text-slate-400">{sky.displayWarning}</p> : null}
          </div>
        </div>
      </div>
    </section>
  )
}

function HeroMetric({ label, value, accent }) {
  return (
    <div className={`hero-metric hero-metric-${accent}`}>
      <span>{label}</span>
      <strong>{value}</strong>
    </div>
  )
}

function HeroAction({ action, variant }) {
  if (!action?.label || !action?.url) return null
  const className = variant === 'primary' ? 'primary-btn' : 'secondary-btn'
  const isInternal = action.url.startsWith('/')

  if (isInternal) {
    return (
      <NavLink to={action.url} className={className}>
        {action.label}
        {variant === 'primary' && <ArrowLeft className="h-4 w-4" />}
      </NavLink>
    )
  }

  return (
    <a href={action.url} target="_blank" rel="noopener noreferrer" className={className}>
      {action.label}
      {variant === 'primary' && <ArrowLeft className="h-4 w-4" />}
    </a>
  )
}
