import { ArrowUpLeft, Code2, Youtube } from 'lucide-react'
import { Link } from 'react-router-dom'
import { getPersianYear, siteConfig } from '../config/site'
import { useSiteSettings } from '../context/SiteSettingsContext'
import Logo from './Logo'
import NewsletterForm from './NewsletterForm'

export default function Footer() {
  const { settings } = useSiteSettings()
  const footer = settings.footer
  const youtube = settings.social.youtube
  const quickLinks = settings.menus.footer || []
  const socialLinks = [
    { label: 'Instagram', url: settings.social.instagram },
    { label: 'Telegram', url: settings.social.telegram },
    { label: 'X', url: settings.social.x },
    { label: 'LinkedIn', url: settings.social.linkedin }
  ].filter((item) => item.url)

  return (
    <footer className="site-footer pb-7 pt-12 md:pb-8">
      <div className="content-shell footer-grid">
        <div className="footer-block">
          <Logo />
          <p className="mt-5 max-w-sm text-sm leading-8 text-slate-400">{footer.description || settings.identity.description}</p>
          <p className="mt-3 text-xs leading-6 text-slate-600">{footer.microcopy}</p>
          {socialLinks.length > 0 && (
            <div className="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
              {socialLinks.map((item) => <a key={item.label} href={item.url} target="_blank" rel="noopener noreferrer" className="footer-social-link">{item.label}</a>)}
            </div>
          )}
        </div>

        <nav className="footer-block" aria-label="دسترسی سریع فوتر">
          <h2 className="font-bold text-white">{footer.quicklinks_title}</h2>
          <div className="footer-link-list mt-4">
            {quickLinks.map((item) => item.external ? (
              <a key={`${item.label}-${item.url}`} href={item.url} target={item.target || '_self'} rel={item.target === '_blank' ? 'noopener noreferrer' : undefined} className="footer-link">{item.label}</a>
            ) : (
              <Link key={`${item.label}-${item.path}`} to={item.path || item.url} className="footer-link">{item.label}</Link>
            ))}
          </div>
        </nav>

        <div className="footer-block">
          <h2 className="font-bold text-white">{footer.newsletter_title}</h2>
          <p className="mt-4 text-sm leading-7 text-slate-400">{footer.newsletter_description}</p>
          <NewsletterForm />
        </div>

        <div className="footer-block">
          <h2 className="font-bold text-white">{footer.youtube_title}</h2>
          <a href={youtube.url} target="_blank" rel="noopener noreferrer" className="footer-youtube-card mt-4">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-500 text-white"><Youtube className="h-5 w-5 fill-current" /></span>
            <span className="min-w-0"><strong className="block text-sm text-white">{youtube.label}</strong><span className="mt-1 block text-xs text-slate-500">{youtube.handle}</span></span>
            <ArrowUpLeft className="mr-auto h-4 w-4 text-slate-600" />
          </a>
          <p className="mt-4 text-xs leading-6 text-slate-600">{footer.youtube_description}</p>
        </div>
      </div>

      <div className="content-shell mt-10 border-t border-white/[.06] pt-6">
        <div className="flex flex-col items-center justify-between gap-4 text-center text-xs text-slate-600 sm:flex-row sm:text-right">
          <span>© {getPersianYear()} {settings.identity.name || siteConfig.fullName} — {footer.copyright_text || 'تمام حقوق محفوظ است.'}</span>
          <a href={siteConfig.developer.url} target="_blank" rel="sponsored nofollow noopener noreferrer" className="developer-credit">
            <Code2 className="h-3.5 w-3.5" /><span>{siteConfig.developer.role} توسط</span><strong>{siteConfig.developer.name}</strong><ArrowUpLeft className="h-3.5 w-3.5" />
          </a>
        </div>
      </div>
    </footer>
  )
}
