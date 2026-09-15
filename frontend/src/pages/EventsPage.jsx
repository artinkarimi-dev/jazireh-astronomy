import { useEffect, useState } from 'react'
import { CalendarDays, RefreshCw } from 'lucide-react'
import PageHero from '../components/PageHero'
import EventCard from '../components/sky/EventCard'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'

const emptyPayload = {
  status: 'loading',
  accuracy: '',
  confidence: '',
  source: '',
  calculatedAt: '',
  location: { city: 'تهران، ایران' },
  items: []
}

export default function EventsPage() {
  const [payload, setPayload] = useState(emptyPayload)
  const [state, setState] = useState('loading')
  const [sourceMode, setSourceMode] = useState('curated')

  usePageMeta('رویدادهای نجومی', 'تقویم رویدادهای نجومی پیش رو، فازهای ماه، فرصت‌های رصدی و برنامه‌های ثبت‌شده در جزیره.')

  useEffect(() => {
    let active = true
    setState('loading')
    setSourceMode('curated')

    api.get('/api/events?status=upcoming&per_page=24').then(async (response) => {
      if (!active) return
      const curated = normalizePayload(response.data)
      if (curated.items.length) {
        setPayload(curated)
        setSourceMode('curated')
        setState(curated.status || 'ready')
        return
      }

      const scientificResponse = await api.get('/api/events?limit=12&days=90')
      if (!active) return
      const scientific = normalizePayload(scientificResponse.data)
      setPayload(scientific)
      setSourceMode(scientific.items.length ? 'scientific' : 'empty')
      setState(scientific.status || 'ready')
    }).catch(() => {
      if (!active) return
      setPayload(emptyPayload)
      setState('error')
      setSourceMode('error')
    })

    return () => { active = false }
  }, [])

  const items = Array.isArray(payload.items) ? payload.items : []
  const isLoading = state === 'loading'
  const isError = state === 'error'
  const hasItems = items.length > 0
  const statusText = getStatusText({ isLoading, isError, sourceMode, payload, hasItems })
  const pillText = getPillText({ isLoading, isError, sourceMode, payload, hasItems })

  return (
    <>
      <PageHero
        eyebrow="تقویم رصد جزیره"
        title="رویدادهای نجومی"
        description="رویدادهای پیش رو، زمان‌های مهم و فرصت‌های رصدی را در یک تقویم ساده و فارسی دنبال کنید."
      />

      <section className="content-shell section-space">
        <div className="events-status-row">
          <span>{statusText}</span>
          {payload.calculatedAt ? <time dateTime={payload.calculatedAt}>به‌روزرسانی: {formatDateTime(payload.calculatedAt)}</time> : null}
        </div>

        <div className="surface-card astronomy-events-panel p-5 sm:p-6">
          <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <span className="eyebrow"><CalendarDays className="h-4 w-4" />رویدادهای پیش رو</span>
              <h2 className="mt-2 card-title">تقویم نجومی جزیره</h2>
              <p className="mt-2 text-xs leading-6 text-slate-500">
                {sourceMode === 'scientific' ? 'رویدادهای محاسباتی بر اساس داده‌های نجومی موجود' : 'رویدادهای ثبت‌شده و مدیریت‌شده در وردپرس'}
                {' · '}
                محاسبه برای {payload.location?.city || payload.location?.label || 'تهران، ایران'}
              </p>
            </div>
            <span className={`state-pill state-${isError ? 'error' : isLoading ? 'loading' : payload.status || 'ready'}`}>
              {pillText}
            </span>
          </div>

          {isLoading ? (
            <div className="events-grid mt-5" aria-busy="true">
              {[0, 1, 2, 3].map((item) => (
                <div key={item} className="event-card event-card-loading" />
              ))}
            </div>
          ) : hasItems ? (
            <div className="events-grid mt-5">
              {items.map((event) => <EventCard key={event.id} event={event} />)}
            </div>
          ) : (
            <div className="event-empty-state mt-5">
              <RefreshCw className="mb-3 h-5 w-5 text-slate-500" />
              فعلا رویداد معتبر یا محاسباتی برای نمایش در دسترس نیست.
            </div>
          )}
        </div>
      </section>
    </>
  )
}

function normalizePayload(data) {
  const payload = data && typeof data === 'object' ? data : {}
  return {
    ...emptyPayload,
    ...payload,
    location: payload.location || emptyPayload.location,
    items: Array.isArray(payload.items) ? payload.items : []
  }
}

function getStatusText({ isLoading, isError, sourceMode, payload, hasItems }) {
  if (isLoading) return 'در حال دریافت رویدادهای نجومی...'
  if (isError) return 'دریافت رویدادهای نجومی ممکن نبود. بعدا دوباره بررسی کنید.'
  if (sourceMode === 'scientific' && hasItems) return payload.displayWarning || 'رویدادهای محاسباتی از داده‌های نجومی موجود نمایش داده می‌شوند.'
  if (sourceMode === 'curated' && hasItems) return 'رویدادهای ثبت‌شده در پنل مدیریت نمایش داده می‌شوند.'
  return 'هیچ رویداد معتبر یا محاسباتی برای این بازه در دسترس نیست.'
}

function getPillText({ isLoading, isError, sourceMode, payload, hasItems }) {
  if (isLoading) return 'در حال آماده‌سازی'
  if (isError) return 'خطا'
  if (!hasItems) return 'بدون رویداد'
  if (sourceMode === 'scientific') return payload.accuracy === 'mixed' ? 'محاسبات نجومی' : 'محاسباتی'
  return 'ثبت‌شده'
}

function formatDateTime(value) {
  if (!value) return ''
  try {
    return new Intl.DateTimeFormat('fa-IR', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }).format(new Date(value))
  } catch {
    return ''
  }
}
