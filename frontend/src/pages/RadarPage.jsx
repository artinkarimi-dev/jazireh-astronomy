import { useCallback, useEffect, useMemo, useState } from 'react'
import { AlertTriangle, Info, Loader2, MapPin, Satellite, Star } from 'lucide-react'
import PageHero from '../components/PageHero'
import SkyRadar from '../components/radar/SkyRadar'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'

const educationalTargets = [
  {
    id: 'sample-iss',
    name: 'ایستگاه فضایی بین‌المللی',
    code: 'ISS',
    type: 'satellite',
    top: 24,
    left: 63,
    statusLabel: 'نمونه آموزشی',
    summary: 'برای رهگیری زنده ISS باید سرویس داده مداری به رادار متصل شود.',
    source: 'educational-static',
    accuracy: 'educational',
    confidence: 'low',
    isFallback: true,
    warning: 'این مورد مسیر زنده ماهواره نیست و فقط برای آموزش رابط رادار نمایش داده می‌شود.',
    icon: Satellite,
  },
  {
    id: 'sample-hst',
    name: 'تلسکوپ فضایی هابل',
    code: 'HST',
    type: 'satellite',
    top: 52,
    left: 28,
    statusLabel: 'نمونه آموزشی',
    summary: 'مسیر نمایشی است و داده مداری زنده ندارد.',
    source: 'educational-static',
    accuracy: 'educational',
    confidence: 'low',
    isFallback: true,
    warning: 'این مورد داده زنده نیست.',
    icon: Satellite,
  },
]

function clamp(value, min, max) {
  return Math.min(Math.max(value, min), max)
}

