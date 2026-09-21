import EarthCard from '../observatory/EarthCard'
import MoonNowCard from '../observatory/MoonNowCard'
import SunNowCard from '../observatory/SunNowCard'

export default function HomeLiveTrio({ widgets = {}, sky = null, sun = null, loading = false }) {
  return (
    <section className="home-live-trio-wrap" aria-label="زمین، ماه و خورشید اکنون">
      <div className="home-live-trio-head">
        <div>
          <span className="eyebrow">رصدخانه اکنون</span>
          <h2>زمین، ماه و خورشید در همین لحظه</h2>
        </div>
        <p>سه نمای اصلی زنده یا محاسباتی جزیره، کنار هم و بدون انتظار برای APIهای خارجی.</p>
      </div>
      <div className="home-live-trio">
        <EarthCard widget={widgets.earth} loading={loading && !widgets.earth} />
        <MoonNowCard widget={widgets.moon} fallbackSky={sky} loading={loading && !widgets.moon} compact />
        <SunNowCard widget={widgets.sun} fallbackSun={sun} loading={loading && !widgets.sun && !sun} />
      </div>
    </section>
  )
}
