import { resolveAssetPath } from '../lib/utils'

export const navItems = [
  { label: 'صفحه اصلی', path: '/' },
  { label: 'آسمان امروز', path: '/sky' },
  { label: 'کاوش فضا', path: '/explore' },
  { label: 'پرونده‌ها', path: '/topics' },
  { label: 'رویدادها', path: '/events' },
  { label: 'اخبار علمی', path: '/news' },
  { label: 'جستجو', path: '/search' },
  { label: 'عکس روز ناسا', path: '/apod' },
  { label: 'جزیره دیلی', path: '/jazireh-daily' },
  { label: 'ویدیوها', path: '/videos' },
  { label: 'رادار', path: '/radar' }
]

export const skyData = {
  status: 'stale',
  location: 'تهران، ایران',
  locationLabel: 'محاسبه برای تهران، ایران',
  source: 'fallback',
  accuracy: 'estimated',
  confidence: 'low',
  calculatedAt: '',
  generatedAtUtc: '',
  timezone: 'Asia/Tehran',
  locationMeta: { city: 'تهران، ایران', lat: 35.6892, lng: 51.389 },
  isFallback: true,
  fallbackReason: 'داده تازه آسمان دریافت نشد.',
  displayWarning: 'داده پشتیبان: مقادیر دقیق زنده نمایش داده نمی‌شوند.',
  temperature: null,
  condition: 'داده زنده هوا در دسترس نیست',
  humidity: null,
  wind: null,
  pressure: null,
  visibility: null,
  cloudCover: null,
  weather: {
    status: 'stale',
    source: 'none',
    accuracy: 'unavailable',
    confidence: 'low',
    temperature: null,
    condition: 'داده زنده هوا در دسترس نیست',
    humidity: null,
    wind: null,
    pressure: null,
    visibility: null,
    cloudCover: null,
    isFallback: true,
    fallbackReason: 'داده هواشناسی دریافت نشد.',
    displayWarning: 'داده پشتیبان: وضعیت واقعی هوا فعلا دریافت نشده است.'
  },
  moonPhase: '',
  moonIllumination: null,
  moonAge: null,
  sunset: null,
  sunrise: null,
  sunTimes: {
    status: 'stale',
    source: 'date_sun_info',
    accuracy: 'unavailable',
    confidence: 'low',
    isFallback: true,
    fallbackReason: 'sunrise/sunset calculation unavailable',
    displayWarning: true,
    message: 'زمان طلوع و غروب در حال حاضر در دسترس نیست.'
  },
  bestTime: null,
  seeing: null,
  transparency: null,
  observingCondition: {
    status: 'estimated',
    label: 'نمایش تقریبی',
    summary: 'پس از دریافت داده تازه، شاخص شرایط رصد با شفافیت بیشتری نمایش داده می‌شود.',
    source: 'fallback',
    accuracy: 'estimated',
    confidence: 'low',
    isFallback: true,
    fallbackReason: 'داده تازه شرایط رصد دریافت نشد.',
    displayWarning: 'نمایش تقریبی'
  },
  events: [],
  planets: {
    status: 'stale',
    accuracy: 'estimated',
    confidence: 'low',
    source: 'fallback',
    location: { label: 'تهران، ایران', latitude: 35.6892, longitude: 51.389 },
    timezone: 'Asia/Tehran',
    calculatedAt: '',
    generatedAtUtc: '',
    isFallback: true,
    fallbackReason: 'API دیدپذیری سیاره‌ها در دسترس نبود.',
    displayWarning: 'داده پشتیبان',
    message: 'پس از دریافت داده معتبر، وضعیت دیدپذیری سیاره‌ها نمایش داده می‌شود.',
    items: []
  },
  upcomingEvents: {
    status: 'stale',
    accuracy: 'mixed',
    confidence: 'low',
    source: 'fallback',
    timezone: 'Asia/Tehran',
    calculatedAt: '',
    generatedAtUtc: '',
    location: { city: 'تهران، ایران', lat: 35.6892, lng: 51.389 },
    isFallback: true,
    fallbackReason: 'API رویدادهای نجومی در دسترس نبود.',
    displayWarning: 'داده پشتیبان',
    message: 'پس از دریافت رویداد معتبر، فهرست رویدادهای نجومی نمایش داده می‌شود.',
    items: []
  },
  tonightHighlights: []
}

