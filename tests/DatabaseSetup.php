<?php

declare(strict_types=1);

namespace Tests;

trait DatabaseSetup
{
    /**
     * Set up the database for testing.
     */
    protected function setUpDatabase(): void
    {
        // Avoid binding Schema facade accessor here; rely on providers or SafeMiddlewareTestBase

        // Use the current default connection configured by the test runner / RefreshDatabase
        // Avoid changing database.default to prevent conflicts with RefreshDatabase lifecycle

        // If core tables already exist, skip manual creation to avoid conflicts
        // Avoid using Schema facade here because some "safe" tests don't fully bootstrap Laravel,
        // which can make the Schema facade's accessor (db.schema) unavailable.
        // Avoid using Laravel's config() helper as Safe* tests don't fully bootstrap the container
        // Prefer environment variable from phpunit.xml and default to sqlite
        $connection = getenv('DB_CONNECTION') ?: 'sqlite';
        try {
            // Probe connection; even if core tables exist, still ensure others are created
            \DB::connection($connection)->select('SELECT 1');
        } catch (\Throwable $e) {
            // If the check fails for any reason, proceed with manual table creation
        }

        // Create tables manually without migrations (idempotent: uses IF NOT EXISTS or existence checks)
        $this->createTablesManually();

        // Ù„Ø§ ØªØ¨Ø¯Ø£ Ù…Ø¹Ø§Ù…Ù„Ø© Ù‡Ù†Ø§ Ù„ØªØ¬Ù†Ø¨ Ø§Ù„ØªØ¯Ø§Ø®Ù„ Ù…Ø¹ Ù…Ø¹Ø§Ù…Ù„Ø§Øª Ø£Ø¯ÙˆØ§Øª Ø§Ù„Ø§Ø®ØªØ¨Ø§Ø±
    }

    /**
     * Create tables manually for testing.
     */
    protected function createTablesManually(): void
    {
        // Only use the default connection (in-memory SQLite for tests)
        // Avoid using config() here; verify the connection by probing it directly
        $connection = getenv('DB_CONNECTION') ?: 'sqlite';
        try {
            \DB::connection($connection)->select('SELECT 1');
        } catch (\Throwable $e) {
            // If probing the connection fails, skip manual setup
            return;
        }

        // Only create tables on the actual test connection
        $this->createTablesInConnection($connection);
    }

    /**
     * Create tables in a specific connection.
     */
    protected function createTablesInConnection(string $connection): void
    {
        // Use the specified connection
        \DB::connection($connection)->statement('PRAGMA foreign_keys=OFF;');
        \DB::connection($connection)->statement('PRAGMA auto_vacuum=0;'); // Disable auto vacuum

        // Avoid dropping tables to prevent FK-related issues on SQLite.
        // Since tests use in-memory SQLite, tables start empty in each process.

        // Ø§Ø¨Ù‚Ù Ù‚ÙŠÙˆØ¯ Ø§Ù„Ù…ÙØ§ØªÙŠØ­ Ø§Ù„Ø£Ø¬Ù†Ø¨ÙŠØ© Ù…Ø¹Ø·Ù„Ø© Ø£Ø«Ù†Ø§Ø¡ Ø¥Ù†Ø´Ø§Ø¡/ØªÙ‡ÙŠØ¦Ø© Ø§Ù„Ø¬Ø¯Ø§ÙˆÙ„ Ù„ØªØ¬Ù†Ø¨
        // Ø§Ù„ØªØ¹Ø§Ø±Ø¶Ø§Øª Ù…Ø¹ Ø¹Ù…Ù„ÙŠØ§Øª drop Ø§Ù„ØªÙŠ ÙŠÙ†ÙØ°Ù‡Ø§ RefreshDatabase Ø¹Ù„Ù‰ SQLite.

        // Create essential tables for testing
        $this->createUsersTable($connection);
        $this->createProductsTable($connection);
        $this->createPriceHistoriesTable($connection);
        $this->createPriceOffersTable($connection);
        $this->createWishlistsTable($connection);
        $this->createBrandsTable($connection);
        $this->createCategoriesTable($connection);
        // Create language-related tables first to satisfy FK constraints
        $this->createLanguagesTable($connection);
        $this->createCurrenciesTable($connection);
        $this->createPriceAlertsTable($connection);
        $this->createLanguageCurrencyTable($connection);
        $this->createStoresTable($connection);
        $this->createProductStoreTable($connection);
        $this->createUserLocaleSettingsTable($connection);
        $this->createReviewsTable($connection);
        $this->createUsersTable($connection);
        $this->createAuditLogsTable($connection);
        $this->createCustomNotificationsTable($connection);
        $this->createNotificationsTable($connection);
        $this->createMigrationsTable($connection);
        $this->createPasswordResetTokensTable($connection);
        $this->createPersonalAccessTokensTable($connection);
        $this->createOrdersTable($connection);
        $this->createOrderItemsTable($connection);
        $this->createUserPointsTable($connection);
        $this->createPaymentsTable($connection);
        $this->createAnalyticsEventsTable($connection);
        $this->createExchangeRatesTable($connection);
        $this->createWebhooksTable($connection);
        $this->createWebhookLogsTable($connection);

        // New tables required by tests
        $this->createCartItemsTable($connection);
        $this->createPaymentMethodsTable($connection);
        $this->createRewardsTable($connection);
    }

