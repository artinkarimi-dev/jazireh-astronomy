import { Satellite, Star } from 'lucide-react'

const targets = [
  { top: '24%', left: '63%', label: 'ISS', type: 'satellite' },
  { top: '52%', left: '28%', label: 'HST', type: 'satellite' },
  { top: '67%', left: '70%', label: 'NOAA 18', type: 'satellite' },
  { top: '35%', left: '38%', label: 'مشتری', type: 'star' },
  { top: '73%', left: '45%', label: 'زهره', type: 'star' }
]

export default function SkyRadar({ compact = false }) {
  const size = compact ? 'h-[210px] w-[210px]' : 'aspect-square w-full max-w-[420px]'
  return (
    <div className={`relative ${size} rounded-full border border-blue-400/20 bg-[#031020] radar-grid shadow-[inset_0_0_50px_rgba(59,130,246,.08),0_0_45px_rgba(59,130,246,.08)]`}>
      <div className="radar-sweep absolute inset-0 animate-radar rounded-full opacity-75" />
      <div className="absolute inset-[8%] rounded-full border border-blue-300/10" />
      <div className="absolute left-1/2 top-1/2 h-2 w-2 -translate-x-1/2 -translate-y-1/2 rounded-full bg-blue-200 shadow-[0_0_20px_rgba(147,197,253,.9)]" />
      {targets.map((target) => {
        const Icon = target.type === 'satellite' ? Satellite : Star
        return <div key={target.label} className="group absolute -translate-x-1/2 -translate-y-1/2" style={{ top: target.top, left: target.left }}><span className="relative flex h-3 w-3 items-center justify-center rounded-full bg-blue-300 shadow-[0_0_14px_rgba(96,165,250,.9)]"><span className="absolute h-6 w-6 animate-ping rounded-full border border-blue-300/35" /></span>{!compact && <span className="pointer-events-none absolute right-4 top-1/2 hidden -translate-y-1/2 items-center gap-1 whitespace-nowrap rounded-lg border border-white/10 bg-space-950/90 px-2 py-1 text-[10px] text-slate-200 group-hover:flex"><Icon className="h-3 w-3 text-blue-300" />{target.label}</span>}</div>
      })}
    </div>
  )
}
