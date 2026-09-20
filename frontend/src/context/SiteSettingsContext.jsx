import { createContext, useContext, useEffect, useMemo, useState } from 'react'
import { siteConfig } from '../config/site'
import { navItems } from '../data/fallback'
import { api } from '../lib/api'

const defaultSettings = {
  identity: {
    name: siteConfig.name,
    tagline: 'نجوم و علم',
    description: siteConfig.description,
    siteUrl: window.JAZIREH_WP?.siteUrl || window.location.origin,
    logo: {
      url: siteConfig.brand.logo,
      alt: siteConfig.name
    },
    siteIconUrl: ''
  },
  social: {
    youtube: {
      url: siteConfig.youtube.url,
      handle: siteConfig.youtube.handle,
      label: siteConfig.youtube.label
    },
    instagram: '',
    telegram: '',
    x: '',
    linkedin: ''
  },
  contact: {
    email: '',
    sponsorEmail: '',
    sponsorLabel: 'همکاری و اسپانسری',
    sponsorPurpose: 'صرفاً برای همکاری‌های تجاری و اسپانسری',
    telegramUrl: '',
    instagramUrl: ''
  },
  menus: {
    primary: navItems.map((item) => ({ ...item, url: item.path, external: false, target: '' })),
    mobile: [
      { label: 'خانه', path: '/', url: '/', external: false, target: '', icon: 'home' },
      { label: 'آسمان', path: '/sky', url: '/sky', external: false, target: '', icon: 'sparkles' },
      { label: 'پرونده‌ها', path: '/topics', url: '/topics', external: false, target: '', icon: 'orbit' },
      { label: 'رویدادها', path: '/events', url: '/events', external: false, target: '', icon: 'calendar' },
      { label: 'جستجو', path: '/search', url: '/search', external: false, target: '', icon: 'search' },
      { label: 'کاوش', path: '/explore', url: '/explore', external: false, target: '', icon: 'telescope' },
      { label: 'اخبار', path: '/news', url: '/news', external: false, target: '', icon: 'newspaper' },
      { label: 'ویدیو', path: '/videos', url: '/videos', external: false, target: '', icon: 'clapperboard' }
    ],
    footer: [
      { label: 'آسمان امروز', path: '/sky', url: '/sky', external: false, target: '' },
      { label: 'رویدادهای نجومی', path: '/events', url: '/events', external: false, target: '' },
      { label: 'پرونده‌های علمی', path: '/topics', url: '/topics', external: false, target: '' },
      { label: 'جستجو در جزیره', path: '/search', url: '/search', external: false, target: '' },
      { label: 'کاوش منظومه شمسی', path: '/explore', url: '/explore', external: false, target: '' },
      { label: 'تصویر روز ناسا', path: '/apod', url: '/apod', external: false, target: '' },
      { label: 'جزیره دیلی', path: '/jazireh-daily', url: '/jazireh-daily', external: false, target: '' },
      { label: 'آخرین اخبار علمی', path: '/news', url: '/news', external: false, target: '' }
    ]
  },
  homepage: {
    hero: {
      kicker: 'جزیره؛ نجوم و علم به زبان فارسی',
      title: 'آسمان را ببینید،',
      highlight: 'جهان را بهتر بفهمید.',
      description: 'خبرهای علمی، کلاس‌های نجوم، وضعیت رصد، تصویر روز ناسا و تجربه‌های تعاملی فضایی؛ همه در مسیر محتوایی جزیره.',
      primaryAction: { label: 'آسمان امشب', url: '/sky' },
      secondaryAction: { label: 'کاوش منظومه شمسی', url: '/explore' },
      media: {
        type: 'video',
        imageUrl: '/media/home-hero-stars.jpg',
        videoUrl: '/media/home-hero-stars.mp4'
      }
    },
    section: {
      eyebrow: 'در جزیره چه می‌بینید؟',
      title: 'از آسمان امشب تا تازه‌ترین روایت‌های علمی',
      description: 'بخش‌های اصلی جزیره برای یادگیری، رصد و دنبال‌کردن خبرها و ویدیوهای نجومی.'
    },
    cards: {
      sky_eyebrow: 'رصد امشب',
      sky_title: 'آسمان امروز',
      sky_cta: 'جزئیات آسمان',
      explore_eyebrow: 'منظومه شمسی',
      explore_title: 'کاوش سیاره‌ها',
      explore_description: 'انتخاب و مشاهده اطلاعات سیاره‌ها',
      explore_cta: 'شروع کاوش',
      news_eyebrow: 'تازه‌ها',
      news_title: 'آخرین اخبار علمی',
      news_cta: 'همه خبرها',
      videos_eyebrow: 'رسانه تصویری',
      videos_title: 'ویدیوهای جزیره',
      videos_cta: 'مشاهده ویدیوها',
      daily_eyebrow: 'جزیره دیلی',
      daily_title: 'عکس روز ناسا در جزیره',
      daily_cta: 'مشاهده جزیره دیلی',
      apod_eyebrow: 'تصویر نجومی روز',
      apod_cta: 'مشاهده تصویر روز',
      radar_eyebrow: 'آموزشی',
      radar_title: 'شبیه‌ساز رادار آسمان',
      radar_cta: 'باز کردن رادار'
    },
    featuredNewsIds: []
  },
  footer: {
    description: siteConfig.description,
    microcopy: 'نجوم، فضا، آموزش و روایت‌های علمی به زبان فارسی.',
    quicklinks_title: 'دسترسی سریع',
    youtube_title: 'جزیره در یوتیوب',
    youtube_description: 'مستندها، کلاس‌های نجوم، خبرهای علمی و روایت‌های تصویری.',
    copyright_text: ''
  }
}

