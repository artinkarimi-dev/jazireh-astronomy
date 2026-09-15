import { memo, useMemo } from 'react'
import { AlertTriangle, Info, Loader2, Satellite, Star } from 'lucide-react'

function formatCoordinate(value, unit = 'deg') {
  if (value === null || value === undefined || Number.isNaN(Number(value))) return 'ناموجود'
  const formatted = Number(value).toLocaleString('fa-IR', { maximumFractionDigits: 1 })
  return unit === 'deg' ? `${formatted}°` : formatted
}

function SkyRadar({
  compact = false,
  targets = [],
  selectedId = '',
  onSelectTarget,
  state = 'ready',
  warning = '',
  locationLabel = '',
}) {
  const plottedTargets = useMemo(() => targets.filter((target) => target.visible !== false), [targets])
  const activeTarget = useMemo(
    () => plottedTargets.find((target) => target.id === selectedId) || plottedTargets[0] || null,
    [plottedTargets, selectedId]
  )
  const sizeClass = compact ? 'radar-visual-compact' : 'radar-visual'
  const isLoading = state === 'loading'
  const isError = state === 'error'

  return (
    <div className="radar-experience">
      <div className={`radar-canvas ${sizeClass}`} role="img" aria-label="نمای آموزشی رادار آسمان">
        <div className="radar-horizon-label radar-horizon-n">شمال</div>
        <div className="radar-horizon-label radar-horizon-e">شرق</div>
        <div className="radar-horizon-label radar-horizon-s">جنوب</div>
        <div className="radar-horizon-label radar-horizon-w">غرب</div>
        <div className="radar-sweep absolute inset-0 animate-radar rounded-full opacity-70" />
        <div className="absolute inset-[8%] rounded-full border border-blue-300/10" />
        <div className="absolute inset-[24%] rounded-full border border-blue-300/10" />
        <div className="absolute left-1/2 top-1/2 h-2.5 w-2.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-200 shadow-[0_0_20px_rgba(147,197,253,.9)]" />

        {isLoading && (
          <div className="radar-overlay">
            <Loader2 className="h-5 w-5 animate-spin text-sky-200" />
            <span>در حال آماده‌سازی نمای رادار...</span>
          </div>
        )}

        {!isLoading && plottedTargets.length === 0 && (
          <div className="radar-overlay">
            {isError ? <AlertTriangle className="h-5 w-5 text-amber-200" /> : <Info className="h-5 w-5 text-sky-200" />}
            <span>{isError ? 'داده رادار در دسترس نیست.' : 'جرم قابل نمایش برای این لحظه وجود ندارد.'}</span>
          </div>
        )}

        {!isLoading && plottedTargets.map((target) => {
          const Icon = target.type === 'satellite' ? Satellite : Star
          const isActive = target.id === activeTarget?.id
          return (
            <button
              key={target.id}
              type="button"
              className={`radar-target ${isActive ? 'radar-target-active' : ''} ${target.isFallback ? 'radar-target-fallback' : ''}`}
              style={{ top: `${target.top}%`, left: `${target.left}%` }}
              onClick={() => onSelectTarget?.(target.id)}
              aria-label={`${target.label}، ${target.statusLabel}`}
            >
              <span className="radar-target-dot">
                <span className="radar-target-pulse" />
              </span>
              {!compact && <span className="radar-target-label"><Icon className="h-3 w-3" />{target.label}</span>}
            </button>
          )
        })}
      </div>

      {!compact && (
        <div className="radar-readout">
          <div className="min-w-0">
            <span className="text-[11px] text-slate-500">{locationLabel || 'موقعیت رصد پیش‌فرض'}</span>
            <strong className="mt-1 block text-sm leading-6 text-white">{activeTarget?.label || 'بدون هدف فعال'}</strong>
          </div>
          <div className="radar-readout-grid">
            <span>ارتفاع: {formatCoordinate(activeTarget?.altitude)}</span>
            <span>سمت: {formatCoordinate(activeTarget?.azimuth)}</span>
            <span>{activeTarget?.statusLabel || 'وضعیت ناموجود'}</span>
          </div>
          {(warning || activeTarget?.warning) && (
            <p className="mt-3 text-xs leading-6 text-slate-500">{activeTarget?.warning || warning}</p>
          )}
        </div>
      )}
    </div>
  )
}

export default memo(SkyRadar)
