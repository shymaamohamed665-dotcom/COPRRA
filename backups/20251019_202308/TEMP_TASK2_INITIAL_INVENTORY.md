Initial inventory of tests, tools and scripts (partial) for COPRRA project

This file is an automated snapshot created during Task 1/Task 2 preparatory phase.

Sources scanned:

- composer.json scripts
- root-level shell scripts
- vendor/bin executables
- tests/ directory (Test files)

---

Scripts found in composer.json (selected):

- test (vendor/bin/phpunit)
- test:ai (vendor/bin/phpunit --testsuite AI)
- test:security (vendor/bin/phpunit --testsuite Security)
- test:performance (vendor/bin/phpunit --testsuite Performance)
- test:integration (vendor/bin/phpunit --testsuite Integration)
- test:comprehensive (phpunit.xml + junit + coverage)
- measure:all (pint, phpstan, phpmd, composer audit, test:comprehensive)
- analyse:all (phpstan, psalm, phpinsights, security)
- format (pint)

Root shell scripts discovered (full list exported):

- setup.sh (/var/www/html/setup.sh)
- run_all_450_tests.sh (/var/www/html/run_all_450_tests.sh)
- run_450_tests_visible.sh (/var/www/html/run_450_tests_visible.sh)
- run-all-checks.sh (/var/www/html/run-all-checks.sh)
- monitor_task4_progress.sh (/var/www/html/monitor_task4_progress.sh)
- execute_task4_individual_tests.sh (/var/www/html/execute_task4_individual_tests.sh)
- execute_task4_demo.sh (/var/www/html/execute_task4_demo.sh)
- execute_task4_batch_runner.sh (/var/www/html/execute_task4_batch_runner.sh)
- execute-audit-phases.sh (/var/www/html/execute-audit-phases.sh)
- comprehensive-quality-audit.sh (/var/www/html/comprehensive-quality-audit.sh)
- comprehensive-audit.sh (/var/www/html/comprehensive-audit.sh)
- comprehensive-audit-execution.sh (/var/www/html/comprehensive-audit-execution.sh)
- cleanup-problematic-dirs.sh (/var/www/html/cleanup-problematic-dirs.sh)

Vendor executables (selected):

- phpunit (/var/www/html/vendor/bin/phpunit)
- phpstan (/var/www/html/vendor/bin/phpstan)
- phpmd (/var/www/html/vendor/bin/phpmd)
- pint (/var/www/html/vendor/bin/pint)
- psalm (/var/www/html/vendor/bin/psalm)
- phpinsights (/var/www/html/vendor/bin/phpinsights)
- composer-unused (/var/www/html/vendor/bin/composer-unused)
- security-checker (/var/www/html/vendor/bin/security-checker)
- php-cs-fixer (/var/www/html/vendor/bin/php-cs-fixer)

Tests directory overview (counts):

- tests/AI: 13 files
- tests/Feature: many subfolders (Api, Auth, Cart, etc.)
- tests/Integration: 3 files
- tests/Performance: 8 files
- tests/Security: 7 files
- tests/Unit: many subfolders

Notes:

- This is an initial inventory. Next step will enumerate each test file as individual runnable items and prepare the full ~450-item list with execution command, env requirements, and success criteria.
