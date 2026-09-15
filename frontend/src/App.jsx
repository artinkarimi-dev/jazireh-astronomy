import { lazy, Suspense, useEffect } from 'react'
import { Route, Routes, useLocation } from 'react-router-dom'
import Layout from './components/Layout'
import LoadingScreen from './components/LoadingScreen'
import ErrorBoundary from './components/ErrorBoundary'

const HomePage = lazy(() => import('./pages/HomePage'))
const SkyTodayPage = lazy(() => import('./pages/SkyTodayPage'))
const ExplorePage = lazy(() => import('./pages/ExplorePage'))
const NewsPage = lazy(() => import('./pages/NewsPage'))
const NewsDetailsPage = lazy(() => import('./pages/NewsDetailsPage'))
const EventsPage = lazy(() => import('./pages/EventsPage'))
const TopicsPage = lazy(() => import('./pages/TopicsPage'))
const TopicDetailsPage = lazy(() => import('./pages/TopicDetailsPage'))
const SearchPage = lazy(() => import('./pages/SearchPage'))
const ApodPage = lazy(() => import('./pages/ApodPage'))
const JazirehDailyPage = lazy(() => import('./pages/JazirehDailyPage'))
const JazirehDailyDetailsPage = lazy(() => import('./pages/JazirehDailyDetailsPage'))
const VideosPage = lazy(() => import('./pages/VideosPage'))
const RadarPage = lazy(() => import('./pages/RadarPage'))
const AboutPage = lazy(() => import('./pages/AboutPage'))
const ContactPage = lazy(() => import('./pages/ContactPage'))
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'))

export default function App() {
  const location = useLocation()

  useEffect(() => {
    const prefetch = () => {
      import('./pages/SkyTodayPage')
      import('./pages/TopicsPage')
      import('./pages/TopicDetailsPage')
      import('./pages/ApodPage')
      import('./pages/JazirehDailyPage')
      import('./pages/VideosPage')
      import('./pages/SearchPage')
    }
    const idleId = 'requestIdleCallback' in window
      ? window.requestIdleCallback(prefetch, { timeout: 3500 })
      : window.setTimeout(prefetch, 1800)
    return () => {
      if ('cancelIdleCallback' in window) window.cancelIdleCallback(idleId)
      else window.clearTimeout(idleId)
    }
  }, [])

  return (
    <ErrorBoundary resetKey={location.pathname}>
      <Suspense fallback={<LoadingScreen />}>
        <Routes>
          <Route element={<Layout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/sky" element={<SkyTodayPage />} />
            <Route path="/explore" element={<ExplorePage />} />
            <Route path="/news" element={<NewsPage />} />
            <Route path="/news/:slug" element={<NewsDetailsPage />} />
            <Route path="/events" element={<EventsPage />} />
            <Route path="/topics" element={<TopicsPage />} />
            <Route path="/topics/:slug" element={<TopicDetailsPage />} />
            <Route path="/search" element={<SearchPage />} />
            <Route path="/apod" element={<ApodPage />} />
            <Route path="/jazireh-daily" element={<JazirehDailyPage />} />
            <Route path="/jazireh-daily/:slug" element={<JazirehDailyDetailsPage />} />
            <Route path="/videos" element={<VideosPage />} />
            <Route path="/radar" element={<RadarPage />} />
            <Route path="/about" element={<AboutPage />} />
            <Route path="/contact" element={<ContactPage />} />
          </Route>
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </Suspense>
    </ErrorBoundary>
  )
}
