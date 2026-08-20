# Jazireh WordPress API

## Base URL

Local development example:

```text
http://localhost/wordpress/wp-json/jazireh/v1
```

On staging or production, the host/domain changes, but the active custom REST namespace remains `jazireh/v1`.

All active public endpoints are served by the `jazireh-core` WordPress plugin. Successful responses use this envelope:

```json
{
  "success": true,
  "data": {}
}
```

## Public Endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/home` | Homepage payload for featured content sections |
| `GET` | `/site` | Branding, menus, homepage, footer, and social settings |
| `GET` | `/news` | Published news list |
| `GET` | `/news/{slug}` | Published news detail |
| `GET` | `/videos` | Latest public videos |
| `GET` | `/apod` | NASA APOD archive served through WordPress |
| `GET` | `/jazireh-daily` | Jazireh Daily archive stored in WordPress |
| `GET` | `/jazireh-daily/{slug}` | Jazireh Daily detail |
| `GET` | `/sky` | Current shipped Sky payload |
| `GET` | `/objects` | Curated celestial objects for Explore |
| `POST` | `/newsletter` | Newsletter subscription |

## Query Parameters

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

## Example Requests

```text
GET /news?limit=24&category=cosmology&search=galaxy
GET /videos?limit=3
GET /apod?limit=20
POST /newsletter
```

Newsletter request body:

```json
{
  "email": "reader@example.com"
}
```

## APOD Behavior

- WordPress fetches APOD data server-side from NASA
- the browser never receives the NASA API key
- `limit=20` produces a rolling date range ending on the current day
- the request uses `start_date`, `end_date`, and `thumbs=true`
- results are normalized and sorted newest to oldest
- cached results are stored in transients with keys such as `jazireh_apod_latest_range_20`

## Videos Behavior

- WordPress uses the official YouTube Data API server-side
- latest-video retrieval follows:
  `channels.list -> relatedPlaylists.uploads -> playlistItems.list -> videos.list -> normalize -> cache`
- Shorts, live streams, and upcoming streams are excluded from the public latest-videos output
- fresh cache lifetime is 45 minutes
- a separate last-known-good cache is retained so transient upstream failures do not blank the public site

## Jazireh Daily Behavior

- Jazireh Daily content is stored as WordPress `jazireh_daily` posts
- public visitors read only from WordPress REST and WordPress-managed media
- automatic extraction is intentionally external to the public site runtime
- the ingestion endpoint is `POST /jazireh-daily-sync`
- synchronization requires HMAC headers, timestamp validation, and replay protection
- WordPress deduplicates items by upstream source identity and content hash

## Sky Behavior

The current shipped Sky payload is a static compatibility snapshot sourced from WordPress-managed values and packaged observational content. It is intentionally not a live OpenWeather-style integration in the current repository state.

## Auth and Admin

Public Jazireh content no longer uses the retired standalone PHP auth flow.

- admin authority is native WordPress
- `/admin` redirects to `wp-admin`
- `/admin/login` redirects to `wp-login.php`
- no active custom JWT login runtime is part of the current public product
