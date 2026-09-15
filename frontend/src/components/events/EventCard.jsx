import { ArrowUpLeft, CalendarDays, MapPin } from 'lucide-react'
import { formatDate } from '../../lib/utils'

const typeAccent = {
  meteor_shower: 'accent-yellow',
  eclipse: 'accent-orange',
  conjunction: 'accent-blue',
  opposition: 'accent-purple',
  supermoon: 'accent-purple',
  other: '',
}

export default function EventCard({ event, compact = false }) {
  const accent = typeAccent[event?.eventType] || typeAccent.other

  return (
    <article className={`surface-card ${accent} flex h-full flex-col overflow-hidden`}>
      {event?.image && !compact && (
        <div className="aspect-[16/9] overflow-hidden border-b border-white/[.07] bg-black/25">
          <img src={event.image} alt={event.title} className="h-full w-full object-cover" loading="lazy" decoding="async" />
        </div>
      )}
      <div className="flex flex-1 flex-col p-5 sm:p-6">
        <div className="flex flex-wrap items-center gap-2">
          <span className="rounded-full bg-amber-300/10 px-3 py-1 text-xs font-bold text-amber-100">{event?.eventTypeLabel || 'رویداد نجومی'}</span>
          {event?.status && <span className="rounded-full border border-white/[.08] bg-white/[.03] px-3 py-1 text-xs text-slate-500">{event.status === 'past' ? 'گذشته' : 'پیش‌رو'}</span>}
        </div>

        <h2 className={`${compact ? 'text-lg' : 'text-xl'} mt-4 line-clamp-2 font-black leading-8 text-white`}>{event?.title || 'رویداد نجومی'}</h2>
        <p className="mt-3 line-clamp-3 text-sm leading-7 text-slate-400">{event?.description || 'جزئیات این رویداد پس از تکمیل در مدیریت وردپرس نمایش داده می‌شود.'}</p>

        <dl className="mt-5 space-y-3 text-xs text-slate-500">
          <div className="flex items-start gap-2">
            <CalendarDays className="mt-0.5 h-4 w-4 shrink-0 text-amber-200" />
            <div>
              <dt className="sr-only">بازه زمانی</dt>
              <dd className="font-bold leading-6 text-slate-300">{formatDateRange(event?.startDate, event?.endDate)}</dd>
            </div>
          </div>
          <div className="flex items-start gap-2">
            <MapPin className="mt-0.5 h-4 w-4 shrink-0 text-sky-200" />
            <div>
              <dt className="sr-only">مکان و مشاهده‌پذیری</dt>
              <dd className="leading-6">{event?.visibility || 'شرایط مشاهده اعلام نشده است'}</dd>
            </div>
          </div>
        </dl>

        {(event?.source?.label || event?.source?.url) && (
          <div className="mt-auto border-t border-white/[.07] pt-4">
            {event.source.url ? (
              <a href={event.source.url} target="_blank" rel="noopener noreferrer" className="inline-flex items-center gap-2 text-xs font-bold text-sky-200 transition hover:text-white">
                {event.source.label || 'منبع رویداد'}
                <ArrowUpLeft className="h-3.5 w-3.5" />
              </a>
            ) : (
              <span className="text-xs font-bold text-slate-500">{event.source.label}</span>
            )}
          </div>
        )}
      </div>
    </article>
  )
}

function formatDateRange(start, end) {
  if (!start && !end) return 'زمان اعلام نشده'
  if (!end || start === end) return formatDate(start)
  return `${formatDate(start)} تا ${formatDate(end)}`
}
