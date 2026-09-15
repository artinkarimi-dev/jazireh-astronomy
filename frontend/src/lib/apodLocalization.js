export function getApodDisplay(item = {}) {
  item = item || {}
  const hasPersianEditorial = Boolean(item.hasPersianEditorial && ['ready', 'auto_ready', 'manual_ready'].includes(item.translationStatus))
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
      : (item.localizationWarning || 'متن اصلی NASA به انگلیسی نمایش داده می‌شود؛ توضیح فارسی این تصویر هنوز آماده نشده است.'),
  }
}
