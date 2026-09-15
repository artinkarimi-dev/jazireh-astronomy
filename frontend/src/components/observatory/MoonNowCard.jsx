import { Moon, Navigation, Sparkles } from 'lucide-react'
import ObservatoryCard from './ObservatoryCard'
import { SKY_CITIES, buildSkyTonight, directionLabel, getCityById, locationFromCoords, toFaNumber } from '../../lib/skyTonight'

export default function MoonNowCard({ widget, skyWidget, fallbackSky, loading = false, compact = false }) {
  const data = widget?.data
  const skyMoon = skyWidget?.data?.moonStatus || {}
  const location = readPreferredLocation()
  const model = buildSkyTonight({ location, date: new Date() })
  const moon = model.moon
  const illuminationNumber = numericOr(
    data?.illuminationPercent,
    skyMoon?.illuminationPercent,
    fallbackSky?.moonIllumination,
    moon.illumination,
  )
  const moonAge = numericOr(data?.moonAgeDays, skyMoon?.moonAgeDays, fallbackSky?.moonAge, moon.moonAge)
  const phaseLabel = data?.phaseLabelFa || skyMoon?.phaseLabelFa || fallbackSky?.moonPhase || moon.phaseLabelFa
  const trendLabel = data?.waxingWaningLabelFa || skyMoon?.waxingWaningLabelFa || fallbackSky?.moonTrend || (moonAge < 14.765 ? 'افزایشی' : 'کاهشی')
  const altitude = moon.altitude
  const azimuth = moon.azimuth
  const visibleLabel = altitude > 0 ? `بالای افق ${directionLabel(azimuth)}` : 'زیر افق'
  const visibilityNote = altitude > 0
    ? `ارتفاع تقریبی ${toFaNumber(altitude.toFixed(0))} درجه و سمت ${directionLabel(azimuth)} برای ${location.name}.`
    : `اکنون برای ${location.name} زیر افق است؛ زمان دیگری از شب را در آسمان امروز بررسی کنید.`
  const illumination = `${toFaNumber(illuminationNumber.toFixed(0))}٪`
  const isWaxing = moonAge < 14.765

  return (
    <ObservatoryCard
      eyebrow="Moon Now"
      title="ماه اکنون"
      description="فاز، روشنایی و جایگاه فعلی ماه با محاسبات نجومی تقریبی؛ این تصویر زنده یا عکس واقعی نیست."
      status={loading ? 'stale' : widget?.status}
      message={loading ? 'در حال محاسبه' : widget?.message}
      updatedAt={widget?.updatedAt}
      source={widget?.source || 'Local astronomical calculation'}
      className="accent-purple"
    >
      <div className="moon-now-layout">
        <div className="moon-now-orb-wrap">
          <MoonPhaseDisk
            illumination={illuminationNumber}
            moonAge={moonAge}
            phaseLabel={phaseLabel}
            trendLabel={trendLabel}
            isWaxing={isWaxing}
          />
        </div>

        <div className="moon-now-copy">
          <div className="moon-now-kicker"><Moon className="h-4 w-4" /> نمودار محاسباتی فاز ماه</div>
          <strong>{phaseLabel || (loading ? 'در حال دریافت' : 'نامشخص')}</strong>
          <p>{trendLabel} · روشنایی {illumination}</p>
          <small>{visibilityNote}</small>
        </div>
      </div>

      <dl className="moon-now-metrics">
        <Metric icon={Sparkles} label="سن ماه" value={`${toFaNumber(moonAge.toFixed(1))} روز`} />
        <Metric icon={Navigation} label="جایگاه اکنون" value={visibleLabel} />
        <Metric label="ارتفاع" value={`${toFaNumber(altitude.toFixed(0))}°`} />
        <Metric label="سمت" value={`${toFaNumber(azimuth.toFixed(0))}° ${directionLabel(azimuth)}`} />
        {!compact ? <Metric label="ماه کامل بعدی" value={formatDate(data?.nextFullMoon)} /> : null}
        {!compact ? <Metric label="ماه نو بعدی" value={formatDate(data?.nextNewMoon)} /> : null}
        <Metric label="مکان" value={`${location.name}${location.country ? `، ${location.country}` : ''}`} />
        {!compact ? <Metric label="دقت" value="تقریبی، آموزشی" /> : null}
      </dl>
    </ObservatoryCard>
  )
}

