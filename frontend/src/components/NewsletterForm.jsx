import { useState } from 'react'
import { CheckCircle2, LoaderCircle } from 'lucide-react'
import { api } from '../lib/api'

export default function NewsletterForm() {
  const [email, setEmail] = useState('')
  const [status, setStatus] = useState({ type: 'idle', message: '' })

  const submit = async (event) => {
    event.preventDefault()
    setStatus({ type: 'loading', message: '' })

    try {
      const response = await api.post('/api/newsletter', { email })
      setStatus({ type: 'success', message: response.message || 'عضویت شما ثبت شد.' })
      setEmail('')
    } catch (error) {
      setStatus({ type: 'error', message: error.message })
    }
  }

  return (
    <form onSubmit={submit} className="mt-4">
      <label htmlFor="newsletter-email" className="sr-only">ایمیل برای عضویت در خبرنامه</label>
      <div className="flex flex-col gap-2 sm:flex-row">
        <input
          id="newsletter-email"
          type="email"
          autoComplete="email"
          required
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          className="input-field"
          placeholder="name@example.com"
          aria-describedby="newsletter-status"
        />
        <button type="submit" disabled={status.type === 'loading'} className="primary-btn shrink-0 disabled:cursor-not-allowed disabled:opacity-60">
          {status.type === 'loading' ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
          عضویت
        </button>
      </div>
      <div id="newsletter-status" aria-live="polite" className={`mt-3 min-h-5 text-xs ${status.type === 'error' ? 'text-red-300' : status.type === 'success' ? 'text-emerald-300' : 'text-slate-600'}`}>
        {status.type === 'success' && <CheckCircle2 className="ml-1 inline h-3.5 w-3.5" />}
        {status.message}
      </div>
    </form>
  )
}
