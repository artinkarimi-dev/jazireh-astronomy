import { ArrowRight, Orbit } from 'lucide-react'
import { Link } from 'react-router-dom'

export default function NotFoundPage() {
  return <div className="flex min-h-screen items-center justify-center bg-space-950 px-4 text-center" dir="rtl"><div><Orbit className="mx-auto h-20 w-20 animate-spin text-blue-300 [animation-duration:12s]" /><span className="mt-8 block text-7xl font-black text-white">۴۰۴</span><h1 className="mt-4 text-2xl font-bold text-white">این بخش از کیهان پیدا نشد</h1><p className="mt-3 text-slate-500">آدرس واردشده در نقشه فعلی جزیره وجود ندارد.</p><Link to="/" className="primary-btn mt-7"><ArrowRight className="h-4 w-4" /> بازگشت به خانه</Link></div></div>
}
