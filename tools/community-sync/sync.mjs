import crypto from 'node:crypto'
import fs from 'node:fs/promises'
import { fetch as undiciFetch, ProxyAgent } from 'undici'

const args = parseArgs(process.argv.slice(2))
const channelUrl = args['channel-url'] || 'https://www.youtube.com/@Jazireh/posts'
const dryRun = hasFlag(args, 'dry-run')
const fixturePath = args['from-file'] || ''
const ingestUrl = args['ingest-url'] || ''
const secret = args.secret || process.env.JAZIREH_DAILY_SYNC_SECRET || ''
const explicitProxy = args.proxy || ''
const envProxy = process.env.HTTPS_PROXY || process.env.HTTP_PROXY || ''
const proxyUrl = explicitProxy || envProxy
const includePhrases = splitLines(args['include-phrases'] || 'عکس روز ناسا')
const requireImage = !hasFlag(args, 'allow-no-image')
const proxyAgents = new Map()
const MAX_INLINE_IMAGE_BYTES = 256 * 1024

async function main() {
  const extraction = fixturePath
    ? buildFixtureExtraction(JSON.parse(await fs.readFile(fixturePath, 'utf8')))
    : await extractPayload(channelUrl, { includePhrases, requireImage, proxyUrl })

  if (dryRun || !ingestUrl) {
    console.log(JSON.stringify(extraction, null, 2))
    return
  }

  if (!secret) {
    throw new Error('Missing --secret for signed ingestion.')
  }

  const result = await signedPost(ingestUrl, extraction.payload, secret, proxyUrl)
  console.log(JSON.stringify({ diagnostics: extraction.diagnostics, result }, null, 2))
}

async function extractPayload(url, options) {
  const response = await fetchForUrl(url, {
    headers: {
      'user-agent': 'Mozilla/5.0 (compatible; JazirehCommunitySync/1.0)',
      accept: 'text/html,application/xhtml+xml'
    }
  }, {
    proxyUrl: options.proxyUrl,
    purpose: 'Community source request'
  })

  if (!response.ok) {
    throw new Error(`Community page request failed with ${response.status}`)
  }

  const html = await response.text()
  const initialData = parseInitialData(html)
  const detectedPosts = collectPosts(initialData)
    .map(normalizePost)
    .filter(Boolean)

  const matchingPosts = detectedPosts.filter((post) => matchesCommunityRule(post, options))
  await Promise.all(matchingPosts.map((post) => hydratePostImages(post, options)))

  return {
    payload: { posts: matchingPosts },
    diagnostics: {
      source: url,
      totalDetected: detectedPosts.length,
      matching: matchingPosts.length,
      proxy: safeProxyLabel(options.proxyUrl),
      matches: matchingPosts.map((post) => ({
        sourcePostId: post.sourcePostId,
        sourceUrl: post.sourceUrl,
        publishedLabel: post.publishedLabel,
        imageCount: Array.isArray(post.images) ? post.images.length : 0
      }))
    }
  }
}

async function hydratePostImages(post, options) {
  const images = Array.isArray(post.images) ? post.images : []
  if (!images.length) return post

  post.images = await Promise.all(images.map((image, index) => hydrateImage(image, {
    proxyUrl: options.proxyUrl,
    sourcePostId: post.sourcePostId,
    index
  })))

  return post
}

async function hydrateImage(image, options) {
  if (!image?.url) return image

  const response = await fetchForUrl(image.url, {}, {
    proxyUrl: options.proxyUrl,
    purpose: false
  })

  if (!response.ok) return image

  const contentType = String(response.headers.get('content-type') || '').toLowerCase()
  if (!/^image\/(jpeg|jpg|png|webp|gif|avif)$/.test(contentType)) return image

  const bytes = Buffer.from(await response.arrayBuffer())
  if (!bytes.length || bytes.length > MAX_INLINE_IMAGE_BYTES) return image

  return {
    ...image,
    mimeType: contentType,
    filename: buildImageFilename(options.sourcePostId, options.index, contentType),
    dataBase64: bytes.toString('base64')
  }
}

function parseInitialData(html) {
  const patterns = [
    /var ytInitialData = (\{.*?\});<\/script>/s,
    /window\["ytInitialData"\] = (\{.*?\});<\/script>/s,
    /ytInitialData"\s*:\s*(\{.*\})\s*<\/script>/s
  ]

  for (const pattern of patterns) {
    const match = html.match(pattern)
    if (!match?.[1]) continue
    try {
      return JSON.parse(match[1])
    } catch {
      continue
    }
  }

  throw new Error('ytInitialData was not found in the Community page HTML.')
}

