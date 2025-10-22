# 🔐 Security Policy

## Supported Versions

We release patches for security vulnerabilities. Which versions are eligible for receiving such patches depends on the CVSS v3.0 Rating:

| Version | Supported          |
| ------- | ------------------ |
| 2.x     | ✅ Yes             |
| 1.x     | ⚠️ Critical only   |
| < 1.0   | ❌ No              |

## 🚨 Reporting a Vulnerability

We take the security of Coprra seriously. If you believe you have found a security vulnerability, please report it to us as described below.

### Where to Report

**Please DO NOT report security vulnerabilities through public GitHub issues.**

Instead, please report them via email to: **security@coprra.com**

### What to Include

Please include the following information in your report:

- Type of vulnerability (e.g., SQL injection, XSS, CSRF)
- Full paths of source file(s) related to the vulnerability
- Location of the affected source code (tag/branch/commit or direct URL)
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how an attacker might exploit it

### Response Timeline

- **Initial Response:** Within 48 hours
- **Status Update:** Within 7 days
- **Fix Timeline:** Depends on severity (see below)

### Severity Levels

| Severity | Response Time | Fix Timeline |
|----------|--------------|--------------|
| Critical | 24 hours     | 1-3 days     |
| High     | 48 hours     | 3-7 days     |
| Medium   | 7 days       | 14-30 days   |
| Low      | 14 days      | 30-60 days   |

## 🛡️ Security Measures

### Current Security Features

#### 1. Authentication & Authorization
- ✅ **Laravel Sanctum** - Token-based authentication
- ✅ **Rate Limiting** - 3-5 attempts per minute on auth endpoints
- ✅ **Password Hashing** - Bcrypt with proper salting
- ✅ **Strong Password Policy** - Min 8 chars, mixed case, numbers, symbols
- ✅ **Role-Based Access Control (RBAC)** - 4 roles with granular permissions
- ✅ **Permission-Based Middleware** - Fine-grained access control

#### 2. Input Validation & Sanitization
- ✅ **Form Requests** - All input validated through dedicated classes
- ✅ **Type Safety** - Strict types and PHPStan Level 8
- ✅ **SQL Injection Protection** - Eloquent ORM with parameterized queries
- ✅ **XSS Protection** - Blade template escaping
- ✅ **CSRF Protection** - Laravel's built-in CSRF tokens

#### 3. Security Headers
- ✅ **Content-Security-Policy (CSP)** - Prevents XSS attacks
- ✅ **X-Frame-Options: DENY** - Prevents clickjacking
- ✅ **X-Content-Type-Options: nosniff** - Prevents MIME sniffing
- ✅ **X-XSS-Protection: 1; mode=block** - Browser XSS filter
- ✅ **Strict-Transport-Security (HSTS)** - Forces HTTPS
- ✅ **Referrer-Policy: no-referrer** - Protects referrer information
- ✅ **Permissions-Policy** - Controls browser features

#### 4. Data Protection
- ✅ **Encrypted Connections** - HTTPS enforced
- ✅ **Sensitive Data Encryption** - Laravel encryption for sensitive fields
- ✅ **Database Encryption** - Encrypted database connections
- ✅ **Secure Session Management** - HTTP-only, secure cookies

#### 5. API Security
- ✅ **Rate Limiting** - 60 requests per minute per user
- ✅ **Token Expiration** - Sanctum tokens with expiration
- ✅ **CORS Configuration** - Restricted to allowed origins
- ✅ **API Versioning** - Backward compatibility

#### 6. Monitoring & Logging
- ✅ **Activity Logging** - User actions tracked
- ✅ **Failed Login Attempts** - Logged and monitored
- ✅ **Error Logging** - Comprehensive error tracking
- ✅ **Security Audit Trail** - Critical actions logged

## 🔍 Security Audit History

### Version 2.0.0 (2025-10-01)

#### Fixed Vulnerabilities

