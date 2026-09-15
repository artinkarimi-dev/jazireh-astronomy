import { Mail, MapPin, Phone, Send, Youtube } from 'lucide-react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { useSiteSettings } from '../context/SiteSettingsContext'

export default function ContactPage() {
  const { settings } = useSiteSettings()
  const contact = settings.contact || {}
  const youtube = settings.social?.youtube || {}
  const hasDirectContact = Boolean(contact.email || contact.phone || contact.address)

  usePageMeta('تماس با جزیره نجوم', 'راه‌های ارتباطی رسمی جزیره نجوم و وضعیت اطلاعات تماس منتشرشده.')

  return (
    <>
      <PageHero eyebrow="تماس" title="ارتباط با جزیره نجوم" description="راه‌های ارتباطی رسمی سایت از تنظیمات WordPress خوانده می‌شوند تا اطلاعات نادرست یا ساختگی نمایش داده نشود.">
        {youtube.url ? <a className="secondary-btn" href={youtube.url} target="_blank" rel="noreferrer"><Youtube className="h-4 w-4" />کانال یوتیوب</a> : null}
      </PageHero>

      <section className="content-shell section-space">
        <div className="grid gap-5 lg:grid-cols-[minmax(0,.9fr)_minmax(320px,1.1fr)]">
          <section className="surface-card p-6 sm:p-8">
            <span className="eyebrow">اطلاعات رسمی</span>
            <div className="mt-5 space-y-3">
              <ContactLine icon={Mail} label="ایمیل" value={contact.email} href={contact.email ? `mailto:${contact.email}` : ''} />
              <ContactLine icon={Phone} label="تلفن" value={contact.phone} href={contact.phone ? `tel:${contact.phone.replace(/\s+/g, '')}` : ''} />
              <ContactLine icon={MapPin} label="نشانی" value={contact.address} />
              <ContactLine icon={Youtube} label={youtube.label || 'یوتیوب'} value={youtube.handle || youtube.url} href={youtube.url} />
            </div>
          </section>

          <section className="surface-card p-6 sm:p-8">
            <span className="eyebrow">پیام به تیم جزیره</span>
            {hasDirectContact ? (
              <div>
                <h2 className="mt-3 text-2xl font-black leading-10 text-white">از مسیرهای رسمی بالا استفاده کنید</h2>
                <p className="mt-4 text-sm leading-8 text-slate-400">
                  فرم تماس عمومی هنوز به سرویس ارسال ایمیل متصل نشده است؛ برای جلوگیری از گم شدن پیام، فقط راه ارتباطی تاییدشده نمایش داده می‌شود.
                </p>
              </div>
            ) : (
              <div>
                <Send className="h-8 w-8 text-amber-300" aria-hidden="true" />
                <h2 className="mt-4 text-2xl font-black leading-10 text-white">اطلاعات تماس هنوز تنظیم نشده است</h2>
                <p className="mt-4 text-sm leading-8 text-slate-400">
                  این صفحه آماده انتشار است، اما ایمیل، تلفن یا نشانی رسمی در تنظیمات WordPress ثبت نشده. تا زمان تکمیل این تنظیمات، سایت اطلاعات تماس ساختگی نمایش نمی‌دهد.
                </p>
              </div>
            )}
          </section>
        </div>
      </section>
    </>
  )
}

function ContactLine({ icon: Icon, label, value, href }) {
  const content = value || 'ثبت نشده'
  const className = `flex items-start gap-3 rounded-[22px] border border-white/[.07] bg-white/[.025] p-4 text-sm leading-7 ${value ? 'text-slate-200' : 'text-slate-500'}`

  const body = (
    <>
      <Icon className="mt-1 h-5 w-5 shrink-0 text-amber-300" aria-hidden="true" />
      <span className="min-w-0">
        <span className="block text-xs text-slate-500">{label}</span>
        <span className="mt-1 block break-words font-bold">{content}</span>
      </span>
    </>
  )

  return href && value ? <a className={className} href={href} target={href.startsWith('http') ? '_blank' : undefined} rel={href.startsWith('http') ? 'noreferrer' : undefined}>{body}</a> : <div className={className}>{body}</div>
}
