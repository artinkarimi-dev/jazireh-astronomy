import ReactDOM from 'react-dom/client'
import { BrowserRouter } from 'react-router-dom'
import App from './App'
import { SiteSettingsProvider } from './context/SiteSettingsContext'
import './index.css'

const siteUrl = window.JAZIREH_WP?.siteUrl || window.location.origin
const basename = new URL(siteUrl).pathname.replace(/\/$/, '') || '/'

ReactDOM.createRoot(document.getElementById('root')).render(
  <BrowserRouter basename={basename}>
    <SiteSettingsProvider>
      <App />
    </SiteSettingsProvider>
  </BrowserRouter>
)
