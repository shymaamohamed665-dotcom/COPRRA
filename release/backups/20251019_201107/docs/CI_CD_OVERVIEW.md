# CI/CD Overview

This document summarizes the project’s continuous integration and deployment workflows, required checks, caching, environment protections, artifact strategy, and rollback guidance.

## Workflows

- `.github/workflows/ci.yml`: CI on `main`/`develop`; installs dependencies, builds assets, migrates DB, runs tests, uploads coverage (Codecov).
- `.github/workflows/comprehensive-tests.yml`: full test matrix (build, static analysis, unit, feature, AI, security, performance, integration, architecture, browser, mutation) with artifacts and a summary.
- `.github/workflows/security-audit.yml`: Composer/NPM audits, PHPStan/Psalm/PHPMD/Deptrac/Pint, ESLint/Stylelint/Prettier, Infection security runs.
- `.github/workflows/performance-tests.yml`: load/stress/memory/database/API suites with MySQL service and coverage artifacts.
- `.github/workflows/deployment.yml`: push/manual deploys; production build, extensive pre-deploy gates, Hostinger deploy via `scripts/deploy-to-hostinger.php`.

## Required Checks (Recommendation)

- Require status checks before merging to protected branches: unit, feature, integration, security, performance minimal gates.
- Enforce coverage thresholds (e.g., 80% project, 90% for critical packages) via Codecov.

## Caching

- Composer: cache `~/.composer/cache` keyed by `composer.lock`.
- NPM: cache `~/.npm` keyed by `package-lock.json`.
- Avoid caching `vendor/` and `node_modules/` directly; rely on lockfile caches.

## Environments & Concurrency

- Use GitHub Environments: `staging`, `production` with required reviewers and wait timers.
- Add concurrency control to deployment:
    ```yaml
    concurrency:
        group: deploy-production
        cancel-in-progress: true
    ```

## Deployment Strategy

- Prefer immutable artifacts built in CI (composer install with `--no-dev`, optimized autoload, compiled assets, cached config/routes) packaged and deployed.
- Avoid remote Composer install on servers when possible.
- Pre-deploy gates: smoke tests, security audits, code quality checks. Post-deploy probes and health checks.

## Rollback

- Keep last N artifacts (e.g., 3) accessible.
- Provide a `deploy:rollback` job or runbook to restore previous artifact; reverse migrations where appropriate.
- Document DB rollback considerations and data preservation.

## Secrets

- Store secrets in GitHub `secrets.*` and environment-level secrets; never commit credentials.
- For Hostinger deploy, pass credentials via workflow env and ensure `config/hostinger.php` reads from env only.

## Observability

- Publish Job Summary with pass/fail counts, coverage links, audit results.
- Retain key artifacts (coverage reports, audit outputs, performance metrics) for 14–30 days.

## Automated Dependency Updates (Recommendation)

- Add `.github/dependabot.yml` for `github-actions`, `composer`, and `npm` (weekly cadence), and gate PRs on audits and CI.
