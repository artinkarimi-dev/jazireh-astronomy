import { useEffect, useState } from 'react'
import Hero from '../components/home/Hero'
import SkyPreview from '../components/home/SkyPreview'
import ExplorePreview from '../components/home/ExplorePreview'
import NewsPreview from '../components/home/NewsPreview'
import VideoPreview from '../components/home/VideoPreview'
import ApodBanner from '../components/home/ApodBanner'
import RadarPreview from '../components/home/RadarPreview'
import DailyPreview from '../components/home/DailyPreview'
import SectionHeader from '../components/SectionHeader'
import usePageMeta from '../hooks/usePageMeta'
import { useSiteSettings } from '../context/SiteSettingsContext'
import { api } from '../lib/api'
import { apodItems, newsItems, skyData } from '../data/fallback'

const fallbackHome = { sky: skyData, news: newsItems.slice(0, 3), videos: [], apod: apodItems[0], dailyPost: null }

export default function HomePage() {
  const [content, setContent] = useState(fallbackHome)
  const { settings } = useSiteSettings()
  const cards = settings.homepage.cards

  usePageMeta('خانه', 'جزیره نجوم؛ رسانه‌ای فارسی برای آسمان شب، اخبار علمی، ویدیوهای نجومی و تجربه‌های تعاملی فضایی.')

  useEffect(() => {
    let active = true
    api.get('/api/home').then((response) => {
      if (!active || !response.data) return
      setContent({
        sky: response.data.sky || fallbackHome.sky,
        news: response.data.news?.length ? response.data.news : fallbackHome.news,
        videos: Array.isArray(response.data.videos) ? response.data.videos : fallbackHome.videos,
        apod: response.data.apod || fallbackHome.apod,
        dailyPost: response.data.dailyPost || null
      })
    }).catch(() => {})
    return () => { active = false }
  }, [])

  return (
    <>
      <Hero sky={content.sky} />
      <section className="content-shell section-space">
        <SectionHeader eyebrow={settings.homepage.section.eyebrow} title={settings.homepage.section.title} description={settings.homepage.section.description} />
        <div className="home-feature-grid">
          <SkyPreview data={content.sky} content={cards} />
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
    </>
  )
}
