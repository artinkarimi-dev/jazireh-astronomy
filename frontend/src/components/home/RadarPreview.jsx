import { ArrowLeft } from 'lucide-react'
import { Link } from 'react-router-dom'
import SkyRadar from '../radar/SkyRadar'

export default function RadarPreview() {
  return (
    <article className="glass-panel min-h-[300px] rounded-3xl p-6">
      <div className="flex items-start justify-between"><div><h3 className="card-title">رادار آسمان</h3><p className="mt-1 text-xs text-slate-500">ردیابی زنده اجرام و ماهواره‌ها</p></div><Link to="/radar" className="icon-button"><ArrowLeft className="h-4 w-4" /></Link></div>
      <div className="mt-3 flex justify-center"><SkyRadar compact /></div>
    </article>
  )
}
