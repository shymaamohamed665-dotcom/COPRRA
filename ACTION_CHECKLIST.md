# COPRRA Issue Resolution - Action Checklist

**Quick Reference Guide for Developers**

Use this checklist to systematically resolve all identified issues. Check off items as you complete them.

---

## ✅ Already Completed

- [x] AI Control Panel Controller implementation
- [x] AI Quality Agents scheduling (StrictQualityAgent, ContinuousQualityMonitor)
- [x] PHPUnit test suite execution (1,191 tests passing)

---

## 🔥 High Priority (Week 1-2)

### Error Handling & Code Quality

- [ ] **Remove @ Error Suppression Operators** (4-6 hours)
  ```bash
  # Find all @ operators
  grep -rn "@" app/Services/Backup*.php | grep -v "@param\|@return\|@var"

  # Files to fix:
  # - app/Services/BackupService.php
  # - app/Services/Backup/Services/BackupCompressionService.php

  # Replace with try-catch blocks
  # Add proper logging
  # Test after changes
  ```
  - [ ] BackupService.php fixed
  - [ ] BackupCompressionService.php fixed
  - [ ] Tests passing after changes
  - [ ] Commit and push

---

- [ ] **Fix PHPStan Type Violations** (8-12 hours)
  ```bash
  # Run PHPStan
  composer run analyse:phpstan

  # Common fixes needed:
  # 1. Remove redundant type checks (is_string on string param)
  # 2. Add Collection generics: @return Collection<int, string>
  # 3. Add missing return types

  # Generate baseline
  ./vendor/bin/phpstan analyse --generate-baseline
  ```
  - [ ] Redundant type checks removed
  - [ ] Collection generics added
  - [ ] Missing return types added
  - [ ] Baseline updated
  - [ ] Tests passing
  - [ ] Commit and push

---

- [ ] **Fix Psalm Strict Comparison Violations** (6-8 hours)
  ```bash
  # Run Psalm
  composer run analyse:psalm

  # Find non-strict comparisons
  grep -rn " == \| != " app/ --include="*.php" > reports/non-strict-comparisons.txt

  # Replace == with ===
  # Replace != with !==
  # Remove redundant type casts

  # Update baseline
  ./vendor/bin/psalm --set-baseline=psalm-baseline.xml
  ```
  - [ ] All == replaced with ===
  - [ ] All != replaced with !==
  - [ ] Redundant casts removed
  - [ ] Baseline updated
  - [ ] Tests passing
  - [ ] Commit and push

---

### Frontend & Tooling

- [ ] **Fix Stylelint SCSS Configuration** (2-4 hours)
  ```bash
  # Update .stylelintrc.json to enable SCSS rules
  # See ISSUE_RESOLUTION_REPORT.md Issue #10 for config

  # Run stylelint
  npm run stylelint

  # Fix auto-fixable issues
  npx stylelint "resources/**/*.{css,scss}" --fix

  # Review and fix remaining issues
  ```
  - [ ] .stylelintrc.json updated with SCSS rules
  - [ ] Auto-fixable issues fixed
  - [ ] Manual fixes applied
  - [ ] Tests passing
  - [ ] Commit and push

---

- [ ] **Docker Linting & Build** (2-3 hours)
  ```bash
  # Install hadolint (if not installed)
  # Windows: Download from https://github.com/hadolint/hadolint/releases

  # Lint Dockerfiles
  hadolint Dockerfile > reports/dockerfile-lint.txt
  hadolint dev-docker/Dockerfile > reports/dockerfile-dev-lint.txt

  # Fix issues (especially version pinning in dev-docker/Dockerfile)

  # Build images
  docker build -t coprra:prod -f Dockerfile . > docker-build-prod.log
  docker build -t coprra:dev -f dev-docker/Dockerfile . > docker-build-dev.log

  # Test containers
  docker-compose up -d > docker-run.log
  docker ps
  ```
  - [ ] Hadolint installed
  - [ ] Dockerfiles linted
  - [ ] Issues fixed (remove strict version pins in dev Dockerfile)
  - [ ] Images built successfully
  - [ ] Containers running
  - [ ] Logs generated
  - [ ] Commit and push

---

## ⚠️ Medium Priority (Week 3-4)

### Architecture Refactoring

- [ ] **Refactor StorageManagementService** (12-16 hours)
  ```
  Current Complexity: 114 (exceeds limit of 90)

  Split into:
  1. StorageMonitoringService (monitoring, status, breakdown)
  2. StorageCleanupService (cleanup, priority)
  3. StorageCalculationService (size calculations)
  4. StorageArchivalService (compression, archival)

  Keep StorageManagementService as orchestrator
  ```
  - [ ] Create StorageMonitoringService
  - [ ] Create StorageCleanupService
  - [ ] Create StorageCalculationService
  - [ ] Create StorageArchivalService
  - [ ] Update StorageManagementService (orchestrator)
  - [ ] Update service provider bindings
  - [ ] Update tests
  - [ ] Verify complexity reduced (<50 per class)
  - [ ] All tests passing
  - [ ] Commit and push

---

### Security & Auditing

- [ ] **Security Configuration Audit** (3-4 hours)
  ```bash
  # Audit .env.example
  grep -i "key\|secret\|password\|token" .env.example
  # Ensure all values are placeholders

  # Audit .gitignore
  cat .gitignore | grep -E "\.env$|secrets|\.key|\.pem|\.p12"
  # Ensure sensitive files excluded

  # Run Gitleaks
  gitleaks detect --source . --verbose --report-format json --report-path reports/gitleaks-report.json

  # Review findings
  # Add false positives to .gitleaksignore
  # Rotate any exposed secrets
  ```
  - [ ] .env.example audited (no hardcoded secrets)
  - [ ] .gitignore verified
  - [ ] Gitleaks run successfully
  - [ ] Findings reviewed and addressed
  - [ ] False positives added to .gitleaksignore
  - [ ] Any exposed secrets rotated
  - [ ] Commit and push

