import { useRef, useState } from 'react'
import { Maximize2, Pause, Play, Volume2, VolumeX } from 'lucide-react'
import PageHero from '../components/PageHero'
import { videos } from '../data/fallback'

export default function VideosPage() {
  const [active, setActive] = useState(videos[0])
  const [playing, setPlaying] = useState(false)
  const [muted, setMuted] = useState(true)
  const videoRef = useRef(null)
  const toggle = () => { if (videoRef.current?.paused) videoRef.current.play(); else videoRef.current?.pause() }
  return (
    <>
      <PageHero eyebrow="ویدیوهای جزیره" title="تماشای کیهان" description="ویدیوهای فضایی ارسال‌شده در پروژه با پخش روان، پوستر اختصاصی و چیدمان کاملاً واکنش‌گرا." image="/media/stars-vertical-poster.jpg" />
      <section className="content-shell py-10 sm:py-14">
        <div className="grid gap-6 xl:grid-cols-[1.5fr_.7fr]">
          <div className="glass-panel overflow-hidden rounded-3xl p-3 sm:p-4"><div className="relative overflow-hidden rounded-2xl bg-black"><video key={active.id} ref={videoRef} className="aspect-video w-full object-cover" poster={active.poster} muted={muted} playsInline onPlay={() => setPlaying(true)} onPause={() => setPlaying(false)}><source src={active.source} type="video/mp4" /></video><button onClick={toggle} className="absolute inset-0 flex items-center justify-center bg-black/10"><span className="flex h-16 w-16 items-center justify-center rounded-full border border-white/25 bg-black/45 text-white backdrop-blur">{playing ? <Pause className="h-7 w-7 fill-current" /> : <Play className="mr-1 h-7 w-7 fill-current" />}</span></button><div className="absolute bottom-4 left-4 flex gap-2"><button onClick={(e) => { e.stopPropagation(); setMuted(!muted) }} className="icon-button bg-black/45">{muted ? <VolumeX className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />}</button><button onClick={() => videoRef.current?.requestFullscreen()} className="icon-button bg-black/45"><Maximize2 className="h-4 w-4" /></button></div></div><div className="p-4"><h2 className="text-2xl font-black text-white">{active.title}</h2><p className="mt-3 text-sm leading-7 text-slate-400">{active.description}</p></div></div>
          <div className="glass-panel rounded-3xl p-5"><h3 className="card-title">فهرست ویدیوها</h3><div className="mt-5 space-y-3">{videos.map((video) => <button key={video.id} onClick={() => { setActive(video); setPlaying(false) }} className={`flex w-full gap-3 rounded-2xl border p-3 text-right transition ${active.id === video.id ? 'border-blue-400/30 bg-blue-500/10' : 'border-white/[.07] bg-white/[.02] hover:bg-white/[.05]'}`}><img src={video.poster} alt="" className="h-20 w-24 rounded-xl object-cover" /><div><strong className="text-sm text-white">{video.title}</strong><span className="mt-2 block text-xs text-slate-500">{video.duration}</span></div></button>)}</div></div>
        </div>
      </section>
    </>
  )
}
