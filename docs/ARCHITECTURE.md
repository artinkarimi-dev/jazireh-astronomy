# Project Architecture

## Final runtime shape

```text
Browser
  |
  v
WordPress theme shell (jazireh-theme)
  |
  v
React + Vite app
  |
  v
WordPress REST API (jazireh-core)
  |\
  | +--> WordPress database and media
  |
  +----> NASA APOD API / YouTube API
```

The live application is a WordPress-first deployment. React is bundled into the custom theme and uses the custom WordPress REST namespace `jazireh/v1`.

## Source layout

- `frontend/`: React source, Vite config, static brand assets, and generated build output
- `wordpress/wp-content/themes/jazireh-theme/`: WordPress theme that serves the React shell and runtime config
- `wordpress/wp-content/plugins/jazireh-core/`: content types, settings, admin integrations, and custom REST endpoints
- `database/`: legacy archive assets and SQL exports kept for rollback/reference
- `archive/backend-retired-2026-08-19/`: retired standalone backend kept only as rollback/archive material

## Frontend responsibilities

The React app handles:

- public routing
- page rendering
- interactive Explore and Radar experiences
- WordPress REST consumption through `frontend/src/lib/api.js`

The API client is intentionally WordPress-only. Logical paths such as `/api/news` still exist inside the frontend codebase, but they are resolved to the active WordPress REST base URL at runtime.

## Theme responsibilities

`jazireh-theme` is responsible for:

- serving the root React shell
- enqueuing the current Vite manifest entrypoints
- exposing runtime config with `window.JAZIREH_WP`
- routing public SPA paths such as `/news`, `/videos`, `/apod`, `/jazireh-daily`, `/sky`, `/explore`, and `/radar`
- redirecting `/admin` and `/admin/login` into native WordPress auth

## Plugin responsibilities

`jazireh-core` is responsible for:

- custom post types and metadata for news and objects
- newsletter schema and subscription handling
- WordPress settings used by the site and homepage
- WordPress admin screens for site management
- Jazireh REST endpoints under `wp-json/jazireh/v1`
- server-side integrations for NASA APOD and YouTube latest videos
- secure ingestion and storage for Jazireh Daily / YouTube Community posts

## External integration boundaries

Latest videos and Community posts do not share the same upstream architecture:

- Latest videos use the official YouTube Data API server-side inside WordPress.
- Jazireh Community post extraction is intentionally external to WordPress page requests because there is no supported official public API for modern YouTube Community posts.
- The public site reads only from WordPress REST and WordPress media after synchronization.

As of Wednesday, August 19, 2026, live Community extraction from `https://www.youtube.com/@Jazireh/posts` is not verified in this local environment, but the external adapter, signed ingestion endpoint, WordPress storage, and React presentation layer are implemented.

Legacy one-time import routines from `jazireh_astronomy` are no longer part of the active production plugin runtime.

## Data model

Active managed content lives in the WordPress database:

- news posts and categories
- celestial objects
- site settings
- newsletter subscribers
- theme menus
- uploads/media

The old `jazireh_astronomy` database is no longer required by the active frontend runtime.

## Build and sync flow

1. Edit React source under `frontend/src`.
2. Run `npm.cmd run build`.
3. Run `npm.cmd run build:wordpress`.
4. Sync the generated theme `dist` into the live WordPress theme.
5. Sync any touched custom theme/plugin PHP files into the live WordPress install.
6. Smoke-test the live WordPress URLs and REST endpoints.

## Known boundaries

- Sky currently ships as a static compatibility snapshot rather than a live weather feed.
- Radar is a simulation, not live orbital tracking.
- Explore uses authored object content rather than live astronomical data feeds.
- Admin authority is native WordPress; the retired React admin and JWT flow are not part of the final architecture.
