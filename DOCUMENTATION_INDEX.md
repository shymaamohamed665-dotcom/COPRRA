# Documentation Index

This index summarizes environment configuration, Docker/PHP/Laravel alignment, and health-check standards for COPRRA.

## Environment Variables

- `APP_ENV`: Use `local` for dev, `production` for live.
- `APP_DEBUG`: `true` for local; must be `false` in production.
- `HEALTH_CHECKS_ENDPOINT`: Defaults to `/api/health`.
- `SESSION_SAME_SITE`: Use `none` when the frontend is on a different domain.
- `SESSION_SECURE_COOKIE`: Set `true` when using `none` for SameSite.
- `SESSION_DOMAIN`: Set to your root domain (e.g., `.example.com`) when sharing cookies across subdomains.
- `SESSION_PATH`: Usually `/`.
- `CORS_SUPPORTS_CREDENTIALS`: `true` when cookies or auth headers are needed across origins.
- `CORS_ALLOWED_ORIGINS`: Comma-separated origins for dev/prod.

## Docker & PHP Configuration

- Unified PHP limits across environments:
  - `memory_limit=512M`
  - `max_execution_time=120`
  - `post_max_size=50M`
  - `upload_max_filesize=50M`
- OPcache policies:
  - Production: `validate_timestamps=0`, `revalidate_freq=0`, `memory_consumption=256`, `max_accelerated_files=10000`, `enable_cli=0`.
  - Development: `validate_timestamps=1`, `revalidate_freq=0`.
- Nginx:
  - `client_max_body_size 50M;`
  - Block PHP execution in `/uploads`.
  - Proxy health to `/api/health`.

## Laravel

- Health route is configured at `bootstrap/app.php` as `/api/health`.
- `config/app.php` “PHP Configuration” keys are informational only; engine-level PHP settings live in `php.ini`.
- CORS and session defaults support cross-origin SPA setups via `.env.example`.

## Frontend (Node/Vite)

- `axios` is a runtime browser dependency → in `dependencies`.
- `codecov` is CI-only → in `devDependencies`.
- Node runtime: `"engines": { "node": ">=20" }`.
- `vite` pinned to `^5` for compatibility with `laravel-vite-plugin@^2.0.1`.
- Build output: `public/build`.

## Testing & Static Analysis

- Dev tools present: `phpunit`, `phpstan`, `psalm`, `pint`.
- Add assertions to avoid “risky tests”.
- Ensure SQLite in-memory is configured for fast tests if applicable.

## Health Check

- Unified endpoint: `GET /api/health`.
- Nginx proxies the route; tests validate the endpoint under Feature suite.

