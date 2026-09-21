import { useEffect, useState } from 'react'
import Hero from '../components/home/Hero'
import SkyPreview from '../components/home/SkyPreview'
import ExplorePreview from '../components/home/ExplorePreview'
import NewsPreview from '../components/home/NewsPreview'
import VideoPreview from '../components/home/VideoPreview'
import ApodBanner from '../components/home/ApodBanner'
import RadarPreview from '../components/home/RadarPreview'
import DailyPreview from '../components/home/DailyPreview'
import HomeLiveTrio from '../components/home/HomeLiveTrio'
import LiveObservatory from '../components/observatory/LiveObservatory'
import SectionHeader from '../components/SectionHeader'
import usePageMeta from '../hooks/usePageMeta'
import { useSiteSettings } from '../context/SiteSettingsContext'
import { api } from '../lib/api'
import { skyData, sunData } from '../data/fallback'

const fallbackHome = { sky: skyData, sun: sunData, liveWidgets: {}, news: [], videos: [], apod: null, dailyPost: null }
const HOME_OBSERVATORY_EXCLUDES = ['moon', 'earth']

export default function HomePage() {
  const [content, setContent] = useState(fallbackHome)
  const [homeState, setHomeState] = useState('loading')
  const { settings } = useSiteSettings()
  const cards = settings.homepage.cards

  usePageMeta('خانه', 'جزیره نجوم؛ رسانه‌ای فارسی برای آسمان شب، اخبار علمی، ویدیوهای نجومی و تجربه‌های تعاملی فضایی.')

  useEffect(() => {
    let active = true
    setHomeState('loading')
    api.get('/api/home').then((response) => {
      if (!active || !response.data) return
      setContent({
        sky: response.data.sky || fallbackHome.sky,
        sun: response.data.sun || fallbackHome.sun,
        liveWidgets: response.data.liveWidgets || fallbackHome.liveWidgets,
        news: Array.isArray(response.data.news) ? response.data.news : fallbackHome.news,
        videos: Array.isArray(response.data.videos) ? response.data.videos : fallbackHome.videos,
        apod: response.data.apod || null,
        dailyPost: response.data.dailyPost || null
      })
      setHomeState('ready')
    }).catch(() => {
      if (active) setHomeState('error')
    })
    return () => { active = false }
  }, [])

  return (
    <>
      <Hero sky={content.sky} />
      <section className="content-shell section-space">
        <HomeLiveTrio widgets={content.liveWidgets} sky={content.sky} sun={content.sun} loading={homeState === 'loading'} />
        <SectionHeader eyebrow={settings.homepage.section.eyebrow} title={settings.homepage.section.title} description={settings.homepage.section.description} />
        <div className="home-observatory-grid home-sky-only-grid">
          <SkyPreview data={content.sky} content={cards} className="home-observatory-card home-observatory-sky" />
        </div>
        <div className="home-feature-grid mt-5">
          <ExplorePreview content={cards} />
          <NewsPreview items={content.news} content={cards} />
          <VideoPreview video={content.videos[0] || null} content={cards} />
        </div>
        <div className="home-daily-grid mt-5">
          <DailyPreview item={content.dailyPost} content={cards} />
        </div>
        <div className="home-wide-grid mt-5">
          <ApodBanner item={content.apod} content={cards} />
          <RadarPreview content={cards} />
        </div>
      </section>
      <LiveObservatory initialSky={content.sky} exclude={HOME_OBSERVATORY_EXCLUDES} />
    </>
  )
}
