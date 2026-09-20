import { useEffect, useMemo, useRef, useState } from 'react'
import {
  Clock3,
  CloudMoon,
  Droplets,
  Eye,
  LocateFixed,
  MapPin,
  Maximize2,
  Moon,
  Search,
  Share2,
  SlidersHorizontal,
  Star,
  Sun,
  Wind,
} from 'lucide-react'
import PageHero from '../components/PageHero'
import InteractiveSkyMap from '../components/sky/InteractiveSkyMap'
import AstronomyEvents from '../components/sky/AstronomyEvents'
import PlanetVisibility from '../components/sky/PlanetVisibility'
import TonightHighlights from '../components/sky/TonightHighlights'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { skyData as fallbackSky } from '../data/fallback'
import {
  SKY_CITIES,
  buildSkyTonight,
  dateFromTonightOffset,
  directionLabel,
  formatTime,
  getCityById,
  locationFromCoords,
  normalizeSkyQuery,
  offsetFromDate,
  toFaNumber,
} from '../lib/skyTonight'

const TIME_MIN = -180
const TIME_MAX = 360

export default function SkyTodayPage() {
  const [sky, setSky] = useState(fallbackSky)
  const [planets, setPlanets] = useState(fallbackSky.planets)
  const [events, setEvents] = useState(fallbackSky.upcomingEvents)
  const [state, setState] = useState('loading')
  const [planetState, setPlanetState] = useState('loading')
  const [eventState, setEventState] = useState('loading')
  const [selectedCityId, setSelectedCityId] = useState(() => readInitialCity())
  const [customLocation, setCustomLocation] = useState(() => readSavedCustomLocation())
  const [timeOffset, setTimeOffset] = useState(() => readInitialOffset())
  const [geoState, setGeoState] = useState('idle')
  const [geoMessage, setGeoMessage] = useState('')
  const [nightMode, setNightMode] = useState(false)
  const [showConstellations, setShowConstellations] = useState(true)
  const [skySearch, setSkySearch] = useState('')
  const [selectedObjectId, setSelectedObjectId] = useState('moon')
  const [shareState, setShareState] = useState('idle')
  const [fullscreenMessage, setFullscreenMessage] = useState('')
  const mapWrapRef = useRef(null)

  usePageMeta('آسمان امروز', 'نقشه تعاملی آسمان امشب، فاز ماه، دیدپذیری سیاره‌ها و رویدادهای رصدی جزیره نجوم.')

  const effectiveLocation = selectedCityId === 'custom' && customLocation ? customLocation : getCityById(selectedCityId)
  const selectedDate = useMemo(() => dateFromTonightOffset(timeOffset), [timeOffset])
  const skyModel = useMemo(
    () => buildSkyTonight({ location: effectiveLocation, date: selectedDate, backendSky: sky, backendPlanets: planets, events }),
    [effectiveLocation, selectedDate, sky, planets, events],
  )
  const derivedHighlights = skyModel.highlights
  const enrichedPlanets = useMemo(() => ({
    ...planets,
    status: planetState === 'loading' ? 'loading' : planets.status || 'ready',
    accuracy: 'mixed',
    location: { city: effectiveLocation.name, label: `${effectiveLocation.name}، ${effectiveLocation.country || 'ایران'}` },
    items: skyModel.planetCards,
    displayWarning: 'دیدپذیری سیاره‌ها برای مکان و زمان انتخاب‌شده به صورت تقریبی محاسبه شده و با داده کش‌شده وردپرس تکمیل می‌شود.',
  }), [effectiveLocation, planetState, planets, skyModel.planetCards])
  const enrichedEvents = useMemo(() => ({
    ...events,
    location: { city: effectiveLocation.name },
  }), [effectiveLocation.name, events])
  const searchResults = useMemo(() => {
    const query = normalizeSkyQuery(skySearch)
    if (!query) return []
    const constellationItems = skyModel.constellations.map((item) => ({
      id: item.id,
      name: item.name,
      latin: '',
      type: 'constellation',
      typeLabel: 'صورت فلکی',
      visible: item.visible,
      targetId: item.points.find((point) => point.altitude > -5)?.id || item.points[0]?.id || 'moon',
    }))
    return [...skyModel.searchIndex, ...constellationItems]
      .filter((item) => normalizeSkyQuery(`${item.name} ${item.latin} ${item.typeLabel}`).includes(query))
      .slice(0, 7)
  }, [skyModel.constellations, skyModel.searchIndex, skySearch])

  const stateMessage = sky.displayWarning || sky.message || ''
  const showStateBanner = state === 'loading' || state === 'error' || Boolean(stateMessage)

  useEffect(() => {
    let active = true
    setState('loading')

    api.get('/api/sky/today').then((response) => {
      if (!active) return
      if (response.data) {
        setSky({ ...fallbackSky, ...response.data })
        setState(response.data.status || 'ready')
      }
    }).catch(() => {
      if (!active) return
      setState('error')
    })

    return () => { active = false }
  }, [])

  useEffect(() => {
    let active = true
    setPlanetState('loading')

    api.get('/api/planets', { timeout: 15000 }).then((response) => {
      if (!active) return
      const payload = normalizePlanets(response.data)
      setPlanets(payload)
      setPlanetState(payload.status || 'ready')
    }).catch(() => {
      if (!active) return
      setPlanets({ ...fallbackSky.planets, status: 'unavailable', items: [] })
      setPlanetState('error')
    })

    return () => { active = false }
  }, [])

  useEffect(() => {
    let active = true
    setEventState('loading')

    api.get('/api/events?status=upcoming&per_page=4').then(async (response) => {
      if (!active) return
      const curated = normalizeEvents(response.data)
      if (curated.items.length) {
        setEvents(curated)
        setEventState(curated.status || 'ready')
        return
      }

      const scientificResponse = await api.get('/api/events?limit=4&days=90')
      if (!active) return
      const scientific = normalizeEvents(scientificResponse.data)
      setEvents(scientific)
      setEventState(scientific.status || 'ready')
    }).catch(() => {
      if (!active) return
      setEvents({ ...fallbackSky.upcomingEvents, status: 'unavailable', items: [] })
      setEventState('error')
    })

    return () => { active = false }
  }, [])

  useEffect(() => {
    try {
      window.localStorage.setItem('jazireh.sky.city', selectedCityId)
      if (customLocation) window.localStorage.setItem('jazireh.sky.customLocation', JSON.stringify(customLocation))
      const url = new URL(window.location.href)
      if (selectedCityId !== 'custom') url.searchParams.set('city', selectedCityId)
      else url.searchParams.delete('city')
      url.searchParams.set('time', String(timeOffset))
      window.history.replaceState({}, '', url)
    } catch {
      // Local storage and history are enhancement-only.
    }
  }, [customLocation, selectedCityId, timeOffset])

  function handleGeolocation() {
    if (!navigator.geolocation) {
      setGeoState('error')
      setGeoMessage('مرورگر شما دسترسی مکان‌یابی را پشتیبانی نمی‌کند؛ لطفا یکی از شهرها را انتخاب کنید.')
      return
    }

    setGeoState('loading')
    setGeoMessage('در حال دریافت موقعیت از مرورگر...')
    navigator.geolocation.getCurrentPosition((position) => {
      const nextLocation = locationFromCoords(position.coords.latitude, position.coords.longitude)
      setCustomLocation(nextLocation)
      setSelectedCityId('custom')
      setGeoState('ready')
      setGeoMessage('موقعیت مرورگر فقط روی همین دستگاه ذخیره شد و در لینک اشتراک‌گذاری قرار نمی‌گیرد.')
    }, () => {
      setGeoState('error')
      setGeoMessage('دسترسی به موقعیت داده نشد یا در دسترس نبود؛ شهر انتخاب‌شده همچنان استفاده می‌شود.')
    }, { enableHighAccuracy: false, timeout: 8000, maximumAge: 900000 })
  }

  function handleFullscreen() {
    const node = mapWrapRef.current
    if (!node?.requestFullscreen) {
      setFullscreenMessage('نمای تمام‌صفحه در این مرورگر فعال نیست؛ نقشه همچنان در همین صفحه قابل استفاده است.')
      return
    }
    node.requestFullscreen()
      .then(() => {
        setFullscreenMessage('نمای تمام‌صفحه فعال شد؛ برای خروج از کلید Esc یا کنترل مرورگر استفاده کنید.')
        window.setTimeout(() => setFullscreenMessage(''), 4200)
      })
      .catch(() => setFullscreenMessage('نمای تمام‌صفحه توسط مرورگر اجازه داده نشد؛ نقشه همچنان در همین صفحه قابل استفاده است.'))
  }

  async function handleShare() {
    const url = new URL(window.location.href)
    if (selectedCityId !== 'custom') url.searchParams.set('city', selectedCityId)
    else url.searchParams.delete('city')
    url.searchParams.set('time', String(timeOffset))
    const shareData = {
      title: 'آسمان امشب در جزیره',
      text: `نقشه تقریبی آسمان برای ${effectiveLocation.name} در ${formatTime(selectedDate, effectiveLocation.timezone)}`,
      url: url.toString(),
    }

    try {
      if (navigator.share) await navigator.share(shareData)
      else if (navigator.clipboard?.writeText) await navigator.clipboard.writeText(shareData.url)
      else if (!legacyCopyText(shareData.url)) throw new Error('copy-unavailable')
      setShareState('copied')
      window.setTimeout(() => setShareState('idle'), 4200)
    } catch {
      if (legacyCopyText(shareData.url)) {
        setShareState('copied')
        window.setTimeout(() => setShareState('idle'), 4200)
      } else {
        setShareState('failed')
        window.setTimeout(() => setShareState('idle'), 5200)
      }
    }
  }

  return (
    <>
      <PageHero eyebrow="راهنمای رصد جزیره" title="آسمان امروز" description="نقشه زنده‌نما و تعاملی آسمان امشب را برای شهر، ساعت و اهداف رصدی خود تنظیم کنید." />

      <section className={`content-shell section-space sky-tonight-page ${nightMode ? 'sky-tonight-night' : ''}`}>
        {showStateBanner ? (
          <div className={`sky-state-banner state-${state}`}>
            <span>{state === 'loading' ? 'در حال دریافت داده‌های آسمان...' : state === 'error' ? 'دریافت داده تازه ممکن نبود؛ داده پشتیبان و محاسبات محلی نمایش داده می‌شود.' : stateMessage}</span>
          </div>
        ) : null}

        <div className="sky-tonight-console">
          <div className="sky-control-card">
            <span className="eyebrow"><MapPin className="h-4 w-4" /> مکان رصد</span>
            <label className="sky-control-label" htmlFor="sky-city">شهر</label>
            <select id="sky-city" className="input-field" value={selectedCityId} onChange={(event) => setSelectedCityId(event.target.value)}>
              {SKY_CITIES.map((city) => <option key={city.id} value={city.id}>{city.name}</option>)}
              {customLocation ? <option value="custom">موقعیت من</option> : null}
            </select>
            <button type="button" className="secondary-btn mt-3 w-full" onClick={handleGeolocation}>
              <LocateFixed className="h-4 w-4" /> استفاده از موقعیت من
            </button>
            {geoMessage ? <p className={`sky-helper-text state-${geoState}`}>{geoMessage}</p> : null}
          </div>

          <div className="sky-control-card sky-time-card">
            <span className="eyebrow"><SlidersHorizontal className="h-4 w-4" /> زمان امشب</span>
            <div className="sky-time-readout">
              <strong>{formatTime(selectedDate, effectiveLocation.timezone)}</strong>
              <span>{timeOffset === 0 ? 'ساعت ۲۱:۰۰' : `${toFaNumber(Math.abs(timeOffset))} دقیقه ${timeOffset > 0 ? 'بعد از' : 'قبل از'} ۲۱:۰۰`}</span>
            </div>
            <input
              className="time-slider"
              type="range"
              min={TIME_MIN}
              max={TIME_MAX}
              step="15"
              value={timeOffset}
              onChange={(event) => setTimeOffset(Number(event.target.value))}
              aria-label="تنظیم زمان نقشه آسمان"
            />
            <div className="sky-time-actions">
              <button type="button" className="secondary-btn" onClick={() => setTimeOffset((value) => clamp(value - 90, TIME_MIN, TIME_MAX))}>زودتر</button>
              <button type="button" className="secondary-btn" onClick={() => setTimeOffset(clamp(offsetFromDate(new Date()), TIME_MIN, TIME_MAX))}>اکنون</button>
              <button type="button" className="secondary-btn" onClick={() => setTimeOffset((value) => clamp(value + 90, TIME_MIN, TIME_MAX))}>دیرتر</button>
            </div>
          </div>

          <div className="sky-control-card">
            <span className="eyebrow"><Search className="h-4 w-4" /> جست‌وجوی آسمان</span>
            <label className="sky-control-label" htmlFor="sky-object-search">جرم یا صورت فلکی</label>
            <input id="sky-object-search" className="input-field" value={skySearch} onChange={(event) => setSkySearch(event.target.value)} placeholder="ماه، زهره، شباهنگ..." />
            {searchResults.length ? (
              <div className="sky-search-results">
                {searchResults.map((item) => (
                  <button key={item.id} type="button" onClick={() => { setSelectedObjectId(item.targetId || item.id); setSkySearch('') }}>
                    <span>{item.name}</span>
                    <small>{item.typeLabel}، {item.type === 'constellation' ? (item.visible ? 'بخشی از آن بالای افق است' : 'در این زمان پایین‌تر است') : item.visible ? `بالای افق ${directionLabel(item.azimuth)}` : 'زیر افق'}</small>
                  </button>
                ))}
              </div>
            ) : skySearch ? <p className="sky-helper-text">نتیجه‌ای در فهرست اجرام این نسخه پیدا نشد.</p> : null}
          </div>
        </div>

        <div className="sky-action-strip">
          <span><Clock3 className="h-4 w-4" /> محاسبه محلی برای {effectiveLocation.name} در {formatTime(selectedDate, effectiveLocation.timezone)}</span>
          <button type="button" className={`secondary-btn ${showConstellations ? 'is-active' : ''}`} onClick={() => setShowConstellations((value) => !value)}>
            <Star className="h-4 w-4" /> صورت‌های فلکی
          </button>
          <button type="button" className={`secondary-btn ${nightMode ? 'is-active' : ''}`} onClick={() => setNightMode((value) => !value)}>
            حالت شب
          </button>
          <button type="button" className="secondary-btn" onClick={handleShare}>
            <Share2 className="h-4 w-4" /> {shareState === 'copied' ? 'لینک کپی شد' : shareState === 'failed' ? 'کپی خودکار نشد' : 'اشتراک‌گذاری'}
          </button>
          <a className="secondary-btn" href="https://stellarium-web.org/" target="_blank" rel="noreferrer">بررسی دقیق‌تر در Stellarium</a>
        </div>
        {fullscreenMessage || shareState !== 'idle' ? (
          <p className="sky-helper-text">
            {fullscreenMessage || (shareState === 'copied'
              ? 'لینک همین آسمان با شهر و زمان انتخاب‌شده آماده اشتراک‌گذاری شد.'
              : 'مرورگر اجازه کپی خودکار لینک را نداد؛ URL همین صفحه شامل شهر و زمان انتخاب‌شده است و می‌توانید آن را از نوار آدرس بردارید.')}
          </p>
        ) : null}

        <div className="mt-5">
          <TonightHighlights highlights={derivedHighlights} state={state === 'loading' ? 'loading' : state === 'error' ? 'error' : 'ready'} />
        </div>

        <div className="sky-metrics-grid mt-5">
          <MetricCard icon={CloudMoon} title="وضعیت آسمان" value={sky.condition || 'محاسبه محلی'} detail={sky.cloudCover !== null && sky.cloudCover !== undefined ? `ابرناکی ${toFaNumber(sky.cloudCover)}٪` : 'داده زنده هوا ناموجود'} accent="blue" />
          <MetricCard icon={Moon} title="ماه اکنون" value={`${skyModel.moon.phaseLabelFa}، ${toFaNumber(skyModel.moon.illumination.toFixed(0))}٪`} detail={`ارتفاع ${toFaNumber(skyModel.moon.altitude.toFixed(0))} درجه، ${directionLabel(skyModel.moon.azimuth)}`} accent="purple" />
          <MetricCard icon={Sun} title="طلوع و غروب" value={formatSunTimes(skyModel.sunTimes.sunrise, skyModel.sunTimes.sunset, effectiveLocation.timezone)} detail={`پایان گرگ‌ومیش نجومی: ${formatTime(skyModel.sunTimes.astronomicalDusk, effectiveLocation.timezone)}`} accent="orange" />
          <MetricCard icon={Clock3} title="بهترین زمان رصد" value={derivedHighlights[0]?.value || sky.bestTime || 'امشب'} detail={derivedHighlights[0]?.summary || 'برآورد براساس غروب خورشید و نور ماه'} accent="yellow" />
        </div>

        <div className="sky-main-grid mt-5">
          <div className="surface-card sky-main-panel p-4 sm:p-5" ref={mapWrapRef}>
            <div className="sky-map-heading">
              <div>
                <span className="eyebrow">نقشه تعاملی آسمان</span>
                <h2 className="mt-2 card-title">Sky Tonight برای {effectiveLocation.name}</h2>
              </div>
              <button type="button" className="secondary-btn" onClick={handleFullscreen}>
                <Maximize2 className="h-4 w-4" /> تمام‌صفحه
              </button>
            </div>
            <InteractiveSkyMap
              className="mt-4"
              model={skyModel}
              selectedObjectId={selectedObjectId}
              onSelectObject={setSelectedObjectId}
              onResetSelection={() => setSelectedObjectId('moon')}
              onFullscreen={handleFullscreen}
              showConstellations={showConstellations}
              nightMode={nightMode}
            />
          </div>

          <div className="surface-card sky-main-panel p-5 sm:p-6">
            <h3 className="card-title">شرایط رصد</h3>
            <div className="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
              <Quality icon={Eye} label="شاخص رصد" value={sky.observingCondition?.label || 'نمایش تقریبی'} />
              <Quality icon={Eye} label="ابرناکی" value={formatPercent(sky.cloudCover)} />
              <Quality icon={Droplets} label="رطوبت" value={formatPercent(sky.humidity)} />
              <Quality icon={Wind} label="سرعت باد" value={formatUnit(sky.wind, 'km/h')} />
            </div>
            <p className="mt-3 text-xs leading-6 text-slate-600">{sky.observingCondition?.displayWarning || sky.observingCondition?.summary || 'seeing و transparency دقیق تلسکوپی در این نسخه اندازه‌گیری نمی‌شوند.'}</p>

            <h3 className="mt-7 card-title">راهنمای اجرام</h3>
            <div className="sky-target-list">
              {skyModel.searchIndex.slice(0, 8).map((item) => (
                <button key={item.id} type="button" className={item.id === selectedObjectId ? 'is-active' : ''} onClick={() => setSelectedObjectId(item.id)}>
                  <span>{item.name}</span>
                  <small>{item.visible ? `${toFaNumber(item.altitude.toFixed(0))} درجه، ${directionLabel(item.azimuth)}` : 'زیر افق'}</small>
                </button>
              ))}
            </div>

            <h3 className="mt-7 card-title">یادداشت علمی</h3>
            <div className="mt-4 space-y-3">
              {skyModel.sourceNotes.map((note) => (
                <p key={note} className="rounded-2xl border border-white/[.06] bg-white/[.02] p-4 text-xs leading-6 text-slate-500">{note}</p>
              ))}
            </div>
          </div>
        </div>

        <div className="mt-5">
          <PlanetVisibility planets={enrichedPlanets} state={planetState === 'loading' ? 'loading' : planetState === 'error' ? 'error' : 'ready'} />
        </div>

        <div className="mt-5">
          <AstronomyEvents events={enrichedEvents} state={eventState} />
        </div>

        <div className="observation-tips mt-5">
          <div>
            <span className="eyebrow">راهنمای سریع</span>
            <h2 className="mt-2 card-title">پیشنهادهای رصد امشب</h2>
          </div>
          <div className="tips-grid">
            <Tip title="نور ماه" text={`${skyModel.moon.phaseLabelFa} با روشنایی ${toFaNumber(skyModel.moon.illumination.toFixed(0))}٪؛ برای اجرام کم‌نور، پنجره‌های تاریک‌تر را انتخاب کنید.`} />
            <Tip title="سیاره‌ها" text="برای سیاره‌های نزدیک افق، محل رصدی با دید باز و بدون ساختمان یا کوه انتخاب کنید." />
            <Tip title="حریم خصوصی مکان" text="موقعیت مرورگر فقط در همین دستگاه ذخیره می‌شود و لینک اشتراک‌گذاری مختصات دقیق را منتشر نمی‌کند." />
          </div>
        </div>
      </section>
    </>
  )
}

