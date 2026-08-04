export const cn = (...classes) => classes.filter(Boolean).join(' ')
export const faNumber = (value) => new Intl.NumberFormat('fa-IR').format(value)
export const formatDate = (value) => new Intl.DateTimeFormat('fa-IR', { dateStyle: 'long' }).format(new Date(value))
