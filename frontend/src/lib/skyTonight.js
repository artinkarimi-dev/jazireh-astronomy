const DEG = Math.PI / 180
const RAD = 180 / Math.PI
const J2000 = 2451545.0

export const SKY_CITIES = [
  { id: 'tehran', name: 'تهران', country: 'ایران', latitude: 35.6892, longitude: 51.3890, timezone: 'Asia/Tehran' },
  { id: 'karaj', name: 'کرج', country: 'ایران', latitude: 35.8327, longitude: 50.9915, timezone: 'Asia/Tehran' },
  { id: 'mashhad', name: 'مشهد', country: 'ایران', latitude: 36.2605, longitude: 59.6168, timezone: 'Asia/Tehran' },
  { id: 'isfahan', name: 'اصفهان', country: 'ایران', latitude: 32.6539, longitude: 51.6660, timezone: 'Asia/Tehran' },
  { id: 'shiraz', name: 'شیراز', country: 'ایران', latitude: 29.5918, longitude: 52.5837, timezone: 'Asia/Tehran' },
  { id: 'tabriz', name: 'تبریز', country: 'ایران', latitude: 38.0962, longitude: 46.2738, timezone: 'Asia/Tehran' },
  { id: 'ahvaz', name: 'اهواز', country: 'ایران', latitude: 31.3183, longitude: 48.6706, timezone: 'Asia/Tehran' },
  { id: 'qom', name: 'قم', country: 'ایران', latitude: 34.6416, longitude: 50.8746, timezone: 'Asia/Tehran' },
  { id: 'rasht', name: 'رشت', country: 'ایران', latitude: 37.2808, longitude: 49.5832, timezone: 'Asia/Tehran' },
  { id: 'kerman', name: 'کرمان', country: 'ایران', latitude: 30.2839, longitude: 57.0834, timezone: 'Asia/Tehran' },
]

const PLANET_NAMES = {
  mercury: 'عطارد',
  venus: 'زهره',
  mars: 'مریخ',
  jupiter: 'مشتری',
  saturn: 'زحل',
}

const PLANET_COLORS = {
  mercury: '#b7aca1',
  venus: '#f2c572',
  mars: '#ef6b4a',
  jupiter: '#f0b879',
  saturn: '#dcc17b',
}

