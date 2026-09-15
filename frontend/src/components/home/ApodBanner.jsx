import { ArrowLeft, Camera } from 'lucide-react'
import { Link } from 'react-router-dom'
import { getApodDisplay } from '../../lib/apodLocalization'

export default function ApodBanner({ item = null, content = {} }) {
  const display = getApodDisplay(item)

  return (
    <article className="surface-card home-wide-card accent-purple p-5 sm:p-6">
      <div className="flex h-full flex-col justify-between">
        <div>
          <span className="eyebrow"><Camera className="h-4 w-4" />{content.apod_eyebrow || 'تصویر نجومی روز'}</span>
          {item.isFallback ? <span className="mt-3 inline-flex rounded-full border border-amber-300/20 bg-amber-300/[.08] px-3 py-1 text-xs text-amber-100">داده پشتیبان</span> : null}
          {!display.hasPersianEditorial && display.warning ? <span className="mt-3 inline-flex w-fit rounded-full border border-sky-300/20 bg-sky-300/[.08] px-3 py-1 text-xs font-bold leading-6 text-sky-100">{display.warning}</span> : null}
          <h3 className="mt-4 max-w-2xl text-2xl font-black leading-[1.45] text-white sm:text-3xl">{item ? display.title : 'تصویر روز پس از دریافت داده معتبر نمایش داده می‌شود'}</h3>
          <p className="mt-4 max-w-2xl text-sm leading-8 text-slate-400">{item ? (display.summary || display.content) : 'این کارت فقط داده ذخیره‌شده یا تازه از API داخلی جزیره را نمایش می‌دهد و تصویر آرشیوی ساختگی جایگزین نمی‌کند.'}</p>
          {item.displayWarning ? <p className="mt-3 text-xs leading-6 text-slate-500">{item.displayWarning}</p> : null}
        </div>
        <div className="mt-7 flex flex-wrap items-end justify-between gap-4">
          <div className="apod-orbit-art" aria-hidden="true"><span /><span /><span /></div>
          <Link to="/apod" className="primary-btn">{content.apod_cta || 'مشاهده تصویر روز'} <ArrowLeft className="h-4 w-4" /></Link>
        </div>
      </div>
    </article>
  )
}
