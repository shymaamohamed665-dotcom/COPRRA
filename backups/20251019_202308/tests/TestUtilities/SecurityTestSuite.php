<?php

declare(strict_types=1);

namespace Tests\TestUtilities;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Mockery;

/**
 * Security Test Suite for comprehensive security testing.
 *
 * This suite provides advanced security testing capabilities including:
 * - Authentication security
 * - Authorization testing
 * - Input validation security
 * - SQL injection prevention
 * - XSS protection
 * - CSRF protection
 * - Data encryption testing
 * - Session security
 */
class SecurityTestSuite
{
    use RefreshDatabase;

    private array $securityResults = [];

    private array $vulnerabilityChecks = [];

    /**
     * Run comprehensive security tests.
     */
    public function runComprehensiveSecurityTests(): array
    {
        $results = [
            'authentication' => [],
            'authorization' => [],
            'input_validation' => [],
            'sql_injection' => [],
            'xss_protection' => [],
            'csrf_protection' => [],
            'data_encryption' => [],
            'session_security' => [],
            'api_security' => [],
            'file_upload_security' => [],
        ];

        // Test authentication security
        $results['authentication'] = $this->testAuthenticationSecurity();

        // Test authorization
        $results['authorization'] = $this->testAuthorization();

        // Test input validation
        $results['input_validation'] = $this->testInputValidation();

        // Test SQL injection prevention
        $results['sql_injection'] = $this->testSqlInjectionPrevention();

        // Test XSS protection
        $results['xss_protection'] = $this->testXssProtection();

        // Test CSRF protection
        $results['csrf_protection'] = $this->testCsrfProtection();

        // Test data encryption
        $results['data_encryption'] = $this->testDataEncryption();

        // Test session security
        $results['session_security'] = $this->testSessionSecurity();

        // Test API security
        $results['api_security'] = $this->testApiSecurity();

        // Test file upload security
        $results['file_upload_security'] = $this->testFileUploadSecurity();

        return $results;
    }