const STAR_CATALOG = [
  { id: 'sirius', name: 'شباهنگ', latin: 'Sirius', ra: 6.7525, dec: -16.7161, mag: -1.46 },
  { id: 'canopus', name: 'سهیل', latin: 'Canopus', ra: 6.3992, dec: -52.6957, mag: -0.74 },
  { id: 'arcturus', name: 'سماک رامح', latin: 'Arcturus', ra: 14.2610, dec: 19.1825, mag: -0.05 },
  { id: 'vega', name: 'نسر واقع', latin: 'Vega', ra: 18.6156, dec: 38.7837, mag: 0.03 },
  { id: 'capella', name: 'عیوق', latin: 'Capella', ra: 5.2782, dec: 45.9980, mag: 0.08 },
  { id: 'rigel', name: 'رجل‌الجبار', latin: 'Rigel', ra: 5.2423, dec: -8.2016, mag: 0.13 },
  { id: 'procyon', name: 'شعرای شامی', latin: 'Procyon', ra: 7.6550, dec: 5.2250, mag: 0.34 },
  { id: 'betelgeuse', name: 'ابط‌الجوزا', latin: 'Betelgeuse', ra: 5.9195, dec: 7.4071, mag: 0.42 },
  { id: 'altair', name: 'نسر طائر', latin: 'Altair', ra: 19.8464, dec: 8.8683, mag: 0.76 },
  { id: 'aldebaran', name: 'دبران', latin: 'Aldebaran', ra: 4.5987, dec: 16.5093, mag: 0.85 },
  { id: 'antares', name: 'قلب‌العقرب', latin: 'Antares', ra: 16.4901, dec: -26.4320, mag: 1.06 },
  { id: 'spica', name: 'سماک اعزل', latin: 'Spica', ra: 13.4199, dec: -11.1614, mag: 0.97 },
  { id: 'regulus', name: 'قلب‌الاسد', latin: 'Regulus', ra: 10.1395, dec: 11.9672, mag: 1.35 },
  { id: 'polaris', name: 'ستاره قطبی', latin: 'Polaris', ra: 2.5303, dec: 89.2641, mag: 1.98 },
  { id: 'dubhe', name: 'دبه', latin: 'Dubhe', ra: 11.0621, dec: 61.7510, mag: 1.79 },
  { id: 'merak', name: 'مراق', latin: 'Merak', ra: 11.0307, dec: 56.3824, mag: 2.37 },
  { id: 'phecda', name: 'فخذ', latin: 'Phecda', ra: 11.8972, dec: 53.6948, mag: 2.44 },
  { id: 'megrez', name: 'مغرز', latin: 'Megrez', ra: 12.2571, dec: 57.0326, mag: 3.31 },
  { id: 'alioth', name: 'جون', latin: 'Alioth', ra: 12.9005, dec: 55.9598, mag: 1.77 },
  { id: 'mizar', name: 'مئزر', latin: 'Mizar', ra: 13.3987, dec: 54.9254, mag: 2.23 },
  { id: 'alkaid', name: 'قائد بنات نعش', latin: 'Alkaid', ra: 13.7923, dec: 49.3133, mag: 1.86 },
  { id: 'schedar', name: 'صدر ذات‌الکرسی', latin: 'Schedar', ra: 0.6751, dec: 56.5373, mag: 2.24 },
  { id: 'caph', name: 'کف‌الخضیب', latin: 'Caph', ra: 0.1529, dec: 59.1498, mag: 2.28 },
  { id: 'gamma-cas', name: 'گاما ذات‌الکرسی', latin: 'Gamma Cas', ra: 0.9451, dec: 60.7167, mag: 2.47 },
  { id: 'ruchbah', name: 'رکبه', latin: 'Ruchbah', ra: 1.4303, dec: 60.2353, mag: 2.68 },
  { id: 'segin', name: 'سگین', latin: 'Segin', ra: 1.9066, dec: 63.6701, mag: 3.38 },
  { id: 'mintaka', name: 'منطقه', latin: 'Mintaka', ra: 5.5334, dec: -0.2991, mag: 2.23 },
  { id: 'alnilam', name: 'نظام', latin: 'Alnilam', ra: 5.6036, dec: -1.2019, mag: 1.69 },
  { id: 'alnitak', name: 'نطاق', latin: 'Alnitak', ra: 5.6793, dec: -1.9426, mag: 1.74 },
  { id: 'bellatrix', name: 'مرزم', latin: 'Bellatrix', ra: 5.4189, dec: 6.3497, mag: 1.64 },
  { id: 'saiph', name: 'سیف', latin: 'Saiph', ra: 5.7959, dec: -9.6696, mag: 2.07 },
]

const CONSTELLATIONS = [
  { id: 'ursa-major', name: 'دب اکبر', stars: ['dubhe', 'merak', 'phecda', 'megrez', 'alioth', 'mizar', 'alkaid'] },
  { id: 'cassiopeia', name: 'ذات‌الکرسی', stars: ['caph', 'schedar', 'gamma-cas', 'ruchbah', 'segin'] },
  { id: 'orion', name: 'جبار', stars: ['betelgeuse', 'bellatrix', 'mintaka', 'alnilam', 'alnitak', 'rigel', 'saiph'] },
]

export function getCityById(id) {
  return SKY_CITIES.find((city) => city.id === id) || SKY_CITIES[0]
}

export function locationFromCoords(latitude, longitude) {
  return {
    id: 'custom',
    name: 'موقعیت من',
    country: 'موقعیت مرورگر',
    latitude: clamp(Number(latitude), -89.9, 89.9),
    longitude: normalizeDegrees(Number(longitude) + 180) - 180,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Tehran',
    precise: true,
  }
}

export function dateFromTonightOffset(offsetMinutes, now = new Date()) {
  const base = observingNightAnchor(now)
  return new Date(base.getTime() + Number(offsetMinutes) * 60000)
}

