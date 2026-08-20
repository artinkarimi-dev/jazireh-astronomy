import { ArrowLeft, Orbit } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function ExplorePreview({ content = {} }) {
  return (
    <article className="surface-card home-feature-card accent-purple p-5 sm:p-6">
      <div><span className="eyebrow">{content.explore_eyebrow || 'منظومه شمسی'}</span><h3 className="mt-2 card-title">{content.explore_title || 'کاوش سیاره‌ها'}</h3></div>
      <div className="solar-miniature mt-5 flex-1" aria-hidden="true">
        {[58, 96, 136].map((size) => <span key={size} className="solar-mini-orbit" style={{ width: size, height: size }} />)}
        <span className="solar-mini-sun" />
        <span className="solar-mini-earth" />
        <span className="absolute bottom-4 right-4 flex items-center gap-2 text-xs text-slate-400"><Orbit className="h-4 w-4 text-violet-300" />{content.explore_description || 'انتخاب و مشاهده اطلاعات سیاره‌ها'}</span>
      </div>
      <Link to="/explore" className="secondary-btn mt-5 w-full">{content.explore_cta || 'شروع کاوش'} <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
