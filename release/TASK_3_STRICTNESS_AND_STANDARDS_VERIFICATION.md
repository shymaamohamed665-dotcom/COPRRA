TASK 3 — Strictness & Standards Verification Plan (Max Strictness)

Objective: For each test/tool in the inventory, verify configuration is set to maximum strictness and aligned with international standards (PSR, OWASP, ISO, W3C where applicable). Per Task 0: do not modify files — only read, verify and record findings.

Scope:

- Static tools: phpstan, psalm, phpmd, php-cs-fixer/pint, phpinsights, phpcs, phpmetrics
- Test suites: PHPUnit (ensure strict flags used), Dusk (browser tests), performance & security tests
- Security tools: composer audit, security-checker, custom security tests

Checklist (applied for each item):

1. Tool Config Present: confirm config file exists and is readable (e.g., phpstan.neon, psalm.xml, phpmd.xml, phpunit.xml).
2. Max strictness configured: known settings ->
    - phpstan: level: max
    - psalm: strict mode settings (strictParamChecks, strictReturnTypeChecks, etc.) enabled
    - phpunit: failOnRisky, failOnWarning, beStrictAbout\* flags enabled
    - phpmd: rulesets include cleancode,codesize,controversial,design,naming,unusedcode
    - phpinsights: configured and active
    - pint/php-cs-fixer: rules present and test flag available
3. Standards mapping:
    - PSR: code style/linting tools should align to PSR-12/PSR-4 where applicable
    - OWASP: security tests cover common classes (XSS, SQLi, CSRF, Auth, Encryption)
    - ISO: where applicable, note controls missing (e.g., logging, access control matrix)
    - W3C: for front-end assets, ensure linting/validations exist if applicable
4. File integrity: confirm config files are syntactically valid (parseable)
5. Failure modes: record any absence, lower-than-max strictness, malformed configs, or disabled checks. DO NOT FIX.

Output:

- A per-tool report recording: config present (Y/N), strictness level, compliance mapping (PSR/OWASP/ISO/W3C), parsing errors (if any).
- Save reports to: /var/www/html/reports/task3/

Execution guidelines:

- Read-only operations only.
- Capture stderr/stdout of parsing commands where safe (e.g., `php -l`, `./vendor/bin/phpstan analyse --version`), but avoid network calls.
- Do not run analysis that would change files.

Next: I'll run read-only checks for phpstan.neon, psalm.xml, phpunit.xml, phpmd.xml, composer.json scripts and parse them to record strictness settings and produce a report file under /var/www/html/reports/task3/ (Task 3 initial pass).
