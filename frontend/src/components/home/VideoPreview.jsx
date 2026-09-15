import { ArrowLeft, Play, Youtube } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function VideoPreview({ video = null, content = {} }) {
  const thumbnail = video?.poster || video?.thumbnail || youtubeThumbnail(video)
  const href = video?.youtubeUrl || video?.sourceUrl || ''
  const isShort = video?.isShort || href.includes('/shorts/')
  const visual = (
    <div className={`video-preview-visual flex-1 ${thumbnail ? 'video-preview-has-image' : ''} ${isShort ? 'video-preview-short' : ''}`}>
      {thumbnail ? <img src={thumbnail} alt={video?.title || 'ویدیوی جزیره'} loading="lazy" decoding="async" referrerPolicy="no-referrer" /> : null}
      <span className="video-preview-shade" />
      <span className="video-preview-play"><Play className="mr-1 h-7 w-7 fill-current" /></span>
      <div className="absolute bottom-4 right-4 left-4">
        <strong className="line-clamp-2 text-sm leading-6 text-white">{video?.title || 'به‌زودی آخرین ویدیوها اینجا نمایش داده می‌شوند'}</strong>
        <span className="mt-1 block text-xs text-slate-300">{video?.duration || (isShort ? 'YouTube Short' : 'همگام‌سازی از یوتیوب')}</span>
      </div>
    </div>
  )

  return (
    <article className="surface-card home-feature-card accent-red p-5 sm:p-6">
      <div className="flex items-start justify-between"><div><span className="eyebrow">{content.videos_eyebrow || 'رسانه تصویری'}</span><h3 className="mt-2 card-title">{content.videos_title || 'ویدیوهای جزیره'}</h3></div><Youtube className="h-5 w-5 text-red-300" /></div>
      {href ? <a href={href} target="_blank" rel="noopener noreferrer" className="mt-5 flex flex-1">{visual}</a> : <div className="mt-5 flex flex-1">{visual}</div>}
      <Link to="/videos" className="secondary-btn mt-5 w-full">{content.videos_cta || 'مشاهده ویدیوها'} <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}

function youtubeThumbnail(video) {
  const id = video?.id || video?.youtubeId || ''
  return id ? `https://i.ytimg.com/vi/${encodeURIComponent(id)}/hqdefault.jpg` : ''
}
