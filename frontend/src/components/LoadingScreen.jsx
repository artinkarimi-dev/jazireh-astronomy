export default function LoadingScreen() {
  return (
    <div className="flex min-h-screen items-center justify-center bg-space-950" dir="rtl">
      <div className="text-center">
        <div className="relative mx-auto h-20 w-20">
          <div className="absolute inset-0 rounded-full border border-blue-400/20" />
          <div className="absolute inset-2 animate-spin rounded-full border border-transparent border-t-blue-300" />
          <div className="absolute inset-[31px] rounded-full bg-blue-400 shadow-[0_0_28px_rgba(96,165,250,.75)]" />
        </div>
        <p className="mt-5 text-sm text-slate-400">در حال ورود به جزیره نجوم...</p>
      </div>
    </div>
  )
}