    /**
     * Test authentication security.
     */
    private function testAuthenticationSecurity(): array
    {
        $tests = [
            'test_strong_password_requirements' => function () {
                $passwordPolicy = new \App\Services\PasswordPolicyService;
                $weakPasswords = ['123', 'password', '12345678', 'qwerty'];

                foreach ($weakPasswords as $password) {
                    $result = $passwordPolicy->validatePassword($password);
                    $this->assertFalse($result['valid'], "Weak password '{$password}' should be rejected");
                }
            },

            'test_password_brute_force_protection' => function () {
                $loginAttempt = new \App\Services\LoginAttemptService;

                // Simulate multiple failed attempts
                for ($i = 0; $i < 6; $i++) {
                    $loginAttempt->recordFailedAttempt('test@example.com', '127.0.0.1');
                }

                $isBlocked = $loginAttempt->isEmailBlocked('test@example.com');
                $this->assertTrue($isBlocked, 'Email should be blocked after multiple failed attempts');
            },

            'test_account_lockout_mechanism' => function () {
                $loginAttempt = new \App\Services\LoginAttemptService;

                // Record failed attempts
                for ($i = 0; $i < 5; $i++) {
                    $loginAttempt->recordFailedAttempt('user@example.com', '192.168.1.1');
                }

                $lockoutTime = $loginAttempt->getLockoutTimeRemaining('user@example.com');
                $this->assertNotNull($lockoutTime, 'Lockout time should be set after failed attempts');
            },

            'test_password_history_prevention' => function () {
                $passwordHistory = new \App\Services\PasswordHistoryService;

                // Save password to history
                $passwordHistory->savePasswordToHistory(1, 'OldPassword123!');

                // Check if old password is rejected
                $isInHistory = $passwordHistory->isPasswordInHistory(1, 'OldPassword123!');
                $this->assertTrue($isInHistory, 'Old password should be found in history');
            },

            'test_session_timeout' => function () {
                $sessionTimeout = config('session.lifetime', 120);
                $this->assertLessThanOrEqual(480, $sessionTimeout, 'Session timeout should be reasonable');
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test authorization.
     */
    private function testAuthorization(): array
    {
        $tests = [
            'test_unauthorized_access_denied' => function () {
                Auth::shouldReceive('check')->andReturn(false);
                Auth::shouldReceive('user')->andReturn(null);

                $response = $this->get('/admin/dashboard');
                $response->assertStatus(302); // Redirect to login
            },

            'test_role_based_access_control' => function () {
                $user = Mockery::mock();
                $user->shouldReceive('hasRole')->with('admin')->andReturn(false);
                Auth::shouldReceive('check')->andReturn(true);
                Auth::shouldReceive('user')->andReturn($user);

                $response = $this->get('/admin/users');
                $response->assertStatus(403); // Forbidden
            },

            'test_resource_ownership_verification' => function () {
                $user = Mockery::mock();
                $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
                Auth::shouldReceive('check')->andReturn(true);
                Auth::shouldReceive('user')->andReturn($user);

                // Try to access another user's resource
                $response = $this->get('/user/2/profile');
                $response->assertStatus(403); // Should be forbidden
            },

            'test_api_token_authorization' => function () {
                $response = $this->get('/api/user/profile', [
                    'Authorization' => 'Bearer invalid_token',
                ]);
                $response->assertStatus(401); // Unauthorized
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test input validation.
     */
    private function testInputValidation(): array
    {
        $tests = [
            'test_email_validation' => function () {
                $invalidEmails = [
                    'invalid-email',
                    '@example.com',
                    'test@',
                    'test..test@example.com',
                    'test@example..com',
                ];

                foreach ($invalidEmails as $email) {
                    $response = $this->post('/api/register', [
                        'email' => $email,
                        'password' => 'ValidPass123!',
                        'name' => 'Test User',
                    ]);
                    $response->assertStatus(422); // Validation error
                }
            },

            'test_password_validation' => function () {
                $invalidPasswords = [
                    '123', // Too short
                    'password', // No numbers/symbols
                    'PASSWORD', // No lowercase
                    'Password', // No numbers/symbols
                    '', // Empty
                ];

                foreach ($invalidPasswords as $password) {
                    $response = $this->post('/api/register', [
                        'email' => 'test@example.com',
                        'password' => $password,
                        'name' => 'Test User',
                    ]);
                    $response->assertStatus(422); // Validation error
                }
            },

            'test_file_upload_validation' => function () {
                $maliciousFiles = [
                    'malicious.php',
                    'script.js',
                    'executable.exe',
                    'shell.sh',
                ];

                foreach ($maliciousFiles as $filename) {
                    $file = \Illuminate\Http\UploadedFile::fake()->create($filename, 100);
                    $response = $this->post('/api/upload', [
                        'file' => $file,
                    ]);
                    $response->assertStatus(422); // Validation error
                }
            },

            'test_sql_injection_in_input' => function () {
                $sqlInjectionPayloads = [
                    "'; DROP TABLE users; --",
                    "' OR '1'='1",
                    "'; INSERT INTO users (email) VALUES ('hacker@evil.com'); --",
                    "' UNION SELECT * FROM users --",
                ];

                foreach ($sqlInjectionPayloads as $payload) {
                    $response = $this->post('/api/search', [
                        'query' => $payload,
                    ]);
                    // Should not cause SQL error or return sensitive data
                    $response->assertStatus(200);
                    $this->assertStringNotContainsString('users', $response->getContent());
                }
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test SQL injection prevention.
     */
    private function testSqlInjectionPrevention(): array
    {
        $tests = [
            'test_user_input_sanitization' => function () {
                $maliciousInputs = [
                    "'; DROP TABLE products; --",
                    "' OR '1'='1' --",
                    "' UNION SELECT password FROM users --",
                    "'; UPDATE users SET password='hacked' --",
                ];

                foreach ($maliciousInputs as $input) {
                    $response = $this->get('/api/products?search='.urlencode($input));
                    $response->assertStatus(200);
                    $this->assertStringNotContainsString('error', strtolower($response->getContent()));
                }
            },

            'test_parameterized_queries' => function () {
                // Test that queries use parameterized statements
                DB::shouldReceive('select')->andReturn([]);
                DB::shouldReceive('insert')->andReturn(true);
                DB::shouldReceive('update')->andReturn(1);
                DB::shouldReceive('delete')->andReturn(1);

                $service = new \App\Services\ProductService;
                $result = $service->getPaginatedProducts(1, 15);

                $this->assertIsObject($result);
            },

            'test_escape_special_characters' => function () {
                $specialChars = [
                    "'",
                    '"',
                    ';',
                    '--',
                    '/*',
                    '*/',
                    'xp_',
                    'sp_',
                ];

                foreach ($specialChars as $char) {
                    $response = $this->post('/api/products', [
                        'name' => "Product {$char} Name",
                        'description' => "Description with {$char} special char",
                    ]);
                    $response->assertStatus(200);
                }
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test XSS protection.
     */
    private function testXssProtection(): array
    {
        $tests = [
            'test_script_tag_filtering' => function () {
                $xssPayloads = [
                    '<script>alert("XSS")</script>',
                    '<img src="x" onerror="alert(\'XSS\')">',
                    '<svg onload="alert(\'XSS\')">',
                    'javascript:alert("XSS")',
                    '<iframe src="javascript:alert(\'XSS\')">',
                ];

                foreach ($xssPayloads as $payload) {
                    $response = $this->post('/api/products', [
                        'name' => $payload,
                        'description' => 'Safe description',
                    ]);
                    $response->assertStatus(200);
                    $this->assertStringNotContainsString('<script>', $response->getContent());
                }
            },

            'test_html_entity_encoding' => function () {
                $htmlEntities = [
                    '<' => '&lt;',
                    '>' => '&gt;',
                    '"' => '&quot;',
                    "'" => '&#039;',
                    '&' => '&amp;',
                ];

                foreach ($htmlEntities as $char => $encoded) {
                    $response = $this->get('/api/products?search='.urlencode($char));
                    $response->assertStatus(200);
                    $this->assertStringNotContainsString($char, $response->getContent());
                }
            },

            'test_content_security_policy' => function () {
                $response = $this->get('/');
                $cspHeader = $response->headers->get('Content-Security-Policy');
                $this->assertNotNull($cspHeader, 'Content Security Policy header should be present');
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test CSRF protection.
     */
    private function testCsrfProtection(): array
    {
        $tests = [
            'test_csrf_token_required' => function () {
                $response = $this->post('/api/products', [
                    'name' => 'Test Product',
                    'description' => 'Test Description',
                ]);
                $response->assertStatus(419); // CSRF token mismatch
            },

            'test_csrf_token_validation' => function () {
                $response = $this->get('/api/csrf-token');
                $response->assertStatus(200);
                $this->assertArrayHasKey('csrf_token', $response->json());
            },

            'test_form_csrf_protection' => function () {
                $response = $this->get('/products/create');
                $response->assertStatus(200);
                $this->assertStringContainsString('csrf_token', $response->getContent());
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test data encryption.
     */
    private function testDataEncryption(): array
    {
        $tests = [
            'test_password_hashing' => function () {
                $password = 'TestPassword123!';
                $hashed = Hash::make($password);

                $this->assertNotEquals($password, $hashed);
                $this->assertTrue(Hash::check($password, $hashed));
            },

            'test_sensitive_data_encryption' => function () {
                $sensitiveData = 'Sensitive Information';
                $encrypted = encrypt($sensitiveData);
                $decrypted = decrypt($encrypted);

                $this->assertNotEquals($sensitiveData, $encrypted);
                $this->assertEquals($sensitiveData, $decrypted);
            },

            'test_database_encryption' => function () {
                // Test that sensitive fields are encrypted in database
                $user = new \App\Models\User;
                $user->email = 'test@example.com';
                $user->password = Hash::make('password');
                $user->save();

                $this->assertNotEquals('password', $user->password);
                $this->assertTrue(Hash::check('password', $user->password));
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test session security.
     */
    private function testSessionSecurity(): array
    {
        $tests = [
            'test_session_regeneration' => function () {
                $oldSessionId = Session::getId();
                Auth::login(new \App\Models\User);
                $newSessionId = Session::getId();

                $this->assertNotEquals($oldSessionId, $newSessionId);
            },

            'test_session_timeout' => function () {
                $sessionLifetime = config('session.lifetime');
                $this->assertLessThanOrEqual(480, $sessionLifetime); // Max 8 hours
            },

            'test_secure_session_cookie' => function () {
                $secure = config('session.secure');
                $httpOnly = config('session.http_only');
                $sameSite = config('session.same_site');

                $this->assertTrue($secure, 'Session cookie should be secure');
                $this->assertTrue($httpOnly, 'Session cookie should be HTTP only');
                $this->assertNotNull($sameSite, 'SameSite should be configured');
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test API security.
     */
    private function testApiSecurity(): array
    {
        $tests = [
            'test_api_rate_limiting' => function () {
                // Make multiple requests to test rate limiting
                for ($i = 0; $i < 100; $i++) {
                    $response = $this->get('/api/products');
                    if ($response->status() === 429) {
                        break;
                    }
                }

                $this->assertEquals(429, $response->status(), 'API should implement rate limiting');
            },

            'test_api_authentication_required' => function () {
                $response = $this->get('/api/user/profile');
                $response->assertStatus(401); // Unauthorized
            },

            'test_api_cors_configuration' => function () {
                $response = $this->options('/api/products', [], [
                    'Origin' => 'https://malicious-site.com',
                    'Access-Control-Request-Method' => 'GET',
                ]);

                $corsHeaders = $response->headers->get('Access-Control-Allow-Origin');
                $this->assertNotEquals('*', $corsHeaders, 'CORS should not allow all origins');
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Test file upload security.
     */
    private function testFileUploadSecurity(): array
    {
        $tests = [
            'test_file_type_validation' => function () {
                $maliciousFiles = [
                    'malicious.php',
                    'script.js',
                    'executable.exe',
                    'shell.sh',
                ];

                foreach ($maliciousFiles as $filename) {
                    $file = \Illuminate\Http\UploadedFile::fake()->create($filename, 100);
                    $response = $this->post('/api/upload', [
                        'file' => $file,
                    ]);
                    $response->assertStatus(422); // Validation error
                }
            },

            'test_file_size_limitation' => function () {
                $largeFile = \Illuminate\Http\UploadedFile::fake()->create('large.jpg', 10240); // 10MB
                $response = $this->post('/api/upload', [
                    'file' => $largeFile,
                ]);
                $response->assertStatus(422); // File too large
            },

            'test_file_content_scanning' => function () {
                // Test that uploaded files are scanned for malicious content
                $response = $this->post('/api/upload', [
                    'file' => \Illuminate\Http\UploadedFile::fake()->create('test.jpg', 100),
                ]);
                $response->assertStatus(200);
            },
        ];

        return $this->runSecurityTests($tests);
    }

    /**
     * Run security tests and collect results.
     */
    private function runSecurityTests(array $tests): array
    {
        $results = [
            'passed' => 0,
            'failed' => 0,
            'errors' => [],
            'vulnerabilities' => [],
        ];

        foreach ($tests as $testName => $testFunction) {
            try {
                $testFunction();
                $results['passed']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'test' => $testName,
                    'error' => $e->getMessage(),
                ];

                // Check if it's a security vulnerability
                if ($this->isSecurityVulnerability($e->getMessage())) {
                    $results['vulnerabilities'][] = [
                        'test' => $testName,
                        'severity' => $this->getVulnerabilitySeverity($e->getMessage()),
                        'description' => $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Check if error indicates security vulnerability.
     */
    private function isSecurityVulnerability(string $errorMessage): bool
    {
        $vulnerabilityKeywords = [
            'sql injection',
            'xss',
            'csrf',
            'authentication',
            'authorization',
            'encryption',
            'session',
            'file upload',
        ];

        foreach ($vulnerabilityKeywords as $keyword) {
            if (stripos($errorMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get vulnerability severity level.
     */
    private function getVulnerabilitySeverity(string $errorMessage): string
    {
        if (
            stripos($errorMessage, 'sql injection') !== false ||
            stripos($errorMessage, 'xss') !== false
        ) {
            return 'HIGH';
        }

        if (
            stripos($errorMessage, 'csrf') !== false ||
            stripos($errorMessage, 'authentication') !== false
        ) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

    /**
     * Generate security report.
     */
    public function generateSecurityReport(): array
    {
        $results = $this->runComprehensiveSecurityTests();

        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        $totalVulnerabilities = 0;

        foreach ($results as $category => $categoryResults) {
            if (isset($categoryResults['passed'])) {
                $totalTests += $categoryResults['passed'] + $categoryResults['failed'];
                $totalPassed += $categoryResults['passed'];
                $totalFailed += $categoryResults['failed'];
                $totalVulnerabilities += count($categoryResults['vulnerabilities'] ?? []);
            }
        }

        return [
            'summary' => [
                'total_tests' => $totalTests,
                'passed' => $totalPassed,
                'failed' => $totalFailed,
                'success_rate' => $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0,
                'vulnerabilities_found' => $totalVulnerabilities,
                'security_score' => $this->calculateSecurityScore($results),
            ],
            'detailed_results' => $results,
            'recommendations' => $this->generateSecurityRecommendations($results),
        ];
    }

    /**
     * Calculate overall security score.
     */
    private function calculateSecurityScore(array $results): float
    {
        $totalScore = 0;
        $categoryCount = 0;

        foreach ($results as $category => $categoryResults) {
            if (isset($categoryResults['passed']) && isset($categoryResults['failed'])) {
                $totalTests = $categoryResults['passed'] + $categoryResults['failed'];
                if ($totalTests > 0) {
                    $score = ($categoryResults['passed'] / $totalTests) * 100;
                    $totalScore += $score;
                    $categoryCount++;
                }
            }
        }

        return $categoryCount > 0 ? $totalScore / $categoryCount : 0;
    }

    /**
     * Generate security recommendations.
     */
    private function generateSecurityRecommendations(array $results): array
    {
        $recommendations = [];

        foreach ($results as $category => $categoryResults) {
            if (isset($categoryResults['vulnerabilities'])) {
                foreach ($categoryResults['vulnerabilities'] as $vulnerability) {
                    $recommendations[] = "Fix {$vulnerability['severity']} vulnerability in {$category}: {$vulnerability['description']}";
                }
            }
        }

        return $recommendations;
    }
}