function MoonPhaseDisk({ illumination, moonAge, phaseLabel, trendLabel, isWaxing }) {
  const safeIllumination = Math.max(0, Math.min(100, Number.isFinite(illumination) ? illumination : 0))
  const litPath = moonPhasePath(safeIllumination, isWaxing)
  const terminatorX = 50 + (isWaxing ? 1 : -1) * (50 - safeIllumination) * 0.42
  const label = `${phaseLabel || 'فاز ماه'}، ${trendLabel || (isWaxing ? 'افزایشی' : 'کاهشی')}، روشنایی ${toFaNumber(safeIllumination.toFixed(0))} درصد، سن ${toFaNumber((Number.isFinite(moonAge) ? moonAge : 0).toFixed(1))} روز`

  return (
    <svg className="moon-now-svg" viewBox="0 0 100 100" role="img" aria-label={label}>
      <defs>
        <filter id="moon-surface-grain" x="-20%" y="-20%" width="140%" height="140%">
          <feTurbulence type="fractalNoise" baseFrequency="0.95" numOctaves="4" seed="21" result="noise" />
          <feColorMatrix type="saturate" values="0" />
          <feComponentTransfer>
            <feFuncA type="table" tableValues="0 0.18" />
          </feComponentTransfer>
        </filter>
        <radialGradient id="moon-lit-surface" cx="35%" cy="28%" r="70%">
          <stop offset="0%" stopColor="#fffef6" />
          <stop offset="42%" stopColor="#ece7d8" />
          <stop offset="78%" stopColor="#b9b2a2" />
          <stop offset="100%" stopColor="#7e7b75" />
        </radialGradient>
        <radialGradient id="moon-dark-surface" cx="38%" cy="28%" r="75%">
          <stop offset="0%" stopColor="#3b4254" />
          <stop offset="58%" stopColor="#131827" />
          <stop offset="100%" stopColor="#060914" />
        </radialGradient>
        <radialGradient id="moon-rim-light" cx="30%" cy="24%" r="76%">
          <stop offset="0%" stopColor="rgba(255,255,255,.55)" />
          <stop offset="50%" stopColor="rgba(255,255,255,.08)" />
          <stop offset="100%" stopColor="rgba(255,255,255,0)" />
        </radialGradient>
        <clipPath id="moon-disk-clip">
          <circle cx="50" cy="50" r="42" />
        </clipPath>
      </defs>
      <circle cx="50" cy="50" r="45" className="moon-now-halo" />
      <circle cx="50" cy="50" r="42" fill="url(#moon-dark-surface)" />
      <path d={litPath} fill="url(#moon-lit-surface)" clipPath="url(#moon-disk-clip)" />
      <circle cx="50" cy="50" r="42" clipPath="url(#moon-disk-clip)" filter="url(#moon-surface-grain)" className="moon-now-grain" />
      <g clipPath="url(#moon-disk-clip)" className="moon-now-craters">
        <circle cx="34" cy="34" r="4.6" />
        <circle cx="62" cy="58" r="6.2" />
        <circle cx="47" cy="70" r="3.4" />
        <circle cx="70" cy="36" r="2.6" />
        <circle cx="28" cy="58" r="2.4" />
        <circle cx="40" cy="48" r="1.8" />
        <circle cx="58" cy="30" r="2.1" />
        <path d="M24 45 C34 39 42 40 52 45 S70 50 77 43" />
        <path d="M29 68 C41 62 52 64 68 72" />
      </g>
      <circle cx="50" cy="50" r="42" fill="url(#moon-rim-light)" clipPath="url(#moon-disk-clip)" className="moon-now-glaze" />
      <path d={`M50 8 A${Math.max(4, Math.abs(50 - safeIllumination) * 0.84)} 42 0 0 ${isWaxing ? 0 : 1} 50 92`} className="moon-now-terminator" transform={`translate(${(terminatorX - 50) * 0.1} 0)`} />
      <circle cx="50" cy="50" r="42" className="moon-now-rim" />
    </svg>
  )
}

function moonPhasePath(illumination, isWaxing) {
  if (illumination <= 1) return ''
  if (illumination >= 99) return 'M50 8 A42 42 0 1 1 49.9 8 Z'

  const rx = Math.max(4, Math.abs(50 - illumination) * 0.84)
  if (isWaxing && illumination < 50) return `M50 8 A42 42 0 0 1 50 92 A${rx} 42 0 0 0 50 8 Z`
  if (isWaxing) return `M50 8 A42 42 0 1 1 50 92 A${rx} 42 0 0 1 50 8 Z`
  if (illumination < 50) return `M50 8 A42 42 0 0 0 50 92 A${rx} 42 0 0 1 50 8 Z`
  return `M50 8 A42 42 0 1 0 50 92 A${rx} 42 0 0 0 50 8 Z`
}

function Metric({ icon: Icon, label, value }) {
  return (
    <div className="moon-now-metric">
      {Icon ? <Icon className="h-4 w-4 text-violet-200" /> : null}
      <dt>{label}</dt>
      <dd>{value}</dd>
    </div>
  )
}

function formatDate(value) {
  if (!value) return '—'
  try {
    return new Intl.DateTimeFormat('fa-IR', { month: 'short', day: 'numeric' }).format(new Date(value))
  } catch {
    return '—'
  }
}

function numericOr(...values) {
  for (const value of values) {
    const number = parseNumberish(value)
    if (Number.isFinite(number)) return number
  }
  return 0
}

function parseNumberish(value) {
  if (typeof value === 'number') return value
  if (typeof value !== 'string') return Number.NaN

  const normalized = value
    .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
    .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)))
    .replace(/[,،]/g, '.')
    .replace(/[^\d.-]/g, '')

  return Number(normalized)
}

function readPreferredLocation() {
  if (typeof window === 'undefined') return getCityById('tehran')

  try {
    const savedCity = window.localStorage.getItem('jazireh.sky.city')
    if (savedCity === 'custom') {
      const savedLocation = JSON.parse(window.localStorage.getItem('jazireh.sky.customLocation') || 'null')
      if (Number.isFinite(Number(savedLocation?.latitude)) && Number.isFinite(Number(savedLocation?.longitude))) {
        return locationFromCoords(savedLocation.latitude, savedLocation.longitude)
      }
    }

    if (SKY_CITIES.some((city) => city.id === savedCity)) return getCityById(savedCity)
  } catch {
    // Saved location is optional; Tehran is the safe default.
  }

  return getCityById('tehran')
}
