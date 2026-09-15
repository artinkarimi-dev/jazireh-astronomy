import { useEffect, useRef, useState } from 'react'
import SectionHeader from '../SectionHeader'
import { api } from '../../lib/api'
import ApodCard from './ApodCard'
import EarthCard from './EarthCard'
import EarthquakeCard from './EarthquakeCard'
import MoonNowCard from './MoonNowCard'
import SkyTonightCard from './SkyTonightCard'

const widgetEndpoints = {
  moon: '/api/widgets/moon',
  sky: '/api/widgets/sky',
  apod: '/api/widgets/apod?limit=1',
  earth: '/api/widgets/earth',
  earthquakes: '/api/widgets/earthquakes',
}

const initialWidgets = {
  moon: null,
  sky: null,
  apod: null,
  earth: null,
  earthquakes: null,
}

export default function LiveObservatory({ initialSky = null, exclude = [] }) {
  const sectionRef = useRef(null)
  const [widgets, setWidgets] = useState(initialWidgets)
  const [loading, setLoading] = useState(false)
  const [shouldLoad, setShouldLoad] = useState(false)

  useEffect(() => {
    const section = sectionRef.current
    if (!section) {
      setShouldLoad(true)
      return undefined
    }

    if (!('IntersectionObserver' in window)) {
      const fallbackTimer = window.setTimeout(() => setShouldLoad(true), 800)
      return () => window.clearTimeout(fallbackTimer)
    }

    let loadTimer = null
    const observer = new IntersectionObserver((entries) => {
      if (entries.some((entry) => entry.isIntersecting)) {
        loadTimer = window.setTimeout(() => setShouldLoad(true), 900)
        observer.disconnect()
      }
    }, { rootMargin: '600px 0px' })

    observer.observe(section)
    return () => {
      observer.disconnect()
      if (loadTimer) window.clearTimeout(loadTimer)
    }
  }, [])

  useEffect(() => {
    if (!shouldLoad) return undefined

    let active = true
    setLoading(true)

    Promise.allSettled(
      Object.entries(widgetEndpoints).filter(([key]) => !exclude.includes(key)).map(([key, endpoint]) =>
        api.get(endpoint).then((response) => [key, response.data])
      )
    ).then((results) => {
      if (!active) return

      const nextWidgets = { ...initialWidgets }
      results.forEach((result) => {
        if (result.status === 'fulfilled') {
          const [key, data] = result.value
          nextWidgets[key] = data
        }
      })
      setWidgets(nextWidgets)
    }).finally(() => {
      if (active) setLoading(false)
    })

    return () => { active = false }
  }, [exclude, shouldLoad])

  return (
    <section ref={sectionRef} className="content-shell section-space">
      <SectionHeader
        eyebrow="رصدخانه زنده"
        title="نمای زنده علم و آسمان"
        description="داده‌های محاسباتی و به‌روزشونده برای خورشید، ماه، آسمان امشب، تصویر روز ناسا، زمین و رخدادهای لرزه‌ای."
      />

      <div className="observatory-grid">
        {!exclude.includes('moon') ? <MoonNowCard widget={widgets.moon} skyWidget={widgets.sky} fallbackSky={initialSky} loading={loading} /> : null}
        <EarthquakeCard widget={widgets.earthquakes} loading={loading} />
        {!exclude.includes('earth') ? <EarthCard widget={widgets.earth} loading={loading} /> : null}
        <SkyTonightCard widget={widgets.sky} loading={loading} />
        <ApodCard widget={widgets.apod} loading={loading} />
      </div>
    </section>
  )
}

