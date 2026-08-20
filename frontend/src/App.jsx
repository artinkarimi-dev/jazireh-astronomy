import { lazy, Suspense } from 'react'
import { Route, Routes } from 'react-router-dom'
import Layout from './components/Layout'
import LoadingScreen from './components/LoadingScreen'
import ErrorBoundary from './components/ErrorBoundary'

const HomePage = lazy(() => import('./pages/HomePage'))
const SkyTodayPage = lazy(() => import('./pages/SkyTodayPage'))
const ExplorePage = lazy(() => import('./pages/ExplorePage'))
const NewsPage = lazy(() => import('./pages/NewsPage'))
const NewsDetailsPage = lazy(() => import('./pages/NewsDetailsPage'))
const ApodPage = lazy(() => import('./pages/ApodPage'))
const JazirehDailyPage = lazy(() => import('./pages/JazirehDailyPage'))
const JazirehDailyDetailsPage = lazy(() => import('./pages/JazirehDailyDetailsPage'))
const VideosPage = lazy(() => import('./pages/VideosPage'))
const RadarPage = lazy(() => import('./pages/RadarPage'))
const NotFoundPage = lazy(() => import('./pages/NotFoundPage'))

export default function App() {
  return (
    <ErrorBoundary>
      <Suspense fallback={<LoadingScreen />}>
        <Routes>
          <Route element={<Layout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/sky" element={<SkyTodayPage />} />
            <Route path="/explore" element={<ExplorePage />} />
            <Route path="/news" element={<NewsPage />} />
            <Route path="/news/:slug" element={<NewsDetailsPage />} />
            <Route path="/apod" element={<ApodPage />} />
            <Route path="/jazireh-daily" element={<JazirehDailyPage />} />
            <Route path="/jazireh-daily/:slug" element={<JazirehDailyDetailsPage />} />
            <Route path="/videos" element={<VideosPage />} />
            <Route path="/radar" element={<RadarPage />} />
          </Route>
          <Route path="*" element={<NotFoundPage />} />
        </Routes>
      </Suspense>
    </ErrorBoundary>
  )
}
