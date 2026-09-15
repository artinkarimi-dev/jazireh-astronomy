# Jazireh WordPress API

## Base URL

```text
http://localhost/wordpress/wp-json/jazireh/v1
```

All active custom endpoints are served by the `jazireh-core` WordPress plugin. Responses are JSON and use this envelope:

```json
{
  "success": true,
  "data": {}
}
```

Error responses return an appropriate HTTP status and may include `message` and `errors`.

## Public endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/home` | Homepage data for Sky, featured news, and latest videos |
| `GET` | `/site` | Site identity, menus, homepage, footer, social, and contact settings |
| `GET` | `/news` | Published news list |
| `GET` | `/news/{slug}` | Published news detail |
| `GET` | `/videos` | Latest videos |
| `GET` | `/apod` | NASA APOD archive served through WordPress |
| `GET` | `/jazireh-daily` | Jazireh Daily archive stored in WordPress |
| `GET` | `/jazireh-daily/{slug}` | Jazireh Daily detail |
| `GET` | `/sky` | Current shipped Sky snapshot payload |
| `GET` | `/objects` | Published celestial objects for Explore |
| `POST` | `/newsletter` | Newsletter subscription |

## Query parameters

### `GET /news`

| Parameter | Notes |
|---|---|
| `limit` | `1..100`, default `24` |
| `category` | Category slug |
| `search` | Free-text search |

### `GET /videos`

| Parameter | Notes |
|---|---|
| `limit` | `1..10`, default `3` |

### `GET /apod`

| Parameter | Notes |
|---|---|
| `limit` | `1..20`, default `20` |

### `GET /jazireh-daily`

| Parameter | Notes |
|---|---|
| `limit` | `1..24`, default `12` |

## Example requests

```text
GET /news?limit=24&category=cosmology&search=galaxy
GET /apod?limit=20
POST /newsletter
```

Newsletter request body:

```json
{
  "email": "reader@example.com"
}
```

## APOD behavior

- WordPress fetches APOD data server-side from NASA.
- The browser never receives the NASA API key.
- Results are cached in WordPress transients for six hours.
- `limit=20` requests the current rolling date range from NASA and is sorted newest to oldest before being returned.

## Videos behavior

- WordPress uses the official YouTube Data API server-side.
- Latest-video retrieval follows the channel uploads-playlist flow:
  `channels.list -> relatedPlaylists.uploads -> playlistItems.list -> videos.list -> filter -> normalize -> cache`
- The browser never receives the YouTube API key.
- Latest public videos are filtered to exclude Shorts, current live streams, and upcoming streams/premieres.
- Cached latest videos expire after 45 minutes.
- A separate last-known-good cache is retained so temporary YouTube failures do not blank the public site.

## Jazireh Daily behavior

- Jazireh Daily content is stored as WordPress `jazireh_daily` posts.
- Public visitors read only from WordPress REST and WordPress media.
- Automatic Community extraction is intentionally external to the public site runtime.
- WordPress accepts synchronized posts only through the signed `POST /jazireh-daily-sync` ingestion endpoint.
- The sync endpoint is not for browsers or public visitors and requires HMAC headers.
- Manual WordPress creation/editing remains a supported fallback.

As of Wednesday, August 19, 2026, the live Jazireh Daily platform and ingestion architecture are implemented, but live extraction from the upstream Jazireh Community page is not verified in this local environment.

## Sky behavior

The current shipped Sky payload is a static compatibility snapshot sourced from WordPress settings plus packaged observational values. It is intentionally not a live OpenWeather integration in the current product.

## Auth and admin

Public Jazireh content no longer uses the retired standalone PHP auth flow.

- Admin authority is native WordPress.
- `/admin` redirects to `wp-admin`.
- `/admin/login` redirects to `wp-login.php`.
- There is no active custom JWT login runtime in the current product.

## Legacy API status

The standalone PHP backend under `backend/` is retired from the active runtime architecture. References to `/api/auth`, `/api/admin`, and the legacy PHP API are obsolete and should not be used for current development or deployment.