function collectPosts(root) {
  const results = []
  visit(root, (node) => {
    if (!node || typeof node !== 'object') return
    const renderer = node.backstagePostRenderer || node.postRenderer
    if (renderer && typeof renderer === 'object') {
      results.push(renderer)
    }
  })
  return dedupeBy(results, (item) => item.postId || item.backstageAttachment?.videoRenderer?.videoId || JSON.stringify(item))
}

function normalizePost(renderer) {
  const sourcePostId = renderer.postId || ''
  if (!sourcePostId) return null

  const text = extractRuns(renderer.contentText?.runs || [])
  const publishedLabel = renderer.publishedTimeText?.runs?.map((entry) => entry.text).join(' ').trim() || ''
  const imageUrls = extractImageUrls(renderer)
  const images = imageUrls.map((url, index) => ({
    url,
    alt: `Jazireh Daily ${sourcePostId} image ${index + 1}`,
    hash: crypto.createHash('sha1').update(url).digest('hex')
  }))

  return {
    source: 'youtube-community',
    sourcePostId,
    sourceUrl: `https://www.youtube.com/post/${sourcePostId}`,
    publishedAt: '',
    publishedLabel,
    title: buildTitle(text),
    text,
    excerpt: summarize(text),
    sourceHash: crypto.createHash('sha1').update(JSON.stringify({ text, imageUrls, publishedLabel })).digest('hex'),
    images
  }
}

function matchesCommunityRule(post, options) {
  const normalized = normalizePersian(post.text)
  const phraseMatch = options.includePhrases.some((phrase) => normalized.includes(normalizePersian(phrase)))
  if (!phraseMatch) return false
  if (options.requireImage && (!post.images || !post.images.length)) return false
  return true
}

function buildFixtureExtraction(payload) {
  const posts = Array.isArray(payload?.posts) ? payload.posts : []
  return {
    payload,
    diagnostics: {
      source: 'fixture',
      totalDetected: posts.length,
      matching: posts.length,
      proxy: null,
      matches: posts.map((post) => ({
        sourcePostId: post.sourcePostId || '',
        sourceUrl: post.sourceUrl || '',
        publishedLabel: post.publishedLabel || '',
        imageCount: Array.isArray(post.images) ? post.images.length : 0
      }))
    }
  }
}

async function signedPost(url, payload, secretValue, activeProxyUrl) {
  const body = JSON.stringify(payload)
  const timestamp = Math.floor(Date.now() / 1000).toString()
  const signature = 'sha256=' + crypto.createHmac('sha256', secretValue).update(`${timestamp}.${body}`).digest('hex')

  const response = await fetchForUrl(url, {
    method: 'POST',
    headers: {
      'content-type': 'application/json',
      'x-jazireh-timestamp': timestamp,
      'x-jazireh-signature': signature
    },
    body
  }, {
    proxyUrl: activeProxyUrl,
    purpose: 'WordPress ingestion request',
    allowProxy: false
  })

  const json = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new Error(json.message || `Ingestion failed with ${response.status}`)
  }
  return json
}

async function fetchForUrl(url, options, fetchOptions = {}) {
  const useProxy = shouldUseProxy(url, fetchOptions)
  const requestOptions = {
    ...options
  }

  if (useProxy) {
    requestOptions.dispatcher = getProxyAgent(fetchOptions.proxyUrl)
  }

  const response = await undiciFetch(url, requestOptions)
  const label = Object.prototype.hasOwnProperty.call(fetchOptions, 'purpose') ? fetchOptions.purpose : 'Request'
  logRequest(label, url, response.status, useProxy ? fetchOptions.proxyUrl : '')
  return response
}

function getProxyAgent(proxyValue) {
  if (!proxyAgents.has(proxyValue)) {
    proxyAgents.set(proxyValue, new ProxyAgent(proxyValue))
  }
  return proxyAgents.get(proxyValue)
}

function shouldUseProxy(url, fetchOptions) {
  if (fetchOptions.allowProxy === false) return false
  if (!fetchOptions.proxyUrl) return false
  return !isLocalUrl(url)
}

function isLocalUrl(url) {
  try {
    const parsed = new URL(url)
    const hostname = parsed.hostname.toLowerCase()
    return hostname === 'localhost' || hostname === '127.0.0.1' || hostname === '::1'
  } catch {
    return false
  }
}

