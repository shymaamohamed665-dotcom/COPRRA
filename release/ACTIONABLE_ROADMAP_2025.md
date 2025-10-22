# خطة العمل التنفيذية - Actionable Roadmap
## مشروع Coprra Laravel - الوصول إلى الكمال

**تاريخ الإنشاء:** 30 سبتمبر 2025  
**الهدف:** تحقيق صفر أخطاء ومشاكل - 100% جودة وأمان وأداء

---

## 🎯 الأهداف الاستراتيجية

### المرحلة الأولى (أسبوع 1-2): إصلاح المشاكل الحرجة
- ✅ إصلاح جميع الثغرات الأمنية الحرجة
- ✅ رفع PHPStan إلى Level 8
- ✅ تحقيق 90%+ Test Coverage

### المرحلة الثانية (أسبوع 3-4): تحسين الجودة
- ✅ إصلاح جميع المشاكل العالية
- ✅ تحسين الأداء وإزالة N+1 queries
- ✅ تحسين التوثيق

### المرحلة الثالثة (أسبوع 5-6): التحسينات النهائية
- ✅ إصلاح المشاكل المتوسطة والمنخفضة
- ✅ تحسين CI/CD
- ✅ تحقيق 100% Lighthouse Score

---

## 📋 المهام التفصيلية - مرتبة حسب الأولوية

---

## 🔴 المرحلة 1: المشاكل الحرجة (Critical) - الأسبوع 1

### TASK-C1: إعادة هيكلة Authentication System
**الأولوية:** 🔴 حرجة  
**الجهد:** متوسط (4-6 ساعات)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 1-2

#### الخطوات:
1. **إنشاء AuthController:**
   ```bash
   php artisan make:controller Auth/AuthController
   ```

2. **نقل منطق Authentication من routes/web.php:**
   - إنشاء `LoginController`
   - إنشاء `RegisterController`
   - إنشاء `PasswordResetController`
   - إنشاء `EmailVerificationController`

3. **إضافة Form Requests:**
   ```bash
   php artisan make:request Auth/LoginRequest
   php artisan make:request Auth/RegisterRequest
   ```

4. **إضافة Rate Limiting:**
   ```php
   Route::middleware(['throttle:login'])->group(function () {
       Route::post('/login', [LoginController::class, 'store']);
   });
   ```

5. **استبدال bcrypt بـ Hash::make:**
   ```php
   // ❌ قبل
   'password' => bcrypt($validated['password'])
   
   // ✅ بعد
   'password' => Hash::make($validated['password'])
   ```

6. **إضافة Tests:**
   - `LoginControllerTest`
   - `RegisterControllerTest`
   - Rate limiting tests

#### معايير القبول:
- [ ] جميع Authentication logic في Controllers
- [ ] Rate limiting مفعّل
- [ ] Tests تغطي 100% من الكود الجديد
- [ ] استخدام Hash::make بدلاً من bcrypt

---

### TASK-C2: إصلاح SQL Injection في UserController
**الأولوية:** 🔴 حرجة  
**الجهد:** صغير (15 دقيقة)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 1

#### الخطوات:
1. **فتح `app/Http/Controllers/UserController.php`**

2. **استبدال whereRaw:**
   ```php
   // ❌ قبل (line 42)
   $query->whereRaw('role = ?', [$request->get('role')]);
   
   // ✅ بعد
   $query->where('role', $request->get('role'));
   ```

3. **إضافة Validation:**
   ```php
   $request->validate([
       'role' => 'string|in:admin,user,moderator',
   ]);
   ```

4. **إضافة Test:**
   ```php
   public function test_role_filter_prevents_sql_injection()
   {
       $response = $this->getJson('/api/users?role=admin\' OR 1=1--');
       $response->assertStatus(422);
   }
   ```

#### معايير القبول:
- [ ] لا استخدام لـ whereRaw مع user input
- [ ] Validation موجود
- [ ] Test يمنع SQL injection

---

### TASK-C3: رفع PHPStan إلى Level 8
**الأولوية:** 🔴 حرجة  
**الجهد:** كبير (20-30 ساعة)  
**المسؤول:** Senior Developer  
**الموعد النهائي:** يوم 3-7

#### الخطوات:
1. **رفع المستوى تدريجيًا:**
   ```bash
   # Level 6
   vendor/bin/phpstan analyse --level=6
   # إصلاح الأخطاء
   
   # Level 7
   vendor/bin/phpstan analyse --level=7
   # إصلاح الأخطاء
   
   # Level 8
   vendor/bin/phpstan analyse --level=8
   ```

2. **إصلاح Missing Return Types:**
   ```php
   // ❌ قبل
   public function getProducts()
   
   // ✅ بعد
   public function getProducts(): Collection
   ```

