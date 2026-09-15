import { AlertTriangle, ExternalLink, RefreshCw, Sun } from 'lucide-react'
import { useEffect, useState } from 'react'
import { sunData } from '../../data/fallback'

const SDO_FALLBACK_IMAGE = 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_1024_0304.jpg'

const statusCopy = {
  ready: 'آماده',
  stale: 'آخرین داده موجود',
  error: 'خطای دریافت',
  loading: 'در حال دریافت',
}

export default function SunPreview({ data = sunData, loading = false, className = '' }) {
  const status = loading ? 'loading' : (data?.status || 'error')
  const imageCandidates = [
    data?.image,
    data?.fallbackImage,
    SDO_FALLBACK_IMAGE,
  ].filter(Boolean)
  const [imageIndex, setImageIndex] = useState(0)
  const image = imageCandidates[imageIndex] || ''

  useEffect(() => {
    setImageIndex(0)
  }, [data?.image, data?.fallbackImage])

  return (
    <article className={`surface-card home-feature-card accent-orange p-5 sm:p-6 ${className}`.trim()}>
      <div className="flex items-start justify-between gap-4">
        <div>
          <span className="eyebrow">رصد خورشید</span>
          <h3 className="mt-2 card-title">{data?.title || 'خورشید اکنون'}</h3>
        </div>
        <StatusIcon status={status} />
      </div>

      <div className={`sun-preview-frame mt-5 ${status === 'loading' ? 'is-loading' : ''}`}>
        {image ? <img src={image} alt="تصویر زنده خورشید" loading="lazy" onError={() => setImageIndex((current) => current + 1)} /> : <Sun className="h-16 w-16 text-amber-200" />}
      </div>

      <div className="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        <span className={`state-pill state-${status}`}>{statusCopy[status] || statusCopy.error}</span>
        {data?.isFallback ? <span className="state-pill state-stale">داده پشتیبان</span> : null}
        <span>{data?.wavelength || 'AIA 304Å'}</span>
      </div>

      <p className="mt-3 min-h-[3rem] text-xs leading-6 text-slate-500">{data?.displayWarning || data?.message || 'تصویر خورشید در حال حاضر در دسترس نیست.'}</p>

      {data?.sourceUrl ? (
        <a className="secondary-btn mt-auto w-full" href={data.sourceUrl} target="_blank" rel="noreferrer">
          منبع تصویر <ExternalLink className="h-4 w-4" />
        </a>
      ) : null}
    </article>
  )
}

function StatusIcon({ status }) {
  const Icon = status === 'error' ? AlertTriangle : status === 'loading' ? RefreshCw : Sun
  return <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/[.04]"><Icon className={`h-5 w-5 ${status === 'loading' ? 'animate-spin' : ''}`} /></span>
}
