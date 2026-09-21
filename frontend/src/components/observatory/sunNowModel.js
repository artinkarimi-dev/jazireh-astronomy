export function getSunNowViewModel(widget, fallbackSun) {
  const widgetData = widget?.data && typeof widget.data === 'object' ? widget.data : null
  const flatWidget = widget && typeof widget === 'object' ? widget : null
  const flatFallback = fallbackSun && typeof fallbackSun === 'object' ? fallbackSun : null

  const preferredData = firstWithImage(widgetData, flatWidget, flatFallback) || widgetData || flatWidget || flatFallback || {}
  const status = widget?.status || preferredData.status || 'error'
  const source = widget?.source || preferredData.source || preferredData.sourceName || ''
  const sourceUrl = widget?.sourceUrl || preferredData.sourceUrl || ''
  const message = widget?.message || preferredData.message || ''
  const updatedAt = widget?.updatedAt || preferredData.updatedAt || preferredData.fetchedAt || ''
  const imageCandidates = [
    normalizeSunImageUrl(preferredData.image),
    normalizeSunImageUrl(preferredData.fallbackImage),
  ].filter(Boolean)

  return {
    data: preferredData,
    imageCandidates,
    message,
    source,
    sourceUrl,
    status,
    updatedAt,
  }
}

export function normalizeSunImageUrl(value) {
  if (!value || typeof value !== 'string') return ''

  const markdownUrl = value.match(/^\[[^\]]+\]\((https:\/\/[^)]+)\)$/)
  const url = (markdownUrl ? markdownUrl[1] : value).trim()
    .replace(/\+/g, '%2B')
    .replace(/\[/g, '%5B')
    .replace(/\]/g, '%5D')

  if (!/^https:\/\//i.test(url)) return ''

  try {
    return new URL(url).href
  } catch {
    return ''
  }
}

function firstWithImage(...items) {
  return items.find((item) => item && (normalizeSunImageUrl(item.image) || normalizeSunImageUrl(item.fallbackImage))) || null
}
