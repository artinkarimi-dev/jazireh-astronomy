<div align="center">

# Jazireh Astronomy

**A production-oriented Persian astronomy platform created for a real client.**

React · Vite · Tailwind CSS · Three.js · PHP · MySQL

[نسخه فارسی](README.fa.md) · [Architecture](docs/ARCHITECTURE.md) · [API](docs/API.md)

</div>

> [!IMPORTANT]
> This is a **public source-available portfolio repository**, not an open-source project. The source may be reviewed for portfolio, recruitment, and technical evaluation. No permission is granted to install, execute, deploy, copy, modify, redistribute, sublicense, sell, or commercially use this project. See [`LICENSE`](LICENSE).

![Jazireh Astronomy homepage overview](docs/screenshots/homepage-overview.png)

## Overview

Jazireh Astronomy is a responsive Persian RTL web experience designed around astronomy education and space exploration. It combines a cinematic dark interface, interactive sky experiences, a Three.js solar-system scene, astronomy content, and a focused administration backend.

The project was developed for a real client and is published with the client's permission as a complete code-review portfolio piece. Repository visibility does not grant reuse rights.

## Product highlights

- Fully responsive Persian RTL interface
- Cinematic dark visual system with glass-like surfaces
- Route-level lazy loading and reusable React components
- Interactive 3D solar-system experience with React Three Fiber
- Interactive constellation map and simulated sky radar
- Astronomy news and content-oriented public pages
- Lightweight PHP REST API with PDO
- JWT-protected administrator routes
- MySQL content model and Persian seed content
- Optional NASA APOD and OpenWeather integrations
- Local fallback data for resilient interface demonstrations

## Implementation status

| Area | Status |
|---|---|
| Responsive Persian RTL interface | Implemented |
| Public news listing | API-backed |
| Administrator authentication | API-backed; no public default credentials |
| Administrator news creation and deletion | API-backed |
| News details | Uses local fallback content |
| APOD, videos, sky, and celestial-object endpoints | Implemented in backend |
| APOD, videos, sky, and exploration pages | Currently use local fallback content |
| NASA APOD synchronization | Implemented in backend |
| OpenWeather synchronization | Implemented in backend |
| Sky radar | Interactive simulation, not live tracking |
| Automated GitHub quality checks | Included |
| End-to-end test suite | Not included yet |

## Architecture

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

More detail is available in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md).

## Technology stack

### Frontend

- React 18
- Vite 5
- Tailwind CSS 3
- React Router
- Framer Motion
- Three.js
- React Three Fiber
- React Three Drei
- Lucide React

### Backend

- PHP 7.4+
- PDO
- Custom REST routing
- HMAC-SHA256 JWT authentication
- cURL-based external API clients

### Data

- MySQL
- UTF-8 Persian content
- Structured content for news, APOD entries, videos, sky conditions, celestial objects, subscribers, and settings

## Repository structure

```text
jazireh-astronomy/
├── .github/                  GitHub quality workflow
├── backend/                  PHP REST API
├── database/                 MySQL schema and sample content
├── docs/                     Architecture, API, screenshots, repository setup
├── frontend/                 React application
├── LICENSE                   Custom source-available terms
├── README.md                 English project overview
└── README.fa.md              Persian project overview
```

## Security and publication hygiene

This GitHub edition was prepared separately from the working client copy:

- Real `.env` files are excluded.
- The previously used JWT secret is not included.
- The public SQL seed contains no active administrator account.
- The login interface contains no prefilled credentials.
- Generated frontend output is excluded from version control.
- Runtime logs, uploaded files, and cache output are ignored.
- JWT creation fails when a sufficiently strong secret is not configured.

Repository secrets must never be committed. External API credentials belong in environment variables only.

## Known limitations

- Several public routes still render fallback data instead of their corresponding API response.
- The news-details route does not yet request an article by slug from the backend.
- APOD and video administrator endpoints exist, but dashboard forms for those sections are not implemented.
- The newsletter interface is not yet connected to its backend endpoint.
- Radar data is simulated.
- The 3D scene is designed for visual exploration rather than scientific scale or orbital accuracy.

## Media and third-party material

The project name, brand identity, client-provided content, written material, screenshots, images, and videos may have rights separate from the source code. They are not granted for reuse by this repository.

Third-party libraries remain governed by their own licenses. See [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

## Contributions

This repository is published for review, not community development. Pull requests and feature contributions are not requested. See [`CONTRIBUTING.md`](CONTRIBUTING.md).

## Author

Developed by **Artin Karimi** for a real client project.

## License

**All rights reserved.** This repository uses a custom source-available license. It is not an OSI-approved open-source license. Review the complete terms in [`LICENSE`](LICENSE) before interacting with the code.
