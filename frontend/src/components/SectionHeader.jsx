import { ArrowLeft } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function SectionHeader({ eyebrow, title, description, link, linkLabel = 'مشاهده همه' }) {
  return (
    <div className="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div><span className="eyebrow">{eyebrow}</span><h2 className="section-title mt-2">{title}</h2>{description && <p className="muted mt-3 max-w-2xl">{description}</p>}</div>
      {link && <Link className="secondary-btn w-fit" to={link}>{linkLabel}<ArrowLeft className="h-4 w-4" /></Link>}
    </div>
  )
}
