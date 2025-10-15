# Runbook: Cache & Queue Maintenance

Keep cache and queue systems healthy and predictable.

## Cache Operations
- Clear caches after config changes or deploy anomalies:
  ```bash
  php artisan optimize:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
- Use Redis for cache/session in production; ensure availability and persistence.

## Queue Workers
- Ensure workers run under correct user and environment.
- Prefer Laravel Horizon for monitoring and auto‑restarts, or harden Supervisor:
  - Separate queues by priority; distinct worker processes per queue.
  - Configure `--sleep`, `--timeout`, `--tries`, and memory limits.
  - Implement periodic recycling and staggered restarts.

## Failed Jobs
- Confirm `failed_jobs` table is present and `QUEUE_FAILED_DRIVER=database-uuids`.
- Periodically review and requeue or archive failed jobs.

## Scheduled Maintenance
- Weekly worker recycle window to prevent memory leaks.
- Monthly cache cleanup and TTL review.
- Audit queue throughput and backlog; adjust worker counts accordingly.

## Verification Checklist
- Horizon/Supervisor dashboards show healthy workers.
- No backlog growth; average queue time within SLA.
- Cache hit rate acceptable; config caches up to date.