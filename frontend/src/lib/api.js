const WORDPRESS_API_URL = (window.JAZIREH_WP?.root || import.meta.env.VITE_WORDPRESS_API_URL || '').replace(/\/+$/, '')

function resolveUrl(path) {
  if (!WORDPRESS_API_URL) {
    throw new Error('WordPress API base URL is not configured.')
  }
  if (path === '/api/home') return `${WORDPRESS_API_URL}/home`
  if (path === '/api/site') return `${WORDPRESS_API_URL}/site`
  if (path === '/api/news') return `${WORDPRESS_API_URL}/news`
  if (path === '/api/videos') return `${WORDPRESS_API_URL}/videos`
  if (path === '/api/jazireh-daily' || path.startsWith('/api/jazireh-daily?')) return `${WORDPRESS_API_URL}/jazireh-daily${path.slice('/api/jazireh-daily'.length)}`
  if (path === '/api/sky/today') return `${WORDPRESS_API_URL}/sky`
  if (path === '/api/newsletter') return `${WORDPRESS_API_URL}/newsletter`
  if (path === '/api/apod' || path.startsWith('/api/apod?')) return `${WORDPRESS_API_URL}/apod${path.slice('/api/apod'.length)}`
  if (path === '/api/objects' || path.startsWith('/api/objects?')) return `${WORDPRESS_API_URL}/objects${path.slice('/api/objects'.length)}`
  if (path.startsWith('/api/news/')) return `${WORDPRESS_API_URL}/news/${path.slice('/api/news/'.length)}`
  if (path.startsWith('/api/jazireh-daily/')) return `${WORDPRESS_API_URL}/jazireh-daily/${path.slice('/api/jazireh-daily/'.length)}`
  throw new Error(`Unsupported API path: ${path}`)
}

async function request(path, options = {}) {
  const controller = new AbortController()
  const timeout = window.setTimeout(() => controller.abort(), options.timeout || 10000)
  const hasBody = options.body !== undefined

  try {
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

    return data
  } catch (error) {
    if (error.name === 'AbortError') throw new Error('Server response timed out. Please try again.')
    throw error
  } finally {
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
