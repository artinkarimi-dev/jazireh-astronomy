import { ArrowLeft, Clock3, Eye, Moon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { skyData } from '../../data/fallback'

export default function SkyPreview({ data = skyData, content = {} }) {
  return (
    <article className="surface-card home-feature-card accent-blue p-5 sm:p-6">
      <div><span className="eyebrow">{content.sky_eyebrow || 'رصد امشب'}</span><h3 className="mt-2 card-title">{content.sky_title || 'آسمان امروز'}</h3></div>
      <div className="mt-5 grid flex-1 grid-cols-2 gap-3">
        <Mini icon={Clock3} label="بهترین بازه" value={data.bestTime} />
        <Mini icon={Moon} label="فاز ماه" value={data.moonPhase} />
        <Mini icon={Eye} label={`شفافیت`} value={`${data.transparency} از ۱۰`} wide />
      </div>
      <Link to="/sky" className="secondary-btn mt-5 w-full">{content.sky_cta || 'جزئیات آسمان'} <ArrowLeft className="h-4 w-4" /></Link>
    </article>
  )
}

function Mini({ icon: Icon, label, value, wide }) {
  return <div className={`metric-tile ${wide ? 'col-span-2' : ''}`}><Icon className="h-4 w-4 text-sky-300" /><span className="mt-3 block text-[10px] text-slate-500">{label}</span><strong className="mt-1 block text-xs text-white">{value}</strong></div>
}