    /**
     * Create product_store pivot table.
     */
    protected function createProductStoreTable(string $connection = 'testing'): void
    {
        \DB::connection($connection)->statement('
            CREATE TABLE IF NOT EXISTS product_store (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                store_id INTEGER NOT NULL,
                price DECIMAL(10,2) DEFAULT 0,
                currency_id INTEGER,
                is_available BOOLEAN DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        // Ensure unique pair per product-store
        \DB::connection($connection)->statement('CREATE UNIQUE INDEX IF NOT EXISTS product_store_unique ON product_store(product_id, store_id)');

        // Helpful indexes
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_product_store_product_id ON product_store(product_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_product_store_store_id ON product_store(store_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_product_store_currency_id ON product_store(currency_id)');
    }

    /**
     * Create price_histories table.
     */
    protected function createPriceHistoriesTable(string $connection = 'testing'): void
    {
        // Create table only if it does not already exist to avoid conflicts with migrations
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='price_histories'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE price_histories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    effective_date DATETIME NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (product_id) REFERENCES products(id)
                )
            ');
        }
    }

    /**
     * Create users table.
     */
    protected function createUsersTable(string $connection = 'testing'): void
    {
        // Check if table already exists
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE,
                    email_verified_at DATETIME,
                    password VARCHAR(255) NOT NULL,
                    password_confirmed_at DATETIME,
                    is_admin BOOLEAN DEFAULT 0,
                    phone VARCHAR(20),
                    role VARCHAR(255) DEFAULT "user",
                    is_blocked BOOLEAN DEFAULT 0,
                    ban_reason VARCHAR(255),
                    ban_description TEXT,
                    banned_at DATETIME,
                    ban_expires_at DATETIME,
                    is_active BOOLEAN DEFAULT 1,
                    session_id VARCHAR(255),
                    remember_token VARCHAR(100),
                    created_at DATETIME,
                    updated_at DATETIME,
                    CONSTRAINT chk_email_format CHECK (email IS NULL OR email GLOB \'*@*\'),
                    CONSTRAINT chk_phone_format CHECK (phone IS NULL OR phone GLOB \'+[0-9]*\')
                )
            ');
        }

        // Ensure password_confirmed_at column exists even if table was pre-created
        $columns = \DB::connection($connection)->select("PRAGMA table_info('users')");
        $hasPasswordConfirmedAt = false;
        foreach ($columns as $col) {
            if (($col->name ?? $col['name'] ?? null) === 'password_confirmed_at') {
                $hasPasswordConfirmedAt = true;
                break;
            }
        }
        if (! $hasPasswordConfirmedAt) {
            \DB::connection($connection)->statement('ALTER TABLE users ADD COLUMN password_confirmed_at DATETIME');
        }
    }

    /**
     * Create products table.
     */
    protected function createProductsTable(string $connection = 'testing'): void
    {
        \DB::connection($connection)->statement('
            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                meta_title VARCHAR(255),
                meta_description TEXT,
                meta_keywords TEXT,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(255),
                image_url VARCHAR(255),
                category_id INTEGER,
                brand_id INTEGER,
                store_id INTEGER,
                currency_id INTEGER,
                is_active BOOLEAN DEFAULT 1,
                is_featured BOOLEAN DEFAULT 0,
                stock INTEGER DEFAULT 0,
                stock_quantity INTEGER DEFAULT 0,
                quantity INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');

        // Helpful indexes to match app expectations
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_products_category_id ON products(category_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_products_brand_id ON products(brand_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_products_store_id ON products(store_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_products_currency_id ON products(currency_id)');
    }

    /**
     * Create price_offers table.
     */
    protected function createPriceOffersTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='price_offers'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE price_offers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER,
                    product_sku VARCHAR(255),
                    store_id INTEGER,
                    price DECIMAL(10,2),
                    currency VARCHAR(3) DEFAULT "USD",
                    product_url VARCHAR(255),
                    affiliate_url VARCHAR(255),
                    in_stock BOOLEAN DEFAULT 1,
                    stock_quantity INTEGER,
                    condition VARCHAR(255) DEFAULT "new",
                    rating DECIMAL(3,1),
                    reviews_count INTEGER DEFAULT 0,
                    image_url VARCHAR(255),
                    specifications TEXT,
                    is_available BOOLEAN DEFAULT 1,
                    original_price DECIMAL(10,2),
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (product_id) REFERENCES products(id),
                    FOREIGN KEY (store_id) REFERENCES stores(id)
                )
            ');
        }
    }

    /**
     * Create wishlists table.
     */
    protected function createWishlistsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='wishlists'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE wishlists (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    product_id INTEGER,
                    notes TEXT,
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (product_id) REFERENCES products(id)
                )
            ');
        }
    }

    /**
     * Create brands table.
     */
    protected function createBrandsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='brands'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE brands (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT,
                    logo_url VARCHAR(255),
                    website_url VARCHAR(255),
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME
                )
            ');
        }
    }

    /**
     * Create categories table.
     */
    protected function createCategoriesTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='categories'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE categories (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR(255) NOT NULL,
                    slug VARCHAR(255) UNIQUE NOT NULL,
                    description TEXT,
                    meta_title VARCHAR(255),
                    meta_description TEXT,
                    meta_keywords TEXT,
                    parent_id INTEGER,
                    level INTEGER DEFAULT 0,
                    is_active BOOLEAN DEFAULT 1,
                    created_at DATETIME,
                    updated_at DATETIME,
                    deleted_at DATETIME
                )
            ');
        }
    }

    /**
     * Create currencies table.
     */
    protected function createCurrenciesTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='currencies'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE currencies (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code VARCHAR(3) UNIQUE NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    symbol VARCHAR(10),
                    is_active BOOLEAN DEFAULT 1,
                    is_default BOOLEAN DEFAULT 0,
                    exchange_rate DECIMAL(10,4) DEFAULT 1.0000,
                    decimal_places INTEGER DEFAULT 2,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create languages table.
     */
    protected function createLanguagesTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='languages'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE languages (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    code VARCHAR(5) UNIQUE NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    native_name VARCHAR(255),
                    direction VARCHAR(3) DEFAULT "ltr",
                    is_active BOOLEAN DEFAULT 1,
                    is_default BOOLEAN DEFAULT 0,
                    sort_order INTEGER DEFAULT 0,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create price_alerts table.
     */
    protected function createPriceAlertsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='price_alerts'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE price_alerts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                target_price DECIMAL(10,2) NOT NULL,
                current_price DECIMAL(10,2),
                repeat_alert BOOLEAN DEFAULT 0,
                is_active BOOLEAN DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');
    }

    protected function createLanguageCurrencyTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='language_currency'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE language_currency (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                language_id INTEGER NOT NULL,
                currency_id INTEGER NOT NULL,
                is_default BOOLEAN DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
    }

    protected function createStoresTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='stores'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE stores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) UNIQUE NOT NULL,
                description TEXT,
                meta_title VARCHAR(255),
                meta_description TEXT,
                meta_keywords TEXT,
                logo_url VARCHAR(255),
                website_url VARCHAR(255),
                country_code VARCHAR(2),
                supported_countries TEXT,
                is_active BOOLEAN DEFAULT 1,
                priority INTEGER DEFAULT 0,
                affiliate_base_url VARCHAR(255),
                affiliate_code VARCHAR(255),
                api_config TEXT,
                currency_id INTEGER,
                created_at DATETIME,
                updated_at DATETIME,
                deleted_at DATETIME
            )
        ');
    }

    protected function createMigrationsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='migrations'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration VARCHAR NOT NULL,
                batch INTEGER NOT NULL
            )
        ');
    }

    /**
     * Create password_reset_tokens table.
     */
    protected function createPasswordResetTokensTable(string $connection = 'testing'): void
    {
        // Check if table already exists
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='password_reset_tokens'");
        if (! empty($exists)) {
            return;
        }

        \DB::connection($connection)->statement('
            CREATE TABLE password_reset_tokens (
                email VARCHAR(255) NOT NULL,
                token VARCHAR(255) NOT NULL,
                created_at DATETIME NULL
            )
        ');

        // Create index on email for faster lookup
        \DB::connection($connection)->statement('CREATE INDEX idx_password_reset_tokens_email ON password_reset_tokens(email)');
    }

    protected function createUserLocaleSettingsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='user_locale_settings'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE user_locale_settings (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                session_id VARCHAR(255),
                language_id INTEGER NOT NULL,
                currency_id INTEGER NOT NULL,
                ip_address VARCHAR(45),
                country_code VARCHAR(2),
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (language_id) REFERENCES languages(id),
                FOREIGN KEY (currency_id) REFERENCES currencies(id)
            )
        ');
    }

    /**
     * Create reviews table.
     */
    protected function createReviewsTable(string $connection = 'testing'): void
    {
        \DB::connection($connection)->statement('
            CREATE TABLE IF NOT EXISTS reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                title VARCHAR(255),
                content TEXT NOT NULL,
                rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
                is_verified_purchase BOOLEAN DEFAULT 0,
                is_approved BOOLEAN DEFAULT 1,
                helpful_votes TEXT,
                helpful_count INTEGER DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (product_id) REFERENCES products(id)
            )
        ');
    }

    /**
     * Create user_points table.
     */
    protected function createUserPointsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='user_points'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE user_points (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                points INTEGER NOT NULL,
                type VARCHAR(255) NOT NULL,
                source VARCHAR(255) NOT NULL,
                order_id INTEGER,
                description TEXT,
                expires_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
            )
        ');

        // Helpful indexes
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS user_points_user_type_index ON user_points (user_id, type)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS user_points_expires_at_index ON user_points (expires_at)');
    }

    /**
     * Create audit_logs table.
     */
    protected function createAuditLogsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='audit_logs'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event VARCHAR(255) NOT NULL,
                auditable_type VARCHAR(255) NOT NULL,
                auditable_id INTEGER NOT NULL,
                user_id INTEGER,
                ip_address VARCHAR(45),
                user_agent TEXT,
                old_values TEXT,
                new_values TEXT,
                metadata TEXT,
                url VARCHAR(255),
                method VARCHAR(10),
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
    }

    /**
     * Create custom_notifications table.
     */
    protected function createCustomNotificationsTable(string $connection = 'testing'): void
    {
        // Check if table already exists
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_notifications'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE custom_notifications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    type VARCHAR(255) NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    message TEXT NOT NULL,
                    data TEXT,
                    read_at DATETIME,
                    sent_at DATETIME,
                    priority INTEGER DEFAULT 2,
                    channel VARCHAR(255) DEFAULT "email",
                    status VARCHAR(255) DEFAULT "pending",
                    metadata TEXT,
                    tags TEXT,
                    created_at DATETIME,
                    updated_at DATETIME
                )
            ');
        }
    }

    /**
     * Create personal_access_tokens table for Sanctum.
     */
    protected function createPersonalAccessTokensTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='personal_access_tokens'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE personal_access_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tokenable_type VARCHAR(255) NOT NULL,
                tokenable_id INTEGER NOT NULL,
                name VARCHAR(255) NOT NULL,
                token VARCHAR(64) NOT NULL UNIQUE,
                abilities TEXT,
                last_used_at DATETIME,
                expires_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
    }

    /**
     * Create orders table.
     */
    protected function createOrdersTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='orders'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number VARCHAR(255) UNIQUE NOT NULL,
                user_id INTEGER,
                status VARCHAR(255) DEFAULT "pending",
                total_amount DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                tax_amount DECIMAL(10,2) DEFAULT 0.00,
                shipping_amount DECIMAL(10,2) DEFAULT 0.00,
                discount_amount DECIMAL(10,2) DEFAULT 0.00,
                currency VARCHAR(3) DEFAULT "USD",
                shipping_address TEXT,
                billing_address TEXT,
                notes TEXT,
                shipped_at DATETIME,
                delivered_at DATETIME,
                order_date DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                CONSTRAINT chk_order_date_format CHECK (order_date IS NULL OR order_date GLOB "[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]"),
                FOREIGN KEY (user_id) REFERENCES users(id)
            )
        ');
    }

    /**
     * Create order_items table.
     */
    protected function createOrderItemsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='order_items'");
        if (empty($exists)) {
            \DB::connection($connection)->statement('
                CREATE TABLE order_items (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    order_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    quantity INTEGER NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    total DECIMAL(10,2) NOT NULL,
                    created_at DATETIME,
                    updated_at DATETIME,
                    FOREIGN KEY (order_id) REFERENCES orders(id),
                    FOREIGN KEY (product_id) REFERENCES products(id)
                )
            ');
        }

        // Ensure total is computed on insert to support tests that only set price/quantity
        \DB::connection($connection)->statement('
            CREATE TRIGGER IF NOT EXISTS order_items_total_calc AFTER INSERT ON order_items
            BEGIN
                UPDATE order_items
                SET total = NEW.quantity * NEW.price
                WHERE id = NEW.id;
            END;
        ');
    }

    /**
     * Create payments table.
     */
    protected function createPaymentsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='payments'");
        if (! empty($exists)) {
            // Table exists; ensure missing columns are present
            $columns = \DB::connection($connection)->select("PRAGMA table_info('payments')");
            $hasGatewayResponse = false;
            $hasPaymentMethodId = false;
            $hasProcessedAt = false;
            foreach ($columns as $col) {
                if (($col->name ?? $col['name'] ?? null) === 'gateway_response') {
                    $hasGatewayResponse = true;
                }
                if (($col->name ?? $col['name'] ?? null) === 'payment_method_id') {
                    $hasPaymentMethodId = true;
                }
                if (($col->name ?? $col['name'] ?? null) === 'processed_at') {
                    $hasProcessedAt = true;
                }
            }
            if (! $hasGatewayResponse) {
                // SQLite stores JSON as TEXT
                \DB::connection($connection)->statement('ALTER TABLE payments ADD COLUMN gateway_response TEXT');
            }
            if (! $hasPaymentMethodId) {
                \DB::connection($connection)->statement('ALTER TABLE payments ADD COLUMN payment_method_id INTEGER');
            }
            if (! $hasProcessedAt) {
                \DB::connection($connection)->statement('ALTER TABLE payments ADD COLUMN processed_at DATETIME');
            }

            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                payment_method_id INTEGER,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(3) DEFAULT "USD",
                status VARCHAR(255) DEFAULT "pending",
                method VARCHAR(255),
                transaction_id VARCHAR(255),
                gateway VARCHAR(255),
                gateway_response TEXT,
                metadata TEXT,
                processed_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (order_id) REFERENCES orders(id),
                FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
            )
        ');
    }

    /**
     * Create notifications table (Laravel default structure).
     */
    protected function createNotificationsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='notifications'");
        if (! empty($exists)) {
            // Ensure user_id column exists as tests rely on it
            $columns = \DB::connection($connection)->select("PRAGMA table_info('notifications')");
            $hasUserId = false;
            foreach ($columns as $col) {
                if (($col->name ?? $col['name'] ?? null) === 'user_id') {
                    $hasUserId = true;
                    break;
                }
            }
            if (! $hasUserId) {
                \DB::connection($connection)->statement('ALTER TABLE notifications ADD COLUMN user_id INTEGER');
                \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)');
            }

            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE notifications (
                id VARCHAR(36) PRIMARY KEY,
                type VARCHAR(255) NOT NULL,
                user_id INTEGER,
                notifiable_type VARCHAR(255) NOT NULL,
                notifiable_id INTEGER NOT NULL,
                data TEXT NOT NULL,
                read_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_notifications_notifiable ON notifications(notifiable_type, notifiable_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)');
    }

    /**
     * Create cart_items table.
     */
    protected function createCartItemsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='cart_items'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE cart_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_cart_items_user_id ON cart_items(user_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_cart_items_product_id ON cart_items(product_id)');
    }

    /**
     * Create payment_methods table.
     */
    protected function createPaymentMethodsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='payment_methods'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE payment_methods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                gateway VARCHAR(255) NOT NULL,
                type VARCHAR(255) NOT NULL,
                config TEXT,
                is_active BOOLEAN DEFAULT 1,
                is_default BOOLEAN DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_payment_methods_gateway_active ON payment_methods(gateway, is_active)');
    }

    /**
     * Create rewards table.
     */
    protected function createRewardsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='rewards'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE rewards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                points_required INTEGER NOT NULL,
                type VARCHAR(50) NOT NULL,
                value TEXT,
                is_active BOOLEAN DEFAULT 1,
                usage_limit INTEGER,
                valid_from DATETIME,
                valid_until DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_rewards_active_points ON rewards(is_active, points_required)');
    }

    /**
     * Create analytics_events table.
     */
    protected function createAnalyticsEventsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='analytics_events'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE analytics_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_type VARCHAR(50) NOT NULL,
                event_name VARCHAR(100) NOT NULL,
                user_id INTEGER,
                product_id INTEGER,
                category_id INTEGER,
                store_id INTEGER,
                metadata TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                session_id VARCHAR(100),
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id),
                FOREIGN KEY (category_id) REFERENCES categories(id),
                FOREIGN KEY (store_id) REFERENCES stores(id)
            )
        ');

        // Create indexes
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_analytics_events_event_type ON analytics_events(event_type)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_analytics_events_session_id ON analytics_events(session_id)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_analytics_events_event_type_created_at ON analytics_events(event_type, created_at)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_analytics_events_user_id_created_at ON analytics_events(user_id, created_at)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_analytics_events_product_id_created_at ON analytics_events(product_id, created_at)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_analytics_events_created_at ON analytics_events(created_at)');
    }

    /**
     * Create exchange_rates table.
     */
    protected function createExchangeRatesTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='exchange_rates'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE exchange_rates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                from_currency VARCHAR(3) NOT NULL,
                to_currency VARCHAR(3) NOT NULL,
                rate DECIMAL(10,6) NOT NULL,
                source VARCHAR(50),
                fetched_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME
            )
        ');

        // Create indexes
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_exchange_rates_from_to ON exchange_rates(from_currency, to_currency)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_exchange_rates_updated_at ON exchange_rates(updated_at)');
    }

    /**
     * Create webhooks table.
     */
    protected function createWebhooksTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='webhooks'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE webhooks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                store_identifier VARCHAR(50) NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                product_identifier VARCHAR(100) NOT NULL,
                product_id INTEGER,
                payload TEXT NOT NULL,
                signature VARCHAR(255),
                status VARCHAR(255) DEFAULT "pending",
                error_message TEXT,
                processed_at DATETIME,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )
        ');

        // Create indexes
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_webhooks_store_identifier ON webhooks(store_identifier)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_webhooks_event_type ON webhooks(event_type)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_webhooks_status ON webhooks(status)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_webhooks_store_event ON webhooks(store_identifier, event_type)');
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_webhooks_status_created ON webhooks(status, created_at)');
    }

    /**
     * Create webhook_logs table.
     */
    protected function createWebhookLogsTable(string $connection = 'testing'): void
    {
        $exists = \DB::connection($connection)->select("SELECT name FROM sqlite_master WHERE type='table' AND name='webhook_logs'");
        if (! empty($exists)) {
            return;
        }
        \DB::connection($connection)->statement('
            CREATE TABLE webhook_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                webhook_id INTEGER NOT NULL,
                action VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                metadata TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
            )
        ');

        // Create index
        \DB::connection($connection)->statement('CREATE INDEX IF NOT EXISTS idx_webhook_logs_webhook_id ON webhook_logs(webhook_id)');
    }

    /**
     * Clean up database after each test.
     *
     * IMPORTANT: Proper cleanup prevents test interdependencies!
     * - Rollback any open transactions (handled by TestCase::$connectionsToTransact)
     * - Clear any cached data that might affect subsequent tests
     * - Reset static/global state if any
     */
    protected function tearDownDatabase(): void
    {
        // Transaction rollback is handled automatically by TestCase::$connectionsToTransact
        // Additional cleanup can be added here if needed:

        // Clear cache between tests to prevent cached data from affecting other tests
        if (function_exists('cache')) {
            try {
                cache()->flush();
            } catch (\Throwable $e) {
                // Silently fail - cache might not be available in all test contexts
            }
        }

        // Clear any application-level caches
        if (class_exists(\Illuminate\Support\Facades\Cache::class)) {
            try {
                \Illuminate\Support\Facades\Cache::flush();
            } catch (\Throwable $e) {
                // Silently fail
            }
        }
    }
}
