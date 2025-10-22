# Comprehensive List of Tests and Tools

This document provides a detailed inventory of all tests, measurement tools, linters, analyzers, and security/quality tools within the COPRRA project.

## PHP Ecosystem

### 1. Testing Frameworks

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 1.1 | PHPUnit | `vendor/bin/phpunit` | `composer test` or `vendor/bin/phpunit` | Exits with code 0 for success, non-zero for failure. | High |

### 2. Static Analysis

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 2.1 | Larastan (PHPStan) | `vendor/bin/phpstan` | `composer analyse:phpstan` | Exits with code 0 if no errors are found. | Max (Level 9) |
| 2.2 | Psalm | `vendor/bin/psalm` | `composer analyse:psalm` | Exits with code 0 if no errors are found. | Level 1 (Strictest) |
| 2.3 | PHP Insights | `vendor/bin/phpinsights` | `composer analyse:insights` | Exits with code 0 if quality gates pass. | High |

### 3. Code Style and Formatting

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 3.1 | Laravel Pint | `vendor/bin/pint` | `composer format` | Exits with code 0. | High (PSR-12) |
| 3.2 | PHPMD | `vendor/bin/phpmd` | `composer analyse` | Exits with code 0 if no violations are found. | High |

### 4. Security Analysis

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 4.1 | Composer Audit | `composer audit` | `composer audit` | Exits with code 0 if no vulnerabilities are found. | High |
| 4.2 | Enlightn Security Checker | `vendor/bin/security-checker` | `composer analyse:security` | Exits with code 0 if no vulnerabilities are found. | High |

### 5. Other Tools

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 5.1 | Composer Unused | `vendor/bin/composer-unused` | `vendor/bin/composer-unused` | Exits with code 0. | High |
| 5.2 | PHPCPD | `vendor/bin/phpcpd` | `vendor/bin/phpcpd app/` | Exits with code 0 if no duplicate code is found. | High |

## JavaScript Ecosystem

### 1. Linters and Formatters

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 6.1 | ESLint | `npx eslint` | `npm run lint` | Exits with code 0 if no errors are found. | High |
| 6.2 | Stylelint | `npx stylelint` | `npm run stylelint` | Exits with code 0 if no errors are found. | High |
| 6.3 | Prettier | `npx prettier` | `npm run format` | Exits with code 0. | High |

### 2. Automation

| # | Tool Name | Path / Command | How to Run | Success/Failure Criteria | Strictness Level |
|---|---|---|---|---|---|
| 7.1 | Husky | `.husky/` | Runs automatically on git hooks. | Exits with code 0. | High |
| 7.2 | lint-staged | `npx lint-staged` | Runs via Husky pre-commit hook. | Exits with code 0. | High |