---

### Documentation

- [ ] **Update Project Documentation** (2-3 hours)
  ```bash
  # Verify all commands in CLAUDE.md work
  # Test each command listed

  # Update README.md with:
  # - Current PHP version (8.2+)
  # - Laravel version (12)
  # - Correct setup instructions
  # - Working test commands
  # - Updated deployment steps
  ```
  - [ ] All commands in CLAUDE.md tested
  - [ ] README.md updated
  - [ ] Setup instructions verified
  - [ ] Deployment docs checked
  - [ ] Commit and push

---

## 📊 Low Priority (Week 5-6)

### Testing & Tooling

- [ ] **Setup Frontend Testing** (4-6 hours)
  ```bash
  # Option A: Install vitest
  npm install --save-dev vitest @vitest/ui

  # Create vitest.config.js
  # Add test files
  # Update package.json scripts

  # Option B: Update package.json
  # Remove vitest dependency from test:frontend
  # Update to just use ESLint/Stylelint
  ```
  - [ ] Decision made (vitest vs alternative)
  - [ ] If vitest: installed and configured
  - [ ] If vitest: initial tests written
  - [ ] package.json updated
  - [ ] npm test runs successfully
  - [ ] Commit and push

---

- [ ] **Generate Tool Discovery Report** (2-3 hours)
  ```bash
  # Create scripts/discover-tools.sh
  # See ISSUE_RESOLUTION_REPORT.md Issue #14 for script

  # Run discovery
  bash scripts/discover-tools.sh

  # Review reports/tool-discovery.json
  ```
  - [ ] Discovery script created
  - [ ] Script executed
  - [ ] Report generated
  - [ ] Report reviewed
  - [ ] Commit and push

---

- [ ] **Review CI/CD Workflows** (2-3 hours)
  ```bash
  # List workflows
  ls -la .github/workflows/

  # Validate each workflow
  # Use actionlint or GitHub API

  # Check for:
  # - Correct paths to scripts
  # - Valid action versions
  # - Proper secrets usage
  # - Job dependencies
  ```
  - [ ] All workflows listed
  - [ ] Syntax validated (actionlint)
  - [ ] Paths verified
  - [ ] Action versions checked
  - [ ] Secrets usage reviewed
  - [ ] Issues documented
  - [ ] Fixes applied
  - [ ] Commit and push

---

### Code Cleanup

- [ ] **Remove Unused Private Method** (30 minutes)
  ```bash
  # Find method in StorageManagementService
  grep -n "sortDirectoriesBySize" app/Services/StorageManagementService.php

  # If found and unused, remove it

  # Run tests
  php artisan test --testsuite=Unit --filter=StorageManagementService
  ```
  - [ ] Method located
  - [ ] Verified unused
  - [ ] Method removed
  - [ ] Tests passing
  - [ ] Commit and push

---

## 🎯 Verification & Testing

After each change:

```bash
# 1. Run affected tests
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# 2. Run static analysis
composer run analyse:phpstan
composer run analyse:psalm

# 3. Check code style
./vendor/bin/pint --test

# 4. Run full test suite
composer run test

# 5. Generate coverage report
composer run test:coverage
```

---

## 📝 Git Workflow

For each issue:

```bash
# 1. Create feature branch
git checkout -b fix/issue-name

# 2. Make changes

# 3. Test thoroughly

# 4. Commit with descriptive message
git add .
git commit -m "fix: description of fix

- Detailed explanation
- What was changed
- Why it was changed
- How it was tested"

# 5. Push to remote
git push origin fix/issue-name

# 6. Create pull request

# 7. After review and merge, delete branch
git branch -d fix/issue-name
```

---

## 📊 Progress Tracking

**Total Issues:** 17
- ✅ Completed: 3 (already resolved)
- 🔥 High Priority: 5
- ⚠️ Medium Priority: 4
- 📊 Low Priority: 4
- ✨ Code Cleanup: 1

**Estimated Total Effort:** 40-60 hours
**Recommended Timeline:** 4-6 weeks

---

## 🎉 Completion Criteria

All issues resolved when:

- [ ] All tests passing (1,191+ tests)
- [ ] PHPStan Level max with no violations
- [ ] Psalm strict mode with no violations
- [ ] No @ error suppression operators
- [ ] StorageManagementService complexity < 90
- [ ] All Dockerfiles linted and building
- [ ] Gitleaks audit passing
- [ ] Documentation up to date
- [ ] Frontend tests configured
- [ ] CI/CD workflows verified

**Final Step:** Generate final system health report

```bash
# Run comprehensive analysis
composer run measure:all

# Generate final report
php artisan stats

# Commit all changes
git add .
git commit -m "chore: complete system-wide issue resolution

All 17 issues from initial audit have been addressed:
- 3 were false positives (already fixed)
- 14 resolved with targeted fixes
- All tests passing
- Code quality improved to industry standards"

git push origin master
```

---

**Last Updated:** October 21, 2025
**Next Review:** November 4, 2025

---

## Quick Links

- 📄 [Detailed Report](ISSUE_RESOLUTION_REPORT.md)
- 📋 [Executive Summary](EXECUTIVE_SUMMARY.md)
- 🔧 [Project Instructions](CLAUDE.md)
- 📖 [README](README.md)
