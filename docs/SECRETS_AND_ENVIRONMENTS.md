# Secrets & Environments

This guide standardizes how secrets and environment configuration are handled across local, CI, staging, and production.

## Environment Categories
- Local development: `.env` based; Docker Compose exports safe defaults.
- Testing (CI): ephemeral env with secrets from GitHub Actions `secrets.*`.
- Staging: mirrors production with protected environment and reviewers.
- Production: hardened environment with restricted access and immutable artifacts.

## Core Environment Variables
- Application: `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY`.
- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Cache/Queue: `CACHE_DRIVER`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `REDIS_HOST`, `REDIS_PORT`.
- Mail: `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_*`.
- Third‑party: AWS/S3, Pusher, Mailgun, etc. as defined in `README.md`.

## Secrets Handling Principles
1. Never commit secrets to VCS; use `.env` locally and CI/CD secrets for pipelines.
2. Prefer environment variables over config files for credentials.
3. Rotate secrets periodically and after any suspected exposure.
4. Scope secrets minimally; avoid sharing credentials across environments.
5. Audit secrets usage regularly (composer/npm audits, secret scanners).

## GitHub Actions
- Store secrets under repository or environment secrets.
- Reference secrets in workflows via `${{ secrets.NAME }}`.
- Use environment protection rules (required reviewers, wait times).

## Hostinger Deployment
- Ensure `config/hostinger.php` sources values from environment variables provided by the workflow.
- Avoid embedding static credentials; keep server addresses, paths, and auth in secrets.

## Docker Compose
- Development values are set via `.env` and `docker-compose.override.yml`.
- Do not commit sensitive `.env` values; use `.env.example` as a template.

## Verification Checklist
- `.env` present locally, not committed.
- CI secrets populated and documented for maintainers.
- Production environment documented with required access and rotation schedule.
- No credentials found in repo via secret scanning.