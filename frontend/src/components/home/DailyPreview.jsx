import { ArrowLeft, ArrowUpLeft, ImageIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { formatDate } from '../../lib/utils'

export default function DailyPreview({ item = null, content = {} }) {
  const image = item?.images?.[0]?.url || ''

  return (
    <article className="surface-card home-daily-card accent-yellow p-5 sm:p-6">
      <div className="grid h-full gap-5 lg:grid-cols-[minmax(0,.95fr)_minmax(260px,.75fr)]">
        <div className="flex min-h-[220px] flex-col justify-between">
          <div>
            <span className="eyebrow">{content.daily_eyebrow || 'جزیره دیلی'}</span>
            <h3 className="mt-3 text-2xl font-black leading-[1.5] text-white">{content.daily_title || 'عکس روز ناسا در جزیره'}</h3>
            <p className="mt-4 text-sm leading-8 text-slate-400">{item?.excerpt || 'پست‌های منتخب کامیونیتی یوتیوب جزیره بعد از همگام‌سازی در وردپرس اینجا نمایش داده می‌شوند.'}</p>
          </div>
          <div className="mt-6 flex flex-wrap gap-3">
            <Link to={item ? `/jazireh-daily/${item.slug}` : '/jazireh-daily'} className="primary-btn">{content.daily_cta || 'مشاهده جزیره دیلی'} <ArrowLeft className="h-4 w-4" /></Link>
            {item?.sourceUrl && <a href={item.sourceUrl} target="_blank" rel="noopener noreferrer" className="secondary-btn">پست اصلی <ArrowUpLeft className="h-4 w-4" /></a>}
          </div>
        </div>
        <div className="daily-preview-visual">
          {image ? <img src={image} alt={item?.title || 'جزیره دیلی'} className="h-full w-full object-cover" loading="lazy" decoding="async" /> : <div className="flex h-full min-h-[220px] items-center justify-center"><ImageIcon className="h-10 w-10 text-amber-200/70" /></div>}
          <div className="daily-preview-overlay">
            <strong className="line-clamp-2 text-base leading-8 text-white">{item?.title || 'جزیره دیلی'}</strong>
            <span className="mt-2 block text-xs text-slate-300">{item?.publishedAt ? formatDate(item.publishedAt) : 'همگام‌سازی دوره‌ای از یوتیوب کامیونیتی'}</span>
          </div>
        </div>
      </div>
    </article>
  )
}