export function offsetFromDate(date) {
  const base = observingNightAnchor(date)
  return Math.round((date.getTime() - base.getTime()) / 60000)
}

export function buildSkyTonight({ location, date, backendSky = {}, backendPlanets = {}, events = {} }) {
  const observer = normalizeLocation(location)
  const when = date instanceof Date ? date : new Date()
  const sun = sunObject(when, observer)
  const moon = moonObject(when, observer, sun)
  const planets = planetObjects(when, observer)
  const stars = STAR_CATALOG.map((star) => ({ ...star, type: 'star', typeLabel: 'ستاره', color: '#dbeafe', ...equatorialToHorizontal(star.ra, star.dec, when, observer) }))
  const plottedStars = stars.filter((star) => star.altitude > -4).sort((a, b) => a.mag - b.mag).slice(0, 22)
  const objects = [sun, moon, ...planets, ...plottedStars]
  const visiblePlanets = planets.filter((planet) => planet.altitude > 5 && sun.altitude < -4)
  const sunTimes = solarTimes(when, observer)
  const eventItems = Array.isArray(events?.items) ? events.items : []

  return {
    location: observer,
    date: when,
    calculatedAt: when.toISOString(),
    sun,
    moon,
    planets,
    stars: plottedStars,
    objects,
    constellations: buildConstellations(stars),
    highlights: buildHighlights({ moon, visiblePlanets, sunTimes, backendSky, eventItems, location: observer }),
    planetCards: buildPlanetCards(planets, backendPlanets, sun),
    sunTimes,
    searchIndex: buildSearchIndex({ sun, moon, planets, stars: plottedStars }),
    sourceNotes: [
      'خورشید، ماه، سیاره‌ها و ستاره‌ها با محاسبات محلی تقریبی RA/Dec به Alt/Az برای مکان و زمان انتخاب‌شده تبدیل می‌شوند.',
      'دیدپذیری سیاره‌ها در کارت‌ها با داده محلی و در صورت وجود با برچسب/رنگ سرویس کش‌شده وردپرس تکمیل می‌شود.',
      'داده‌های دقیق‌تر JPL در endpoint جداگانه سیارات کش می‌شود و مسیر اصلی /sky را کند نمی‌کند.',
    ],
  }
}

function buildHighlights({ moon, visiblePlanets, sunTimes, backendSky, eventItems, location }) {
  const bestPlanet = visiblePlanets.sort((a, b) => b.altitude - a.altitude)[0]
  const nextEvent = eventItems[0]
  const darkStart = sunTimes.astronomicalDusk || sunTimes.nauticalDusk || sunTimes.sunset
  return [
    {
      id: 'best-window',
      title: 'بهترین پنجره رصد',
      value: darkStart ? `پس از ${formatTime(darkStart, location.timezone)}` : backendSky.bestTime || 'امشب پس از تاریکی',
      summary: 'براساس غروب و پایان گرگ‌ومیش محاسبه شده برای شهر انتخاب‌شده.',
    },
    {
      id: 'moon-interference',
      title: 'اثر نور ماه',
      value: `${moon.phaseLabelFa}، ${toFaNumber(moon.illumination.toFixed(0))}٪`,
      summary: moon.illumination > 65 ? 'نور ماه برای اجرام کم‌نور مزاحم است؛ سیاره‌ها و ماه اهداف بهتری هستند.' : 'نور ماه برای رصد عمومی آسمان عمیق مزاحمت کمتری دارد.',
    },
    {
      id: 'best-planet',
      title: 'سیاره شاخص',
      value: bestPlanet ? bestPlanet.name : 'سیاره شاخصی بالای افق نیست',
      summary: bestPlanet ? `${bestPlanet.name} حدود ${toFaNumber(bestPlanet.altitude.toFixed(0))} درجه بالای افق ${directionLabel(bestPlanet.azimuth)} است.` : 'در زمان انتخاب‌شده سیاره روشنی با ارتفاع مناسب دیده نشد.',
    },
    {
      id: 'next-event',
      title: 'رویداد نزدیک',
      value: nextEvent?.title || 'رویداد ویژه‌ای ثبت نشده',
      summary: nextEvent?.description || nextEvent?.excerpt || 'اگر رویداد مهمی در تقویم وردپرس ثبت شود، اینجا نمایش داده می‌شود.',
    },
  ]
}

