export const cn = (...classes) => classes.filter(Boolean).join(' ')

const themeAssetBase = (window.JAZIREH_WP?.assetBase || window.JAZIREH_ASSET_BASE || '').replace(/\/+$/, '')

export const faNumber = (value) => new Intl.NumberFormat('fa-IR').format(value)

export const formatDate = (value) => {
  if (!value) return 'تاریخ نامشخص'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return new Intl.DateTimeFormat('fa-IR', { dateStyle: 'long' }).format(date)
}

export const normalizePersianText = (value = '') => value
  .toString()
  .trim()
  .toLocaleLowerCase('fa-IR')
  .replace(/ي/g, 'ی')
  .replace(/ك/g, 'ک')
  .replace(/\u200c/g, ' ')

export const resolveAssetPath = (value = '') => {
  if (!value) return value
  if (/^(?:https?:)?\/\//.test(value) || value.startsWith('data:') || value.startsWith('blob:')) return value
  if (themeAssetBase && (value.startsWith('/brand/') || value.startsWith('/media/'))) {
    return `${themeAssetBase}${value}`
  }
  return value
}
