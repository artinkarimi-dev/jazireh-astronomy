import { useEffect, useRef, useState } from 'react'
import { ArrowUpLeft, ExternalLink, Maximize2, Pause, Play, Volume2, VolumeX, Youtube } from 'lucide-react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { useSiteSettings } from '../context/SiteSettingsContext'

let youtubeApiPromise
const TRUSTED_YOUTUBE_HOSTS = new Set(['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'])
const YOUTUBE_ID_PATTERN = /^[A-Za-z0-9_-]{6,}$/

function loadYoutubeApi() {
  if (window.YT?.Player) return Promise.resolve(window.YT)
  if (youtubeApiPromise) return youtubeApiPromise

  youtubeApiPromise = new Promise((resolve, reject) => {
    const existingScript = document.querySelector('script[src="https://www.youtube.com/iframe_api"]')
    const previousReady = window.onYouTubeIframeAPIReady

    window.onYouTubeIframeAPIReady = () => {
      previousReady?.()
      resolve(window.YT)
    }

    if (existingScript) return

    const script = document.createElement('script')
    script.src = 'https://www.youtube.com/iframe_api'
    script.async = true
    script.onerror = () => reject(new Error('YouTube Iframe API failed to load.'))
    document.head.appendChild(script)
  })

  return youtubeApiPromise
}

function getYoutubeVideoId(url) {
  if (!url) return ''
  try {
    const parsed = new URL(url)
    const host = parsed.hostname.toLowerCase()
    if (parsed.protocol !== 'https:' || !TRUSTED_YOUTUBE_HOSTS.has(host)) return ''
    let videoId = ''
    if (host === 'youtu.be' || host === 'www.youtu.be') {
      videoId = parsed.pathname.replace(/^\/+/, '').split('/')[0] || ''
    } else if (parsed.pathname.startsWith('/embed/')) {
      videoId = parsed.pathname.split('/')[2] || ''
    } else {
      videoId = parsed.searchParams.get('v') || ''
    }
    return YOUTUBE_ID_PATTERN.test(videoId) ? videoId : ''
  } catch {
    return ''
  }
}

function getYoutubeEmbedUrl(videoId, embedUrl = '') {
  if (!videoId && !embedUrl) return ''
  const safeVideoId = YOUTUBE_ID_PATTERN.test(videoId || '') ? videoId : getYoutubeVideoId(embedUrl)
  if (!safeVideoId) return ''

  const embed = new URL(`https://www.youtube.com/embed/${safeVideoId}`)
  embed.searchParams.set('enablejsapi', '1')
  embed.searchParams.set('origin', window.location.origin)
  embed.searchParams.set('playsinline', '1')
  embed.searchParams.set('rel', '0')
  embed.searchParams.set('autoplay', '1')
  return embed.toString()
}