function buildPlanetCards(localPlanets, backendPlanets, sun) {
  const backendItems = Array.isArray(backendPlanets?.items) ? backendPlanets.items : []
  return localPlanets.map((planet) => {
    const backend = backendItems.find((item) => item.id === planet.id) || {}
    const visible = planet.altitude > 5 && sun.altitude < -4
    const limited = planet.altitude > 0
    return {
      ...backend,
      id: planet.id,
      name: planet.name,
      status: visible ? (planet.altitude > 25 ? 'excellent' : 'visible') : limited ? 'limited' : 'poor',
      statusLabel: visible ? (planet.altitude > 25 ? 'دید خوب' : 'قابل رصد') : limited ? 'نزدیک افق' : 'زیر افق',
      visibilityScore: visible ? Math.min(100, Math.round(planet.altitude * 2 + 25)) : limited ? Math.max(20, Math.round(planet.altitude * 3)) : 0,
      bestTime: visible ? 'زمان انتخاب‌شده' : 'زمان دیگری از شب را امتحان کنید',
      direction: directionLabel(planet.azimuth),
      altitudeLabel: altitudeLabel(planet.altitude),
      brightnessLabel: planet.magnitude ? `قدر تقریبی ${toFaNumber(planet.magnitude.toFixed(1))}` : backend.brightnessLabel || 'برآورد محلی',
      color: PLANET_COLORS[planet.id],
      summary: `${planet.name}: موقعیت تقریبی ${toFaNumber(planet.altitude.toFixed(1))} درجه ارتفاع و ${directionLabel(planet.azimuth)}؛ محاسبه محلی برای زمان انتخاب‌شده.`,
      altitude: planet.altitude,
      azimuth: planet.azimuth,
      calculatedAt: planet.calculatedAt,
      source: 'local-low-precision-astronomy',
      accuracy: 'approximate-sky-tonight',
      confidence: 'medium',
      displayWarning: 'موقعیت تقریبی برای راهنمای رصد عمومی',
    }
  }).sort((a, b) => b.visibilityScore - a.visibilityScore)
}

function buildSearchIndex({ sun, moon, planets, stars }) {
  return [sun, moon, ...planets, ...stars].map((item) => ({
    id: item.id,
    name: item.name,
    latin: item.latin || '',
    type: item.type,
    typeLabel: item.typeLabel,
    altitude: item.altitude,
    azimuth: item.azimuth,
    visible: item.altitude > 0,
  }))
}

function buildConstellations(stars) {
  const map = new Map(stars.map((star) => [star.id, star]))
  return CONSTELLATIONS.map((item) => {
    const points = item.stars.map((id) => map.get(id)).filter(Boolean)
    return {
      ...item,
      type: 'constellation',
      typeLabel: 'صورت فلکی',
      visible: points.some((star) => star.altitude > 0),
      points,
    }
  })
}

function sunObject(date, location) {
  const d = daysSinceJ2000(date)
  const w = 282.9404 + 4.70935E-5 * d
  const e = 0.016709 - 1.151E-9 * d
  const m = normalizeDegrees(356.0470 + 0.9856002585 * d)
  const oblecl = 23.4393 - 3.563E-7 * d
  const eAnomaly = m + RAD * e * Math.sin(m * DEG) * (1 + e * Math.cos(m * DEG))
  const xv = Math.cos(eAnomaly * DEG) - e
  const yv = Math.sqrt(1 - e * e) * Math.sin(eAnomaly * DEG)
  const v = atan2d(yv, xv)
  const r = Math.sqrt(xv * xv + yv * yv)
  const lon = normalizeDegrees(v + w)
  const { ra, dec } = eclipticToEquatorial(lon, 0, r, oblecl)
  return {
    id: 'sun',
    name: 'خورشید',
    latin: 'Sun',
    type: 'sun',
    typeLabel: 'خورشید',
    color: '#fbbf24',
    magnitude: -26.7,
    calculatedAt: date.toISOString(),
    eclipticLongitude: lon,
    ...equatorialToHorizontal(ra, dec, date, location),
  }
}