export const sunData = {
  status: 'stale',
  title: 'خورشید اکنون',
  image: 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_1024_0304.jpg',
  sourceName: 'NASA SDO',
  sourceUrl: 'https://sdo.gsfc.nasa.gov/data/',
  wavelength: 'AIA 304Å',
  observedAt: '',
  fetchedAt: '',
  calculatedAt: '',
  generatedAtUtc: '',
  timezone: 'Asia/Tehran',
  location: { city: 'تهران، ایران', lat: 35.6892, lng: 51.389 },
  source: 'frontend-fallback',
  accuracy: 'observed-image-stale',
  confidence: 'low',
  isFallback: true,
  fallbackReason: 'API خورشید در دسترس نبود.',
  displayWarning: 'داده پشتیبان: زمان دقیق ثبت تصویر تایید نشده است.',
  fallbackLevel: 'frontend',
  message: 'تا دریافت پاسخ زنده، تصویر آماده SDO نمایش داده می‌شود.'
}

export const newsItems = []

export const videos = [
  { id: 1, title: 'آسمان پرستاره جزیره', duration: '۰۰:۱۱', source: '/media/home-hero-stars.mp4', poster: '/media/home-hero-stars.jpg', description: 'نمایی آرام از آسمان شب برای همراهی با روایت‌های علمی جزیره.' },
  { id: 2, title: 'سفر در میان ستاره‌ها', duration: '۰۰:۱۵', source: '/media/starfield-background.mp4', poster: '/media/starfield-poster.jpg', description: 'حرکت آرام در میدان ستاره‌ای و فضای عمیق.' },
  { id: 3, title: 'چشم‌انداز کیهانی', duration: '۰۰:۱۲', source: '/media/space-cinematic.mp4', poster: '/media/space-cinematic-poster.jpg', description: 'روایتی تصویری از پهنه تاریک و نورانی کیهان.' }
]

export const apodItems = []

