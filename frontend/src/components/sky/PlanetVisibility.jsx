import { Telescope } from 'lucide-react'

export default function PlanetVisibility({ planets, state = 'ready' }) {
  const payload = planets || {}
  const items = Array.isArray(payload.items) ? payload.items : []
  const isLoading = state === 'loading'
  const isError = state === 'error'
  const isStale = state === 'stale' || payload.status === 'stale'

  return (
    <section className="surface-card planet-visibility-panel p-5 sm:p-6">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <span className="eyebrow">رصد سیاره‌ها</span>
          <h2 className="mt-2 card-title">دیدپذیری سیاره‌ها امشب</h2>
          <p className="mt-2 text-xs leading-6 text-slate-500">
            راهنمای رصدی برآوردی برای اینکه بدانید امشب کدام سیاره‌ها ارزش بررسی دارند.
          </p>
          <p className="mt-1 text-xs leading-6 text-slate-600">
            محاسبه برای {payload.location?.label || payload.location?.city || 'موقعیت پیش‌فرض'}
          </p>
        </div>
          <span className={`state-pill state-${isError ? 'error' : isLoading ? 'loading' : payload.status || 'ready'}`}>
            {isLoading ? 'در حال محاسبه' : isError ? 'خطا' : isStale ? 'آخرین داده معتبر' : payload.status === 'unavailable' ? 'ناموجود' : payload.accuracy === 'estimated' ? 'راهنمای برآوردی' : 'آماده'}
          </span>
      </div>

      {isLoading ? (
        <>
          <div className="mt-5 rounded-2xl border border-sky-300/10 bg-sky-300/[.03] p-4 text-xs leading-6 text-sky-100">
            در حال محاسبه دیدپذیری سیاره‌ها...
          </div>
          <div className="planet-grid mt-4" aria-busy="true">
            {[0, 1, 2].map((item) => <div key={item} className="planet-card planet-card-loading" />)}
          </div>
        </>
      ) : items.length ? (
        <div className="planet-grid mt-5">
          {items.map((planet) => <PlanetCard key={planet.id} planet={planet} />)}
        </div>
      ) : (
        <div className="mt-5 rounded-2xl border border-white/[.06] bg-white/[.02] p-4 text-xs leading-6 text-slate-500">
          {isError ? 'دریافت داده دیدپذیری سیاره‌ها با خطا یا زمان‌بر شدن سرویس روبه‌رو شد.' : 'در حال حاضر داده‌ای برای دیدپذیری سیاره‌ها در دسترس نیست.'}
        </div>
      )}

      <p className="mt-4 text-xs leading-6 text-slate-600">
        {payload.displayWarning || payload.message || 'برای رصد کاملا دقیق، شرایط افق محل خود و جدول‌های تخصصی نجومی را هم بررسی کنید.'}
      </p>
    </section>
  )
}

function PlanetCard({ planet }) {
  return (
    <article className={`planet-card planet-status-${planet.status || 'visible'}`}>
      <div className="flex items-start justify-between gap-3">
        <div className="min-w-0">
          <span className="planet-name">{planet.name}</span>
          <strong className="planet-status-label">{planet.statusLabel || 'برآوردی'}</strong>
          <span className="planet-score">{formatVisibilityScore(planet.visibilityScore)}</span>
        </div>
        <span className="planet-orb" style={{ '--planet-color': planet.color || '#7dd3fc' }}>
          <Telescope className="h-4 w-4" />
        </span>
      </div>
      <div className="mt-4 flex flex-wrap gap-2">
        <span className="planet-chip">راهنمای برآوردی</span>
        {planet.displayWarning ? <span className="planet-chip">{planet.displayWarning}</span> : null}
        <span className="planet-chip">{planet.bestTime || 'امشب'}</span>
        <span className="planet-chip">افق {planet.direction || '-'}</span>
      </div>
      <dl className="planet-facts">
        <div><dt>ارتفاع</dt><dd>{planet.altitudeLabel || '-'}</dd></div>
        <div><dt>درخشندگی</dt><dd>{planet.brightnessLabel || '-'}</dd></div>
      </dl>
      <p className="mt-3 text-xs leading-6 text-slate-500">{planet.summary}</p>
    </article>
  )
}

function formatVisibilityScore(score) {
  return score === null || score === undefined || score === ''
    ? 'امتیاز رصد: در دسترس نیست'
    : `امتیاز رصد: ${score} از ۱۰۰`
}
