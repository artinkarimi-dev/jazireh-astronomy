import { ArrowLeft, Radar } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function RadarPreview({ content = {} }) {
  return (
    <article className="surface-card home-wide-card accent-blue p-5 sm:p-6">
      <div className="flex h-full flex-col">
        <div className="flex items-start justify-between"><div><span className="eyebrow">{content.radar_eyebrow || 'آموزشی'}</span><h3 className="mt-2 card-title">{content.radar_title || 'شبیه‌ساز رادار آسمان'}</h3></div><Radar className="h-6 w-6 text-sky-300" /></div>
        <div className="radar-miniature mt-5 flex-1" aria-hidden="true"><span className="radar-mini-sweep" /><i className="radar-dot radar-dot-one" /><i className="radar-dot radar-dot-two" /><i className="radar-dot radar-dot-three" /></div>
        <Link to="/radar" className="secondary-btn mt-5 w-full">{content.radar_cta || 'باز کردن رادار'} <ArrowLeft className="h-4 w-4" /></Link>
      </div>
    </article>
  )
}
