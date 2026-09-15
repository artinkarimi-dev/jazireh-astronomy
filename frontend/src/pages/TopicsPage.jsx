import { useEffect, useState } from 'react'
import { ArrowLeft, BookOpen, Loader2, Moon, Orbit, Sun } from 'lucide-react'
import { Link } from 'react-router-dom'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'

const fallbackTopics = [
  { slug: 'moon', title: 'ماه', eyebrow: 'Moon', description: 'فازها، رصد، ماموریت‌ها و داده‌های محاسباتی ماه در جزیره.', accent: '#c4b5fd', featuredRoute: '/sky' },
  { slug: 'sun', title: 'خورشید', eyebrow: 'Sun', description: 'خورشید اکنون، تصویرهای رصدی معتبر و توضیح علمی فعالیت‌های خورشیدی.', accent: '#f6c84f', featuredRoute: '/' },
  { slug: 'mars', title: 'مریخ', eyebrow: 'Mars', description: 'ماموریت‌ها، سطح مریخ، شرایط رصد و خبرهای سیاره سرخ.', accent: '#fb923c', featuredRoute: '/explore' },
  { slug: 'james-webb', title: 'جیمز وب', eyebrow: 'JWST', description: 'رصدهای فروسرخ، کهکشان‌های دور و تصویرهای علمی تلسکوپ فضایی جیمز وب.', accent: '#7dd3fc', featuredRoute: '/apod' },
  { slug: 'earth', title: 'زمین', eyebrow: 'Earth', description: 'زمین از فضا، رخدادهای زمین‌لرزه و ارتباط سیاره ما با رصد آسمان.', accent: '#38bdf8', featuredRoute: '/sky' },
]

export default function TopicsPage() {
  const [topics, setTopics] = useState(fallbackTopics)
  const [state, setState] = useState('loading')

  usePageMeta('پرونده‌های علمی', 'پرونده‌های موضوعی جزیره برای ماه، خورشید، مریخ، جیمز وب و زمین.')

  useEffect(() => {
    let active = true
    setState('loading')
    api.get('/api/topics').then((response) => {
      if (!active) return
      const items = Array.isArray(response.data) ? response.data : (Array.isArray(response.data?.topics) ? response.data.topics : [])
      if (items.length) setTopics(items)
      setState('ready')
    }).catch(() => {
      if (active) setState('stale')
    })
    return () => { active = false }
  }, [])

  return (
    <>
      <PageHero
        eyebrow="پرونده‌های جزیره"
        title="مسیرهای موضوعی برای دنبال‌کردن علم"
        description="هر پرونده، خبرها، ویدیوها، تصویرهای روز، رویدادها و ابزارهای مرتبط جزیره را حول یک موضوع علمی کنار هم می‌آورد."
      >
        <span className="secondary-btn"><BookOpen className="h-4 w-4" />پرونده‌های زنده</span>
        <span className="secondary-btn"><Orbit className="h-4 w-4" />متصل به داده‌های سایت</span>
      </PageHero>

      <section className="content-shell section-space">
        <div className="topics-status-row">
          <span>{state === 'loading' ? 'در حال آماده‌سازی پرونده‌ها...' : state === 'stale' ? 'نمایش نسخه پشتیبان پرونده‌ها' : 'پرونده‌ها با داده‌های فعلی جزیره آماده‌اند.'}</span>
          <span className={`state-pill state-${state === 'loading' ? 'loading' : state === 'stale' ? 'stale' : 'ready'}`}>
            {state === 'loading' ? 'در حال دریافت' : state === 'stale' ? 'پشتیبان' : `${topics.length} پرونده`}
          </span>
        </div>

        {state === 'loading' ? (
          <div className="surface-card flex min-h-[260px] items-center justify-center p-8">
            <Loader2 className="h-8 w-8 animate-spin text-sky-200" />
          </div>
        ) : (
          <div className="topics-grid">
            {topics.map((topic) => <TopicCard key={topic.slug} topic={topic} />)}
          </div>
        )}
      </section>
    </>
  )
}

function TopicCard({ topic }) {
  const Icon = topic.slug === 'moon' ? Moon : topic.slug === 'sun' ? Sun : Orbit
  return (
    <Link to={`/topics/${topic.slug}`} className="topic-card surface-card">
      <div className="topic-card-glow" style={{ '--topic-accent': topic.accent || '#7dd3fc' }} />
      <div className="topic-card-head">
        <span className="topic-icon" style={{ '--topic-accent': topic.accent || '#7dd3fc' }}><Icon className="h-5 w-5" /></span>
        <span className="eyebrow">{topic.eyebrow || 'Topic'}</span>
      </div>
      <h2>{topic.title}</h2>
      <p>{topic.description}</p>
      <span className="topic-card-link">باز کردن پرونده <ArrowLeft className="h-4 w-4" /></span>
    </Link>
  )
}
