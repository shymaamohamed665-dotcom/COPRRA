# Runbook: Rollback

Restore the previous stable version when a deployment causes regressions.

## Preconditions

- Last known good artifact available and integrity‑checked.
- Access to server or deployment system.
- Database migration plan reviewed (forward/backward compatibility).

## Procedure

1. Announce rollback to stakeholders and freeze new deploys.
2. Stop traffic to the failing release if needed (maintenance mode).
    ```bash
    php artisan down --message="Rolling back to previous version"
    ```
3. Restore previous artifact (code + assets) to the application directory.
4. Database:
    - If migrations introduced breaking changes, run targeted rollback:
        ```bash
        php artisan migrate:rollback --step=1 --force
        ```
    - Prefer additive, backward‑compatible migrations to minimize need to rollback DB.
5. Warm caches:
    ```bash
    php artisan config:cache && php artisan route:cache && php artisan view:cache
    ```
6. Bring app back:
    ```bash
    php artisan up
    ```
7. Verify via health checks, logs, and monitoring.

## Post‑Rollback

- Create an incident report (root cause, impact, remediation).
- Add tests to prevent recurrence; update CI/CD gates as needed.
- Schedule follow‑up deploy when fix is ready.
