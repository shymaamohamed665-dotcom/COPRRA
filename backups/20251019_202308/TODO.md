# Sequential To-Do Execution Plan

This plan outlines the sequential execution of the provided commands. Each command will be run individually, with fixes applied as needed until 100% success and clean output.

## Commands to Execute

1. Execute PHPUnit Security tests: `(time vendor/bin/phpunit --configuration phpunit.xml --testsuite Security --log-junit storage/logs/junit/security-tests-report.xml --coverage-xml storage/logs/coverage/security-coverage.xml --coverage-clover storage/logs/clover/security-clover.xml --coverage-text --stop-on-failure --verbose) 2>&1 | tee storage/logs/output/phpunit-security.log`

2. Execute PHPUnit Performance tests: `(time vendor/bin/phpunit --configuration phpunit.xml --testsuite Performance --log-junit storage/logs/junit/performance-tests-report.xml --coverage-xml storage/logs/coverage/performance-coverage.xml --coverage-clover storage/logs/clover/performance-clover.xml --coverage-text --stop-on-failure --verbose) 2>&1 | tee storage/logs/output/phpunit-performance.log`

3. Execute PHPUnit Integration tests: `(time vendor/bin/phpunit --configuration phpunit.xml --testsuite Integration --log-junit storage/logs/junit/integration-tests-report.xml --coverage-xml storage/logs/coverage/integration-coverage.xml --coverage-clover storage/logs/clover/integration-clover.xml --coverage-text --stop-on-failure --verbose) 2>&1 | tee storage/logs/output/phpunit-integration.log`

4. Execute PHPUnit Unit tests: `(time vendor/bin/phpunit --configuration phpunit.xml --testsuite Unit --log-junit storage/logs/junit/unit-tests-report.xml --coverage-xml storage/logs/coverage/unit-coverage.xml --coverage-clover storage/logs/clover/unit-clover.xml --coverage-text --stop-on-failure --verbose) 2>&1 | tee storage/logs/output/phpunit-unit.log`

5. Execute PHPUnit Feature tests: `(time vendor/bin/phpunit --configuration phpunit.xml --testsuite Feature --log-junit storage/logs/junit/feature-tests-report.xml --coverage-xml storage/logs/coverage/feature-coverage.xml --coverage-clover storage/logs/clover/feature-clover.xml --coverage-text --stop-on-failure --verbose) 2>&1 | tee storage/logs/output/phpunit-feature.log`

6. Execute PHPUnit Comprehensive tests: `(time vendor/bin/phpunit --configuration phpunit.xml --log-junit storage/logs/junit/comprehensive-report.xml --coverage-html storage/logs/coverage/comprehensive-html --coverage-xml storage/logs/coverage/comprehensive-coverage.xml --coverage-clover storage/logs/clover/comprehensive-clover.xml --coverage-text --stop-on-failure --verbose) 2>&1 | tee storage/logs/output/phpunit-comprehensive.log`

7. Execute PHPStan analysis (level max): `(time php -d memory_limit=-1 ./vendor/bin/phpstan analyse --level=max --configuration=phpstan.neon --error-format=table --verbose) 2>&1 | tee storage/logs/output/phpstan.log`

8. Execute PHPStan analysis (extended): `(time php -d memory_limit=-1 ./vendor/bin/phpstan analyse --level=max --configuration=phpstan.neon --memory-limit=4G --error-format=table --verbose) 2>&1 | tee storage/logs/output/phpstan-extended.log`

9. Execute Psalm analysis: `(time ./vendor/bin/psalm --config=psalm.xml --show-info=true --stats) 2>&1 | tee storage/logs/output/psalm.log`

10. Execute PHP Insights analysis: `(time ./vendor/bin/phpinsights analyse app --config-path=phpinsights.php --min-quality=100 --min-complexity=100 --min-architecture=100 --min-style=100 --verbose) 2>&1 | tee storage/logs/output/phpinsights.log`

11. Execute PHPMD cleancode check: `(time ./vendor/bin/phpmd app text cleancode --strict) 2>&1 | tee storage/logs/output/phpmd-cleancode.log`

12. Execute PHPMD codesize check: `(time ./vendor/bin/phpmd app text codesize --strict) 2>&1 | tee storage/logs/output/phpmd-codesize.log`

13. Execute PHPMD controversial check: `(time ./vendor/bin/phpmd app text controversial --strict) 2>&1 | tee storage/logs/output/phpmd-controversial.log`

14. Execute PHPMD design check: `(time ./vendor/bin/phpmd app text design --strict) 2>&1 | tee storage/logs/output/phpmd-design.log`

15. Execute PHPMD naming check: `(time ./vendor/bin/phpmd app text naming --strict) 2>&1 | tee storage/logs/output/phpmd-naming.log`

16. Execute PHPMD unusedcode check: `(time ./vendor/bin/phpmd app text unusedcode --strict) 2>&1 | tee storage/logs/output/phpmd-unusedcode.log`

17. Execute PHPMD all rules check: `(time ./vendor/bin/phpmd app text cleancode,codesize,controversial,design,naming,unusedcode --strict) 2>&1 | tee storage/logs/output/phpmd-all.log`

18. Execute PHP-CS-Fixer dry-run check: `(time ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run --verbose --diff) 2>&1 | tee storage/logs/output/php-cs-fixer-check.log`

19. Execute PHP-CS-Fixer fix: `(time ./vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --verbose --diff) 2>&1 | tee storage/logs/output/php-cs-fixer-fix.log`

## Execution Status

- [ ]   1. PHPUnit Security tests
- [ ]   2. PHPUnit Performance tests
- [ ]   3. PHPUnit Integration tests
- [ ]   4. PHPUnit Unit tests
- [ ]   5. PHPUnit Feature tests
- [ ]   6. PHPUnit Comprehensive tests
- [ ]   7. PHPStan analysis (level max)
- [ ]   8. PHPStan analysis (extended)
- [ ]   9. Psalm analysis
- [ ]   10. PHP Insights analysis
- [ ]   11. PHPMD cleancode check
- [ ]   12. PHPMD codesize check
- [ ]   13. PHPMD controversial check
- [ ]   14. PHPMD design check
- [ ]   15. PHPMD naming check
- [ ]   16. PHPMD unusedcode check
- [ ]   17. PHPMD all rules check
- [ ]   18. PHP-CS-Fixer dry-run check
- [ ]   19. PHP-CS-Fixer fix