function MetricCard({ icon: Icon, title, value, detail, accent }) {
  return <div className={`surface-card sky-metric-card accent-${accent} p-5`}><div className="flex items-start justify-between gap-4"><div className="min-w-0"><span className="text-xs text-slate-500">{title}</span><strong className="mt-3 block text-lg leading-8 text-white sm:text-xl">{value}</strong><p className="mt-2 text-xs leading-6 text-slate-500">{detail}</p></div><span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/[.04]"><Icon className="h-5 w-5" /></span></div></div>
}
function Quality({ icon: Icon, label, value }) { return <div className="metric-tile"><Icon className="h-5 w-5 text-sky-300" /><span className="mt-3 block text-xs text-slate-500">{label}</span><strong className="mt-1 block text-sm text-white">{value}</strong></div> }
function Tip({ title, text }) { return <div className="tip-card"><strong>{title}</strong><p>{text}</p></div> }
function formatPercent(value) { return value === null || value === undefined || value === '' ? 'ناموجود' : `${toFaNumber(value)}٪` }
function formatUnit(value, unit) { return value === null || value === undefined || value === '' ? 'ناموجود' : `${toFaNumber(value)} ${unit}` }
function formatSunTimes(sunrise, sunset, timezone) { return sunrise && sunset ? `${formatTime(sunrise, timezone)} / ${formatTime(sunset, timezone)}` : 'در دسترس نیست' }
function normalizePlanets(data) {
  const payload = data && typeof data === 'object' ? data : {}
  const items = Array.isArray(payload.items) ? payload.items : []
  return {
    ...fallbackSky.planets,
    ...payload,
    status: payload.status || (items.length ? 'ready' : 'unavailable'),
    location: payload.location || fallbackSky.planets.location,
    items,
  }
}
function normalizeEvents(data) {
  const payload = data && typeof data === 'object' ? data : {}
  const items = Array.isArray(payload.items) ? payload.items : []
  return {
    ...fallbackSky.upcomingEvents,
    ...payload,
    status: payload.status || (items.length ? 'ready' : 'unavailable'),
    accuracy: payload.accuracy || (items.length ? 'curated' : 'unavailable'),
    location: payload.location || fallbackSky.upcomingEvents.location,
    items,
  }
}
function readInitialCity() {
  try {
    const urlCity = new URL(window.location.href).searchParams.get('city')
    if (SKY_CITIES.some((city) => city.id === urlCity)) return urlCity
    const saved = window.localStorage.getItem('jazireh.sky.city')
    if (saved === 'custom' && readSavedCustomLocation()) return 'custom'
    if (SKY_CITIES.some((city) => city.id === saved)) return saved
    } catch {
      // URL/localStorage access is enhancement-only.
    }
  return 'tehran'
}
function readInitialOffset() {
  try {
    const value = Number(new URL(window.location.href).searchParams.get('time'))
    if (Number.isFinite(value)) return clamp(value, TIME_MIN, TIME_MAX)
  } catch {
    // URL parsing is enhancement-only.
  }
  return clamp(offsetFromDate(new Date()), TIME_MIN, TIME_MAX)
}
function readSavedCustomLocation() {
  try {
    const saved = JSON.parse(window.localStorage.getItem('jazireh.sky.customLocation') || 'null')
    if (saved && Number.isFinite(Number(saved.latitude)) && Number.isFinite(Number(saved.longitude))) {
      return locationFromCoords(saved.latitude, saved.longitude)
    }
  } catch {
    // Saved browser location is optional and can be ignored if malformed.
  }
  return null
}
function clamp(value, min, max) { return Math.min(max, Math.max(min, Number.isFinite(Number(value)) ? Number(value) : min)) }
function legacyCopyText(value) {
  try {
    const textarea = document.createElement('textarea')
    textarea.value = value
    textarea.setAttribute('readonly', '')
    textarea.style.position = 'fixed'
    textarea.style.opacity = '0'
    document.body.appendChild(textarea)
    textarea.select()
    const copied = document.execCommand('copy')
    document.body.removeChild(textarea)
    return copied
  } catch {
    return false
  }
}
