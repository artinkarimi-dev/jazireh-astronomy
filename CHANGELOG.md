## 2026-08-05 — Responsive public-interface rebuild

- Rebuilt the public frontend with a 12-column responsive bento system.
- Replaced repeated full-width hero media with one restrained global starfield background.
- Removed the desktop sidebar and recovered horizontal space.
- Reworked Home, Sky, Explore, News, APOD, Videos, Radar, header, mobile navigation, and footer.
- Added the high-resolution Jazireh logo and optimized derivatives.
- Added a high-resolution NASA Webb Carina image with attribution.
- Optimized the supplied videos for web playback.
- Kept the backend, API integration, admin area, repository policy, and security work intact.

# Changelog

## 2026-08 — Professional GitHub and product pass

- Integrated the supplied Jazireh logo across the header, footer, favicon, PWA icons, and YouTube sections.
- Added centralized site, YouTube, and developer-credit configuration.
- Added a dedicated homepage feature for the official `@Jazireh` YouTube channel.
- Added a single restrained developer attribution in the global footer.
- Connected homepage, news details, APOD, videos, sky conditions, exploration, and newsletter interfaces to existing API endpoints with safe fallback behavior.
- Expanded administrator workflows for news, APOD, and videos.
- Removed public default credentials and added a secure CLI administrator-creation script.
- Hardened JWT validation, login behavior, and CORS handling.
- Added bilingual GitHub documentation, custom source-available terms, security policy, Dependabot, and automated quality checks.
- Removed working `.env` files, generated frontend output, runtime files, and private design references from the GitHub edition.

## 1.2.0 - Client polish and responsive rebuild

- Removed internal design and implementation language from public pages.
- Limited the supplied star video to the homepage hero.
- Rebuilt the homepage with a client-focused hero and balanced content cards.
- Standardized card and panel heights across desktop layouts.
- Replaced the unstable WebGL explorer with a lightweight interactive solar-system view.
- Removed React Three Fiber, Drei, and Three.js dependencies.
- Prevented low-quality local placeholder images from appearing in APOD.
- Updated public copy, color accents, navigation, and YouTube calls to action.
