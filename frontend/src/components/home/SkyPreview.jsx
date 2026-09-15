import { Activity, ArrowLeft, CalendarDays, Clock3, Eye, MapPin, Moon, Sparkles } from 'lucide-react'
import { Link } from 'react-router-dom'
import { skyData } from '../../data/fallback'

export default function SkyPreview({ data = skyData, content = {}, className = '' }) {
  const highlights = Array.isArray(data.tonightHighlights) ? data.tonightHighlights.slice(0, 3) : []
  const events = Array.isArray(data.upcomingEvents?.items) ? data.upcomingEvents.items.slice(0, 2) : []
  const location = data.locationLabel || data.location || data.locationMeta?.city || data.location?.city || 'موقعیت پیش‌فرض'
  const freshness = data.calculatedAt || data.generatedAtUtc || data.updatedAt || ''
  const moonValue = data.moonIllumination !== null && data.moonIllumination !== undefined
    ? `${data.moonPhase || 'نامشخص'}، ${data.moonIllumination}٪`
    : data.moonPhase || 'در دسترس نیست'

  return (
    <article className={`surface-card home-feature-card home-sky-snapshot accent-blue p-5 sm:p-6 ${className}`.trim()}>
      <div className="home-sky-snapshot-head">
        <div>
          <span className="eyebrow">{content.sky_eyebrow || 'رصد امشب'}</span>
          <h3 className="mt-2 card-title">{content.sky_title || 'آسمان امروز'}</h3>
        </div>
        <Link to="/sky" className="secondary-btn home-sky-snapshot-cta">{content.sky_cta || 'جزئیات آسمان'} <ArrowLeft className="h-4 w-4" /></Link>
      </div>

      <div className="sky-preview-summary-grid mt-5 grid grid-cols-2 gap-3">
        <Mini icon={Clock3} label="بهترین بازه" value={data.bestTime || 'در دسترس نیست'} />
        <Mini icon={Moon} label="فاز ماه" value={moonValue} />
        <Mini icon={Eye} label="شرایط رصد" value={data.observingCondition?.label || data.condition || 'نمایش تقریبی'} />
        <Mini icon={Activity} label="ابرناکی" value={formatPercent(data.cloudCover)} />
      </div>

      <div className="home-sky-context-row">
        <span><MapPin className="h-4 w-4" />{location}</span>
        {data.isFallback || data.upcomingEvents?.isFallback ? <em>داده پشتیبان</em> : null}
      </div>

      {highlights.length ? (
        <section className="home-sky-section">
          <span className="home-sky-section-title"><Sparkles className="h-4 w-4" />خلاصه رصد امشب</span>
          <div className="home-sky-list mt-4">
            {highlights.map((item) => (
              <div key={item.id || item.title} className="home-sky-list-item">
                <span>{item.title}</span>
                <strong>{item.value}</strong>
              </div>
            ))}
          </div>
        </section>
      ) : null}

      {events.length ? (
        <section className="home-sky-section">
          <span className="home-sky-section-title"><CalendarDays className="h-4 w-4" />رویدادهای نزدیک</span>
          <div className="home-event-list mt-4">
            {events.map((event) => (
              <div key={event.id || event.title} className="home-event-list-item">
                <div>
                  <span>{event.categoryLabel || event.category || 'رویداد نجومی'}</span>
                  <strong>{event.title}</strong>
                </div>
                <em>{event.bestTime || event.peakDate || event.startDate || 'زمان اعلام‌شده'}</em>
              </div>
            ))}
          </div>
          <Link to="/events" className="secondary-btn mt-4 w-full">همه رویدادها <ArrowLeft className="h-4 w-4" /></Link>
        </section>
      ) : null}

      <footer className="home-sky-footer">
        <span>{data.displayWarning || data.message || data.observingCondition?.summary || 'وضعیت آسمان بر اساس داده‌های موجود نمایش داده می‌شود.'}</span>
        {freshness ? <time>{freshness}</time> : null}
      </footer>
    </article>
  )
}

function Mini({ icon: Icon, label, value, wide }) {
  return (
    <div className={`sky-summary-tile ${wide ? 'col-span-2' : ''}`}>
      <div className="sky-summary-label">
        <Icon className="h-4 w-4 text-sky-300" />
        <span>{label}</span>
      </div>
      <strong>{value}</strong>
    </div>
  )
}

function formatPercent(value) {
  return value === null || value === undefined || value === '' ? 'ناموجود' : `${value}٪`
}
