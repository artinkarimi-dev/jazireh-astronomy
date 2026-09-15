const rawWordPressApiUrl = (window.JAZIREH_WP?.root || import.meta.env.VITE_WORDPRESS_API_URL || '').replace(/\/+$/, '')
const WORDPRESS_API_URL = rawWordPressApiUrl.endsWith('/jazireh/v1')
  ? rawWordPressApiUrl
  : `${rawWordPressApiUrl.replace(/\/wp-json$/, '/wp-json/jazireh/v1')}`

const GET_CACHE_PREFIX = `jazireh_api_cache:${window.JAZIREH_WP?.buildVersion || 'dev'}:`
const GET_INFLIGHT = new Map()
const GET_CACHE_MEMORY = new Map()
const DEFAULT_GET_CACHE_TTL = 10 * 60 * 1000

function getCacheTtl(path) {
  if (path === '/api/apod' || path.startsWith('/api/apod?')) return 20 * 60 * 1000
  if (path === '/api/search' || path.startsWith('/api/search?')) return 2 * 60 * 1000
  if (path === '/api/topics' || path.startsWith('/api/topics/')) return 15 * 60 * 1000
  if (path === '/api/home' || path === '/api/sky/today' || path === '/api/planets' || path === '/api/events' || path.startsWith('/api/events?') || path === '/api/sun' || path === '/api/widgets/sun' || path === '/api/widgets/sky') return 15 * 60 * 1000
  if (path === '/api/widgets/apod' || path.startsWith('/api/widgets/apod?')) return 20 * 60 * 1000
  if (path === '/api/widgets/moon' || path === '/api/widgets/earth' || path === '/api/widgets/earthquakes' || path === '/api/widgets') return 10 * 60 * 1000
  return DEFAULT_GET_CACHE_TTL
}

function hasStorage() {
  try {
    return typeof window !== 'undefined' && !!window.sessionStorage
  } catch {
    return false
  }
}

function cacheKey(path) {
  return `${GET_CACHE_PREFIX}${path}`
}

function readCachedResponse(path) {
  const key = cacheKey(path)
  const memoryEntry = GET_CACHE_MEMORY.get(key)
  if (memoryEntry && memoryEntry.expiresAt > Date.now()) {
    return memoryEntry.value
  }
  if (!hasStorage()) {
    return null
  }

  try {
    const raw = window.sessionStorage.getItem(key)
    if (!raw) return null
    const parsed = JSON.parse(raw)
    if (!parsed || typeof parsed !== 'object' || !parsed.expiresAt || parsed.expiresAt <= Date.now()) {
      window.sessionStorage.removeItem(key)
      GET_CACHE_MEMORY.delete(key)
      return null
    }
    GET_CACHE_MEMORY.set(key, parsed)
    return parsed.value || null
  } catch {
    return null
  }
}

function writeCachedResponse(path, value, ttl) {
  const key = cacheKey(path)
  const entry = {
    expiresAt: Date.now() + Math.max(1000, ttl || DEFAULT_GET_CACHE_TTL),
    value,
  }
  GET_CACHE_MEMORY.set(key, entry)
  if (!hasStorage()) {
    return value
  }

  try {
    window.sessionStorage.setItem(key, JSON.stringify(entry))
  } catch {
    // Ignore quota or privacy-mode storage errors.
  }
  return value
}

