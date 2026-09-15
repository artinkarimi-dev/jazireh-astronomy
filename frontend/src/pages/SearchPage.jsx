import { useEffect, useMemo, useState } from 'react'
import { ArrowLeft, BookOpen, ImageIcon, Loader2, Newspaper, Orbit, Search, Sparkles, Video, X } from 'lucide-react'
import { Link } from 'react-router-dom'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { api } from '../lib/api'
import { formatDate, normalizePersianText } from '../lib/utils'

const groupOrder = [
  ['news', 'اخبار', Newspaper],
  ['topics', 'پرونده‌های علمی', BookOpen],
  ['videos', 'ویدیوها', Video],
  ['apod', 'عکس روز ناسا', ImageIcon],
  ['daily', 'جزیره دیلی', Sparkles],
  ['objects', 'اجرام', Orbit],
  ['events', 'رویدادها', Sparkles],
]

const emptyGroups = Object.fromEntries(groupOrder.map(([key, label]) => [key, { type: key, label, total: 0, items: [] }]))

export default function SearchPage() {
  const [query, setQuery] = useState('')
  const [payload, setPayload] = useState({ query: '', total: 0, groups: emptyGroups, results: [] })
  const [state, setState] = useState('idle')

  usePageMeta('جستجو', 'جستجو در اخبار، ویدیوها، تصویر روز ناسا، جزیره دیلی، اجرام و رویدادهای نجومی.')

  const normalizedQuery = useMemo(() => normalizePersianText(query), [query])

  useEffect(() => {
    if (normalizedQuery.length < 2) {
      setPayload({ query, total: 0, groups: emptyGroups, results: [] })
      setState('idle')
      return undefined
    }

    const controller = new AbortController()
    const timer = window.setTimeout(() => {
      setState('loading')
      api.get(`/api/search?q=${encodeURIComponent(query)}&per_page=20`, { signal: controller.signal }).then((response) => {
        setPayload({
          query,
          total: response.data?.total || 0,
          groups: { ...emptyGroups, ...(response.data?.groups || {}) },
          results: Array.isArray(response.data?.results) ? response.data.results : []
        })
        setState('ready')
      }).catch(() => {
        setPayload({ query, total: 0, groups: emptyGroups, results: [] })
        setState('error')
      })
    }, 260)

    return () => {
      window.clearTimeout(timer)
      controller.abort()
    }
  }, [normalizedQuery, query])

  const groups = payload.groups || emptyGroups
  const hasResults = Object.values(groups).some((group) => Array.isArray(group.items) && group.items.length)

  return (
    <>
      <PageHero
        eyebrow="جستجوی جزیره"
        title="جستجو در محتوای نجومی"
        description="خبرها، ویدیوها، تصویرهای ناسا، نوشته‌های روزانه، اجرام و رویدادهای نجومی را یکجا پیدا کنید."
      />

      <section className="content-shell section-space">
        <form className="surface-card news-filter-bar mb-6 p-4 sm:p-5" onSubmit={(event) => event.preventDefault()}>
          <label htmlFor="site-search" className="sr-only">عبارت جستجو</label>
          <div className={`search-field ${query ? 'has-clear' : ''}`}>
            <input
              id="site-search"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
              className="input-field search-input text-base"
              placeholder="مثلا ماه، مریخ، جیمز وب..."
              autoComplete="off"
            />
            <Search className="search-icon h-5 w-5" />
            {query ? (
              <button
                type="button"
                onClick={() => setQuery('')}
                className="search-input-clear"
                aria-label="پاک کردن جستجو"
              >
                <X className="h-4 w-4" />
              </button>
            ) : null}
          </div>
          <div className="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <span className={`state-pill state-${state === 'error' ? 'error' : state === 'loading' ? 'loading' : 'ready'}`}>
              {state === 'loading' ? 'در حال جستجو' : state === 'error' ? 'خطای جستجو' : normalizedQuery.length < 2 ? 'حداقل دو حرف وارد کنید' : `${payload.total || 0} نتیجه`}
            </span>
          </div>
        </form>

        {state === 'loading' ? (
          <div className="surface-card flex min-h-[260px] items-center justify-center p-8">
            <Loader2 className="h-8 w-8 animate-spin text-sky-200" />
          </div>
        ) : state === 'error' ? (
          <SearchEmpty title="جستجو در دسترس نیست" text="دریافت نتیجه از WordPress ممکن نبود. کمی بعد دوباره تلاش کنید." />
        ) : normalizedQuery.length < 2 ? (
          <SearchEmpty title="برای شروع جستجو کنید" text="عبارت فارسی یا انگلیسی مرتبط با نجوم را وارد کنید." />
        ) : hasResults ? (
          <div className="grid gap-5">
            {groupOrder.map(([key, fallbackLabel, Icon]) => (
              <SearchGroup key={key} group={groups[key]} fallbackLabel={fallbackLabel} icon={Icon} />
            ))}
          </div>
        ) : (
          <SearchEmpty title="نتیجه‌ای پیدا نشد" text="عبارت دیگری را امتحان کنید یا از واژه‌های کوتاه‌تر استفاده کنید." />
        )}
      </section>
    </>
  )
}

function SearchGroup({ group, fallbackLabel, icon: Icon }) {
  const items = Array.isArray(group?.items) ? group.items : []
  if (!items.length) return null

  return (
    <section className="surface-card p-5 sm:p-6">
      <div className="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <span className="eyebrow"><Icon className="h-4 w-4" />{group?.label || fallbackLabel}</span>
          <h2 className="mt-2 card-title">{items.length} نتیجه</h2>
        </div>
      </div>
      <div className="news-grid">
        {items.slice(0, 6).map((item) => <SearchResultCard key={`${item.type}-${item.id}`} item={item} />)}
      </div>
    </section>
  )
}

function SearchResultCard({ item }) {
  const to = item.type === 'events' ? '/events' : item.url || '/'
  const isInternal = to.startsWith('/')
  const content = (
    <article className="surface-card news-card p-5 sm:p-6">
      <div className="flex items-start justify-between gap-3">
        <span className="event-category">{item.typeLabel || 'نتیجه'}</span>
        {item.publishedAt ? <span className="state-pill">{formatDate(item.publishedAt)}</span> : null}
      </div>
      <h3 className="mt-5 text-lg font-black leading-8 text-white">{item.title}</h3>
      {item.excerpt ? <p className="mt-3 line-clamp-3 text-sm leading-7 text-slate-400">{item.excerpt}</p> : null}
      <span className="mt-auto flex items-center gap-1 pt-5 text-xs text-amber-200">
        مشاهده <ArrowLeft className="h-3.5 w-3.5" />
      </span>
    </article>
  )

  return isInternal ? (
    <Link to={to} className="block h-full">{content}</Link>
  ) : (
    <a href={to} target="_blank" rel="noopener noreferrer" className="block h-full">{content}</a>
  )
}

function SearchEmpty({ title, text }) {
  return (
    <div className="surface-card flex min-h-[280px] flex-col items-center justify-center p-8 text-center">
      <Search className="h-10 w-10 text-slate-600" />
      <h2 className="mt-4 text-xl font-bold text-white">{title}</h2>
      <p className="mt-3 max-w-xl text-sm leading-8 text-slate-500">{text}</p>
    </div>
  )
}