function toNumber(value) {
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

function toPersianNumber(value, options = {}) {
  if (value === null || value === undefined || value === '') return 'ناموجود'
  return Number(value).toLocaleString('fa-IR', options)
}

function projectPlanet(planet) {
  const altitude = toNumber(planet.altitude)
  const azimuth = toNumber(planet.azimuth)
  if (altitude === null || azimuth === null || altitude < -6) return null

  const normalizedAltitude = clamp(altitude, 0, 90)
  const radius = ((90 - normalizedAltitude) / 90) * 42
  const radians = (azimuth * Math.PI) / 180

  return {
    top: clamp(50 - Math.cos(radians) * radius, 8, 92),
    left: clamp(50 + Math.sin(radians) * radius, 8, 92),
  }
}

function mapPlanetTarget(planet) {
  const position = projectPlanet(planet)
  if (!position) return null

  return {
    id: `planet-${planet.id || planet.name}`,
    name: planet.name,
    label: planet.name,
    code: planet.englishName || planet.id || 'planet',
    type: 'planet',
    altitude: toNumber(planet.altitude),
    azimuth: toNumber(planet.azimuth),
    top: position.top,
    left: position.left,
    statusLabel: planet.statusLabel || planet.status || 'وضعیت ناموجود',
    summary: planet.summary || 'بر پایه داده موقعیت سیاره در همین لحظه.',
    source: planet.source || 'unknown',
    accuracy: planet.accuracy || 'unknown',
    confidence: planet.confidence || 'unknown',
    calculatedAt: planet.calculatedAt,
    isFallback: Boolean(planet.isFallback),
    warning: planet.displayWarning || planet.warning || '',
    icon: Star,
  }
}

export default function RadarPage() {
  const [planetPayload, setPlanetPayload] = useState(null)
  const [state, setState] = useState('loading')
  const [selectedId, setSelectedId] = useState('')

  usePageMeta('نمای آموزشی رادار آسمان', 'نمای آموزشی و واکنش‌گرا برای دیدن موقعیت اجرام قابل رصد و نمونه‌های ماهواره‌ای در جزیره نجوم.')

  useEffect(() => {
    let active = true
    setState('loading')

    api.get('/api/planets', { timeout: 12000 }).then((response) => {
      if (!active) return
      const payload = response.data || null
      setPlanetPayload(payload)
      setState(payload?.status || 'ready')
    }).catch(() => {
      if (!active) return
      setPlanetPayload(null)
      setState('error')
    })

    return () => { active = false }
  }, [])

  const planetTargets = useMemo(
    () => (planetPayload?.items || []).map(mapPlanetTarget).filter(Boolean),
    [planetPayload]
  )

  const targets = useMemo(() => {
    const samples = educationalTargets.map((target) => ({
      ...target,
      label: target.name,
      altitude: null,
      azimuth: null,
    }))
    return [...planetTargets, ...samples]
  }, [planetTargets])

  useEffect(() => {
    if (!targets.length) return
    setSelectedId((current) => targets.some((target) => target.id === current) ? current : targets[0].id)
  }, [targets])

  const selectedTarget = useMemo(
    () => targets.find((target) => target.id === selectedId) || targets[0] || null,
    [targets, selectedId]
  )

  const handleSelectTarget = useCallback((id) => setSelectedId(id), [])

  const statusMessage = useMemo(() => {
    if (state === 'loading') return 'در حال دریافت موقعیت واقعی سیاره‌ها...'
    if (state === 'error') return 'داده سیاره‌ها در دسترس نیست؛ نمونه‌های آموزشی با برچسب داده پشتیبان نمایش داده می‌شوند.'
    if (planetPayload?.displayWarning) return planetPayload.displayWarning
    return 'موقعیت سیاره‌ها از API رصدی جزیره گرفته می‌شود؛ نمونه‌های ماهواره‌ای آموزشی هستند.'
  }, [planetPayload, state])

  const locationLabel = planetPayload?.locationLabel || planetPayload?.observer?.city || 'محاسبه برای تهران'

  return (
    <>
      <PageHero eyebrow="رادار آسمان" title="مسیر اجرام را دنبال کنید" description="نمایی آموزشی و واکنش‌گرا برای آشنایی با موقعیت سیاره‌ها و نمونه‌های ماهواره‌ای." />
      <section className="content-shell section-space">
        <div className={`radar-status-banner state-${state}`}>
          {state === 'loading' ? <Loader2 className="h-4 w-4 animate-spin text-sky-200" /> : state === 'error' ? <AlertTriangle className="h-4 w-4 text-amber-200" /> : <Info className="h-4 w-4 text-sky-300" />}
          <span>{statusMessage}</span>
        </div>

        <div className="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
          <span className="state-pill"><MapPin className="ml-1 h-3.5 w-3.5" />{locationLabel}</span>
          <span className="state-pill">منبع سیاره‌ها: {planetPayload?.source || 'در حال بررسی'}</span>
          <span className="state-pill">دقت: {planetPayload?.accuracy || 'آموزشی/رصدی'}</span>
        </div>

        <div className="radar-layout mt-5">
          <div className="surface-card radar-panel radar-main-panel">
            <SkyRadar
              targets={targets}
              selectedId={selectedId}
              onSelectTarget={handleSelectTarget}
              state={state}
              warning={statusMessage}
              locationLabel={locationLabel}
            />
          </div>

          <aside className="surface-card radar-panel radar-side-panel">
            <div>
              <span className="eyebrow">اجرام در محدوده</span>
              <h2 className="mt-2 card-title">راهنمای رصدی رادار</h2>
              <p className="mt-2 text-xs leading-6 text-slate-500">سیاره‌ها از داده واقعی موقعیت استفاده می‌کنند؛ ماهواره‌ها فعلا نمونه آموزشی هستند.</p>
            </div>

            <div className="radar-target-list custom-scrollbar">
              {state === 'loading' && [0, 1, 2].map((item) => <div key={item} className="radar-target-card radar-target-card-loading" />)}

              {state !== 'loading' && targets.map((target) => {
                const Icon = target.icon || (target.type === 'satellite' ? Satellite : Star)
                const isActive = target.id === selectedTarget?.id
                return (
                  <button
                    key={target.id}
                    type="button"
                    className={`radar-target-card ${isActive ? 'radar-target-card-active' : ''}`}
                    onClick={() => handleSelectTarget(target.id)}
                  >
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-400/10 text-sky-200"><Icon className="h-5 w-5" /></span>
                    <span className="min-w-0 flex-1 text-right">
                      <strong className="block text-sm leading-6 text-white">{target.name}</strong>
                      <span className="mt-1 block text-xs leading-5 text-slate-500">{target.code} · {target.statusLabel}</span>
                    </span>
                    {target.isFallback && <span className="radar-data-chip">داده پشتیبان</span>}
                  </button>
                )
              })}
            </div>

            <div className="radar-detail-panel">
              <div className="flex items-center justify-between gap-3">
                <strong className="text-sm text-white">{selectedTarget?.name || 'هدف انتخاب نشده'}</strong>
                <span className="radar-data-chip">{selectedTarget?.accuracy || 'ناموجود'}</span>
              </div>
              <p className="mt-3 text-xs leading-6 text-slate-500">{selectedTarget?.summary || 'داده‌ای برای نمایش وجود ندارد.'}</p>
              <div className="mt-4 grid grid-cols-2 gap-2">
                <RadarFact label="ارتفاع" value={selectedTarget?.altitude !== null && selectedTarget?.altitude !== undefined ? `${toPersianNumber(selectedTarget.altitude, { maximumFractionDigits: 1 })}°` : 'ناموجود'} />
                <RadarFact label="سمت" value={selectedTarget?.azimuth !== null && selectedTarget?.azimuth !== undefined ? `${toPersianNumber(selectedTarget.azimuth, { maximumFractionDigits: 1 })}°` : 'ناموجود'} />
                <RadarFact label="منبع" value={selectedTarget?.source || 'ناموجود'} />
                <RadarFact label="اطمینان" value={selectedTarget?.confidence || 'ناموجود'} />
              </div>
              {(selectedTarget?.warning || selectedTarget?.isFallback) && (
                <p className="mt-4 rounded-2xl border border-amber-300/15 bg-amber-300/[.045] p-3 text-xs leading-6 text-amber-100">{selectedTarget.warning || 'این داده پشتیبان است.'}</p>
              )}
            </div>
          </aside>
        </div>
      </section>
    </>
  )
}

function RadarFact({ label, value }) {
  return (
    <div className="rounded-2xl border border-white/[.06] bg-white/[.025] p-3">
      <span className="block text-[10px] text-slate-500">{label}</span>
      <strong className="mt-1 block min-h-[22px] text-xs leading-5 text-slate-100">{value}</strong>
    </div>
  )
}