function logRequest(label, url, status, activeProxyUrl) {
  if (label === false) return
  const parsed = new URL(url)
  const lines = [
    `${label}:`,
    `PROXY: ${activeProxyUrl ? safeProxyLabel(activeProxyUrl) : 'DIRECT'}`,
    `SOURCE: ${parsed.hostname}`,
    `STATUS: ${status}`
  ]
  process.stderr.write(`${lines.join('\n')}\n`)
}

function safeProxyLabel(value) {
  if (!value) return ''
  try {
    const parsed = new URL(value)
    const hasAuth = parsed.username || parsed.password
    const authLabel = hasAuth ? `${parsed.username ? '***' : ''}${parsed.password ? ':***' : ''}@` : ''
    const port = parsed.port ? `:${parsed.port}` : ''
    return `${parsed.protocol}//${authLabel}${parsed.hostname}${port}`
  } catch {
    return 'REDACTED_PROXY'
  }
}

function extractRuns(runs) {
  if (!Array.isArray(runs)) return ''
  return runs.map((entry) => entry?.text || '').join('').replace(/\r\n?/g, '\n').trim()
}

function extractImageUrls(renderer) {
  const urls = new Map()
  visit(renderer, (node) => {
    if (!node || typeof node !== 'object') return
    const candidates = [
      node.url,
      node.thumbnails?.[0]?.url,
      node.thumbnails?.[1]?.url,
      node.image?.thumbnails?.[0]?.url,
      node.image?.thumbnails?.[1]?.url
    ].filter(Boolean)

    for (const candidate of candidates) {
      if (typeof candidate === 'string' && /^https:\/\//.test(candidate) && isAllowedImageSource(candidate)) {
        const key = canonicalImageKey(candidate)
        if (!urls.has(key)) {
          urls.set(key, candidate)
        }
      }
    }
  })
  return Array.from(urls.values()).slice(0, 4)
}

function canonicalImageKey(value) {
  return String(value || '').split('=')[0]
}

function buildImageFilename(sourcePostId, index, contentType) {
  const subtype = contentType.split('/')[1] || 'jpg'
  const extension = subtype === 'jpeg' ? 'jpg' : subtype
  return `${sourcePostId}-${index + 1}.${extension}`
}

function isAllowedImageSource(value) {
  try {
    const hostname = new URL(value).hostname.toLowerCase()
    return hostname === 'yt3.ggpht.com'
      || hostname === 'i.ytimg.com'
      || hostname === 'ytimg.com'
      || hostname.endsWith('.ytimg.com')
      || hostname === 'ggpht.com'
      || hostname.endsWith('.ggpht.com')
      || hostname === 'googleusercontent.com'
      || hostname.endsWith('.googleusercontent.com')
  } catch {
    return false
  }
}

function buildTitle(text) {
  const firstLine = text.split('\n').map((line) => line.trim()).find(Boolean) || 'جزیره دیلی'
  return firstLine.slice(0, 120)
}

function summarize(text) {
  return text.length > 220 ? `${text.slice(0, 217).trim()}...` : text
}

function normalizePersian(value) {
  return String(value || '')
    .trim()
    .toLocaleLowerCase('fa-IR')
    .replace(/ي/g, 'ی')
    .replace(/ك/g, 'ک')
    .replace(/\u200c/g, ' ')
}

function splitLines(value) {
  return String(value || '')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
}

function dedupeBy(items, keyFn) {
  const seen = new Set()
  return items.filter((item) => {
    const key = keyFn(item)
    if (seen.has(key)) return false
    seen.add(key)
    return true
  })
}

function visit(node, callback) {
  callback(node)
  if (!node || typeof node !== 'object') return
  if (Array.isArray(node)) {
    for (const value of node) visit(value, callback)
    return
  }
  for (const value of Object.values(node)) {
    visit(value, callback)
  }
}

function parseArgs(argv) {
  const parsed = {}
  for (const arg of argv) {
    if (!arg.startsWith('--')) continue
    const withoutPrefix = arg.slice(2)
    const eq = withoutPrefix.indexOf('=')
    if (eq === -1) parsed[withoutPrefix] = true
    else parsed[withoutPrefix.slice(0, eq)] = withoutPrefix.slice(eq + 1)
  }
  return parsed
}

function hasFlag(argsMap, key) {
  return argsMap[key] === true || argsMap[key] === 'true'
}

main().catch((error) => {
  process.stderr.write(`${error.message}\n`)
  process.exit(1)
})
