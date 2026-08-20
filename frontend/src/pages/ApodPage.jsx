import { Camera, ChevronLeft, ChevronRight, ExternalLink } from 'lucide-react'
import { useEffect, useMemo, useState } from 'react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { apodItems as fallbackItems } from '../data/fallback'
import { resolveAssetPath } from '../lib/utils'

const localFallback = [{
  ...fallbackItems[0],
  image: resolveAssetPath('/media/carina-webb.webp'),
  photographer: 'NASA, ESA, CSA, STScI',
  sourceUrl: 'https://science.nasa.gov/asset/webb/cosmic-cliffs-in-the-carina-nebula-nircam-and-miri-composite-image/'
}]

export default function ApodPage() {
  const initialItems = useMemo(() => localFallback, [])
  const [items, setItems] = useState(initialItems)
  const [index, setIndex] = useState(0)
  usePageMeta('عکس روز ناسا', 'تصویر نجومی روز همراه با روایت و توضیح فارسی در جزیره نجوم.')

  useEffect(() => {
    let active = true
    api.get('/api/apod?limit=20').then((response) => {
      if (!active || !response.data?.length) return
      const blockedLocalImages = ['/media/galaxy.jpg', '/media/nebula.jpg', '/media/saturn.jpg', '/media/hero-planet.jpg']
      const usableItems = response.data.filter((entry) => {
        if (entry.mediaType === 'video') return Boolean(entry.sourceUrl)
        return entry.image && !blockedLocalImages.includes(entry.image)
      })
      if (usableItems.length) { setItems(usableItems); setIndex(0) }
    }).catch(() => {})
    return () => { active = false }
  }, [])

  const item = items[index] || localFallback[0]
  const isVideo = item.mediaType === 'video' && item.sourceUrl
  const change = (direction) => setIndex((current) => (current + direction + items.length) % items.length)

  return (
    <>
      <PageHero eyebrow="Astronomy Picture of the Day" title="عکس روز ناسا" description="تصویرهای منتخب نجومی همراه با توضیح فارسی، اعتبار اثر و پیوند منبع اصلی." />
      <section className="content-shell section-space">
        <div className="apod-layout">
          <div className="surface-card apod-panel p-3 sm:p-4">
            <div className="apod-image-frame relative">
              {isVideo ? (
                <iframe
                  src={item.sourceUrl}
                  title={item.title}
                  className="apod-media-frame"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowFullScreen
                />
              ) : (
                <img src={item.image || localFallback[0].image} onError={(event) => { event.currentTarget.src = localFallback[0].image }} alt={item.title} className="h-full w-full object-contain" loading="eager" decoding="async" />
              )}
              {items.length > 1 && <div className="absolute bottom-4 left-4 flex gap-2"><button onClick={() => change(-1)} className="icon-button bg-black/70" aria-label="تصویر قبلی"><ChevronRight className="h-5 w-5" /></button><button onClick={() => change(1)} className="icon-button bg-black/70" aria-label="تصویر بعدی"><ChevronLeft className="h-5 w-5" /></button></div>}
            </div>
          </div>

          <aside className="surface-card apod-panel flex flex-col justify-center p-6 sm:p-8">
            <span className="eyebrow"><Camera className="h-4 w-4" />{item.date}</span>
            <h2 className="mt-4 text-[clamp(1.8rem,4vw,2.4rem)] font-black leading-[1.35] text-white">{item.title}</h2>
            {item.excerpt && <p className="mt-5 text-sm font-medium leading-8 text-slate-300">{item.excerpt}</p>}
            <p className="mt-4 text-sm leading-8 text-slate-500">{item.content}</p>
            <div className="mt-7 border-t border-white/[.08] pt-5 text-xs leading-6 text-slate-500">
              <span>اعتبار تصویر: {item.photographer || 'منبع اصلی تصویر'}</span>
              {item.sourceUrl && <a href={item.sourceUrl} target="_blank" rel="noopener noreferrer" className="mt-4 flex w-fit items-center gap-2 text-amber-200">مشاهده منبع رسمی <ExternalLink className="h-4 w-4" /></a>}
            </div>
          </aside>
        </div>
      </section>
    </>
  )
}