function moonObject(date, location, sun) {
  const d = daysSinceJ2000(date)
  const n = normalizeDegrees(125.1228 - 0.0529538083 * d)
  const i = 5.1454
  const w = normalizeDegrees(318.0634 + 0.1643573223 * d)
  const a = 60.2666
  const e = 0.054900
  const m = normalizeDegrees(115.3654 + 13.0649929509 * d)
  const eAnomaly = m + RAD * e * Math.sin(m * DEG) * (1 + e * Math.cos(m * DEG))
  const xv = a * (Math.cos(eAnomaly * DEG) - e)
  const yv = a * (Math.sqrt(1 - e * e) * Math.sin(eAnomaly * DEG))
  const v = atan2d(yv, xv)
  const r = Math.sqrt(xv * xv + yv * yv)
  const xh = r * (cosd(n) * cosd(v + w) - sind(n) * sind(v + w) * cosd(i))
  const yh = r * (sind(n) * cosd(v + w) + cosd(n) * sind(v + w) * cosd(i))
  const zh = r * (sind(v + w) * sind(i))
  const lon = atan2d(yh, xh)
  const lat = atan2d(zh, Math.sqrt(xh * xh + yh * yh))
  const { ra, dec } = eclipticToEquatorial(lon, lat, r, 23.4393)
  const elongation = angularSeparation(ra * 15, dec, sun.rightAscension * 15, sun.declination)
  const illumination = (1 - Math.cos(elongation * DEG)) * 50
  const age = normalizeDegrees(eclipticLongitude(lon) - sun.eclipticLongitude) / 360 * 29.530588
  return {
    id: 'moon',
    name: 'ماه',
    latin: 'Moon',
    type: 'moon',
    typeLabel: 'ماه',
    color: '#e5e7eb',
    illumination,
    moonAge: age,
    phaseLabelFa: moonPhaseLabel(age),
    calculatedAt: date.toISOString(),
    ...equatorialToHorizontal(ra, dec, date, location),
  }
}

function planetObjects(date, location) {
  const d = daysSinceJ2000(date)
  const earth = heliocentric('earth', d)
  return ['mercury', 'venus', 'mars', 'jupiter', 'saturn'].map((id) => {
    const p = heliocentric(id, d)
    const xg = p.x - earth.x
    const yg = p.y - earth.y
    const zg = p.z - earth.z
    const lon = atan2d(yg, xg)
    const lat = atan2d(zg, Math.sqrt(xg * xg + yg * yg))
    const distance = Math.sqrt(xg * xg + yg * yg + zg * zg)
    const { ra, dec } = eclipticToEquatorial(lon, lat, distance, 23.4393)
    return {
      id,
      name: PLANET_NAMES[id],
      latin: id,
      type: 'planet',
      typeLabel: 'سیاره',
      color: PLANET_COLORS[id],
      distance,
      magnitude: approximateMagnitude(id, distance),
      calculatedAt: date.toISOString(),
      ...equatorialToHorizontal(ra, dec, date, location),
    }
  })
}

function heliocentric(id, d) {
  const elements = orbitalElements(id, d)
  const eAnomaly = solveKepler(elements.m, elements.e)
  const xv = elements.a * (Math.cos(eAnomaly * DEG) - elements.e)
  const yv = elements.a * (Math.sqrt(1 - elements.e * elements.e) * Math.sin(eAnomaly * DEG))
  const v = atan2d(yv, xv)
  const r = Math.sqrt(xv * xv + yv * yv)
  const x = r * (cosd(elements.n) * cosd(v + elements.w) - sind(elements.n) * sind(v + elements.w) * cosd(elements.i))
  const y = r * (sind(elements.n) * cosd(v + elements.w) + cosd(elements.n) * sind(v + elements.w) * cosd(elements.i))
  const z = r * (sind(v + elements.w) * sind(elements.i))
  return { x, y, z }
}

