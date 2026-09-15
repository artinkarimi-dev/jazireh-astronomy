import { Maximize2, RotateCcw } from 'lucide-react'
import { altitudeLabel, directionLabel, toFaNumber } from '../../lib/skyTonight'

const DEG = Math.PI / 180

export default function InteractiveSkyMap({
  className = '',
  model,
  selectedObjectId,
  onSelectObject,
  onResetSelection,
  onFullscreen,
  showConstellations = true,
  nightMode = false,
}) {
  const allObjects = Array.isArray(model?.objects) ? model.objects : []
  const objects = allObjects.filter((item) => item.altitude > -2)
  const stars = objects.filter((item) => item.type === 'star')
  const prominentObjects = objects.filter((item) => item.type !== 'star')
  const selected = allObjects.find((item) => item.id === selectedObjectId) || prominentObjects.find((item) => item.visible) || objects[0]
  const constellations = Array.isArray(model?.constellations) ? model.constellations : []

  return (
    <div className={`sky-map-shell ${nightMode ? 'sky-map-night' : ''} ${className}`}>
      <div className="sky-map-glow" />
      <svg viewBox="0 0 100 100" className="sky-map-svg" role="img" aria-label="نقشه تعاملی آسمان امشب با موقعیت تقریبی اجرام">
        <defs>
          <radialGradient id="skyDomeGlow" cx="50%" cy="50%" r="52%">
            <stop offset="0" stopColor="#1d4ed8" stopOpacity=".2" />
            <stop offset=".62" stopColor="#020617" stopOpacity=".95" />
            <stop offset="1" stopColor="#01030a" />
          </radialGradient>
          <filter id="softObjectGlow" x="-80%" y="-80%" width="260%" height="260%">
            <feGaussianBlur stdDeviation="1.6" result="blur" />
            <feMerge>
              <feMergeNode in="blur" />
              <feMergeNode in="SourceGraphic" />
            </feMerge>
          </filter>
        </defs>

        <circle cx="50" cy="50" r="45" fill="url(#skyDomeGlow)" stroke="rgba(125,211,252,.25)" strokeWidth=".35" />
        {[15, 30, 45].map((radius) => (
          <circle key={radius} cx="50" cy="50" r={radius} fill="none" stroke="rgba(148,163,184,.13)" strokeWidth=".22" />
        ))}
        <line x1="50" y1="5" x2="50" y2="95" stroke="rgba(148,163,184,.12)" strokeWidth=".18" />
        <line x1="5" y1="50" x2="95" y2="50" stroke="rgba(148,163,184,.12)" strokeWidth=".18" />

        {showConstellations ? constellations.map((constellation) => (
          <Constellation key={constellation.id} constellation={constellation} />
        )) : null}

        {stars.map((star) => {
          const point = project(star.altitude, star.azimuth)
          const active = star.id === selectedObjectId
          const radius = Math.max(.42, Math.min(1.35, 1.7 - (star.mag || 2) * .25))
          return (
            <g
              key={star.id}
              role="button"
              tabIndex="0"
              className="sky-object-button"
              aria-label={`${star.name}، ${star.typeLabel}`}
              onClick={() => onSelectObject?.(star.id)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') onSelectObject?.(star.id)
              }}
            >
              <circle cx={point.x} cy={point.y} r={active ? radius + .9 : radius} fill={active ? '#fde68a' : '#dbeafe'} opacity={active ? 1 : .78} />
            </g>
          )
        })}

        {prominentObjects.map((object) => {
          const point = project(object.altitude, object.azimuth)
          const active = object.id === selectedObjectId
          const isSun = object.type === 'sun'
          const isMoon = object.type === 'moon'
          const size = isSun ? 2.8 : isMoon ? 2.25 : 1.85
          return (
            <g
              key={object.id}
              role="button"
              tabIndex="0"
              className="sky-object-button"
              aria-label={`${object.name}، ${object.typeLabel}`}
              onClick={() => onSelectObject?.(object.id)}
              onKeyDown={(event) => {
                if (event.key === 'Enter' || event.key === ' ') onSelectObject?.(object.id)
              }}
            >
              <circle cx={point.x} cy={point.y} r={active ? size + 1.15 : size} fill={object.color || '#7dd3fc'} opacity={object.altitude > 0 ? 1 : .45} filter="url(#softObjectGlow)" />
              <circle cx={point.x - size / 3} cy={point.y - size / 3} r={Math.max(.35, size / 3)} fill="#fff" opacity=".48" />
              <text x={point.x} y={point.y + size + 4.2} textAnchor="middle" fill={active ? '#fde68a' : '#cbd5e1'} fontSize="2.35" fontWeight={active ? '700' : '500'}>
                {object.name}
              </text>
            </g>
          )
        })}

        <text x="50" y="8" textAnchor="middle" className="sky-compass-label">شمال</text>
        <text x="93" y="51" textAnchor="middle" className="sky-compass-label">شرق</text>
        <text x="50" y="95" textAnchor="middle" className="sky-compass-label">جنوب</text>
        <text x="7" y="51" textAnchor="middle" className="sky-compass-label">غرب</text>
        <text x="50" y="50.9" textAnchor="middle" className="sky-zenith-label">سمت‌الرأس</text>
      </svg>

      <div className="sky-map-readout">
        <span className="text-[10px] text-slate-500">جرم انتخاب‌شده</span>
        <strong>{selected?.name || 'جرمی انتخاب نشده'}</strong>
        {selected ? (
          <p>
            {selected.typeLabel}، ارتفاع {toFaNumber(selected.altitude.toFixed(1))} درجه، جهت {directionLabel(selected.azimuth)}.
            {' '}{altitudeLabel(selected.altitude)}
          </p>
        ) : <p>برای دیدن جزئیات، یکی از نقاط آسمان را انتخاب کنید.</p>}
      </div>

      <div className="sky-map-actions">
        <button type="button" className="icon-button" aria-label="بازنشانی انتخاب نقشه" onClick={onResetSelection}>
          <RotateCcw className="h-4 w-4" />
        </button>
        <button type="button" className="icon-button" aria-label="نمای تمام‌صفحه نقشه" onClick={onFullscreen}>
          <Maximize2 className="h-4 w-4" />
        </button>
      </div>

      <div className="sky-map-note">موقعیت‌ها تقریبی و برای راهنمای رصد عمومی هستند.</div>
    </div>
  )
}

function Constellation({ constellation }) {
  const points = constellation.points
    .filter((star) => star.altitude > -5)
    .map((star) => ({ ...star, point: project(star.altitude, star.azimuth) }))

  if (points.length < 2) return null
  const first = points[0]

  return (
    <g opacity={constellation.visible ? .9 : .42}>
      <polyline
        points={points.map((star) => `${star.point.x},${star.point.y}`).join(' ')}
        fill="none"
        stroke={constellation.visible ? 'rgba(147,197,253,.62)' : 'rgba(100,116,139,.58)'}
        strokeWidth=".28"
      />
      {points.map((star) => <circle key={star.id} cx={star.point.x} cy={star.point.y} r=".7" fill="#bfdbfe" />)}
      <text x={first.point.x} y={first.point.y - 3} textAnchor="middle" fill="#93c5fd" fontSize="2.2">
        {constellation.name}
      </text>
    </g>
  )
}

function project(altitude, azimuth) {
  const radius = Math.max(0, Math.min(1, (90 - altitude) / 90)) * 44
  const angle = azimuth * DEG
  return {
    x: 50 + Math.sin(angle) * radius,
    y: 50 - Math.cos(angle) * radius,
  }
}