function resolveUrl(path) {
  if (!WORDPRESS_API_URL) {
    throw new Error('WordPress API base URL is not configured.')
  }
  if (path === '/api/home') return `${WORDPRESS_API_URL}/home`
  if (path === '/api/site') return `${WORDPRESS_API_URL}/site`
  if (path === '/api/widgets') return `${WORDPRESS_API_URL}/widgets`
  if (path === '/api/widgets/apod' || path.startsWith('/api/widgets/apod?')) return `${WORDPRESS_API_URL}/widgets/apod${path.slice('/api/widgets/apod'.length)}`
  if (path === '/api/widgets/sun') return `${WORDPRESS_API_URL}/widgets/sun`
  if (path === '/api/widgets/moon') return `${WORDPRESS_API_URL}/widgets/moon`
  if (path === '/api/widgets/sky') return `${WORDPRESS_API_URL}/widgets/sky`
  if (path === '/api/widgets/earth') return `${WORDPRESS_API_URL}/widgets/earth`
  if (path === '/api/widgets/earthquakes') return `${WORDPRESS_API_URL}/widgets/earthquakes`
  if (path === '/api/news') return `${WORDPRESS_API_URL}/news`
  if (path === '/api/videos') return `${WORDPRESS_API_URL}/videos`
  if (path === '/api/jazireh-daily' || path.startsWith('/api/jazireh-daily?')) return `${WORDPRESS_API_URL}/jazireh-daily${path.slice('/api/jazireh-daily'.length)}`
  if (path === '/api/sky/today') return `${WORDPRESS_API_URL}/sky`
  if (path === '/api/planets') return `${WORDPRESS_API_URL}/planets`
  if (path === '/api/events' || path.startsWith('/api/events?')) return `${WORDPRESS_API_URL}/events${path.slice('/api/events'.length)}`
  if (path === '/api/events/today' || path.startsWith('/api/events/today?')) return `${WORDPRESS_API_URL}/events/today${path.slice('/api/events/today'.length)}`
  if (path === '/api/search' || path.startsWith('/api/search?')) return `${WORDPRESS_API_URL}/search${path.slice('/api/search'.length)}`
  if (path === '/api/topics') return `${WORDPRESS_API_URL}/topics`
  if (path.startsWith('/api/topics/')) return `${WORDPRESS_API_URL}/topics/${path.slice('/api/topics/'.length)}`
  if (path === '/api/sun') return `${WORDPRESS_API_URL}/sun`
  if (path === '/api/newsletter') return `${WORDPRESS_API_URL}/newsletter`
  if (path === '/api/apod' || path.startsWith('/api/apod?')) return `${WORDPRESS_API_URL}/apod${path.slice('/api/apod'.length)}`
  if (path === '/api/objects' || path.startsWith('/api/objects?')) return `${WORDPRESS_API_URL}/objects${path.slice('/api/objects'.length)}`
  if (path.startsWith('/api/news/')) return `${WORDPRESS_API_URL}/news/${path.slice('/api/news/'.length)}`
  if (path.startsWith('/api/jazireh-daily/')) return `${WORDPRESS_API_URL}/jazireh-daily/${path.slice('/api/jazireh-daily/'.length)}`
  throw new Error(`Unsupported API path: ${path}`)
}

async function request(path, options = {}) {
  const method = String(options.method || 'GET').toUpperCase()
  const cacheable = method === 'GET' && options.cache !== false
  const ttl = cacheable ? getCacheTtl(path) : 0
  const key = cacheable ? cacheKey(path) : ''
  const controller = new AbortController()
  const timeout = window.setTimeout(() => controller.abort(), options.timeout || 30000)
  const hasBody = options.body !== undefined
  const externalSignal = options.signal
  let ownsInflight = false

  try {
    if (cacheable) {
      const cached = readCachedResponse(path)
      if (cached) {
        return cached
      }
      if (!externalSignal && GET_INFLIGHT.has(key)) {
        return GET_INFLIGHT.get(key)
      }
    }

    if (externalSignal) {
      if (externalSignal.aborted) {
        controller.abort()
      } else {
        externalSignal.addEventListener('abort', () => controller.abort(), { once: true })
      }
    }

    const execute = async () => {
      const response = await fetch(resolveUrl(path), {
        ...options,
        signal: controller.signal,
        headers: {
          Accept: 'application/json',
          ...(hasBody ? { 'Content-Type': 'application/json' } : {}),
          ...(options.headers || {}),
        },
      })

      const data = await response.json().catch(() => ({}))

      if (!response.ok) {
        const error = new Error(data.message || 'Server request failed.')
        error.status = response.status
        error.details = data.errors || null
        throw error
      }

      const normalized = {
        ...data,
        data: data && typeof data === 'object' && 'data' in data ? data.data : data,
      }

      if (cacheable) {
        writeCachedResponse(path, normalized, ttl)
      }

      return normalized
    }

    if (cacheable && !externalSignal) {
      const inflight = execute()
      GET_INFLIGHT.set(key, inflight)
      ownsInflight = true
      return await inflight
    }

    return await execute()
  } catch (error) {
    if (error.name === 'AbortError') throw new Error('Server response timed out. Please try again.')
    throw error
  } finally {
    if (cacheable && !externalSignal && ownsInflight) {
      GET_INFLIGHT.delete(key)
    }
    window.clearTimeout(timeout)
  }
}

export const api = {
  get: (path, options) => request(path, options),
  post: (path, body) => request(path, { method: 'POST', body: JSON.stringify(body) }),
  put: (path, body) => request(path, { method: 'PUT', body: JSON.stringify(body) }),
  delete: (path) => request(path, { method: 'DELETE' }),
  wordpressUrl: WORDPRESS_API_URL,
}