3. **إصلاح Missing Parameter Types:**
   ```php
   // ❌ قبل
   public function create($data)
   
   // ✅ بعد
   public function create(array $data): Model
   ```

4. **إزالة @phpstan-ignore:**
   - إصلاح المشاكل الحقيقية بدلاً من إخفائها

5. **إضافة PHPDoc كامل:**
   ```php
   /**
    * @param array<string, mixed> $data
    * @return Collection<int, Product>
    */
   ```

#### معايير القبول:
- [ ] PHPStan Level 8 يمر بنجاح
- [ ] صفر @phpstan-ignore غير ضروري
- [ ] جميع Methods لها return types
- [ ] جميع Parameters لها types

---

### TASK-C4: إضافة Admin Middleware Protection
**الأولوية:** 🔴 حرجة  
**الجهد:** متوسط (2-3 ساعات)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 2

#### الخطوات:
1. **إنشاء Admin Middleware:**
   ```bash
   php artisan make:middleware EnsureUserIsAdmin
   ```

2. **تطبيق المنطق:**
   ```php
   public function handle(Request $request, Closure $next)
   {
       if (!$request->user() || !$request->user()->is_admin) {
           abort(403, 'Unauthorized');
       }
       return $next($request);
   }
   ```

3. **تسجيل في Kernel:**
   ```php
   protected $middlewareAliases = [
       'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
   ];
   ```

4. **تطبيق على Routes:**
   ```php
   Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {
       Route::get('/dashboard', [DashboardController::class, 'index']);
       Route::get('/ai-control-panel', [AIControlPanelController::class, 'index']);
   });
   ```

5. **إضافة Tests:**
   ```php
   public function test_non_admin_cannot_access_admin_routes()
   {
       $user = User::factory()->create(['is_admin' => false]);
       $response = $this->actingAs($user)->get('/admin/dashboard');
       $response->assertStatus(403);
   }
   ```

#### معايير القبول:
- [ ] جميع Admin routes محمية
- [ ] Tests تغطي جميع السيناريوهات
- [ ] Non-admin users يحصلون على 403

---

### TASK-C5: إضافة API Rate Limiting
**الأولوية:** 🔴 حرجة  
**الجهد:** صغير (2-3 ساعات)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 2

#### الخطوات:
1. **تكوين Rate Limiters في RouteServiceProvider:**
   ```php
   RateLimiter::for('api', function (Request $request) {
       return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
   });
   
   RateLimiter::for('login', function (Request $request) {
       return Limit::perMinute(5)->by($request->ip());
   });
   ```

2. **تطبيق على Routes:**
   ```php
   Route::middleware(['throttle:api'])->group(function () {
       // API routes
   });
   
   Route::middleware(['throttle:login'])->group(function () {
       Route::post('/login', ...);
       Route::post('/register', ...);
   });
   ```

3. **إضافة Tests:**
   ```php
   public function test_api_rate_limiting()
   {
       for ($i = 0; $i < 61; $i++) {
           $response = $this->getJson('/api/products');
       }
       $response->assertStatus(429);
   }
   ```

#### معايير القبول:
- [ ] Rate limiting على جميع API routes
- [ ] Rate limiting على Authentication routes
- [ ] Tests تتحقق من الحدود
- [ ] Headers تحتوي على X-RateLimit-*

---

### TASK-C6: تحسين Security Headers
**الأولوية:** 🔴 حرجة  
**الجهد:** متوسط (3-4 ساعات)  
**المسؤول:** Security Engineer  
**الموعد النهائي:** يوم 3

#### الخطوات:
1. **تحسين CSP Headers:**
   ```php
   $csp = [
       "default-src 'self'",
       "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
       "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
       "font-src 'self' https://fonts.gstatic.com",
       "img-src 'self' data: https:",
       "connect-src 'self' https://api.openai.com",
   ];
   ```

2. **إضافة Permissions-Policy:**
   ```php
   $response->headers->set('Permissions-Policy', 
       'geolocation=(), microphone=(), camera=()'
   );
   ```

3. **تحسين Logging:**
   ```php
   // إزالة جميع البيانات الحساسة
   $safePayload = $request->except([
       'password', 'password_confirmation', 
       'token', 'api_key', 'secret'
   ]);
   ```

4. **إضافة Tests:**
   ```php
   public function test_security_headers_are_present()
   {
       $response = $this->get('/');
       $response->assertHeader('X-Frame-Options', 'DENY');
       $response->assertHeader('X-Content-Type-Options', 'nosniff');
       // ... المزيد
   }
   ```

