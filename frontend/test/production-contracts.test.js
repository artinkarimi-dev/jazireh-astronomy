import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { test } from 'node:test'

const appSource = await readFile(new URL('../src/App.jsx', import.meta.url), 'utf8')
const apiSource = await readFile(new URL('../src/lib/api.js', import.meta.url), 'utf8')
const apodPageSource = await readFile(new URL('../src/pages/ApodPage.jsx', import.meta.url), 'utf8')
const apodBannerSource = await readFile(new URL('../src/components/home/ApodBanner.jsx', import.meta.url), 'utf8')
const headerSource = await readFile(new URL('../src/components/Header.jsx', import.meta.url), 'utf8')
const layoutSource = await readFile(new URL('../src/components/Layout.jsx', import.meta.url), 'utf8')
const footerSource = await readFile(new URL('../src/components/Footer.jsx', import.meta.url), 'utf8')
const contactPageSource = await readFile(new URL('../src/pages/ContactPage.jsx', import.meta.url), 'utf8')
const newsPageSource = await readFile(new URL('../src/pages/NewsPage.jsx', import.meta.url), 'utf8')
const skyTodayPageSource = await readFile(new URL('../src/pages/SkyTodayPage.jsx', import.meta.url), 'utf8')
const videosPageSource = await readFile(new URL('../src/pages/VideosPage.jsx', import.meta.url), 'utf8')
const sunPreviewSource = await readFile(new URL('../src/components/home/SunPreview.jsx', import.meta.url), 'utf8')
const sunNowCardSource = await readFile(new URL('../src/components/observatory/SunNowCard.jsx', import.meta.url), 'utf8')
const moonNowCardSource = await readFile(new URL('../src/components/observatory/MoonNowCard.jsx', import.meta.url), 'utf8')
const viteConfigSource = await readFile(new URL('../vite.config.js', import.meta.url), 'utf8')
const webManifestSource = await readFile(new URL('../public/manifest.webmanifest', import.meta.url), 'utf8')
const themeFunctionsSource = await readFile(new URL('../../wordpress/wp-content/themes/jazireh-theme/functions.php', import.meta.url), 'utf8')
const restSource = await readFile(new URL('../../wordpress/wp-content/plugins/jazireh-core/includes/class-jazireh-rest.php', import.meta.url), 'utf8')
const dailySource = await readFile(new URL('../../wordpress/wp-content/plugins/jazireh-core/includes/class-jazireh-daily.php', import.meta.url), 'utf8')
const corePluginSource = await readFile(new URL('../../wordpress/wp-content/plugins/jazireh-core/jazireh-core.php', import.meta.url), 'utf8')
const gitignoreSource = await readFile(new URL('../../.gitignore', import.meta.url), 'utf8')
const wordpressHtaccessSource = await readFile(new URL('../../wordpress/.htaccess', import.meta.url), 'utf8')
const { getApodDisplay } = await import('../src/lib/apodLocalization.js')
const { normalizeApodVideo } = await import('../src/lib/apodVideo.js')
const { getSunNowViewModel } = await import('../src/components/observatory/sunNowModel.js')

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

