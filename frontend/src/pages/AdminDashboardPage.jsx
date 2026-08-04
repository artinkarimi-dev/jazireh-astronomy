import { useEffect, useState } from 'react'
import { BarChart3, FileText, Image, LogOut, Menu, Plus, Save, Trash2, Video, X } from 'lucide-react'
import { useNavigate } from 'react-router-dom'
import { api } from '../lib/api'
import { newsItems as fallbackNews } from '../data/fallback'
import Logo from '../components/Logo'

const tabs = [{ id: 'dashboard', label: 'داشبورد', icon: BarChart3 }, { id: 'news', label: 'اخبار', icon: FileText }, { id: 'apod', label: 'عکس روز', icon: Image }, { id: 'videos', label: 'ویدیوها', icon: Video }]
const emptyForm = { title: '', slug: '', excerpt: '', content: '', category: 'نجوم', image: '/media/galaxy.jpg', status: 'published' }

export default function AdminDashboardPage() {
  const navigate = useNavigate()
  const [tab, setTab] = useState('dashboard')
  const [menu, setMenu] = useState(false)
  const [news, setNews] = useState(fallbackNews)
  const [form, setForm] = useState(emptyForm)
  const [message, setMessage] = useState('')
  useEffect(() => {
    if (!localStorage.getItem('jazireh_token')) navigate('/admin/login')
    api.get('/api/admin/news').then((res) => setNews(res.data || fallbackNews)).catch(() => {})
  }, [navigate])
  const logout = () => { localStorage.removeItem('jazireh_token'); navigate('/admin/login') }
  const createNews = async (e) => {
    e.preventDefault(); setMessage('')
    try { const res = await api.post('/api/admin/news', form); setNews([res.data, ...news]); setForm(emptyForm); setMessage('خبر با موفقیت ثبت شد.') }
    catch (err) { setMessage(err.message) }
  }
  const remove = async (id) => { if (!confirm('این خبر حذف شود؟')) return; try { await api.delete(`/api/admin/news/${id}`); setNews(news.filter((item) => item.id !== id)) } catch (err) { setMessage(err.message) } }
  return (
    <div className="min-h-screen bg-space-950 text-slate-100" dir="rtl">
      <header className="fixed inset-x-0 top-0 z-40 flex h-[70px] items-center justify-between border-b border-white/[.08] bg-space-950/90 px-4 backdrop-blur-xl lg:pr-[290px]"><button className="icon-button lg:hidden" onClick={() => setMenu(true)}><Menu className="h-5 w-5" /></button><h1 className="text-sm font-bold text-white">پنل مدیریت جزیره نجوم</h1><button onClick={logout} className="secondary-btn"><LogOut className="h-4 w-4" /> خروج</button></header>
      <aside className={`fixed right-0 top-0 z-50 h-screen w-[280px] border-l border-white/[.08] bg-[#030a15] p-5 transition lg:translate-x-0 ${menu ? 'translate-x-0' : 'translate-x-full'}`}><div className="flex items-center justify-between"><Logo /><button onClick={() => setMenu(false)} className="icon-button lg:hidden"><X className="h-5 w-5" /></button></div><nav className="mt-10 space-y-2">{tabs.map(({ id, label, icon: Icon }) => <button key={id} onClick={() => { setTab(id); setMenu(false) }} className={`flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-sm font-semibold transition ${tab === id ? 'border border-blue-400/20 bg-blue-500/10 text-blue-200' : 'text-slate-500 hover:bg-white/[.04] hover:text-white'}`}><Icon className="h-5 w-5" />{label}</button>)}</nav></aside>
      <main className="px-4 pb-12 pt-24 sm:px-6 lg:pr-[310px] lg:pl-8">
        {message && <div className="mb-5 rounded-2xl border border-blue-400/20 bg-blue-500/10 px-4 py-3 text-sm text-blue-200">{message}</div>}
        {tab === 'dashboard' && <Dashboard newsCount={news.length} />}
        {tab === 'news' && <NewsManager news={news} form={form} setForm={setForm} createNews={createNews} remove={remove} />}
        {tab === 'apod' && <Placeholder title="مدیریت عکس روز" text="ساختار دیتابیس و API عکس روز آماده است. فرم اختصاصی را می‌توانید با الگوی بخش اخبار توسعه دهید." />}
        {tab === 'videos' && <Placeholder title="مدیریت ویدیوها" text="ویدیوهای محلی پروژه در مسیر public/media قرار دارند و جدول و API ویدیو نیز در بک‌اند آماده است." />}
      </main>
    </div>
  )
}

