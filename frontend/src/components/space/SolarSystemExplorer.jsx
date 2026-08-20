const positions = [
  { top: '47%', left: '59%' },
  { top: '35%', left: '65%' },
  { top: '27%', left: '49%' },
  { top: '33%', left: '34%' },
  { top: '52%', left: '24%' },
  { top: '68%', left: '35%' },
  { top: '72%', left: '58%' },
  { top: '54%', left: '76%' }
]

export default function SolarSystemExplorer({ planets, selected, onSelect }) {
  return (
    <div className="solar-explorer" role="group" aria-label="نمای تعاملی منظومه شمسی">
      <div className="solar-starfield" />
      {[22, 33, 44, 56, 68, 80].map((size) => <span key={size} className="solar-orbit" style={{ width: `${size}%`, height: `${size}%` }} />)}
      <span className="solar-center" aria-hidden="true" />
      {planets.map((planet, index) => (
        <button
          type="button"
          key={planet.id}
          onClick={() => onSelect(planet)}
          aria-label={`انتخاب ${planet.name}`}
          className={`solar-planet ${selected?.id === planet.id ? 'solar-planet-active' : ''}`}
          data-active={selected?.id === planet.id ? 'true' : 'false'}
          style={{ ...positions[index % positions.length], '--planet-color': planet.color, '--planet-size': `${Math.max(15, Math.min(34, planet.size * 22))}px` }}
        >
          <span className="solar-planet-dot" />
          <span className="solar-planet-name">{planet.name}</span>
        </button>
      ))}
      <div className="solar-explorer-note">برای مشاهده اطلاعات، یک سیاره را انتخاب کنید</div>
    </div>
  )
}
