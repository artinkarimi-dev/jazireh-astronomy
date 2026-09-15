import { BookOpen, Globe2, Satellite, Sparkles } from 'lucide-react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { useSiteSettings } from '../context/SiteSettingsContext'

const pillars = [
  { title: 'رسانه علمی فارسی', text: 'جزیره نجوم خبر، ویدیو، تصویر روز ناسا و روایت‌های آموزشی را برای مخاطب فارسی‌زبان کنار هم می‌آورد.', icon: BookOpen },
  { title: 'داده واقعی و شفاف', text: 'بخش‌های رصدی سایت فقط داده محاسباتی یا provider-backed معتبر را نمایش می‌دهند و وضعیت stale یا خطا را صریح اعلام می‌کنند.', icon: Satellite },
  { title: 'تجربه عمومی Phase 1', text: 'تمرکز نسخه فعلی روی آسمان امروز، APOD، اخبار علمی، ویدیوها، رویدادها، پرونده‌ها و کاوش منظومه شمسی است.', icon: Globe2 },
]

export default function AboutPage() {
  const { settings } = useSiteSettings()
  const description = settings.identity.description || 'رسانه‌ای فارسی برای روایت علمی نجوم، فضا و شگفتی‌های جهان.'

  usePageMeta('درباره جزیره نجوم', 'درباره ماموریت، دامنه نسخه فعلی و رویکرد داده‌ای جزیره نجوم.')

  return (
    <>
      <PageHero eyebrow="درباره جزیره" title="جزیره نجوم؛ روایت فارسی آسمان، فضا و علم" description={description}>
        <span className="secondary-btn"><Sparkles className="h-4 w-4" />نسخه عمومی Phase 1</span>
      </PageHero>

      <section className="content-shell section-space">
        <div className="grid gap-4 lg:grid-cols-3">
          {pillars.map(({ title, text, icon: Icon }) => (
            <article key={title} className="surface-card p-5 sm:p-6">
              <Icon className="h-6 w-6 text-amber-300" aria-hidden="true" />
              <h2 className="mt-4 text-xl font-black leading-8 text-white">{title}</h2>
              <p className="mt-3 text-sm leading-8 text-slate-400">{text}</p>
            </article>
          ))}
        </div>

        <div className="mt-5 grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(280px,.9fr)]">
          <section className="surface-card p-6 sm:p-8">
            <span className="eyebrow">ماموریت</span>
            <h2 className="mt-3 text-2xl font-black leading-10 text-white">یک تجربه قابل اعتماد برای دنبال کردن آسمان و علم</h2>
            <p className="mt-4 text-sm leading-8 text-slate-300">
              جزیره نجوم تلاش می‌کند میان محتوای editorial فارسی و داده‌های علمی قابل پیگیری پلی روشن بسازد: از وضعیت رصد آسمان و رویدادهای نجومی تا عکس روز ناسا، خبرهای علمی و ویدیوهای آموزشی کانال جزیره.
            </p>
            <p className="mt-4 text-sm leading-8 text-slate-400">
              هرجا داده زنده در دسترس نباشد، سایت باید همان را به کاربر بگوید؛ نه اینکه مقدار ساختگی یا بدون منبع را به جای داده واقعی نمایش دهد.
            </p>
          </section>

          <aside className="surface-card p-6 sm:p-8">
            <span className="eyebrow">دامنه فعلی</span>
            <ul className="mt-4 space-y-3 text-sm leading-8 text-slate-300">
              <li>آسمان امروز و وضعیت رصد برای تهران</li>
              <li>تصویر روز ناسا با جریان editorial فارسی</li>
              <li>اخبار علمی منتشرشده در WordPress</li>
              <li>ویدیوهای کانال رسمی جزیره</li>
              <li>پرونده‌ها، رویدادها و ابزارهای آموزشی عمومی</li>
            </ul>
          </aside>
        </div>
      </section>
    </>
  )
}
