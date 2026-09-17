import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

const appSource = await readFile(new URL('../src/App.jsx', import.meta.url), 'utf8')
const apiSource = await readFile(new URL('../src/lib/api.js', import.meta.url), 'utf8')
const apodPageSource = await readFile(new URL('../src/pages/ApodPage.jsx', import.meta.url), 'utf8')
const contactPageSource = await readFile(new URL('../src/pages/ContactPage.jsx', import.meta.url), 'utf8')
const videosPageSource = await readFile(new URL('../src/pages/VideosPage.jsx', import.meta.url), 'utf8')
const sunPreviewSource = await readFile(new URL('../src/components/home/SunPreview.jsx', import.meta.url), 'utf8')
const sunNowCardSource = await readFile(new URL('../src/components/observatory/SunNowCard.jsx', import.meta.url), 'utf8')
const moonNowCardSource = await readFile(new URL('../src/components/observatory/MoonNowCard.jsx', import.meta.url), 'utf8')
const { getApodDisplay } = await import('../src/lib/apodLocalization.js')
const { normalizeApodVideo } = await import('../src/lib/apodVideo.js')

test('public Phase 1 routes are registered in the React router', () => {
  for (const route of ['/', '/sky', '/explore', '/news', '/apod', '/videos', '/events', '/topics', '/radar', '/about', '/contact']) {
    assert.match(appSource, new RegExp(`path="${route.replace('/', '\\/')}"`))
  }
})