const SiteSettingsContext = createContext({
  settings: defaultSettings,
  loading: false
})

export function SiteSettingsProvider({ children }) {
  const [settings, setSettings] = useState(defaultSettings)
  const [loading, setLoading] = useState(Boolean(api.wordpressUrl))

  useEffect(() => {
    let active = true
    if (!api.wordpressUrl) {
      setLoading(false)
      return () => { active = false }
    }

    api.get('/api/site')
      .then((response) => {
        if (!active || !response.data) return
        setSettings(mergeSettings(defaultSettings, response.data))
      })
      .catch(() => {})
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => { active = false }
  }, [])

  const value = useMemo(() => ({ settings, loading }), [settings, loading])

  return <SiteSettingsContext.Provider value={value}>{children}</SiteSettingsContext.Provider>
}

export function useSiteSettings() {
  return useContext(SiteSettingsContext)
}

function mergeSettings(base, incoming) {
  return {
    ...base,
    ...incoming,
    identity: {
      ...base.identity,
      ...(incoming.identity || {}),
      logo: {
        ...base.identity.logo,
        ...(incoming.identity?.logo || {})
      }
    },
    social: {
      ...base.social,
      ...(incoming.social || {}),
      youtube: {
        ...base.social.youtube,
        ...(incoming.social?.youtube || {})
      }
    },
    contact: {
      ...base.contact,
      ...(incoming.contact || {})
    },
    menus: {
      ...base.menus,
      ...(incoming.menus || {})
    },
    homepage: {
      ...base.homepage,
      ...(incoming.homepage || {}),
      hero: {
        ...base.homepage.hero,
        ...(incoming.homepage?.hero || {}),
        primaryAction: {
          ...base.homepage.hero.primaryAction,
          ...(incoming.homepage?.hero?.primaryAction || {})
        },
        secondaryAction: {
          ...base.homepage.hero.secondaryAction,
          ...(incoming.homepage?.hero?.secondaryAction || {})
        },
        media: {
          ...base.homepage.hero.media,
          ...(incoming.homepage?.hero?.media || {})
        }
      },
      section: {
        ...base.homepage.section,
        ...(incoming.homepage?.section || {})
      },
      cards: {
        ...base.homepage.cards,
        ...(incoming.homepage?.cards || {})
      }
    },
    footer: {
      ...base.footer,
      ...(incoming.footer || {})
    }
  }
}
