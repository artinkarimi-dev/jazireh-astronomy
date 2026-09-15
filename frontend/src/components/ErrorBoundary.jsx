import { Component } from 'react'

export default class ErrorBoundary extends Component {
  constructor(props) {
    super(props)
    this.state = { hasError: false }
  }

  static getDerivedStateFromError() {
    return { hasError: true }
  }

  componentDidUpdate(previousProps) {
    if (this.state.hasError && previousProps.resetKey !== this.props.resetKey) {
      this.setState({ hasError: false })
    }
  }

  reset = () => {
    this.setState({ hasError: false })
  }

  render() {
    if (this.state.hasError) {
      return (
        <div className="flex min-h-screen items-center justify-center bg-space-950 px-6 text-center" dir="rtl">
          <div className="glass-panel max-w-lg rounded-3xl p-8">
            <h1 className="text-2xl font-black text-white">یک اختلال موقت رخ داد</h1>
            <p className="mt-4 text-slate-400">صفحه را تازه‌سازی کنید. اگر مشکل ادامه داشت، اتصال بک‌اند و تنظیمات محیطی را بررسی کنید.</p>
            <button className="primary-btn mt-6" onClick={this.reset}>بارگذاری دوباره</button>
          </div>
        </div>
      )
    }
    return this.props.children
  }
}
