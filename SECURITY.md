# Security Policy

## Reporting a vulnerability

Do not disclose a suspected vulnerability in a public issue, discussion, pull request, or social-media post.

Use GitHub private vulnerability reporting when it is enabled for the repository. Otherwise, contact the repository owner privately through the GitHub profile associated with this repository and provide:

- the affected component;
- reproduction steps;
- the potential impact;
- any suggested mitigation;
- whether the report has been shared elsewhere.

The public repository contains no production credentials. Any secret discovered in Git history should still be treated as compromised and rotated immediately.

## Supported version

Only the latest state of the default branch is considered for security review. This portfolio repository does not provide a public support or patch commitment.

## Production deployment hardening

Keep production secrets in `wp-config.php`, host environment variables, or a private server secret store. Do not commit `.env` files, database dumps, API keys, WordPress salts, or provider credentials. NASA, YouTube, and sync credentials must stay server-side; the React build and public REST payloads must never expose them.

Recommended production constants:

```php
define('WP_DEBUG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);
define('SAVEQUERIES', false);
define('DISALLOW_FILE_EDIT', true);
define('JAZIREH_ENABLE_HSTS', true); // HTTPS production only.
```

Do not enable `JAZIREH_ENABLE_HSTS` on local HTTP/XAMPP. Local development may keep debug logging enabled privately, but public pages and REST responses should not display PHP warnings, stack traces, SQL errors, or filesystem paths.

The theme sends application security headers for public responses, including CSP, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and frame protection. HSTS is intentionally opt-in and only sent over HTTPS when `JAZIREH_ENABLE_HSTS` is true.

For Apache deployments, keep the hardening block in `wordpress/.htaccess` active. It disables directory indexes and denies web access to dotfiles, `.git`, logs, SQL dumps, backups, and local config artifacts while preserving normal WordPress rewrites.

For low-traffic production, disable traffic-triggered cron only when a real server cron exists:

```php
define('DISABLE_WP_CRON', true);
```

Then run a host cron every 5-15 minutes against `wp-cron.php`. Keep standard WP-Cron enabled on local XAMPP unless an external runner is configured.

Production operations remain responsible for TLS certificates, backups, file permissions, WordPress/core/plugin/theme updates, strong admin credentials, and host-level firewall/rate-limit controls.
