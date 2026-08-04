import { ArrowLeft, Play } from 'lucide-react'
import { Link } from 'react-router-dom'
import { videos } from '../../data/fallback'

export default function VideoPreview() {
  const video = videos[0]
  return (
    <article className="glass-panel glass-panel-hover min-h-[410px] rounded-3xl p-5">
      <div><h3 className="card-title">آخرین ویدیو</h3><p className="mt-1 text-xs text-slate-500">ویدیوهای تازه کانال جزیره</p></div>
      <div className="group relative mt-4 overflow-hidden rounded-2xl border border-white/[.08] bg-black/40">
        <img src={video.poster} alt={video.title} className="h-[220px] w-full object-cover transition duration-700 group-hover:scale-105" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/90 via-black/15 to-transparent" />
        <div className="absolute inset-0 flex items-center justify-center"><span className="flex h-14 w-14 items-center justify-center rounded-full border border-white/25 bg-black/40 text-white backdrop-blur transition group-hover:scale-110 group-hover:bg-blue-500/30"><Play className="mr-0.5 h-6 w-6 fill-current" /></span></div>
        <div className="absolute bottom-4 right-4 left-4"><h4 className="font-bold text-white">{video.title}</h4><span className="mt-1 block text-xs text-slate-300">{video.duration}</span></div>
      </div>
      <Link to="/videos" className="secondary-btn mt-4 w-full">مشاهده همه ویدیوها <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}