function Dashboard({ newsCount }) {
  const cards = [{ label: 'خبرهای ثبت‌شده', value: newsCount, icon: FileText }, { label: 'تصاویر روز', value: 2, icon: Image }, { label: 'ویدیوهای فعال', value: 3, icon: Video }, { label: 'بازدید آزمایشی', value: '۱۲٬۸۴۰', icon: BarChart3 }]
  return <><h2 className="text-3xl font-black text-white">نمای کلی</h2><p className="mt-2 text-sm text-slate-500">مدیریت محتوای سایت از یک پنل ساده و روشن.</p><div className="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{cards.map(({ label, value, icon: Icon }) => <div key={label} className="glass-panel rounded-3xl p-6"><span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-300"><Icon className="h-5 w-5" /></span><strong className="mt-6 block text-3xl text-white">{value}</strong><span className="mt-2 block text-sm text-slate-500">{label}</span></div>)}</div></>
}

function NewsManager({ news, form, setForm, createNews, remove }) {
  return <div><div className="flex items-end justify-between"><div><h2 className="text-3xl font-black text-white">مدیریت اخبار</h2><p className="mt-2 text-sm text-slate-500">ثبت و حذف خبرهای علمی.</p></div><span className="primary-btn"><Plus className="h-4 w-4" /> خبر جدید</span></div><div className="mt-7 grid gap-5 xl:grid-cols-[.8fr_1.2fr]"><form onSubmit={createNews} className="glass-panel rounded-3xl p-5"><h3 className="font-bold text-white">ثبت خبر</h3><div className="mt-5 space-y-4"><input required className="input-field" placeholder="عنوان خبر" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} /><input required className="input-field" placeholder="slug انگلیسی" value={form.slug} onChange={(e) => setForm({ ...form, slug: e.target.value })} /><div className="grid grid-cols-2 gap-3"><input className="input-field" placeholder="دسته‌بندی" value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} /><input className="input-field" placeholder="مسیر تصویر" value={form.image} onChange={(e) => setForm({ ...form, image: e.target.value })} /></div><textarea required rows="3" className="input-field resize-none" placeholder="خلاصه" value={form.excerpt} onChange={(e) => setForm({ ...form, excerpt: e.target.value })} /><textarea required rows="7" className="input-field resize-none" placeholder="متن کامل" value={form.content} onChange={(e) => setForm({ ...form, content: e.target.value })} /><button className="primary-btn w-full"><Save className="h-4 w-4" /> ذخیره خبر</button></div></form><div className="glass-panel rounded-3xl p-5"><h3 className="font-bold text-white">خبرهای موجود</h3><div className="mt-5 space-y-3">{news.map((item) => <div key={item.id} className="flex items-center gap-3 rounded-2xl border border-white/[.07] bg-white/[.025] p-3"><img src={item.image} className="h-16 w-20 rounded-xl object-cover" /><div className="min-w-0 flex-1"><strong className="line-clamp-1 text-sm text-white">{item.title}</strong><span className="mt-1 block text-xs text-slate-500">{item.category}</span></div><button onClick={() => remove(item.id)} className="icon-button text-red-300"><Trash2 className="h-4 w-4" /></button></div>)}</div></div></div></div>
}

function Placeholder({ title, text }) { return <div className="glass-panel rounded-3xl p-8"><h2 className="text-2xl font-black text-white">{title}</h2><p className="mt-4 max-w-2xl leading-8 text-slate-400">{text}</p></div> }
