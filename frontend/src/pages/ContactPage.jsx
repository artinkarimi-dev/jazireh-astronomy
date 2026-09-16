import { Instagram, Mail, Send } from 'lucide-react'
import PageHero from '../components/PageHero'
import usePageMeta from '../hooks/usePageMeta'
import { useSiteSettings } from '../context/SiteSettingsContext'

export default function ContactPage() {
  const { settings } = useSiteSettings()
  const contact = settings.contact || {}
  const telegramUrl = contact.telegramUrl || settings.social?.telegram || ''
  const instagramUrl = contact.instagramUrl || settings.social?.instagram || ''
  const sponsorEmail = contact.sponsorEmail || contact.email || ''

  usePageMeta('ارتباط با جزیره', 'راه‌های رسمی ارتباط با جزیره برای دنبال‌کردن محتوا و همکاری‌های تجاری.')

  return (
    <>
      <PageHero
        eyebrow="ارتباط"
        title="ارتباط با جزیره"
        description="برای دنبال‌کردن تازه‌ترین محتوای جزیره، می‌توانید از کانال‌های رسمی ما استفاده کنید."
      />

      <section className="content-shell section-space">
        <div className="mx-auto grid max-w-5xl gap-5 lg:grid-cols-[minmax(0,1.05fr)_minmax(320px,.95fr)]">
          <section className="surface-card p-6 sm:p-8" aria-labelledby="official-social-heading">
            <span className="eyebrow">کانال‌های رسمی</span>
            <h2 id="official-social-heading" className="mt-3 text-2xl font-black leading-10 text-white">کانال‌های رسمی جزیره</h2>
            <p className="mt-3 text-sm leading-8 text-slate-400">
              برای دریافت تازه‌ترین محتوا، خبرها و به‌روزرسانی‌های جزیره از مقصدهای رسمی زیر استفاده کنید.
            </p>
            <div className="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
              <OfficialChannel
                icon={Send}
                title="تلگرام جزیره"
                description="تازه‌ترین محتوا، خبرها و به‌روزرسانی‌های جزیره را در کانال رسمی تلگرام دنبال کنید."
                cta="مشاهده کانال تلگرام"
                href={telegramUrl}
                ariaLabel="باز کردن کانال رسمی تلگرام جزیره"
              />
              <OfficialChannel
                icon={Instagram}
                title="اینستاگرام جزیره"
                description="محتوای تصویری و تازه‌های جزیره را از صفحه رسمی اینستاگرام دنبال کنید."
                cta="مشاهده اینستاگرام"
                href={instagramUrl}
                ariaLabel="باز کردن صفحه رسمی اینستاگرام جزیره"
              />
            </div>
          </section>

          <section className="surface-card p-6 sm:p-8" aria-labelledby="sponsor-contact-heading">
            <span className="eyebrow">همکاری رسمی</span>
            <h2 id="sponsor-contact-heading" className="mt-3 text-2xl font-black leading-10 text-white">همکاری و اسپانسری</h2>
            <p className="mt-3 text-sm leading-8 text-slate-400">
              برای پیشنهادهای تجاری، همکاری‌های رسمی و درخواست‌های مربوط به اسپانسری می‌توانید از طریق ایمیل زیر با جزیره در ارتباط باشید.
            </p>
            {sponsorEmail ? (
              <a
                className="mt-6 flex items-start gap-3 rounded-[22px] border border-amber-200/15 bg-amber-200/[.055] p-4 text-sm leading-7 text-slate-100 transition hover:border-amber-200/30 hover:bg-amber-200/[.08] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200/60"
                href={`mailto:${sponsorEmail}`}
                aria-label="ارسال ایمیل فقط برای همکاری تجاری و اسپانسری با جزیره"
              >
                <Mail className="mt-1 h-5 w-5 shrink-0 text-amber-300" aria-hidden="true" />
                <span className="min-w-0">
                  <span className="block text-xs text-amber-100/75">{contact.sponsorPurpose || 'صرفاً برای همکاری‌های تجاری و اسپانسری'}</span>
                  <span className="mt-1 block break-words font-bold">{sponsorEmail}</span>
                </span>
              </a>
            ) : null}
            <p className="mt-4 text-xs leading-6 text-slate-500">
              این ایمیل صرفاً برای همکاری‌های تجاری و اسپانسری است.
            </p>
          </section>
        </div>
      </section>
    </>
  )
}

function OfficialChannel({ icon: Icon, title, description, cta, href, ariaLabel }) {
  if (!href) return null

  return (
    <a
      className="group flex min-h-[180px] flex-col rounded-[22px] border border-white/[.07] bg-white/[.025] p-5 text-right transition hover:border-sky-200/25 hover:bg-white/[.045] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-sky-200/50"
      href={href}
      target="_blank"
      rel="noopener noreferrer"
      aria-label={ariaLabel}
    >
      <span className="flex h-11 w-11 items-center justify-center rounded-2xl border border-sky-200/15 bg-sky-200/[.08] text-sky-100">
        <Icon className="h-5 w-5" aria-hidden="true" />
      </span>
      <span className="mt-4 text-lg font-black leading-8 text-white">{title}</span>
      <span className="mt-2 grow text-sm leading-7 text-slate-400">{description}</span>
      <span className="mt-5 text-sm font-bold text-amber-200 transition group-hover:text-amber-100">{cta}</span>
    </a>
  )
}
