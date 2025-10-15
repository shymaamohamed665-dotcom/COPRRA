# Runbook: Deployment

This runbook standardizes how to deploy the application safely and consistently.

## Pre‑Deployment Checklist
- CI green on required checks (unit, feature, integration, security).
- Coverage thresholds met; audits pass (Composer/NPM).
- Changelog updated; migrations reviewed for safety.
- Secrets validated for target environment.

## Deployment Paths
### 1) GitHub Actions (Recommended)
1. Push to `main` (or trigger manual dispatch) with approved changes.
2. Deployment workflow builds production artifact and runs gates.
3. Artifact is shipped to Hostinger via `scripts/deploy-to-hostinger.php`.
4. Post‑deploy probes execute; deployment reports posted.

### 2) Manual (Fallback)
1. Upload build to server (`public_html` or container volume).
2. Set environment variables and `.env`.
3. Run:
   ```bash
   php artisan key:generate
   php artisan migrate --force
   php artisan storage:link
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

## Post‑Deployment Verification
- Health endpoint returns OK (e.g., `/api/health`).
- Error logs clean; Sentry/monitoring shows no spikes.
- Cache/queue operational; jobs processing and no backlog growth.

## Rollback Trigger
- Deploy status red, probes failing, or elevated error rates.
- See `docs/runbooks/Rollback.md` for procedure.