#### معايير القبول:
- [ ] CSP Headers محسّنة
- [ ] لا تسجيل للبيانات الحساسة
- [ ] Tests تتحقق من جميع Headers
- [ ] A+ على securityheaders.com

---

## 🟠 المرحلة 2: المشاكل العالية (High) - الأسبوع 2

### TASK-H1: تحويل Validation إلى Form Requests
**الأولوية:** 🟠 عالية  
**الجهد:** متوسط (8-10 ساعات)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 8-10

#### الخطوات:
1. **تحديد جميع Controllers التي تستخدم $request->validate():**
   ```bash
   grep -r "request->validate" app/Http/Controllers/
   ```

2. **إنشاء Form Requests:**
   ```bash
   php artisan make:request StoreProductRequest
   php artisan make:request UpdateProductRequest
   php artisan make:request StorePriceAlertRequest
   # ... إلخ
   ```

3. **نقل Validation Logic:**
   ```php
   // ❌ قبل في Controller
   $request->validate([
       'name' => 'required|string|max:255',
   ]);
   
   // ✅ بعد في Form Request
   public function rules(): array
   {
       return [
           'name' => 'required|string|max:255',
       ];
   }
   ```

4. **تحديث Controller Methods:**
   ```php
   // ❌ قبل
   public function store(Request $request)
   
   // ✅ بعد
   public function store(StoreProductRequest $request)
   ```

#### معايير القبول:
- [ ] جميع Validation في Form Requests
- [ ] صفر $request->validate() في Controllers
- [ ] Tests محدّثة

---

### TASK-H2: إضافة Database Indexes
**الأولوية:** 🟠 عالية  
**الجهد:** متوسط (4-6 ساعات)  
**المسؤول:** Database Engineer  
**الموعد النهائي:** يوم 9-10

#### الخطوات:
1. **تحليل Slow Queries باستخدام Telescope:**
   ```bash
   php artisan telescope:install
   ```

2. **إنشاء Migration للـ Indexes:**
   ```bash
   php artisan make:migration add_indexes_to_products_table
   ```

3. **إضافة Indexes:**
   ```php
   Schema::table('products', function (Blueprint $table) {
       $table->index('slug');
       $table->index('category_id');
       $table->index('brand_id');
       $table->index(['is_active', 'created_at']);
       $table->fullText(['name', 'description']);
   });
   ```

4. **قياس التحسين:**
   ```bash
   php artisan tinker
   >>> DB::enableQueryLog();
   >>> Product::where('slug', 'test')->first();
   >>> DB::getQueryLog();
   ```

#### معايير القبول:
- [ ] جميع Foreign Keys لها indexes
- [ ] Columns المستخدمة في WHERE لها indexes
- [ ] Full-text search indexes للبحث
- [ ] تحسين ملحوظ في Query Time

---

### TASK-H3: تحقيق 90%+ Test Coverage
**الأولوية:** 🟠 عالية  
**الجهد:** كبير (15-20 ساعة)  
**المسؤول:** QA Engineer  
**الموعد النهائي:** يوم 11-14

#### الخطوات:
1. **تشغيل Coverage Report:**
   ```bash
   XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-html build/coverage
   ```

2. **تحديد الملفات غير المختبرة:**
   - فتح `build/coverage/index.html`
   - تحديد الملفات ذات Coverage < 80%

3. **كتابة Tests للملفات المفقودة:**
   ```bash
   php artisan make:test Services/ProductServiceTest --unit
   php artisan make:test Controllers/CartControllerTest
   ```

4. **التركيز على:**
   - Services (أهم شيء)
   - Controllers
   - Repositories
   - Models (Business Logic)

5. **تشغيل Infection:**
   ```bash
   vendor/bin/infection --threads=4
   ```

#### معايير القبول:
- [ ] Test Coverage >= 90%
- [ ] Infection MSI >= 80%
- [ ] جميع Services لها tests
- [ ] جميع Controllers لها tests

---

### TASK-H4: إزالة N+1 Queries
**الأولوية:** 🟠 عالية  
**الجهد:** متوسط (6-8 ساعات)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 11-12

#### الخطوات:
1. **تفعيل Telescope N+1 Detection:**
   ```php
   // config/telescope.php
   'watchers' => [
       Watchers\QueryWatcher::class => [
           'enabled' => true,
           'slow' => 100,
       ],
   ],
   ```

2. **تحديد N+1 Queries:**
   - زيارة الصفحات الرئيسية
   - فحص Telescope Dashboard

3. **إصلاح باستخدام Eager Loading:**
   ```php
   // ❌ قبل
   $products = Product::all();
   foreach ($products as $product) {
       echo $product->category->name; // N+1!
   }
   
   // ✅ بعد
   $products = Product::with('category')->get();
   ```

