# Jazireh Astronomy API

The PHP backend exposes a JSON REST API. The base URL depends on the deployment environment and is configured through `APP_URL` and `VITE_API_URL`.

## Public endpoints

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/health` | API and database health check |
| `GET` | `/api/home` | Aggregated homepage content |
| `GET` | `/api/news` | Published news list |
| `GET` | `/api/news/{slug}` | News article details |
| `GET` | `/api/apod` | Astronomy Picture of the Day archive |
| `GET` | `/api/apod/today` | Latest APOD entry |
| `GET` | `/api/videos` | Published video list |
| `GET` | `/api/sky/today` | Latest sky conditions |
| `GET` | `/api/objects` | Celestial-object list |
| `GET` | `/api/objects/{slug}` | Celestial-object details |
| `POST` | `/api/newsletter` | Newsletter subscription |
| `POST` | `/api/auth/login` | Administrator authentication |

## Protected administrator endpoints

Protected requests require an environment-specific administrator account and a valid bearer token:

```text
Authorization: Bearer <token>
```

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/api/auth/me` | Current administrator |
| `GET` | `/api/admin/news` | All news entries |
| `POST` | `/api/admin/news` | Create news |
| `PUT` | `/api/admin/news/{id}` | Update news |
| `DELETE` | `/api/admin/news/{id}` | Delete news |
| `POST` | `/api/admin/apod` | Create APOD entry |
| `PUT` | `/api/admin/apod/{id}` | Update APOD entry |
| `DELETE` | `/api/admin/apod/{id}` | Delete APOD entry |
| `POST` | `/api/admin/videos` | Create video |
| `PUT` | `/api/admin/videos/{id}` | Update video |
| `DELETE` | `/api/admin/videos/{id}` | Delete video |
| `POST` | `/api/admin/sky` | Store sky conditions |
| `POST` | `/api/admin/sync/apod` | Synchronize NASA APOD |
| `POST` | `/api/admin/sync/weather` | Synchronize OpenWeather data |

## Authentication request shape

The public repository intentionally contains no working administrator credentials.

```json
{
  "email": "<environment-specific-admin-email>",
  "password": "<environment-specific-password>"
}
```

## News creation request shape

```json
{
  "title": "عنوان خبر",
  "slug": "sample-news-slug",
  "excerpt": "خلاصه خبر",
  "content": "متن کامل خبر",
  "category": "کیهان‌شناسی",
  "image": "/media/galaxy.jpg",
  "status": "published",
  "featured": true,
  "readingTime": "۵ دقیقه"
}
```

## Error format

Errors are returned as JSON with an appropriate HTTP status code. Debug details are suppressed when `APP_DEBUG=false`.
