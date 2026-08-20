import { ArrowLeft, Camera } from 'lucide-react'
import { Link } from 'react-router-dom'
import { apodItems } from '../../data/fallback'

export default function ApodBanner({ item = apodItems[0], content = {} }) {
  return (
    <article className="surface-card home-wide-card accent-purple p-5 sm:p-6">
      <div className="flex h-full flex-col justify-between">
        <div>
          <span className="eyebrow"><Camera className="h-4 w-4" />{content.apod_eyebrow || 'تصویر نجومی روز'}</span>
          <h3 className="mt-4 max-w-2xl text-2xl font-black leading-[1.45] text-white sm:text-3xl">{item.title}</h3>
          <p className="mt-4 max-w-2xl text-sm leading-8 text-slate-400">{item.excerpt || item.content}</p>
        </div>
        <div className="mt-7 flex flex-wrap items-end justify-between gap-4">
          <div className="apod-orbit-art" aria-hidden="true"><span /><span /><span /></div>
          <Link to="/apod" className="primary-btn">{content.apod_cta || 'مشاهده تصویر روز'} <ArrowLeft className="h-4 w-4" /></Link>
        </div>
      </div>
    </article>
  )
}
