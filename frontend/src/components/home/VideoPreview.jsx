import { ArrowLeft, Play, Youtube } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function VideoPreview({ video = null, content = {} }) {
  return (
    <article className="surface-card home-feature-card accent-red p-5 sm:p-6">
      <div className="flex items-start justify-between"><div><span className="eyebrow">{content.videos_eyebrow || 'رسانه تصویری'}</span><h3 className="mt-2 card-title">{content.videos_title || 'ویدیوهای جزیره'}</h3></div><Youtube className="h-5 w-5 text-red-300" /></div>
      <div className="video-preview-visual mt-5 flex-1">
        <span className="video-preview-play"><Play className="mr-1 h-7 w-7 fill-current" /></span>
        <div className="absolute bottom-4 right-4 left-4">
          <strong className="line-clamp-2 text-sm leading-6 text-white">{video?.title || 'به‌زودی آخرین ویدیوها اینجا نمایش داده می‌شوند'}</strong>
          <span className="mt-1 block text-xs text-slate-500">{video?.duration || 'همگام‌سازی از یوتیوب'}</span>
        </div>
      </div>
      <Link to="/videos" className="secondary-btn mt-5 w-full">{content.videos_cta || 'مشاهده ویدیوها'} <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