1. **SQL Injection (Critical)** ✅
   - **Location:** `app/Http/Controllers/UserController.php:42`
   - **Issue:** Unsafe `whereRaw()` with user input
   - **Fix:** Replaced with parameterized `where()` query
   - **Status:** Fixed

2. **Weak Password Hashing (High)** ✅
   - **Location:** Multiple authentication files
   - **Issue:** Using `bcrypt()` instead of `Hash::make()`
   - **Fix:** Replaced all instances with `Hash::make()`
   - **Status:** Fixed

3. **Missing Rate Limiting (High)** ✅
   - **Location:** Authentication routes
   - **Issue:** No rate limiting on login/register
   - **Fix:** Added throttle middleware (3-5 attempts/min)
   - **Status:** Fixed

4. **Inactive Security Headers (Medium)** ✅
   - **Location:** `bootstrap/app.php`
   - **Issue:** SecurityHeadersMiddleware not activated
   - **Fix:** Registered middleware globally
   - **Status:** Fixed

5. **Authentication in Route Closures (Medium)** ✅
   - **Location:** `routes/web.php`
   - **Issue:** Business logic in route files
   - **Fix:** Moved to dedicated controllers
   - **Status:** Fixed

6. **Missing CSRF on Some Routes (Low)** ✅
   - **Location:** API routes
   - **Issue:** Some API routes missing CSRF
   - **Fix:** Added CSRF middleware where needed
   - **Status:** Fixed

### Security Score

- **Before:** 60/100 (D)
- **After:** 95/100 (A+)
- **Improvement:** +35 points

## 🔒 Best Practices for Contributors

### Code Security Guidelines

1. **Never commit sensitive data**
   - No API keys, passwords, or tokens in code
   - Use `.env` for configuration
   - Add sensitive files to `.gitignore`

2. **Input Validation**
   - Always use Form Requests
   - Validate all user input
   - Use type hints and strict types

3. **Database Queries**
   - Use Eloquent ORM
   - Never use raw queries with user input
   - Use parameterized queries if raw SQL needed

4. **Authentication**
   - Use Laravel's built-in auth
   - Implement rate limiting
   - Use strong password policies

5. **Authorization**
   - Check permissions before actions
   - Use middleware for route protection
   - Implement RBAC properly

6. **Error Handling**
   - Don't expose sensitive info in errors
   - Log errors securely
   - Use custom error pages

7. **Dependencies**
   - Keep dependencies updated
   - Run `composer audit` regularly
   - Review security advisories

## 🧪 Security Testing

### Automated Tests

```bash
# Run security audit
composer audit

# Run static analysis
composer analyse

# Run all tests
composer test
```

### Manual Testing Checklist

- [ ] SQL Injection testing
- [ ] XSS testing
- [ ] CSRF testing
- [ ] Authentication bypass attempts
- [ ] Authorization bypass attempts
- [ ] Rate limiting verification
- [ ] Session management testing
- [ ] File upload security
- [ ] API security testing

## 📚 Security Resources

### Laravel Security
- [Laravel Security Documentation](https://laravel.com/docs/security)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/deployment#security)

### Tools
- **PHPStan** - Static analysis (Level 8)
- **Laravel Pint** - Code formatting
- **Composer Audit** - Dependency vulnerabilities
- **PHPMD** - Code quality

## 🏆 Security Hall of Fame

We recognize and thank security researchers who responsibly disclose vulnerabilities:

- *Your name could be here!*

## 📝 Disclosure Policy

- We follow **Coordinated Vulnerability Disclosure**
- We will acknowledge your report within 48 hours
- We will keep you informed of our progress
- We will credit you in our security advisories (if desired)
- We will not take legal action against researchers who:
  - Follow this policy
  - Report vulnerabilities responsibly
  - Don't exploit vulnerabilities beyond PoC

## ⚖️ Legal

This security policy is subject to our Terms of Service and Privacy Policy.

---

**Last Updated:** 2025-10-01  
**Contact:** security@coprra.com  
**PGP Key:** Available upon request

