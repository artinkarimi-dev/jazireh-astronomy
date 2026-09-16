export function getApodDisplay(item = {}) {
  item = item || {}
  const currentHash = item.sourceHash || ''
  const translationHash = item.translationSourceHash || ''
  const hasCurrentHash = Boolean(currentHash && translationHash && currentHash === translationHash)
  const hasPersianEditorial = Boolean(item.hasPersianEditorial && hasCurrentHash && ['ready', 'auto_ready', 'manual_ready'].includes(item.translationStatus))
  const isLocalFallback = Boolean(item.isFallback && item.source === 'frontend-fallback')
  const titleOriginal = item.titleOriginal || item.title || 'NASA APOD'
  const contentOriginal = item.contentOriginal || item.content || ''
  const excerptOriginal = item.excerptOriginal || item.excerpt || contentOriginal
  const titleFa = item.titleFa || ''
  const summaryFa = item.summaryFa || ''
  const contentFa = item.contentFa || ''

  return {
    hasPersianEditorial,
    title: hasPersianEditorial ? (titleFa || titleOriginal) : titleOriginal,
    summary: hasPersianEditorial ? (summaryFa || contentFa) : excerptOriginal,
    content: hasPersianEditorial ? (contentFa || summaryFa) : contentOriginal,
    titleOriginal,
    contentOriginal,
    excerptOriginal,
    warning: hasPersianEditorial || isLocalFallback
      ? ''
      : (item.localizationWarning || 'ترجمه فارسی معتبر برای نسخه فعلی NASA هنوز آماده نیست؛ متن اصلی NASA نمایش داده می‌شود.'),
  }
}
