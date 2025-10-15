# Runbook: Incident Response

Coordinate a rapid, safe response to production incidents.

## Detection
- Monitor alerts (APM, logs, uptime checks) and user reports.
- Confirm severity and scope (critical paths, affected users, regions).

## Triage
- Assign incident commander and roles (comms, ops, dev).
- Enable maintenance mode for critical issues to preserve data integrity.
- Capture context: recent deploys, config changes, error signatures.

## Mitigation
- Apply safe feature flags or config changes to reduce impact.
- Rollback if mitigation fails (see `Rollback.md`).
- Scale resources if the issue is load‑related.

## Investigation
- Gather logs, traces, metrics, and reproduce on staging if possible.
- Identify root cause; document hypotheses and findings.

## Resolution
- Deploy fix under standard CI/CD gates where feasible.
- Verify resolution through probes, logs, and canary checks.

## Communication
- Update status page and notify stakeholders at key milestones.
- Publish a post‑mortem after resolution.

## Post‑Incident
- Add tests and guards to prevent recurrence.
- Review CI/CD gates, monitoring alerts, and runbooks for improvements.