function orbitalElements(id, d) {
  const table = {
    mercury: [48.3313, 3.24587E-5, 7.0047, 5.00E-8, 29.1241, 1.01444E-5, 0.387098, 0, 0.205635, 5.59E-10, 168.6562, 4.0923344368],
    venus: [76.6799, 2.46590E-5, 3.3946, 2.75E-8, 54.8910, 1.38374E-5, 0.723330, 0, 0.006773, -1.302E-9, 48.0052, 1.6021302244],
    earth: [0, 0, 0, 0, 282.9404, 4.70935E-5, 1.000000, 0, 0.016709, -1.151E-9, 356.0470, 0.9856002585],
    mars: [49.5574, 2.11081E-5, 1.8497, -1.78E-8, 286.5016, 2.92961E-5, 1.523688, 0, 0.093405, 2.516E-9, 18.6021, 0.5240207766],
    jupiter: [100.4542, 2.76854E-5, 1.3030, -1.557E-7, 273.8777, 1.64505E-5, 5.20256, 0, 0.048498, 4.469E-9, 19.8950, 0.0830853001],
    saturn: [113.6634, 2.38980E-5, 2.4886, -1.081E-7, 339.3939, 2.97661E-5, 9.55475, 0, 0.055546, -9.499E-9, 316.9670, 0.0334442282],
  }[id]
  return { n: normalizeDegrees(table[0] + table[1] * d), i: table[2] + table[3] * d, w: normalizeDegrees(table[4] + table[5] * d), a: table[6] + table[7] * d, e: table[8] + table[9] * d, m: normalizeDegrees(table[10] + table[11] * d) }
}

function solveKepler(m, e) {
  let eAnomaly = m + RAD * e * Math.sin(m * DEG) * (1 + e * Math.cos(m * DEG))
  for (let i = 0; i < 5; i += 1) {
    eAnomaly -= (eAnomaly - RAD * e * Math.sin(eAnomaly * DEG) - m) / (1 - e * Math.cos(eAnomaly * DEG))
  }
  return eAnomaly
}

function eclipticToEquatorial(lon, lat, r, obliquity) {
  const x = r * cosd(lon) * cosd(lat)
  const y = r * sind(lon) * cosd(lat)
  const z = r * sind(lat)
  const yeq = y * cosd(obliquity) - z * sind(obliquity)
  const zeq = y * sind(obliquity) + z * cosd(obliquity)
  const ra = normalizeDegrees(atan2d(yeq, x)) / 15
  const dec = atan2d(zeq, Math.sqrt(x * x + yeq * yeq))
  return { ra, dec }
}

function equatorialToHorizontal(raHours, decDeg, date, location) {
  const lst = localSiderealTime(date, location.longitude)
  const ha = normalizeDegrees(lst - raHours * 15)
  const alt = asind(sind(decDeg) * sind(location.latitude) + cosd(decDeg) * cosd(location.latitude) * cosd(ha))
  const az = normalizeDegrees(atan2d(sind(ha), cosd(ha) * sind(location.latitude) - tand(decDeg) * cosd(location.latitude)) + 180)
  return { altitude: alt, azimuth: az, rightAscension: raHours, declination: decDeg, visible: alt > 0 }
}

function solarTimes(date, location) {
  const day = new Date(date)
  day.setHours(12, 0, 0, 0)
  return {
    sunrise: solarEvent(day, location, -0.833, true),
    sunset: solarEvent(day, location, -0.833, false),
    civilDusk: solarEvent(day, location, -6, false),
    nauticalDusk: solarEvent(day, location, -12, false),
    astronomicalDusk: solarEvent(day, location, -18, false),
  }
}

function solarEvent(date, location, altitude, isRise) {
  const jd = julianDate(date)
  const n = Math.round(jd - J2000 - 0.0009 + location.longitude / 360)
  const jStar = J2000 + 0.0009 - location.longitude / 360 + n
  const m = normalizeDegrees(357.5291 + 0.98560028 * (jStar - J2000))
  const c = 1.9148 * sind(m) + 0.0200 * sind(2 * m) + 0.0003 * sind(3 * m)
  const lambda = normalizeDegrees(m + c + 180 + 102.9372)
  const jTransit = jStar + 0.0053 * sind(m) - 0.0069 * sind(2 * lambda)
  const dec = asind(sind(lambda) * sind(23.44))
  const cosOmega = (sind(altitude) - sind(location.latitude) * sind(dec)) / (cosd(location.latitude) * cosd(dec))
  if (cosOmega < -1 || cosOmega > 1) return null
  const omega = acosd(cosOmega)
  const j = isRise ? jTransit - omega / 360 : jTransit + omega / 360
  return dateFromJulian(j)
}