export const celestialObjects = [
  { id: 1, slug: 'mercury', name: 'عطارد', type: 'سیاره سنگی', color: '#8f8a80', size: 0.42, distance: 4.8, speed: 0.015, facts: ['نزدیک‌ترین سیاره به خورشید است.', 'یک سال عطارد فقط ۸۸ روز زمینی طول می‌کشد.', 'با وجود نزدیکی به خورشید، یخ آب در دهانه‌های همیشه‌سایه قطبی دیده شده است.'], stats: { diameter: '۴٬۸۸۰ کیلومتر', day: '۵۸٫۶ روز زمینی', year: '۸۸ روز زمینی', moons: '۰' } },
  { id: 2, slug: 'venus', name: 'زهره', type: 'سیاره سنگی', color: '#d7a86e', size: 0.62, distance: 6.5, speed: 0.012, facts: ['چرخش زهره برخلاف بیشتر سیاره‌ها معکوس است.', 'جو غلیظ دی‌اکسیدکربن اثر گلخانه‌ای شدیدی ایجاد می‌کند.', 'فشار سطحی آن حدود ۹۰ برابر فشار سطح زمین است.'], stats: { diameter: '۱۲٬۱۰۴ کیلومتر', day: '۲۴۳ روز زمینی', year: '۲۲۵ روز زمینی', moons: '۰' } },
  { id: 3, slug: 'earth', name: 'زمین', type: 'سیاره اقیانوسی', color: '#2c7fca', size: 0.67, distance: 8.4, speed: 0.01, facts: ['تنها جرم شناخته‌شده با حیات قطعی است.', 'حدود ۷۱ درصد سطح آن با آب پوشیده شده است.', 'میدان مغناطیسی زمین بخش زیادی از باد خورشیدی را منحرف می‌کند.'], stats: { diameter: '۱۲٬۷۴۲ کیلومتر', day: '۲۳ ساعت و ۵۶ دقیقه', year: '۳۶۵٫۲۵ روز', moons: '۱' } },
  { id: 4, slug: 'mars', name: 'مریخ', type: 'سیاره سنگی', color: '#b94f35', size: 0.5, distance: 10.5, speed: 0.008, facts: ['رنگ سرخ مریخ از اکسید آهن در خاک آن می‌آید.', 'المپوس مانس بزرگ‌ترین آتشفشان شناخته‌شده منظومه شمسی است.', 'شواهد فراوانی از جریان آب در گذشته مریخ وجود دارد.'], stats: { diameter: '۶٬۷۷۹ کیلومتر', day: '۲۴ ساعت و ۳۷ دقیقه', year: '۶۸۷ روز زمینی', moons: '۲' } },
  { id: 5, slug: 'jupiter', name: 'مشتری', type: 'غول گازی', color: '#c89462', size: 1.55, distance: 14.5, speed: 0.0045, facts: ['بزرگ‌ترین سیاره منظومه شمسی است.', 'لکه سرخ بزرگ یک طوفان عظیم و دیرپا است.', 'میدان مغناطیسی مشتری بسیار قدرتمند و گسترده است.'], stats: { diameter: '۱۳۹٬۸۲۰ کیلومتر', day: '۹ ساعت و ۵۶ دقیقه', year: '۱۱٫۸۶ سال زمینی', moons: 'بیش از ۹۰' } },
  { id: 6, slug: 'saturn', name: 'زحل', type: 'غول گازی حلقه‌دار', color: '#d6bb82', size: 1.35, distance: 19, speed: 0.0032, ring: true, facts: ['حلقه‌ها عمدتاً از قطعات یخ و سنگ تشکیل شده‌اند.', 'چگالی متوسط زحل از آب کمتر است.', 'قمر تیتان جوی غلیظ و دریاچه‌های هیدروکربنی دارد.'], stats: { diameter: '۱۱۶٬۴۶۰ کیلومتر', day: '۱۰ ساعت و ۴۲ دقیقه', year: '۲۹٫۴ سال زمینی', moons: 'بیش از ۱۴۰' } },
  { id: 7, slug: 'uranus', name: 'اورانوس', type: 'غول یخی', color: '#73c9d6', size: 0.95, distance: 23.5, speed: 0.0024, facts: ['محور چرخش آن تقریباً روی پهلو قرار دارد.', 'متان موجود در جو باعث رنگ آبی-سبز آن می‌شود.', 'فصل‌های اورانوس چندین دهه طول می‌کشند.'], stats: { diameter: '۵۰٬۷۲۴ کیلومتر', day: '۱۷ ساعت و ۱۴ دقیقه', year: '۸۴ سال زمینی', moons: '۲۷' } },
  { id: 8, slug: 'neptune', name: 'نپتون', type: 'غول یخی', color: '#315ec9', size: 0.92, distance: 27.5, speed: 0.0019, facts: ['سریع‌ترین بادهای سیاره‌ای منظومه شمسی در نپتون ثبت شده‌اند.', 'رنگ آبی آن به ترکیب جو و پراکندگی نور مربوط است.', 'قمر تریتون در جهتی مخالف چرخش نپتون حرکت می‌کند.'], stats: { diameter: '۴۹٬۲۴۴ کیلومتر', day: '۱۶ ساعت', year: '۱۶۴٫۸ سال زمینی', moons: '۱۴' } }
]

videos.forEach((item) => { item.source = resolveAssetPath(item.source); item.poster = resolveAssetPath(item.poster) })
