import { Instagram, Send, Youtube, Github } from 'lucide-react'
import Logo from './Logo'

export default function Footer() {
  return (
    <footer className="mt-20 border-t border-white/[.08] bg-black/20 pb-24 pt-12 lg:pb-8">
      <div className="content-shell grid gap-10 md:grid-cols-2 xl:grid-cols-4">
        <div><Logo /><p className="mt-5 max-w-sm text-sm leading-7 text-slate-500">رسانه‌ای فارسی برای رصد آسمان، شناخت ماموریت‌های فضایی و روایت ساده و دقیق شگفتی‌های کیهان.</p></div>
        <div><h3 className="font-bold text-white">دسترسی سریع</h3><div className="mt-4 grid gap-2 text-sm text-slate-500"><a href="/sky">آسمان امروز</a><a href="/explore">کاوش منظومه شمسی</a><a href="/apod">تصویر روز ناسا</a><a href="/news">آخرین اخبار</a></div></div>
        <div><h3 className="font-bold text-white">خبرنامه جزیره</h3><p className="mt-4 text-sm leading-7 text-slate-500">خلاصه رویدادهای نجومی مهم را در ایمیل خود دریافت کنید.</p><div className="mt-4 flex gap-2"><input className="input-field" placeholder="ایمیل شما" /><button className="primary-btn shrink-0">عضویت</button></div></div>
        <div><h3 className="font-bold text-white">ما را دنبال کنید</h3><div className="mt-4 flex gap-2"><a className="icon-button" href="#"><Instagram className="h-4 w-4" /></a><a className="icon-button" href="#"><Send className="h-4 w-4" /></a><a className="icon-button" href="#"><Youtube className="h-4 w-4" /></a><a className="icon-button" href="#"><Github className="h-4 w-4" /></a></div><p className="mt-6 text-xs text-slate-600">منابع علمی پیشنهادی: NASA، ESA و JPL</p></div>
      </div>
      <div className="content-shell mt-10 border-t border-white/[.06] pt-6 text-center text-xs text-slate-600">© ۱۴۰۵ جزیره نجوم — تمام حقوق محفوظ است.</div>
    </footer>
  )
}
