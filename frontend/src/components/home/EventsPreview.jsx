import { CalendarDays } from 'lucide-react'
import { useEffect, useState } from 'react'
import EventCard from '../events/EventCard'
import SectionHeader from '../SectionHeader'
import { api } from '../../lib/api'

export default function EventsPreview() {
  const [events, setEvents] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(false)

  useEffect(() => {
    let active = true
    api.get('/api/events?status=upcoming&per_page=3')
      .then((response) => {
        if (!active) return
        setEvents(Array.isArray(response.data?.items) ? response.data.items : [])
      })
      .catch(() => {
        if (!active) return
        setEvents([])
        setError(true)
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => { active = false }
  }, [])

  return (
    <section className="content-shell section-space pt-0">
      <SectionHeader
        eyebrow="تقویم علمی"
        title="رویدادهای پیش‌رو"
        description="نگاهی سریع به پدیده‌های نجومی آینده و شرایط مشاهده آن‌ها."
        link="/events"
        linkLabel="تقویم رویدادها"
      />

      {loading ? (
        <div className="surface-card flex min-h-[180px] items-center justify-center p-8"><div className="h-8 w-8 animate-spin rounded-full border-2 border-white/10 border-t-amber-300" /></div>
      ) : error ? (
        <div className="surface-card accent-red flex min-h-[180px] items-center justify-center p-8 text-center text-sm leading-7 text-slate-400">تقویم رویدادها فعلاً در دسترس نیست.</div>
      ) : events.length ? (
        <div className="grid grid-cols-1 gap-5 md:grid-cols-3">
          {events.map((event) => <EventCard key={event.id || event.slug} event={event} compact />)}
        </div>
      ) : (
        <div className="surface-card flex min-h-[180px] flex-col items-center justify-center p-8 text-center">
          <CalendarDays className="h-9 w-9 text-slate-600" />
          <p className="mt-3 text-sm leading-7 text-slate-500">هنوز رویداد پیش‌رویی در تقویم ثبت نشده است.</p>
        </div>
      )}
    </section>
  )
}