4. **إضافة Tests:**
   ```php
   public function test_products_index_has_no_n_plus_one()
   {
       Product::factory()->count(10)->create();
       DB::enableQueryLog();
       $this->get('/products');
       $queries = DB::getQueryLog();
       $this->assertLessThan(5, count($queries));
   }
   ```

#### معايير القبول:
- [ ] صفر N+1 queries في الصفحات الرئيسية
- [ ] Tests تمنع N+1 في المستقبل
- [ ] تحسين ملحوظ في Response Time

---

## 🟡 المرحلة 3: المشاكل المتوسطة (Medium) - الأسبوع 3-4

### TASK-M1: استبدال Strings بـ Enums
**الأولوية:** 🟡 متوسطة  
**الجهد:** متوسط (10-12 ساعة)  
**المسؤول:** Backend Developer  
**الموعد النهائي:** يوم 15-18

#### الخطوات:
1. **إنشاء Enums:**
   ```bash
   php artisan make:enum OrderStatus
   php artisan make:enum PaymentStatus
   php artisan make:enum UserRole
   ```

2. **تعريف Enum:**
   ```php
   enum OrderStatus: string
   {
       case PENDING = 'pending';
       case PROCESSING = 'processing';
       case COMPLETED = 'completed';
       case CANCELLED = 'cancelled';
   }
   ```

3. **استخدام في Models:**
   ```php
   protected $casts = [
       'status' => OrderStatus::class,
   ];
   ```

4. **تحديث Database:**
   ```php
   $table->string('status')->default(OrderStatus::PENDING->value);
   ```

#### معايير القبول:
- [ ] جميع Status fields تستخدم Enums
- [ ] Migration للبيانات الموجودة
- [ ] Tests محدّثة

---

### TASK-M2: تحسين التوثيق
**الأولوية:** 🟡 متوسطة  
**الجهد:** متوسط (8-10 ساعات)  
**المسؤول:** Technical Writer  
**الموعد النهائي:** يوم 19-21

#### الخطوات:
1. **إنشاء API Documentation:**
   ```bash
   php artisan l5-swagger:generate
   ```

2. **إضافة ملفات:**
   - `CONTRIBUTING.md`
   - `SECURITY.md`
   - `CHANGELOG.md`
   - `docs/ARCHITECTURE.md`
   - `docs/API.md`

3. **تحديث README.md:**
   - إضافة Badges
   - تحسين التنسيق
   - إضافة Screenshots

#### معايير القبول:
- [ ] API Documentation كاملة
- [ ] جميع الملفات المطلوبة موجودة
- [ ] README محدّث

---

## 🟢 المرحلة 4: التحسينات النهائية - الأسبوع 5-6

### TASK-L1: تحسين CI/CD
### TASK-L2: Google Lighthouse Optimization
### TASK-L3: إضافة Monitoring & Alerting

---

## 📊 جدول المتابعة

| المهمة | الأولوية | الجهد | الموعد | الحالة |
|--------|----------|-------|--------|--------|
| TASK-C1 | 🔴 | 6h | يوم 1-2 | ⏳ |
| TASK-C2 | 🔴 | 0.25h | يوم 1 | ⏳ |
| TASK-C3 | 🔴 | 25h | يوم 3-7 | ⏳ |
| TASK-C4 | 🔴 | 3h | يوم 2 | ⏳ |
| TASK-C5 | 🔴 | 3h | يوم 2 | ⏳ |
| TASK-C6 | 🔴 | 4h | يوم 3 | ⏳ |
| TASK-H1 | 🟠 | 10h | يوم 8-10 | ⏳ |
| TASK-H2 | 🟠 | 6h | يوم 9-10 | ⏳ |
| TASK-H3 | 🟠 | 18h | يوم 11-14 | ⏳ |
| TASK-H4 | 🟠 | 8h | يوم 11-12 | ⏳ |

**إجمالي الجهد المقدر:** 150-200 ساعة  
**المدة الزمنية:** 6 أسابيع  
**حجم الفريق المقترح:** 3-4 مطورين

---

## ✅ معايير النجاح النهائية

- [ ] PHPStan Level 8 - صفر أخطاء
- [ ] Test Coverage >= 90%
- [ ] Infection MSI >= 80%
- [ ] صفر ثغرات أمنية حرجة
- [ ] Google Lighthouse Score >= 90
- [ ] صفر N+1 queries
- [ ] جميع Dependencies محدّثة
- [ ] CI/CD Pipeline كامل
- [ ] Documentation شاملة

---

*تم إنشاء هذه الخطة بواسطة Augment Agent - 2025*

