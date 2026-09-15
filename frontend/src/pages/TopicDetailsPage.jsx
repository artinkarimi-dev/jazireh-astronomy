import { useEffect, useState } from 'react'
import { ArrowLeft, BookOpen, CalendarDays, ImageIcon, Loader2, Newspaper, Orbit, Video } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { formatDate } from '../lib/utils'

const fallbackTopic = {
  slug: 'moon',
  title: 'ماه',
  eyebrow: 'Moon',
  description: 'فازها، رصد، ماموریت‌ها و داده‌های محاسباتی ماه در جزیره.',
  accent: '#c4b5fd',
  featuredRoute: '/sky',
  featuredLabel: 'آسمان امشب',
  related: { news: [], videos: [], apod: [], events: [], objects: [] }
}

export default function TopicDetailsPage() {
  const { slug } = useParams()
  const [topic, setTopic] = useState({ ...fallbackTopic, slug: slug || fallbackTopic.slug })
  const [state, setState] = useState('loading')

  usePageMeta(topic.title ? `پرونده ${topic.title}` : 'پرونده علمی', topic.description || 'پرونده علمی جزیره.')

  useEffect(() => {
    let active = true
    setState('loading')
    api.get(`/api/topics/${encodeURIComponent(slug || '')}`).then((response) => {
      if (!active) return
      setTopic(normalizeTopic(response.data))
      setState('ready')
    }).catch((error) => {
      if (!active) return
      setState(error.status === 404 ? 'not-found' : 'error')
    })
    return () => { active = false }
  }, [slug])

  if (state === 'not-found') {
    return (
      <>
        <PageHero eyebrow="پرونده پیدا نشد" title="این مسیر موضوعی هنوز آماده نیست" description="از فهرست پرونده‌های علمی، یکی از موضوع‌های فعال جزیره را انتخاب کنید." />
        <section className="content-shell section-space">
          <Link to="/topics" className="primary-btn w-fit"><ArrowLeft className="h-4 w-4" />بازگشت به پرونده‌ها</Link>
        </section>
      </>
    )
  }

  const related = topic.related || {}
  const counts = [
    ['خبر', related.news?.length || 0],
    ['ویدیو', related.videos?.length || 0],
    ['تصویر روز', related.apod?.length || 0],
    ['رویداد', related.events?.length || 0],
  ]

  return (
    <>
      <PageHero
        eyebrow={topic.eyebrow || 'Topic Hub'}
        title={`پرونده ${topic.title || 'علمی'}`}
        description={topic.description || 'محتوا و ابزارهای مرتبط جزیره در یک مسیر موضوعی.'}
      >
        <Link to="/topics" className="secondary-btn"><BookOpen className="h-4 w-4" />همه پرونده‌ها</Link>
        {topic.featuredRoute ? <Link to={topic.featuredRoute} className="primary-btn"><ArrowLeft className="h-4 w-4" />{topic.featuredLabel || 'مشاهده ابزار مرتبط'}</Link> : null}
      </PageHero>

      <section className="content-shell section-space">
        {state === 'loading' ? (
          <div className="surface-card flex min-h-[280px] items-center justify-center p-8">
            <Loader2 className="h-8 w-8 animate-spin text-sky-200" />
          </div>
        ) : (
          <div className="topic-detail-layout">
            <aside className="surface-card topic-detail-summary">
              <span className="eyebrow">وضعیت پرونده</span>
              <h2>{topic.title}</h2>
              <p>این صفحه داده‌های موجود در خود جزیره را تجمیع می‌کند؛ اگر منبعی در دسترس نباشد، بخش مربوطه بدون داده جعلی نمایش داده می‌شود.</p>
              <div className="topic-stat-grid">
                {counts.map(([label, value]) => <span key={label}><strong>{value.toLocaleString('fa-IR')}</strong>{label}</span>)}
              </div>
            </aside>

            <div className="topic-related-stack">
              {state === 'error' ? <Notice text="دریافت داده تازه این پرونده ممکن نبود؛ ساختار صفحه آماده است و با بازگشت API کامل می‌شود." /> : null}
              <RelatedSection title="اخبار مرتبط" icon={Newspaper} items={related.news} empty="هنوز خبر مرتبطی برای این پرونده ثبت نشده است." />
              <RelatedSection title="ویدیوهای مرتبط" icon={Video} items={related.videos} empty="ویدیوی مرتبطی برای نمایش پیدا نشد." />
              <RelatedSection title="تصویرهای روز مرتبط" icon={ImageIcon} items={related.apod} empty="تصویر روز مرتبطی در آرشیو فعلی نیست." />
              <RelatedSection title="رویدادها و اجرام مرتبط" icon={CalendarDays} items={[...(related.events || []), ...(related.objects || [])]} empty="رویداد یا جرم مرتبطی برای این پرونده پیدا نشد." />
            </div>
          </div>
        )}
      </section>
    </>
  )
}

function RelatedSection({ title, icon: Icon, items, empty }) {
  const source = Array.isArray(items) ? items.slice(0, 6) : []
  return (
    <section className="surface-card topic-related-section">
      <div className="topic-related-head">
        <span className="eyebrow"><Icon className="h-4 w-4" />{title}</span>
        <span className="state-pill">{source.length.toLocaleString('fa-IR')} مورد</span>
      </div>
      {source.length ? (
        <div className="topic-related-grid">
          {source.map((item) => <RelatedCard key={`${item.type || title}-${item.id || item.slug || item.title}`} item={item} />)}
        </div>
      ) : <Notice text={empty} />}
    </section>
  )
}

function RelatedCard({ item }) {
  const to = item.url || (item.slug ? `/news/${item.slug}` : '')
  const isInternal = to && to.startsWith('/')
  const content = (
    <article className="topic-related-card">
      <span>{item.typeLabel || item.category || item.provider || 'جزیره'}</span>
      <h3>{item.title || item.name}</h3>
      {item.excerpt || item.description || item.summary ? <p>{item.excerpt || item.description || item.summary}</p> : null}
      {item.publishedAt || item.date ? <small>{formatDate(item.publishedAt || item.date)}</small> : null}
      {item.type === 'objects' ? <Orbit className="topic-related-orbit h-5 w-5" /> : null}
    </article>
  )
  if (!to) return content
  return isInternal ? <Link to={to} className="block h-full">{content}</Link> : <a href={to} target="_blank" rel="noopener noreferrer" className="block h-full">{content}</a>
}

function Notice({ text }) {
  return <div className="topic-empty-state">{text}</div>
}

function normalizeTopic(data) {
  const payload = data && typeof data === 'object' ? data : {}
  return {
    ...fallbackTopic,
    ...payload,
    related: {
      news: Array.isArray(payload.related?.news) ? payload.related.news : [],
      videos: Array.isArray(payload.related?.videos) ? payload.related.videos : [],
      apod: Array.isArray(payload.related?.apod) ? payload.related.apod : [],
      events: Array.isArray(payload.related?.events) ? payload.related.events : [],
      objects: Array.isArray(payload.related?.objects) ? payload.related.objects : []
    }
  }
}
