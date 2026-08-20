import { ArrowRight, Orbit } from 'lucide-react'
import { Link } from 'react-router-dom'
import Logo from '../components/Logo'
import usePageMeta from '../hooks/usePageMeta'

export default function NotFoundPage() {
  usePageMeta('صفحه پیدا نشد', 'آدرس درخواستی در جزیره نجوم وجود ندارد.')

  return (
    <main className="flex min-h-screen items-center justify-center bg-space-950 px-4 text-center" dir="rtl">
      <div className="glass-panel w-full max-w-lg rounded-[2rem] p-8 sm:p-12">
        <div className="flex justify-center"><Logo /></div>
        <Orbit className="mx-auto mt-10 h-20 w-20 animate-spin text-amber-300 [animation-duration:12s]" />
        <span className="mt-8 block text-7xl font-black text-white">۴۰۴</span>
        <h1 className="mt-4 text-2xl font-bold text-white">این بخش از کیهان پیدا نشد</h1>
        <p className="mt-3 leading-7 text-slate-500">آدرس واردشده در نقشه فعلی جزیره وجود ندارد یا جابه‌جا شده است.</p>
        <Link to="/" className="primary-btn mt-7"><ArrowRight className="h-4 w-4" /> بازگشت به خانه</Link>
      </div>
    </main>
  )
}
