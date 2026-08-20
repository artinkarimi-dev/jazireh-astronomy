<div align="center">
  <img src="frontend/public/brand/jazireh-logo.webp" width="148" alt="Jazireh Astronomy logo" />

# Jazireh Astronomy

**A Persian RTL astronomy platform for a real client, now delivered as a WordPress-based production architecture with a React frontend and WordPress-managed external integrations.**

React · Vite · WordPress · PHP · MySQL

[Persian README](README.fa.md) · [YouTube Channel](https://www.youtube.com/@Jazireh) · [Architecture](docs/ARCHITECTURE.md) · [API](docs/API.md)
</div>

> [!IMPORTANT]
> This is a public source-available portfolio repository, not an open-source project. The source may be inspected for portfolio, recruitment, and technical review, but it may not be installed, executed, copied, modified, deployed, redistributed, or commercially used without prior written permission. See [`LICENSE`](LICENSE).

## Overview

Jazireh Astronomy is a responsive Persian-language astronomy experience covering science news, APOD, videos, sky conditions, and solar-system exploration. The final shipped architecture uses:

- a React + Vite frontend
- a custom WordPress theme that serves the React shell
- a custom `jazireh-core` plugin for content models, settings, admin tooling, REST endpoints, YouTube latest-video caching, and Jazireh Daily ingestion
- WordPress as the authoritative CMS and admin/auth system

The retired standalone PHP backend is no longer part of the active runtime.

## Final architecture

```text
Browser
  |
  v
WordPress theme (jazireh-theme)
  |
  v
React app
  |
  v
WordPress REST API (jazireh-core)
  |\
  | +--> WordPress DB, menus, uploads
  |
  +----> NASA APOD / YouTube Data API

External Community synchronization is intentionally isolated outside public page requests:

```text
External adapter
  |
  v
Signed ingestion endpoint
  |
  v
WordPress jazireh_daily posts + media
```
```

See [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) for more detail.

## Product areas

- Home
- News and news detail
- APOD
- Jazireh Daily / YouTube Community NASA posts
- Videos
- Sky
- Explore / celestial objects
- Radar simulation
- Newsletter
- Native WordPress admin and login

## Repository structure

```text
jazireh-source/
├── docs/                                 Architecture and API docs
├── frontend/                             React source and Vite build pipeline
├── wordpress/
│   └── wp-content/
│       ├── plugins/jazireh-core/         REST API, admin, CPTs, newsletter, settings
│       └── themes/jazireh-theme/         React shell theme and built dist assets
├── archive/backend-retired-2026-08-19/   Retired standalone backend kept only for rollback/archive
├── database/                             Legacy SQL archive/reference material
├── README.md
└── README.fa.md
```

## Local development

### Requirements

- Node.js 20 or newer
- npm 10 or newer
- PHP 7.4 or newer
- MySQL
- Apache / WordPress local environment such as XAMPP

### React build flow

From `frontend/`:

```bash
npm ci
npm run build
npm run build:wordpress
```

`build:wordpress` writes the current Vite bundle into the source theme under:

```text
wordpress/wp-content/themes/jazireh-theme/dist
```

### Live runtime sync

After a successful build, sync these custom project assets into the live WordPress install:

- `wordpress/wp-content/themes/jazireh-theme`
- `wordpress/wp-content/plugins/jazireh-core`

The active local runtime in this environment is:

```text
C:\xampp\htdocs\wordpress
```

### Quality checks

```bash
npm run lint
npm run build
npm run build:wordpress
```

PHP syntax checks should be run against touched custom theme/plugin files.

## Active API base

The frontend resolves its logical API paths to:

```text
http://localhost/wordpress/wp-json/jazireh/v1
```

See [`docs/API.md`](docs/API.md) for endpoint details.

## External integrations

- NASA APOD: server-side in WordPress
- YouTube latest videos: server-side in WordPress through the channel uploads-playlist API flow with last-known-good cache fallback
- YouTube Community posts: external adapter -> signed WordPress ingestion -> WordPress storage
- Sky: current release uses a static compatibility payload, not live OpenWeather data

Do not expose API keys to the browser.

## Production deployment outline

The eventual hosting package should include:

- WordPress core on the target host
- custom theme `jazireh-theme`
- custom plugin `jazireh-core`
- WordPress database content
- uploads/media
- production `wp-config.php` and hosting configuration

It should not include:

- `frontend/node_modules`
- the retired standalone backend as active application code
- stale Vite bundles
- local XAMPP paths
- legacy database dumps unless intentionally archived

## Security notes

- Native WordPress auth is authoritative.
- Do not commit secrets, API keys, or real environment files.
- Keep WordPress and custom code on HTTPS in production.
- Review uploads, plugin/theme permissions, backups, and monitoring before go-live.

## Known limitations

- Sky currently serves a static compatibility snapshot.
- Live Jazireh Community extraction is not verified in this local environment on August 19, 2026; the adapter, secure ingest path, and WordPress fallback platform are implemented.
- Radar is a simulation, not live tracking.
- Explore uses authored educational object data.
- Lint still contains a set of pre-existing non-blocking issues outside the final migration scope.

## Developer

Designed and developed by **Artin Karimi** for a real client project.
