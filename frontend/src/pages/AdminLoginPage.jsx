import { useState } from 'react'
import { LockKeyhole, Mail, Orbit } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { api } from '../lib/api'

export default function AdminLoginPage() {
  const navigate = useNavigate()
  const [form, setForm] = useState({ email: '', password: '' })
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)
  const submit = async (e) => {
    e.preventDefault(); setError(''); setLoading(true)
    try { const response = await api.post('/api/auth/login', form); localStorage.setItem('jazireh_token', response.token); navigate('/admin') }
    catch (err) { setError(err.message) }
    finally { setLoading(false) }
  }
  return (
    <div className="relative flex min-h-screen items-center justify-center overflow-hidden bg-space-950 px-4" dir="rtl">
      <video autoPlay muted loop playsInline className="absolute inset-0 h-full w-full object-cover opacity-20" poster="/media/hero-earth-poster.jpg"><source src="/media/hero-earth.mp4" type="video/mp4" /></video><div className="absolute inset-0 bg-space-950/70" />
      <form onSubmit={submit} className="glass-panel relative w-full max-w-md rounded-[2rem] p-7 sm:p-9">
        <div className="flex items-center gap-3"><span className="flex h-12 w-12 items-center justify-center rounded-full border border-blue-300/25 bg-blue-500/10"><Orbit className="h-7 w-7 text-blue-200" /></span><div><h1 className="text-2xl font-black text-white">مدیریت جزیره</h1><p className="mt-1 text-xs text-slate-500">ورود به پنل محتوای نجومی</p></div></div>
        {error && <div className="mt-6 rounded-xl border border-red-400/20 bg-red-500/10 px-4 py-3 text-sm text-red-300">{error}</div>}
        <label className="mt-7 block text-sm text-slate-300">ایمیل</label><div className="relative mt-2"><Mail className="absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" /><input type="email" autoComplete="username" placeholder="admin@example.com" className="input-field pr-11" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} /></div>
        <label className="mt-5 block text-sm text-slate-300">رمز عبور</label><div className="relative mt-2"><LockKeyhole className="absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" /><input type="password" autoComplete="current-password" placeholder="••••••••••••" className="input-field pr-11" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} /></div>
        <button disabled={loading} className="primary-btn mt-7 w-full py-3">{loading ? 'در حال ورود...' : 'ورود به پنل'}</button>
        <p className="mt-5 text-center text-[11px] leading-5 text-slate-600">این نسخه عمومی شامل اطلاعات ورود پیش‌فرض یا حساب فعال نیست.</p>
      </form>
    </div>
  )
}
