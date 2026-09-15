import { ArrowLeft, Orbit } from 'lucide-react'
import { Link } from 'react-router-dom'
import { resolveAssetPath } from '../../lib/utils'

export default function ExplorePreview({ content = {} }) {
  const image = resolveAssetPath('/media/explore-planets-astronaut.jpg')

  return (
    <article className="surface-card home-feature-card accent-purple p-5 sm:p-6">
      <div><span className="eyebrow">{content.explore_eyebrow || 'منظومه شمسی'}</span><h3 className="mt-2 card-title">{content.explore_title || 'کاوش سیاره‌ها'}</h3></div>
      <div className="solar-miniature solar-miniature-photo mt-5 flex-1">
        <img src={image} alt="" aria-hidden="true" loading="lazy" decoding="async" />
        <span className="solar-miniature-shade" />
        <span className="absolute bottom-4 right-4 left-4 flex items-center gap-2 text-xs font-bold leading-6 text-slate-100"><Orbit className="h-4 w-4 shrink-0 text-violet-200" />{content.explore_description || 'انتخاب و مشاهده اطلاعات سیاره‌ها'}</span>
      </div>
      <Link to="/explore" className="secondary-btn mt-5 w-full">{content.explore_cta || 'شروع کاوش'} <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
