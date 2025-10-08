<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add performance indexes to improve query speed
     */
    public function up(): void
    {
        // Products table indexes
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table): void {
                // Index for product search and filtering
                if (! $this->indexExists('products', 'products_name_index')) {
                    $table->index('name', 'products_name_index');
                }

                if (! $this->indexExists('products', 'products_slug_index')) {
                    $table->index('slug', 'products_slug_index');
                }

                if (! $this->indexExists('products', 'products_category_id_index')) {
                    $table->index('category_id', 'products_category_id_index');
                }

                if (! $this->indexExists('products', 'products_brand_id_index')) {
                    $table->index('brand_id', 'products_brand_id_index');
                }

                if (! $this->indexExists('products', 'products_status_index')) {
                    $table->index('status', 'products_status_index');
                }

                if (! $this->indexExists('products', 'products_price_index')) {
                    $table->index('price', 'products_price_index');
                }

                // Composite index for common queries
                if (! $this->indexExists('products', 'products_status_category_index')) {
                    $table->index(['status', 'category_id'], 'products_status_category_index');
                }

                if (! $this->indexExists('products', 'products_created_at_index')) {
                    $table->index('created_at', 'products_created_at_index');
                }
            });
        }

        // Orders table indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! $this->indexExists('orders', 'orders_user_id_index')) {
                    $table->index('user_id', 'orders_user_id_index');
                }

                if (! $this->indexExists('orders', 'orders_status_index')) {
                    $table->index('status', 'orders_status_index');
                }

                if (! $this->indexExists('orders', 'orders_order_number_index')) {
                    $table->index('order_number', 'orders_order_number_index');
                }

                // Composite index for user orders by status
                if (! $this->indexExists('orders', 'orders_user_status_index')) {
                    $table->index(['user_id', 'status'], 'orders_user_status_index');
                }

                if (! $this->indexExists('orders', 'orders_created_at_index')) {
                    $table->index('created_at', 'orders_created_at_index');
                }
            });
        }

        // Order Items table indexes
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table): void {
                if (! $this->indexExists('order_items', 'order_items_order_id_index')) {
                    $table->index('order_id', 'order_items_order_id_index');
                }

                if (! $this->indexExists('order_items', 'order_items_product_id_index')) {
                    $table->index('product_id', 'order_items_product_id_index');
                }
            });
        }

        // Users table indexes
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (! $this->indexExists('users', 'users_email_index')) {
                    $table->index('email', 'users_email_index');
                }

                if (! $this->indexExists('users', 'users_status_index')) {
                    $table->index('status', 'users_status_index');
                }

                if (! $this->indexExists('users', 'users_created_at_index')) {
                    $table->index('created_at', 'users_created_at_index');
                }
            });
        }

        // Categories table indexes
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table): void {
                if (! $this->indexExists('categories', 'categories_slug_index')) {
                    $table->index('slug', 'categories_slug_index');
                }

                if (! $this->indexExists('categories', 'categories_parent_id_index')) {
                    $table->index('parent_id', 'categories_parent_id_index');
                }

                if (! $this->indexExists('categories', 'categories_status_index')) {
                    $table->index('status', 'categories_status_index');
                }
            });
        }

        // Reviews table indexes
        if (Schema::hasTable('reviews')) {
            Schema::table('reviews', function (Blueprint $table): void {
                if (! $this->indexExists('reviews', 'reviews_product_id_index')) {
                    $table->index('product_id', 'reviews_product_id_index');
                }

                if (! $this->indexExists('reviews', 'reviews_user_id_index')) {
                    $table->index('user_id', 'reviews_user_id_index');
                }

                if (! $this->indexExists('reviews', 'reviews_rating_index')) {
                    $table->index('rating', 'reviews_rating_index');
                }

                // Composite index for product reviews
                if (! $this->indexExists('reviews', 'reviews_product_rating_index')) {
                    $table->index(['product_id', 'rating'], 'reviews_product_rating_index');
                }
            });
        }

        // Carts table indexes
        if (Schema::hasTable('carts')) {
            Schema::table('carts', function (Blueprint $table): void {
                if (! $this->indexExists('carts', 'carts_user_id_index')) {
                    $table->index('user_id', 'carts_user_id_index');
                }

                if (! $this->indexExists('carts', 'carts_session_id_index')) {
                    $table->index('session_id', 'carts_session_id_index');
                }
            });
        }

        // Wishlist table indexes
        if (Schema::hasTable('wishlists')) {
            Schema::table('wishlists', function (Blueprint $table): void {
                if (! $this->indexExists('wishlists', 'wishlists_user_id_index')) {
                    $table->index('user_id', 'wishlists_user_id_index');
                }

                if (! $this->indexExists('wishlists', 'wishlists_product_id_index')) {
                    $table->index('product_id', 'wishlists_product_id_index');
                }

                // Composite unique index to prevent duplicates
                if (! $this->indexExists('wishlists', 'wishlists_user_product_unique')) {
                    $table->unique(['user_id', 'product_id'], 'wishlists_user_product_unique');
                }
            });
        }

        // Payments table indexes
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table): void {
                if (! $this->indexExists('payments', 'payments_order_id_index')) {
                    $table->index('order_id', 'payments_order_id_index');
                }

                if (! $this->indexExists('payments', 'payments_transaction_id_index')) {
                    $table->index('transaction_id', 'payments_transaction_id_index');
                }

                if (! $this->indexExists('payments', 'payments_status_index')) {
                    $table->index('status', 'payments_status_index');
                }
            });
        }

        // Notifications table indexes
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table): void {
                if (! $this->indexExists('notifications', 'notifications_notifiable_index')) {
                    $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_index');
                }

                if (! $this->indexExists('notifications', 'notifications_read_at_index')) {
                    $table->index('read_at', 'notifications_read_at_index');
                }
            });
        }

        // Activity Log table indexes
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table): void {
                if (! $this->indexExists('activity_log', 'activity_log_subject_index')) {
                    $table->index(['subject_type', 'subject_id'], 'activity_log_subject_index');
                }

                if (! $this->indexExists('activity_log', 'activity_log_causer_index')) {
                    $table->index(['causer_type', 'causer_id'], 'activity_log_causer_index');
                }

                if (! $this->indexExists('activity_log', 'activity_log__index')) {
                    $table->index('created_at', 'activity_log_created_at_index');
                }
            });
        }

        // Sessions table indexes
        if (Schema::hasTable('sessions')) {
            Schema::table('sessions', function (Blueprint $table): void {
                if (! $this->indexExists('sessions', 'sessions_user_id_index')) {
                    $table->index('user_id', 'sessions_user_id_index');
                }

                if (! $this->indexExists('sessions', 'sessions_last_activity_index')) {
                    $table->index('last_activity', 'sessions_last_activity_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes in reverse order of creation
        $indexes = [
            'products' => [
                'products_name_index',
                'products_slug_index',
                'products_category_id_index',
                'products_brand_id_index',
                'products_status_index',
                'products_price_index',
                'products_status_category_index',
                'products_created_at_index',
            ],
            'orders' => [
                'orders_user_id_index',
                'orders_status_index',
                'orders_order_number_index',
                'orders_user_status_index',
                'orders_created_at_index',
            ],
            'order_items' => [
                'order_items_order_id_index',
                'order_items_product_id_index',
            ],
            'users' => [
                'users_email_index',
                'users_status_index',
                'users_created_at_index',
            ],
            'categories' => [
                'categories_slug_index',
                'categories_parent_id_index',
                'categories_status_index',
            ],
            'reviews' => [
                'reviews_product_id_index',
                'reviews_user_id_index',
                'reviews_rating_index',
                'reviews_product_rating_index',
            ],
            'carts' => [
                'carts_user_id_index',
                'carts_session_id_index',
            ],
            'wishlists' => [
                'wishlists_user_id_index',
                'wishlists_product_id_index',
                'wishlists_user_product_unique',
            ],
            'payments' => [
                'payments_order_id_index',
                'payments_transaction_id_index',
                'payments_status_index',
            ],
            'notifications' => [
                'notifications_notifiable_index',
                'notifications_read_at_index',
            ],
            'activity_log' => [
                'activity_log_subject_index',
                'activity_log_causer_index',
                'activity_log_created_at_index',
            ],
            'sessions' => [
                'sessions_user_id_index',
                'sessions_last_activity_index',
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($tableIndexes): void {
                    foreach ($tableIndexes as $index) {
                        if ($this->indexExists($table->getTable(), $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $index): bool
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return false;
        }

        return Schema::getConnection()->getSchemaBuilder()->hasIndex($table, $index);
    }
};
