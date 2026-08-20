# Jazireh Community Sync Adapter

This adapter is intentionally outside the WordPress runtime.

It performs one job:

```text
YouTube Community
  -> normalized Jazireh Daily payload
  -> signed POST to WordPress ingestion endpoint
```

It must never be called by public page requests.

## Manual dry run

```bash
node tools/community-sync/sync.mjs --channel-url=https://www.youtube.com/@Jazireh/posts --dry-run
```

## Direct sync

```bash
node tools/community-sync/sync.mjs --channel-url=https://www.youtube.com/@Jazireh/posts --ingest-url=http://localhost/wordpress/wp-json/jazireh/v1/jazireh-daily-sync --secret=YOUR_SYNC_SECRET
```

## Proxy sync

```bash
node tools/community-sync/sync.mjs --channel-url=https://www.youtube.com/@Jazireh/posts --ingest-url=http://localhost/wordpress/wp-json/jazireh/v1/jazireh-daily-sync --secret=YOUR_SYNC_SECRET --proxy=http://127.0.0.1:10808
```

## Fixture-based ingest test

```bash
node tools/community-sync/sync.mjs --from-file=tools/community-sync/sample-payload.json --ingest-url=http://localhost/wordpress/wp-json/jazireh/v1/jazireh-daily-sync --secret=YOUR_SYNC_SECRET
```

`--proxy` is optional. If it is not provided, the adapter also checks `HTTPS_PROXY` and `HTTP_PROXY`.

Proxy configuration is applied only to upstream external extraction requests. Local WordPress ingestion URLs such as `http://localhost/wordpress/...` remain direct.

When the adapter can reach YouTube thumbnails but WordPress cannot, the adapter also embeds the normalized community image bytes in the signed payload so WordPress can persist local media without depending on direct CDN access.

## Production scheduling

Run every 30-60 minutes from any machine that can access:

- `https://www.youtube.com/@Jazireh/posts`
- the WordPress ingestion endpoint

Recommended scheduler examples:

- Linux cron
- systemd timer
- external CI runner
- managed job runner

Do not run this from visitor requests.

## Local automatic sync

On the local Windows development machine, automatic Community sync is installed as a persistent Windows Task Scheduler task:

- task name: `Jazireh Community Sync`
- frequency: every 30 minutes
- extra trigger: at user logon
- local helper: `tools\community-sync\sync-local.cmd`
- proxy requirement: `http://127.0.0.1:10808` must be reachable through v2rayN
- WordPress ingestion: direct to `http://localhost/wordpress/wp-json/jazireh/v1/jazireh-daily-sync`

The scheduled task never stores the Jazireh sync secret in task arguments. The helper reads the secret from the local WordPress runtime each time it runs, then passes it to `sync.mjs` through a temporary process environment variable.

### Logs

Local logs are written to:

`tools\community-sync\logs\`

Files:

- `history.log` -> one summary line per automated run
- `latest.log` -> latest request/error log
- `latest.json` -> latest adapter JSON result
- `run-YYYYMMDD-HHMMSS.log` -> per-run request/error log
- `run-YYYYMMDD-HHMMSS.json` -> per-run adapter JSON result

Old per-run files older than 14 days are removed automatically. `history.log` is trimmed to the latest 1000 lines.

### v2rayN requirement

Before each run, `sync-local.cmd` checks whether `127.0.0.1:10808` is reachable.

If the proxy is unavailable:

- the run exits safely
- no WordPress content is deleted
- the failure is logged
- the next scheduled run retries naturally

### Concurrency protection

`sync-local.cmd` creates a local lock directory before running.

If a previous sync is still active:

- the new run is skipped safely
- no overlapping adapter process starts

WordPress-side deduplication and sync protections remain unchanged.

### Install / run / disable / remove

Install or refresh the scheduled task:

```bat
tools\community-sync\install-scheduler.cmd
```

Run the scheduled task immediately:

```bat
tools\community-sync\run-scheduler-now.cmd
```

Check the task:

```bat
schtasks /Query /TN "Jazireh Community Sync" /V /FO LIST
```

Disable the task:

```bat
schtasks /Change /TN "Jazireh Community Sync" /Disable
```

Enable it again:

```bat
schtasks /Change /TN "Jazireh Community Sync" /Enable
```

Delete the task:

```bat
tools\community-sync\remove-scheduler.cmd
```