export default function VideosPage() {
  const [items, setItems] = useState([])
  const [active, setActive] = useState(null)
  const [loading, setLoading] = useState(true)
  const [loadError, setLoadError] = useState(false)
  const [playing, setPlaying] = useState(false)
  const [muted, setMuted] = useState(true)
  const [iframeActivated, setIframeActivated] = useState(false)
  const videoRef = useRef(null)
  const youtubePlayerRef = useRef(null)
  const playerFrameRef = useRef(null)
  const mutedRef = useRef(muted)
  const { settings } = useSiteSettings()
  const youtube = settings.social.youtube

  usePageMeta('ویدیوها', 'ویدیوهای علمی و نجومی جزیره و دسترسی مستقیم به کانال رسمی یوتیوب.')

  useEffect(() => {
    let mounted = true
    setLoading(true)
    setLoadError(false)

    api.get('/api/videos')
      .then((response) => {
        if (!mounted) return
        const nextItems = Array.isArray(response.data) ? response.data : []
        setItems(nextItems)
        setActive((current) => current && nextItems.find((item) => item.id === current.id) ? current : nextItems[0] || null)
      })
      .catch(() => {
        if (!mounted) return
        setItems([])
        setActive(null)
        setLoadError(true)
      })
      .finally(() => {
        if (mounted) setLoading(false)
      })

    return () => { mounted = false }
  }, [])

  const activeYoutubeId = getYoutubeVideoId(active?.youtubeUrl || active?.embedUrl)
  const embedUrl = getYoutubeEmbedUrl(activeYoutubeId, active?.embedUrl)
  const isYoutubeVideo = Boolean(activeYoutubeId)

  useEffect(() => {
    mutedRef.current = muted
  }, [muted])

  useEffect(() => {
    setIframeActivated(false)
    setPlaying(false)
  }, [active?.id])

  useEffect(() => {
    if (!isYoutubeVideo || !active || !iframeActivated) {
      youtubePlayerRef.current?.destroy?.()
      youtubePlayerRef.current = null
      return
    }

    let cancelled = false

    loadYoutubeApi()
      .then((YT) => {
        if (cancelled || !playerFrameRef.current) return

        youtubePlayerRef.current = new YT.Player(playerFrameRef.current, {
          events: {
            onReady: (event) => {
              if (mutedRef.current) event.target.mute()
              else event.target.unMute()
            },
            onStateChange: (event) => {
              setPlaying(event.data === YT.PlayerState.PLAYING)
            }
          }
        })
      })
      .catch(() => {})

    return () => {
      cancelled = true
      youtubePlayerRef.current?.destroy?.()
      youtubePlayerRef.current = null
    }
  }, [active, isYoutubeVideo, activeYoutubeId, iframeActivated])

  useEffect(() => {
    if (!isYoutubeVideo) return
    if (muted) youtubePlayerRef.current?.mute?.()
    else youtubePlayerRef.current?.unMute?.()
  }, [isYoutubeVideo, muted])

  const toggle = () => {
    if (isYoutubeVideo) {
      if (!iframeActivated) {
        setIframeActivated(true)
        setPlaying(true)
        return
      }
      const player = youtubePlayerRef.current
      if (!player) return
      const playerState = player.getPlayerState?.()
      if (playerState === window.YT?.PlayerState?.PLAYING) player.pauseVideo()
      else player.playVideo()
      return
    }

    if (videoRef.current?.paused) videoRef.current.play()
    else videoRef.current?.pause()
  }

  const requestFullscreen = () => {
    if (isYoutubeVideo) playerFrameRef.current?.requestFullscreen?.()
    else videoRef.current?.requestFullscreen?.()
  }

  return (
    <>
      <PageHero eyebrow="ویدیوهای جزیره" title="تماشای نجوم و فضا" description="آخرین ویدیوهای عمومی کانال رسمی جزیره از طریق WordPress و منبع رسمی یوتیوب به‌صورت سروری همگام می‌شوند.">
        <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="youtube-cta"><Youtube className="h-5 w-5 fill-current" />کانال رسمی {youtube.handle}<ArrowUpLeft className="h-4 w-4" /></a>
      </PageHero>

      <section className="content-shell section-space">
        {loading ? (
          <div className="surface-card p-8 sm:p-10"><div className="flex min-h-[360px] items-center justify-center"><div className="h-9 w-9 animate-spin rounded-full border-2 border-white/10 border-t-red-300" /></div></div>
        ) : !active ? (
          <div className="surface-card p-8 sm:p-10">
            <div className="flex min-h-[320px] flex-col items-center justify-center text-center">
              <span className="eyebrow">ویدیوهای جزیره</span>
              <h2 className="mt-4 text-2xl font-black text-white">فعلاً ویدیویی برای نمایش در دسترس نیست</h2>
              <p className="mt-3 max-w-xl text-sm leading-8 text-slate-400">{loadError ? 'در آخرین درخواست، WordPress داده‌ی تازه‌ای برای ویدیوها برنگرداند.' : 'به‌محض این‌که ویدیوهای عمومی واجد شرایط موجود باشند، این بخش به‌روزرسانی می‌شود.'}</p>
              <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="youtube-cta mt-6">مشاهده کانال در یوتیوب <ArrowUpLeft className="h-4 w-4" /></a>
            </div>
          </div>
        ) : (
          <div className="videos-layout">
            <div className="surface-card videos-panel p-3 sm:p-4">
              <div className="relative overflow-hidden rounded-[22px] border border-white/[.08] bg-black">
                {isYoutubeVideo && iframeActivated ? (
                  <iframe
                    key={activeYoutubeId}
                    ref={playerFrameRef}
                    className="aspect-video w-full"
                    src={embedUrl}
                    title={active.title}
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    referrerPolicy="strict-origin-when-cross-origin"
                    allowFullScreen
                  />
                ) : isYoutubeVideo ? (
                  <button type="button" onClick={toggle} className="video-poster aspect-video w-full" aria-label="پخش ویدیو از یوتیوب">
                    <span className="absolute inset-0 bg-[radial-gradient(circle_at_70%_30%,rgba(239,68,68,.35),transparent_28%),linear-gradient(135deg,rgba(15,23,42,.98),rgba(30,41,59,.78)_48%,rgba(127,29,29,.65))]" />
                    <span className="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent" />
                    <span className="absolute inset-0 flex items-center justify-center"><span className="flex h-16 w-16 items-center justify-center rounded-full border border-white/20 bg-red-500/90 text-white shadow-[0_18px_60px_rgba(239,68,68,.35)]"><Play className="mr-1 h-7 w-7 fill-current" /></span></span>
                  </button>
                ) : (
                  <video key={active.id} ref={videoRef} className="aspect-video w-full object-cover" poster={active.poster} muted={muted} playsInline preload="metadata" onPlay={() => setPlaying(true)} onPause={() => setPlaying(false)}><source src={active.source} type="video/mp4" /></video>
                )}
                {isYoutubeVideo && iframeActivated ? (
                  <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <button type="button" onClick={toggle} className="pointer-events-auto flex h-14 w-14 items-center justify-center rounded-full border border-white/20 bg-black/55 text-white backdrop-blur sm:h-16 sm:w-16" aria-label={playing ? 'توقف ویدیو' : 'پخش ویدیو'}>{playing ? <Pause className="h-6 w-6 fill-current sm:h-7 sm:w-7" /> : <Play className="mr-1 h-6 w-6 fill-current sm:h-7 sm:w-7" />}</button>
                  </div>
                ) : (
                  <>
                    <button type="button" onClick={toggle} className="absolute inset-0 flex items-center justify-center bg-black/5" aria-label={playing ? 'توقف ویدیو' : 'پخش ویدیو'}><span className="flex h-14 w-14 items-center justify-center rounded-full border border-white/20 bg-black/55 text-white backdrop-blur sm:h-16 sm:w-16">{playing ? <Pause className="h-6 w-6 fill-current sm:h-7 sm:w-7" /> : <Play className="mr-1 h-6 w-6 fill-current sm:h-7 sm:w-7" />}</span></button>
                    <div className="absolute bottom-3 left-3 flex gap-2 sm:bottom-4 sm:left-4"><button type="button" onClick={() => setMuted((value) => !value)} className="icon-button bg-black/55" aria-label="تغییر صدا">{muted ? <VolumeX className="h-4 w-4" /> : <Volume2 className="h-4 w-4" />}</button><button type="button" onClick={requestFullscreen} className="icon-button bg-black/55" aria-label="تمام‌صفحه"><Maximize2 className="h-4 w-4" /></button></div>
                  </>
                )}
              </div>
              <div className="p-4 sm:p-5">
                <div className="flex flex-wrap items-start justify-between gap-4">
                  <div className="min-w-0">
                    <h2 className="text-[clamp(1.35rem,3vw,1.9rem)] font-black leading-8 text-white">{active.title}</h2>
                    <p className="mt-3 text-sm leading-8 text-slate-400">{active.description}</p>
                    <div className="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                      {active.duration && <span className="rounded-full border border-white/[.08] bg-white/[.03] px-3 py-1.5">{active.duration}</span>}
                      {active.publishedAt && <span className="rounded-full border border-white/[.08] bg-white/[.03] px-3 py-1.5">{new Intl.DateTimeFormat('fa-IR', { dateStyle: 'long' }).format(new Date(active.publishedAt))}</span>}
                    </div>
                  </div>
                  {active.youtubeUrl && <a href={active.youtubeUrl} target="_blank" rel="noopener noreferrer" className="secondary-btn">نسخه یوتیوب <ExternalLink className="h-4 w-4" /></a>}
                </div>
              </div>
            </div>

            <aside className="surface-card videos-panel p-5 sm:p-6">
              <div className="flex items-center justify-between"><div><h2 className="card-title">فهرست ویدیوها</h2><p className="mt-1 text-xs text-slate-500">آخرین ویدیوهای واجد شرایط کانال</p></div><Youtube className="h-5 w-5 text-red-300" /></div>
              <div className="mt-5 max-h-[480px] space-y-3 overflow-y-auto pr-1 custom-scrollbar">
                {items.map((video, order) => (
                  <button type="button" key={video.id} onClick={() => { setActive(video); setPlaying(false) }} className={`flex w-full items-center gap-3 rounded-2xl border p-3 text-right transition ${active.id === video.id ? 'border-violet-300/30 bg-violet-400/[.08]' : 'border-white/[.07] bg-white/[.02] hover:bg-white/[.05]'}`}>
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-black/35 text-sm font-black text-slate-400">{String(order + 1).padStart(2, '0')}</span>
                    <span className="min-w-0">
                      <strong className="line-clamp-2 text-sm leading-6 text-white">{video.title}</strong>
                      <span className="mt-1 block text-xs text-slate-500">{video.duration || 'یوتیوب'}</span>
                    </span>
                  </button>
                ))}
              </div>
              <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="youtube-cta mt-5 w-full">مشاهده همه در یوتیوب <ArrowUpLeft className="h-4 w-4" /></a>
            </aside>
          </div>
        )}
      </section>
    </>
  )
}