function julianDate(date) {
  return date.getTime() / 86400000 + 2440587.5
}

function dateFromJulian(jd) {
  return new Date((jd - 2440587.5) * 86400000)
}

function daysSinceJ2000(date) {
  return julianDate(date) - J2000
}

function localSiderealTime(date, longitude) {
  const jd = julianDate(date)
  const d = jd - J2000
  return normalizeDegrees(280.46061837 + 360.98564736629 * d + longitude)
}

function angularSeparation(ra1, dec1, ra2, dec2) {
  return acosd(sind(dec1) * sind(dec2) + cosd(dec1) * cosd(dec2) * cosd(ra1 - ra2))
}

function approximateMagnitude(id, distance) {
  const base = { mercury: -0.4, venus: -4.0, mars: -1.2, jupiter: -2.2, saturn: 0.5 }[id] || 2
  return base + 5 * Math.log10(Math.max(distance, 0.2))
}

function moonPhaseLabel(age) {
  if (age < 1.85 || age >= 27.68) return 'ماه نو'
  if (age < 5.54) return 'هلال افزاینده'
  if (age < 9.23) return 'تربیع اول'
  if (age < 12.92) return 'کوژ افزاینده'
  if (age < 16.61) return 'بدر'
  if (age < 20.30) return 'کوژ کاهنده'
  if (age < 23.99) return 'تربیع آخر'
  return 'هلال کاهنده'
}

export function directionLabel(azimuth) {
  const directions = ['شمال', 'شمال شرق', 'شرق', 'جنوب شرق', 'جنوب', 'جنوب غرب', 'غرب', 'شمال غرب']
  return directions[Math.floor((normalizeDegrees(azimuth) + 22.5) / 45) % 8]
}

export function altitudeLabel(altitude) {
  if (altitude >= 50) return 'بالای آسمان'
  if (altitude >= 25) return 'ارتفاع خوب'
  if (altitude > 5) return 'نزدیک افق'
  if (altitude > 0) return 'لب افق'
  return 'زیر افق'
}

export function formatTime(date, timezone = 'Asia/Tehran') {
  if (!date) return 'ناموجود'
  return new Intl.DateTimeFormat('fa-IR', { hour: '2-digit', minute: '2-digit', timeZone: timezone || 'Asia/Tehran' }).format(date)
}

export function toFaNumber(value) {
  return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)])
}

export function normalizeSkyQuery(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[ي]/g, 'ی')
    .replace(/[ك]/g, 'ک')
    .replace(/[\u064B-\u065F\u0670]/g, '')
    .replace(/\u200c/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
}

function normalizeLocation(location) {
  return {
    ...SKY_CITIES[0],
    ...(location || {}),
    latitude: Number(location?.latitude ?? SKY_CITIES[0].latitude),
    longitude: Number(location?.longitude ?? SKY_CITIES[0].longitude),
  }
}

function observingNightAnchor(date) {
  const base = new Date(date)
  base.setHours(21, 0, 0, 0)
  if (date.getHours() < 12) base.setDate(base.getDate() - 1)
  return base
}

function eclipticLongitude(value) { return normalizeDegrees(value) }
function normalizeDegrees(value) { return ((Number(value) % 360) + 360) % 360 }
function clamp(value, min, max) { return Math.min(max, Math.max(min, Number.isFinite(value) ? value : min)) }
function sind(value) { return Math.sin(value * DEG) }
function cosd(value) { return Math.cos(value * DEG) }
function tand(value) { return Math.tan(value * DEG) }
function asind(value) { return Math.asin(clamp(value, -1, 1)) * RAD }
function acosd(value) { return Math.acos(clamp(value, -1, 1)) * RAD }
function atan2d(y, x) { return Math.atan2(y, x) * RAD }
