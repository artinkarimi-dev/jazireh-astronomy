import { useMemo, useState } from 'react'
import { Maximize2, RotateCcw } from 'lucide-react'

const constellations = [
  { name: 'دب اکبر', points: [[19,30],[27,27],[35,31],[42,38],[51,35],[61,37],[70,32]] },
  { name: 'ذات‌الکرسی', points: [[62,20],[68,15],[74,22],[80,16],[86,23]] },
  { name: 'جبار', points: [[38,57],[44,48],[50,58],[46,69],[52,76],[58,67],[54,55]] },
  { name: 'ثور', points: [[65,55],[72,49],[78,54],[72,59],[82,66]] },
  { name: 'اسد', points: [[20,66],[26,60],[32,63],[35,71],[29,77],[22,75]] }
]

export default function InteractiveSkyMap({ className = '' }) {
  const [selected, setSelected] = useState('جبار')
  const stars = useMemo(() => Array.from({ length: 90 }, (_, i) => ({ x: (i * 37.7) % 96 + 2, y: (i * 53.3) % 90 + 4, r: i % 11 === 0 ? 1.6 : i % 4 === 0 ? 1.05 : .65, o: .35 + (i % 6) * .1 })), [])
  return (
    <div className={`relative overflow-hidden rounded-3xl border border-white/10 bg-[#020b19] ${className}`}>
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_50%_100%,rgba(37,99,235,.18),transparent_55%)]" />
      <svg viewBox="0 0 100 80" className="relative h-full min-h-[420px] w-full" role="img" aria-label="نقشه تعاملی صورت‌های فلکی">
        <defs><radialGradient id="starGlow"><stop offset="0" stopColor="#fff" /><stop offset=".3" stopColor="#bfdbfe" /><stop offset="1" stopColor="#60a5fa" stopOpacity="0" /></radialGradient></defs>
        {stars.map((s, i) => <circle key={i} cx={s.x} cy={s.y} r={s.r} fill="#dbeafe" opacity={s.o} />)}
        {constellations.map((c) => {
          const active = selected === c.name
          return <g key={c.name} onClick={() => setSelected(c.name)} className="cursor-pointer">
            <polyline points={c.points.map((p) => p.join(',')).join(' ')} fill="none" stroke={active ? '#93c5fd' : '#334155'} strokeWidth={active ? '.35' : '.18'} opacity={active ? .95 : .65} />
            {c.points.map((p, i) => <g key={i}><circle cx={p[0]} cy={p[1]} r={active ? 1.8 : 1.3} fill="url(#starGlow)" /><circle cx={p[0]} cy={p[1]} r={active ? .45 : .28} fill="#fff" /></g>)}
            <text x={c.points[0][0]} y={c.points[0][1]-3} fill={active ? '#bfdbfe' : '#64748b'} fontSize="2.2" textAnchor="middle">{c.name}</text>
          </g>
        })}
        <path d="M2 78 Q50 63 98 78" fill="#020814" opacity=".9" />
      </svg>
      <div className="absolute right-5 top-5 rounded-xl border border-blue-400/20 bg-space-950/80 px-4 py-3 backdrop-blur"><span className="text-[10px] text-slate-500">صورت فلکی انتخاب‌شده</span><strong className="mt-1 block text-sm text-blue-200">{selected}</strong></div>
      <div className="absolute bottom-5 left-5 flex gap-2"><button className="icon-button"><RotateCcw className="h-4 w-4" /></button><button className="icon-button"><Maximize2 className="h-4 w-4" /></button></div>
      <div className="absolute bottom-5 right-5 flex gap-5 text-xs text-slate-500"><span>شرق</span><span className="text-blue-300">جنوب</span><span>غرب</span></div>
    </div>
  )
}
