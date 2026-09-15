import { Moon, Orbit, Sparkles, Telescope } from 'lucide-react'

const iconMap = {
  'best-window': Telescope,
  'best-planet': Orbit,
  'moon-interference': Moon,
  'next-event': Sparkles,
}

export default function TonightHighlights({ highlights, state = 'ready' }) {
  const items = Array.isArray(highlights) ? highlights : []

  return (
    <section className="tonight-highlights">
      <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <span className="eyebrow">خلاصه امشب</span>
          <h2 className="mt-2 card-title">امشب چه چیزی ارزش رصد دارد؟</h2>
        </div>
        <span className={`state-pill state-${state === 'error' ? 'error' : state === 'loading' ? 'loading' : 'ready'}`}>
          {state === 'loading' ? 'در حال تحلیل' : state === 'error' ? 'محاسبه محلی' : 'راهنمای رصدی برآوردی'}
        </span>
      </div>

      <div className="highlights-grid">
        {(items.length ? items : fallbackHighlights).slice(0, 4).map((item) => {
          const Icon = iconMap[item.id] || Sparkles
          return (
            <article key={item.id} className="highlight-card">
              <Icon className="h-5 w-5 text-amber-200" />
              <span>{item.title}</span>
              <strong>{item.value}</strong>
              <p>{item.summary}</p>
            </article>
          )
        })}
      </div>
    </section>
  )
}

const fallbackHighlights = [
  { id: 'best-window', title: 'بهترین پنجره رصد', value: 'امشب', summary: 'پس از دریافت داده تازه، زمان پیشنهادی دقیق‌تر نمایش داده می‌شود.' },
  { id: 'best-planet', title: 'بهترین سیاره', value: 'در انتظار داده', summary: 'پس از دریافت داده معتبر، سیاره پیشنهادی نمایش داده می‌شود.' },
  { id: 'moon-interference', title: 'اثر نور ماه', value: 'برآوردی', summary: 'روشنایی ماه در انتخاب هدف رصدی اثر دارد.' },
  { id: 'next-event', title: 'رویداد بعدی', value: 'در انتظار داده', summary: 'رویدادهای آینده پس از دریافت داده معتبر نمایش داده می‌شوند.' },
]
