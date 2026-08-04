# Architecture

## System overview

```text
Browser
  |
  v
React + Vite frontend
  |
  v
PHP REST API
  |  | +--> MySQL
  |
  +----> NASA APOD API / OpenWeather API
```

## Frontend

The frontend is a Persian RTL single-page application built with React and Vite. React Router manages public and administrator routes. Heavy route modules are lazy-loaded, while Three.js is isolated to the interactive exploration experience.

Primary layers:

- `components`: shared interface primitives and layout components
- `components/home`: homepage sections
- `components/space`: interactive 3D solar-system scene
- `components/sky`: interactive sky map
- `components/radar`: simulated radar visualization
- `pages`: route-level screens
- `data`: local fallback content
- `lib`: API client and shared utilities

The codebase currently uses a hybrid data strategy. News listing and administrator news actions communicate with the backend. Several public experiences still use local fallback data until their API integration is completed.

## Backend

The backend is a lightweight PHP REST API that does not require a framework or Composer. It targets PHP 7.4+ and uses PDO for database access.

Primary layers:

- `public/index.php`: front controller and route registration
- `app/Router.php`: routing and protected-route handling
- `app/Request.php`: request parsing
- `app/Response.php`: JSON responses
- `app/Database.php`: PDO connection
- `app/Auth.php`: HMAC-SHA256 JWT authentication
- `app/Controllers`: public and administrator use cases
- `app/Services`: NASA and weather integrations

The API requires a configured JWT secret of at least 32 characters. The public repository contains no usable default administrator account.

## Database

Main tables:

- `users`
- `news_categories`
- `news`
- `apod`
- `videos`
- `sky_conditions`
- `celestial_objects`
- `site_settings`
- `newsletter_subscribers`
- `login_attempts`
- `contact_messages`

The SQL seed includes an inactive placeholder author only to preserve sample-content relationships. It cannot be used to sign in.

## Performance decisions

- Route-level code splitting
- Dedicated Three.js bundle chunk
- Web-optimized image assets
- H.264 video assets with browser-friendly metadata
- Responsive Tailwind CSS layouts
- Reduced-motion support for users who request it

## Current boundaries

- Radar locations and status values are simulated.
- The solar-system scene is a visual interaction, not a scientifically accurate orbital simulator.
- Some public pages still rely on fallback content.
- APOD and video administration APIs exist, but their dashboard forms are not yet implemented.
- Automated end-to-end tests are not included.
