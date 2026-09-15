import { CalendarDays, Clock3, MapPin, Sparkles } from 'lucide-react'

export default function EventCard({ event }) {
  const dateLabel = eventDateLabel(event)
  const confidenceLabel = eventConfidenceLabel(event)
  const categoryLabel = event.categoryLabel || event.eventTypeLabel || 'رویداد نجومی'
  const summary = event.summary || event.description || ''
  const statusLabel = event.visibilityLabel || (event.status === 'past' ? 'گذشته' : 'پیش‌رو')

  return (
    <article className="event-card">
      <div className="event-card-head">
        <div className="min-w-0">
          <span className="event-category">{categoryLabel}</span>
          <h3 className="event-title">{event.title}</h3>
        </div>
        <span className="event-score">{statusLabel}</span>
      </div>

      {summary ? <p className="mt-3 text-xs leading-6 text-slate-500">{summary}</p> : null}

      <div className="event-meta">
        <Meta icon={CalendarDays} value={dateLabel} />
        <Meta icon={Clock3} value={event.bestTime || 'زمان اعلام‌شده'} />
        {event.direction ? <Meta icon={MapPin} value={`افق ${event.direction}`} /> : null}
        <Meta icon={Sparkles} value={confidenceLabel} />
      </div>
      {event.displayWarning ? <p className="mt-3 text-[11px] leading-6 text-amber-100">{event.displayWarning}</p> : null}
    </article>
  )
}

function Meta({ icon: Icon, value }) {
  return (
    <span>
      <Icon className="h-3.5 w-3.5" />
      {value}
    </span>
  )
}

function eventDateLabel(event) {
  const rawDate = event.peakDate || event.startDate
  const formatted = formatEventDate(rawDate)
  if (!formatted) return 'به‌زودی'
  if (event.category === 'meteor_shower') return `اوج تقریبی: ${formatted}`
  if (event.category === 'seasonal') return `تاریخ تقریبی: ${formatted}`
  if (event.peakDate && event.peakDate !== event.startDate) return `زمان اوج: ${formatted}`
  return formatted
}

function eventConfidenceLabel(event) {
  if (event.confidence === 'curated') return 'ثبت‌شده در وردپرس'
  if (event.category === 'meteor_shower' || event.category === 'seasonal') return 'زمان تقریبی'
  return 'راهنمای رصدی برآوردی'
}

function formatEventDate(value) {
  if (!value) return ''
  const normalized = String(value).includes('T') ? String(value) : `${value}T12:00:00`
  const date = new Date(normalized)
  if (Number.isNaN(date.getTime())) return ''
  return new Intl.DateTimeFormat('fa-IR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    ...(String(value).includes('T') ? { hour: '2-digit', minute: '2-digit' } : {}),
  }).format(date)
}
