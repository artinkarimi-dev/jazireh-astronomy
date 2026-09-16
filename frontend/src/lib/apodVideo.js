const YOUTUBE_HOSTS = new Set(['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be', 'www.youtu.be', 'youtube-nocookie.com', 'www.youtube-nocookie.com'])
const VIMEO_HOSTS = new Set(['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'])
const TRUSTED_VIDEO_FILE_HOSTS = new Set(['apod.nasa.gov', 'www.apod.nasa.gov', 'science.nasa.gov'])
const VIDEO_FILE_EXTENSIONS = ['.mp4', '.webm', '.ogg', '.ogv', '.mov']

export function normalizeApodVideo(item = {}) {
  const sourceUrl = item?.mediaUrl || item?.sourceUrl || ''
  const parsed = parseHttpsUrl(sourceUrl)
  if (!parsed) {
    return {
      type: 'missing',
      sourceUrl: '',
      embedUrl: '',
      posterUrl: safeHttpsUrl(item?.thumbnailUrl || item?.image || ''),
      canEmbed: false,
      canPlayInline: false,
    }
  }

  const youtubeId = extractYoutubeId(parsed)
  if (youtubeId) {
    return videoResult('embed', parsed, `https://www.youtube-nocookie.com/embed/${youtubeId}`, item)
  }

  const vimeoId = extractVimeoId(parsed)
  if (vimeoId) {
    return videoResult('embed', parsed, `https://player.vimeo.com/video/${vimeoId}`, item)
  }

  if (isTrustedVideoFile(parsed)) {
    return {
      type: 'file',
      sourceUrl: parsed.href,
      embedUrl: '',
      posterUrl: safeHttpsUrl(item?.thumbnailUrl || item?.image || ''),
      canEmbed: false,
      canPlayInline: true,
    }
  }

  return {
    type: 'external',
    sourceUrl: parsed.href,
    embedUrl: '',
    posterUrl: safeHttpsUrl(item?.thumbnailUrl || item?.image || ''),
    canEmbed: false,
    canPlayInline: false,
  }
}

export function safeHttpsUrl(value = '') {
  const parsed = parseHttpsUrl(value)
  return parsed ? parsed.href : ''
}

function videoResult(type, parsed, embedUrl, item) {
  return {
    type,
    sourceUrl: parsed.href,
    embedUrl,
    posterUrl: safeHttpsUrl(item?.thumbnailUrl || item?.image || ''),
    canEmbed: true,
    canPlayInline: false,
  }
}

function parseHttpsUrl(value = '') {
  if (!value || typeof value !== 'string') return null
  try {
    const parsed = new URL(value.trim())
    if (parsed.protocol !== 'https:') return null
    return parsed
  } catch {
    return null
  }
}

function normalizedHost(parsed) {
  return parsed.hostname.toLowerCase()
}

function extractYoutubeId(parsed) {
  const host = normalizedHost(parsed)
  if (!YOUTUBE_HOSTS.has(host)) return ''
  if (host === 'youtu.be' || host === 'www.youtu.be') {
    return cleanVideoId(parsed.pathname.split('/').filter(Boolean)[0] || '')
  }
  if (parsed.pathname.startsWith('/embed/')) {
    return cleanVideoId(parsed.pathname.split('/')[2] || '')
  }
  if (parsed.pathname.startsWith('/shorts/')) {
    return cleanVideoId(parsed.pathname.split('/')[2] || '')
  }
  return cleanVideoId(parsed.searchParams.get('v') || '')
}

function extractVimeoId(parsed) {
  const host = normalizedHost(parsed)
  if (!VIMEO_HOSTS.has(host)) return ''
  const parts = parsed.pathname.split('/').filter(Boolean)
  if (host === 'player.vimeo.com' && parts[0] === 'video') {
    return cleanNumericId(parts[1] || '')
  }
  return cleanNumericId(parts[0] || '')
}

function isTrustedVideoFile(parsed) {
  const host = normalizedHost(parsed)
  const path = parsed.pathname.toLowerCase()
  return TRUSTED_VIDEO_FILE_HOSTS.has(host) && VIDEO_FILE_EXTENSIONS.some((extension) => path.endsWith(extension))
}

function cleanVideoId(value = '') {
  const match = String(value).match(/^[A-Za-z0-9_-]{6,}$/)
  return match ? match[0] : ''
}

function cleanNumericId(value = '') {
  const match = String(value).match(/^\d{6,}$/)
  return match ? match[0] : ''
}
