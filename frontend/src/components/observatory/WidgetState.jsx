import { AlertTriangle, CheckCircle2, Clock3 } from 'lucide-react'

const stateConfig = {
  ready: {
    icon: CheckCircle2,
    label: 'به‌روز',
    className: 'border-emerald-400/20 bg-emerald-400/10 text-emerald-200',
  },
  stale: {
    icon: Clock3,
    label: 'نیازمند به‌روزرسانی',
    className: 'border-amber-300/20 bg-amber-300/10 text-amber-100',
  },
  error: {
    icon: AlertTriangle,
    label: 'در دسترس نیست',
    className: 'border-rose-300/20 bg-rose-300/10 text-rose-100',
  },
}

export default function WidgetState({ status = 'error', message, updatedAt, className = '' }) {
  const config = stateConfig[status] || stateConfig.error
  const Icon = config.icon
  const label = getPublicLabel(status, message, config.label)

  return (
    <div className={`inline-flex min-h-8 items-center gap-2 rounded-full border px-3 py-1 text-[11px] font-bold ${config.className} ${className}`}>
      <Icon className="h-3.5 w-3.5" />
      <span>{label}</span>
      {updatedAt && <time className="text-white/55" dateTime={updatedAt}>{formatDate(updatedAt)}</time>}
    </div>
  )
}

function getPublicLabel(status, message, fallback) {
  if (!message) return fallback
  if (/curl|openssl|ssl|errno|timed out|timeout|failed to open stream|wp_remote/i.test(message)) {
    return status === 'error'
      ? 'داده موقتاً در دسترس نیست'
      : 'داده پشتیبان'
  }
  return message.length > 44 ? fallback : message
}

function formatDate(value) {
  try {
    return new Intl.DateTimeFormat('fa-IR', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    }).format(new Date(value))
  } catch {
    return ''
  }
}