test('frontend API client only resolves supported WordPress REST endpoints', () => {
  for (const endpoint of ['home', 'site', 'news', 'objects', 'planets', 'apod', 'videos', 'events']) {
    assert.match(apiSource, new RegExp(`\\$\\{WORDPRESS_API_URL\\}/${endpoint}`))
  }
  assert.match(apiSource, /throw new Error\(`Unsupported API path:/)
})

test('GET request coalescing and session cache remain enabled for provider-backed endpoints', () => {
  assert.match(apiSource, /const GET_INFLIGHT = new Map\(\)/)
  assert.match(apiSource, /GET_INFLIGHT\.has\(key\)/)
  assert.match(apiSource, /window\.sessionStorage\.setItem/)
  assert.match(apiSource, /path === '\/api\/planets'/)
  assert.match(apiSource, /path === '\/api\/apod'/)
})

test('APOD Persian localization only renders when source hashes match', () => {
  const display = getApodDisplay({
    titleOriginal: 'NASA title',
    contentOriginal: 'NASA explanation',
    excerptOriginal: 'NASA excerpt',
    titleFa: 'عنوان فارسی',
    summaryFa: 'خلاصه فارسی',
    contentFa: 'متن فارسی معتبر',
    hasPersianEditorial: true,
    translationStatus: 'auto_ready',
    sourceHash: 'a'.repeat(64),
    translationSourceHash: 'a'.repeat(64),
  })

  assert.equal(display.hasPersianEditorial, true)
  assert.equal(display.title, 'عنوان فارسی')
  assert.equal(display.content, 'متن فارسی معتبر')
})

test('APOD stale or failed translations fall back to current NASA original', () => {
  for (const item of [
    {
      translationStatus: 'manual_ready',
      sourceHash: 'a'.repeat(64),
      translationSourceHash: 'b'.repeat(64),
    },
    {
      translationStatus: 'failed',
      sourceHash: 'a'.repeat(64),
      translationSourceHash: 'a'.repeat(64),
    },
  ]) {
    const display = getApodDisplay({
      titleOriginal: 'NASA current title',
      contentOriginal: 'NASA current explanation',
      excerptOriginal: 'NASA current excerpt',
      titleFa: 'عنوان قدیمی',
      summaryFa: 'خلاصه قدیمی',
      contentFa: 'متن قدیمی',
      hasPersianEditorial: true,
      ...item,
    })

    assert.equal(display.hasPersianEditorial, false)
    assert.equal(display.title, 'NASA current title')
    assert.equal(display.content, 'NASA current explanation')
    assert.match(display.warning, /NASA/)
  }
})

test('APOD image days use image rendering and not video embed markup', () => {
  assert.match(apodPageSource, /const isVideo = item\?\.mediaType === 'video' && \(item\.mediaUrl \|\| item\.sourceUrl\)/)
  assert.match(apodPageSource, /isVideo \? \(/)
  assert.match(apodPageSource, /<ApodVideo item=\{item\} display=\{display\} \/>/)
  assert.match(apodPageSource, /<ApodImage item=\{item\} display=\{display\} \/>/)
  assert.match(apodPageSource, /<img/)
  assert.match(apodPageSource, /alt=\{usingFallback \? 'تصویر APOD با وضعیت داده غیرتازه' : display\.title\}/)
  assert.doesNotMatch(apodPageSource, /youtube|YouTube|play button|fake play/i)
})

test('contact page presents only approved public communication channels', () => {
  assert.match(contactPageSource, /telegramUrl/)
  assert.match(contactPageSource, /instagramUrl/)
  assert.match(contactPageSource, /sponsorEmail/)
  assert.match(contactPageSource, /صرفاً برای همکاری‌های تجاری و اسپانسری/)
  assert.match(contactPageSource, /target="_blank"/)
  assert.match(contactPageSource, /rel="noopener noreferrer"/)
  assert.doesNotMatch(contactPageSource, /Youtube|youtube|phone|address|WhatsApp|Twitter|X\/Twitter/)
})

test('videos page uses cached internal data and safe click-to-load YouTube embeds', () => {
  assert.match(videosPageSource, /api\.get\('\/api\/videos'\)/)
  assert.match(videosPageSource, /TRUSTED_YOUTUBE_HOSTS/)
  assert.match(videosPageSource, /parsed\.protocol !== 'https:'/)
  assert.match(videosPageSource, /!TRUSTED_YOUTUBE_HOSTS\.has\(host\)/)
  assert.match(videosPageSource, /YOUTUBE_ID_PATTERN/)
  assert.match(videosPageSource, /setIframeActivated\(true\)/)
  assert.match(videosPageSource, /loading="lazy"/)
  assert.doesNotMatch(videosPageSource, /dangerouslySetInnerHTML/)
  assert.doesNotMatch(videosPageSource, /https:\/\/www\.googleapis\.com\/youtube/)
})

test('sun components render backend-provided states without hardcoded image fallback', () => {
  for (const source of [sunPreviewSource, sunNowCardSource]) {
    assert.match(source, /data\?\.image/)
    assert.match(source, /data\?\.fallbackImage/)
    assert.match(source, /onError=\{\(\) => setImageIndex\(\(current\) => current \+ 1\)\}/)
    assert.doesNotMatch(source, /sdo\.gsfc\.nasa\.gov\/assets\/img\/latest\/latest_1024_0304\.jpg/)
  }
  assert.match(sunPreviewSource, /ready: 'آماده'/)
  assert.match(sunPreviewSource, /stale: 'آخرین داده موجود'/)
  assert.match(sunPreviewSource, /error: 'خطای دریافت'/)
  assert.match(sunPreviewSource, /loading: 'در حال دریافت'/)
})

test('moon component uses backend phase direction and labels default location explicitly', () => {
  assert.match(moonNowCardSource, /const backendTrend = data\?\.waxingWaning \|\| skyMoon\?\.waxingWaning \|\| ''/)
  assert.match(moonNowCardSource, /backendTrend \? backendTrend === 'waxing' : moonAge < 14\.765/)
  assert.match(moonNowCardSource, /isDefaultLocation/)
  assert.match(moonNowCardSource, /پیش‌فرض:/)
  assert.match(moonNowCardSource, /Math\.max\(0, Math\.min\(100/)
  assert.match(moonNowCardSource, /این تصویر زنده یا عکس واقعی نیست|جایگاه افقی برای مکان انتخاب‌شده/)
})

test('APOD video URL normalization supports safe providers and trusted NASA files', () => {
  const youtubeWatch = normalizeApodVideo({ sourceUrl: 'https://www.youtube.com/watch?v=abcDEF_1234' })
  assert.equal(youtubeWatch.type, 'embed')
  assert.equal(youtubeWatch.embedUrl, 'https://www.youtube-nocookie.com/embed/abcDEF_1234')
  assert.equal(youtubeWatch.canEmbed, true)

  const youtubeEmbed = normalizeApodVideo({ sourceUrl: 'https://www.youtube.com/embed/abcDEF_1234' })
  assert.equal(youtubeEmbed.embedUrl, 'https://www.youtube-nocookie.com/embed/abcDEF_1234')

  const vimeo = normalizeApodVideo({ sourceUrl: 'https://vimeo.com/123456789' })
  assert.equal(vimeo.embedUrl, 'https://player.vimeo.com/video/123456789')

  const nasaFile = normalizeApodVideo({ sourceUrl: 'https://apod.nasa.gov/apod/image/2609/NoctilucentNeowise_Girotti.mp4' })
  assert.equal(nasaFile.type, 'file')
  assert.equal(nasaFile.canPlayInline, true)
  assert.equal(nasaFile.canEmbed, false)

  const separatedSource = normalizeApodVideo({
    sourceUrl: 'https://apod.nasa.gov/apod/ap260913.html',
    mediaUrl: 'https://apod.nasa.gov/apod/image/2609/NoctilucentNeowise_Girotti.mp4',
  })
  assert.equal(separatedSource.type, 'file')
  assert.equal(separatedSource.sourceUrl, 'https://apod.nasa.gov/apod/image/2609/NoctilucentNeowise_Girotti.mp4')
})

test('APOD unsafe or unsupported video URLs never create embeds', () => {
  for (const sourceUrl of [
    'javascript:alert(1)',
    'data:text/html,<h1>x</h1>',
    'http://www.youtube.com/watch?v=abcDEF_1234',
    'https://example.com/watch/video',
    'not a url',
    '',
  ]) {
    const video = normalizeApodVideo({ sourceUrl })
    assert.equal(video.canEmbed, false)
    assert.equal(video.canPlayInline, false)
    assert.equal(video.embedUrl, '')
  }
})

test('APOD video component is click-to-load and does not eagerly instantiate iframe', () => {
  assert.match(apodPageSource, /const \[activated, setActivated\] = useState\(false\)/)
  assert.match(apodPageSource, /const canLoadMedia = activated && !failed/)
  assert.match(apodPageSource, /onClick=\{\(\) => setActivated\(true\)\}/)
  assert.match(apodPageSource, /loading="lazy"/)
  assert.doesNotMatch(apodPageSource, /dangerouslySetInnerHTML/)
})

test('APOD attribution separates source, media, and real credit without fake ownership', () => {
  assert.match(apodPageSource, /sourceName = item\?\.sourceName \|\| 'NASA Astronomy Picture of the Day'/)
  assert.match(apodPageSource, /const credit = item\?\.copyright \|\| item\?\.credit \|\| item\?\.photographer \|\| ''/)
  assert.match(apodPageSource, /اعتبار \/ حق نشر/)
  assert.match(apodPageSource, /رسانه اصلی/)
  assert.match(apodPageSource, /safeHttpsUrl/)
  assert.doesNotMatch(apodPageSource, /© NASA|Copyright:|اعتبار تصویر: \{item\.photographer \|\| 'منبع اصلی تصویر'\}/)
})
