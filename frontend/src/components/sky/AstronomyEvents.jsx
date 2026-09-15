import EventCard from './EventCard'

export default function AstronomyEvents({ events, state = 'ready' }) {
  const payload = events || {}
  const items = Array.isArray(payload.items) ? payload.items : []
  const isLoading = state === 'loading'
  const isError = state === 'error'

  return (
    <section className="surface-card astronomy-events-panel p-5 sm:p-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <span className="eyebrow">تقویم رصد</span>
          <h2 className="mt-2 card-title">رویدادهای نجومی پیش رو</h2>
          <p className="mt-2 text-xs leading-6 text-slate-500">
            ترکیبی از رویدادهای محاسبه‌شده و برنامه‌های ثبت‌شده در وردپرس.
          </p>
          <p className="mt-1 text-xs leading-6 text-slate-600">
            محاسبه برای {payload.location?.city || 'موقعیت پیش‌فرض'}
          </p>
        </div>
          <span className={`state-pill state-${isError ? 'error' : isLoading ? 'loading' : payload.status === 'unavailable' ? 'stale' : payload.status || 'ready'}`}>
            {isLoading ? 'در حال آماده‌سازی' : isError ? 'داده پشتیبان' : payload.status === 'unavailable' ? 'ناموجود' : payload.accuracy === 'mixed' ? 'ترکیبی' : 'آماده'}
          </span>
      </div>

      {isLoading ? (
        <div className="events-grid mt-5" aria-busy="true">
          {[0, 1, 2].map((item) => <div key={item} className="event-card event-card-loading" />)}
        </div>
      ) : items.length ? (
        <div className="events-grid mt-5">
          {items.slice(0, 4).map((event) => <EventCard key={event.id} event={event} />)}
        </div>
      ) : (
        <div className="event-empty-state mt-5">
          فعلا رویدادی برای روزهای آینده ثبت نشده است.
        </div>
      )}

      <p className="mt-4 text-xs leading-6 text-slate-600">
        {payload.displayWarning || payload.message || 'زمان‌ها و امتیازها برای راهنمایی رصد عمومی هستند و ممکن است با شرایط محلی افق تغییر کنند.'}
      </p>
    </section>
  )
}
