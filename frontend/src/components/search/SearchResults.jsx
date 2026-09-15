import { ArrowLeft, FileSearch, Image, Newspaper, Play, Sparkles, Telescope } from 'lucide-react'
import { Link } from 'react-router-dom'
import { formatDate } from '../../lib/utils'

const groupOrder = ['news', 'videos', 'apod', 'daily', 'objects']

const groupMeta = {
  news: { label: 'اخبار علمی', icon: Newspaper, accent: 'accent-orange' },
  videos: { label: 'ویدیوها', icon: Play, accent: 'accent-red' },
  apod: { label: 'تصویر روز ناسا', icon: Image, accent: 'accent-yellow' },
  daily: { label: 'جزیره دیلی', icon: Sparkles, accent: 'accent-purple' },
  objects: { label: 'اجرام آسمانی', icon: Telescope, accent: 'accent-blue' },
}

export function SearchLoading() {
  return (
    <div className="surface-card flex min-h-[260px] items-center justify-center p-8">
      <div className="h-9 w-9 animate-spin rounded-full border-2 border-white/10 border-t-amber-200" />
    </div>
  )
}

export function SearchEmpty({ hasQuery }) {
  return (
    <div className="surface-card flex min-h-[280px] flex-col items-center justify-center p-8 text-center">
      <FileSearch className="h-10 w-10 text-slate-600" />
      <h2 className="mt-4 text-xl font-black text-white">{hasQuery ? 'نتیجه‌ای پیدا نشد' : 'جستجوی علمی جزیره'}</h2>
      <p className="mt-3 max-w-xl text-sm leading-8 text-slate-500">
        {hasQuery ? 'عبارت دیگری را امتحان کنید یا شکل نوشتاری کلمات فارسی را ساده‌تر وارد کنید.' : 'یک عبارت فارسی یا انگلیسی وارد کنید تا در محتوای اصلی پورتال جستجو شود.'}
      </p>
    </div>
  )
}

export function SearchError() {
  return (
    <div className="surface-card accent-red flex min-h-[240px] flex-col items-center justify-center p-8 text-center">
      <FileSearch className="h-10 w-10 text-red-300" />
      <h2 className="mt-4 text-xl font-black text-white">جستجو در دسترس نیست</h2>
      <p className="mt-3 max-w-xl text-sm leading-8 text-slate-500">ارتباط با سرویس جستجو برقرار نشد. کمی بعد دوباره تلاش کنید.</p>
    </div>
  )
}

export default function SearchResults({ data }) {
  const groups = data?.groups || {}
  const visibleGroups = groupOrder.map((key) => groups[key]).filter((group) => group?.items?.length)

  if (!visibleGroups.length) return <SearchEmpty hasQuery={Boolean(data?.query)} />

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500">
        <span>{data.total?.toLocaleString('fa-IR') || '۰'} نتیجه برای «{data.query}»</span>
        {data.hasMore && <span>نمایش بخشی از نتایج</span>}
      </div>

      {visibleGroups.map((group) => {
        const meta = groupMeta[group.type] || { label: group.label, icon: FileSearch, accent: '' }
        const Icon = meta.icon
        return (
          <section key={group.type} className="space-y-3" aria-labelledby={`search-group-${group.type}`}>
            <div className="flex items-center justify-between gap-3">
              <h2 id={`search-group-${group.type}`} className="flex items-center gap-2 text-lg font-black text-white">
                <Icon className="h-5 w-5 text-amber-200" />
                {meta.label}
              </h2>
              <span className="rounded-full border border-white/[.08] bg-white/[.03] px-3 py-1 text-xs text-slate-500">{group.total.toLocaleString('fa-IR')} مورد</span>
            </div>
            <div className="grid grid-cols-1 gap-3 lg:grid-cols-2">
              {group.items.map((item) => <SearchResultCard key={`${item.type}-${item.id}-${item.url}`} item={item} accent={meta.accent} />)}
            </div>
          </section>
        )
      })}
    </div>
  )
}

function SearchResultCard({ item, accent }) {
  const external = !item.url?.startsWith('/')
  const content = (
    <article className={`surface-card ${accent} flex h-full gap-4 p-4 transition hover:border-amber-200/25 hover:bg-white/[.035]`}>
      {item.thumbnail ? (
        <img src={item.thumbnail} alt="" className="h-20 w-24 shrink-0 rounded-2xl border border-white/[.08] object-cover sm:h-24 sm:w-32" loading="lazy" decoding="async" />
      ) : (
        <div className="flex h-20 w-24 shrink-0 items-center justify-center rounded-2xl border border-white/[.08] bg-white/[.025] text-slate-600 sm:h-24 sm:w-32">
          <FileSearch className="h-6 w-6" />
        </div>
      )}
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <span className="rounded-full bg-white/[.05] px-2.5 py-1 text-[11px] font-bold text-amber-100">{item.typeLabel || item.type}</span>
          {item.publishedAt && <span className="text-[11px] text-slate-600">{formatDate(item.publishedAt)}</span>}
        </div>
        <h3 className="mt-3 line-clamp-2 text-base font-black leading-7 text-white">{item.title}</h3>
        <p className="mt-2 line-clamp-2 text-xs leading-6 text-slate-500">{item.excerpt}</p>
        <span className="mt-3 inline-flex items-center gap-1 text-xs font-bold text-amber-200">
          مشاهده
          <ArrowLeft className="h-3.5 w-3.5" />
        </span>
      </div>
    </article>
  )

  if (external) {
    return <a href={item.url} target="_blank" rel="noopener noreferrer">{content}</a>
  }
  return <Link to={item.url || '/'}>{content}</Link>
}
