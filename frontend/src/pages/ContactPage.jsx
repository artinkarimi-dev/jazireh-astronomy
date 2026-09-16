import { Mail, Youtube } from 'lucide-react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { useSiteSettings } from '../context/SiteSettingsContext'

export default function ContactPage() {
  const { settings } = useSiteSettings()
  const contact = settings.contact || {}
  const youtube = settings.social?.youtube || {}

  usePageMeta('تماس با جزیره نجوم', 'راه‌های ارتباطی رسمی جزیره نجوم و وضعیت اطلاعات تماس منتشرشده.')

  return (
    <>
      <PageHero eyebrow="تماس" title="ارتباط با جزیره نجوم" description="راه‌های ارتباطی رسمی جزیره نجوم از تنظیمات WordPress خوانده می‌شوند.">
        {youtube.url ? <a className="secondary-btn" href={youtube.url} target="_blank" rel="noopener noreferrer" aria-label="باز کردن کانال رسمی جزیره در یوتیوب"><Youtube className="h-4 w-4" />کانال یوتیوب</a> : null}
      </PageHero>

      <section className="content-shell section-space">
        <section className="surface-card mx-auto max-w-3xl p-6 sm:p-8" aria-labelledby="official-contact-heading">
          <span className="eyebrow">اطلاعات رسمی</span>
          <h2 id="official-contact-heading" className="mt-3 text-2xl font-black leading-10 text-white">راه‌های ارتباطی تاییدشده</h2>
          <p className="mt-3 text-sm leading-8 text-slate-400">برای ارتباط با جزیره نجوم فقط از کانال‌های رسمی زیر استفاده کنید.</p>
          <div className="mt-6 space-y-3">
            <ContactLine icon={Mail} label="ایمیل رسمی" value={contact.email} href={contact.email ? `mailto:${contact.email}` : ''} ariaLabel="ارسال ایمیل به جزیره نجوم" />
            <ContactLine icon={Youtube} label={youtube.label || 'کانال رسمی یوتیوب'} value={youtube.handle || youtube.url} href={youtube.url} ariaLabel="باز کردن کانال رسمی جزیره در یوتیوب" />
          </div>
        </section>
      </section>
    </>
  )
}

function ContactLine({ icon: Icon, label, value, href, ariaLabel }) {
  if (!value || !href) return null
  const className = 'flex items-start gap-3 rounded-[22px] border border-white/[.07] bg-white/[.025] p-4 text-sm leading-7 text-slate-200 transition hover:border-amber-200/25 hover:bg-white/[.04]'

  const body = (
    <>
      <Icon className="mt-1 h-5 w-5 shrink-0 text-amber-300" aria-hidden="true" />
      <span className="min-w-0">
        <span className="block text-xs text-slate-500">{label}</span>
        <span className="mt-1 block break-words font-bold">{value}</span>
      </span>
    </>
  )

  return <a className={className} href={href} target={href.startsWith('http') ? '_blank' : undefined} rel={href.startsWith('http') ? 'noopener noreferrer' : undefined} aria-label={ariaLabel}>{body}</a>
}