test('WordPress build uses relative chunk and manifest asset paths', () => {
  assert.match(viteConfigSource, /base:\s*'\.\/'/)
  assert.match(webManifestSource, /"start_url": "\.\/"/)
  assert.match(webManifestSource, /"src": "\.\/brand\/icon-192\.png"/)
  assert.match(webManifestSource, /"src": "\.\/brand\/icon-512\.png"/)
  assert.doesNotMatch(webManifestSource, /\/wordpress\//)
  assert.doesNotMatch(webManifestSource, /"src": "\/brand\//)
})

test('global navigation exposes skip link and keyboard-safe mobile drawer', () => {
  assert.match(layoutSource, /href="#main-content"/)
  assert.match(layoutSource, /<main id="main-content" tabIndex="-1"/)
  assert.match(headerSource, /aria-controls=\{open \? 'mobile-menu-drawer' : undefined\}/)
  assert.match(headerSource, /aria-expanded=\{open\}/)
  assert.match(headerSource, /role="dialog"/)
  assert.match(headerSource, /aria-modal="true"/)
  assert.match(headerSource, /drawerRef/)
  assert.match(headerSource, /menuButtonRef/)
  assert.match(headerSource, /getFocusableElements/)
  assert.match(headerSource, /event\.key !== 'Tab'/)
  assert.match(headerSource, /menuButtonRef\.current\.focus\(\)/)
})

test('public search controls have programmatic labels', () => {
  assert.match(newsPageSource, /htmlFor="news-search"/)
  assert.match(newsPageSource, /id="news-search"/)
  assert.match(skyTodayPageSource, /htmlFor="sky-object-search"/)
  assert.match(skyTodayPageSource, /id="sky-object-search"/)
  assert.match(skyTodayPageSource, /aria-label="تنظیم زمان نقشه آسمان"/)
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

test('homepage APOD banner handles missing APOD without throwing', () => {
  assert.match(apodBannerSource, /item\?\.isFallback/)
  assert.match(apodBannerSource, /item\?\.displayWarning/)
  assert.match(apodBannerSource, /item \? display\.title/)
  assert.doesNotMatch(apodBannerSource, /item\.isFallback/)
  assert.doesNotMatch(apodBannerSource, /\{item\.displayWarning \?/)
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

test('public newsletter signup is intentionally hidden until production workflow exists', () => {
  assert.doesNotMatch(footerSource, /NewsletterForm|newsletter|خبرنامه|عضویت|subscribe/i)
  assert.doesNotMatch(apiSource, /\/api\/newsletter|\/newsletter/)
  assert.doesNotMatch(restSource, /'\/newsletter'|Jazireh_Newsletter::subscribe|public static function newsletter/)
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
  for (const source of [sunPreviewSource]) {
    assert.match(source, /data\?\.image/)
    assert.match(source, /data\?\.fallbackImage/)
    assert.match(source, /onError=\{\(\) => setImageIndex\(\(current\) => current \+ 1\)\}/)
    assert.doesNotMatch(source, /sdo\.gsfc\.nasa\.gov\/assets\/img\/latest\/latest_1024_0304\.jpg/)
  }
  assert.match(sunNowCardSource, /getSunNowViewModel\(widget, fallbackSun\)/)
  assert.match(sunNowCardSource, /view\.imageCandidates/)
  assert.match(sunNowCardSource, /onError=\{\(\) => setImageIndex\(\(current\) => current \+ 1\)\}/)
  assert.doesNotMatch(sunNowCardSource, /sdo\.gsfc\.nasa\.gov\/assets\/img\/latest\/latest_1024_0304\.jpg/)
  assert.match(sunPreviewSource, /ready: 'آماده'/)
  assert.match(sunPreviewSource, /stale: 'آخرین داده موجود'/)
  assert.match(sunPreviewSource, /error: 'خطای دریافت'/)
  assert.match(sunPreviewSource, /loading: 'در حال دریافت'/)
})

test('Sun Now renders stale fallback images without requiring observation time', () => {
  const sdoImage = 'https://sdo.gsfc.nasa.gov/assets/img/latest/latest_1024_0304.jpg'
  const stale = getSunNowViewModel(null, {
    status: 'stale',
    image: sdoImage,
    fallbackImage: sdoImage,
    sourceName: 'NASA SDO',
    sourceUrl: 'https://sdo.gsfc.nasa.gov/data/',
    wavelength: 'AIA 304Å',
    observedAt: '',
    isFallback: true,
    displayWarning: 'داده پشتیبان',
  })

  assert.equal(stale.status, 'stale')
  assert.equal(stale.imageCandidates[0], sdoImage)
  assert.equal(stale.data.observedAt, '')
  assert.equal(stale.data.isFallback, true)
  assert.equal(stale.data.displayWarning, 'داده پشتیبان')

  const ready = getSunNowViewModel({
    status: 'ready',
    data: {
      image: 'https://example.com/sun.jpg',
      observedAt: '2026-09-21T10:00:00Z',
    },
  })
  assert.equal(ready.status, 'ready')
  assert.equal(ready.imageCandidates[0], 'https://example.com/sun.jpg')

  const unavailable = getSunNowViewModel({ status: 'error', data: { image: '', fallbackImage: '' } })
  assert.equal(unavailable.status, 'error')
  assert.deepEqual(unavailable.imageCandidates, [])
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

test('production hardening keeps secrets out of public build and sensitive files ignored', () => {
  for (const source of [apiSource, appSource, webManifestSource]) {
    assert.doesNotMatch(source, /OPENAI_API_KEY|NASA_API_KEY|YOUTUBE_API_KEY|GOOGLE_API_KEY|DB_PASSWORD|BEGIN PRIVATE KEY|Bearer\s+[A-Za-z0-9._-]+/)
  }
  assert.match(gitignoreSource, /^\.env$/m)
  assert.match(gitignoreSource, /^\.env\.\*$/m)
  assert.match(gitignoreSource, /\*.bak/)
  assert.match(gitignoreSource, /database\/\*\.sql/)
})

test('Jazireh production security headers are configured without local-only HSTS', () => {
  assert.match(themeFunctionsSource, /function jazireh_theme_security_headers/)
  assert.match(corePluginSource, /function jazireh_core_send_base_security_headers/)
  assert.match(corePluginSource, /rest_pre_serve_request/)
  assert.match(corePluginSource, /login_init/)
  assert.match(themeFunctionsSource, /Content-Security-Policy/)
  assert.match(themeFunctionsSource, /X-Content-Type-Options: nosniff/)
  assert.match(themeFunctionsSource, /Referrer-Policy: strict-origin-when-cross-origin/)
  assert.match(themeFunctionsSource, /Permissions-Policy:/)
  assert.match(themeFunctionsSource, /frame-ancestors 'self'/)
  assert.match(themeFunctionsSource, /JAZIREH_ENABLE_HSTS/)
  assert.doesNotMatch(themeFunctionsSource, /Strict-Transport-Security.*localhost/)
})

test('Apache deployment template blocks directory listings and sensitive web files', () => {
  assert.match(wordpressHtaccessSource, /Options -Indexes/)
  assert.match(wordpressHtaccessSource, /RewriteRule \(\^\|\/\)\\\./)
  assert.match(wordpressHtaccessSource, /FilesMatch/)
  assert.match(wordpressHtaccessSource, /wp-config/)
  assert.match(wordpressHtaccessSource, /log\|old\|orig\|sql/)
})

test('state-changing Jazireh Daily sync route enforces signed request permission', () => {
  assert.match(restSource, /'\/jazireh-daily-sync'[\s\S]*'permission_callback' => array\('Jazireh_Daily', 'authorize_sync_request'\)/)
  assert.match(dailySource, /x-jazireh-timestamp/)
  assert.match(dailySource, /x-jazireh-signature/)
  assert.match(dailySource, /hash_hmac\('sha256'/)
  assert.match(dailySource, /hash_equals\(\$expected, \$signature\)/)
  assert.match(dailySource, /set_transient\(\$replay_key, 1, self::SYNC_WINDOW\)/)
})
