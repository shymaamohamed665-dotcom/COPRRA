-- FK Index and Column Type Fix Suggestions
-- Generated from CI MySQL FK diagnostics analysis
-- Apply indexes first, then column modifications

-- ============================================
-- ADD INDEX statements for FK columns
-- ============================================

-- Orders table FK indexes
ALTER TABLE `orders` ADD INDEX `idx_orders_user_id` (`user_id`);

-- Order Items table FK indexes  
ALTER TABLE `order_items` ADD INDEX `idx_order_items_order_id` (`order_id`);
ALTER TABLE `order_items` ADD INDEX `idx_order_items_product_id` (`product_id`);

-- Products table FK indexes
ALTER TABLE `products` ADD INDEX `idx_products_category_id` (`category_id`);
ALTER TABLE `products` ADD INDEX `idx_products_brand_id` (`brand_id`);
ALTER TABLE `products` ADD INDEX `idx_products_store_id` (`store_id`);
ALTER TABLE `products` ADD INDEX `idx_products_currency_id` (`currency_id`);

-- Reviews table FK indexes
ALTER TABLE `reviews` ADD INDEX `idx_reviews_product_id` (`product_id`);
ALTER TABLE `reviews` ADD INDEX `idx_reviews_user_id` (`user_id`);

-- Wishlists table FK indexes
ALTER TABLE `wishlists` ADD INDEX `idx_wishlists_product_id` (`product_id`);
ALTER TABLE `wishlists` ADD INDEX `idx_wishlists_user_id` (`user_id`);

-- Price Alerts table FK indexes
ALTER TABLE `price_alerts` ADD INDEX `idx_price_alerts_product_id` (`product_id`);
ALTER TABLE `price_alerts` ADD INDEX `idx_price_alerts_user_id` (`user_id`);

-- Price Offers table FK indexes
ALTER TABLE `price_offers` ADD INDEX `idx_price_offers_product_id` (`product_id`);

-- Price History table FK indexes
ALTER TABLE `price_history` ADD INDEX `idx_price_history_product_id` (`product_id`);

-- Payments table FK indexes
ALTER TABLE `payments` ADD INDEX `idx_payments_order_id` (`order_id`);

-- Product Store pivot table FK indexes
ALTER TABLE `product_store` ADD INDEX `idx_product_store_product_id` (`product_id`);
ALTER TABLE `product_store` ADD INDEX `idx_product_store_store_id` (`store_id`);
ALTER TABLE `product_store` ADD INDEX `idx_product_store_currency_id` (`currency_id`);

-- ============================================
-- MODIFY COLUMN statements for type alignment
-- ============================================

-- Ensure all FK columns are BIGINT UNSIGNED to match Laravel conventions
ALTER TABLE `orders` MODIFY `user_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `order_items` MODIFY `order_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `order_items` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `products` MODIFY `category_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `products` MODIFY `brand_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `products` MODIFY `store_id` BIGINT UNSIGNED NULL;
ALTER TABLE `products` MODIFY `currency_id` BIGINT UNSIGNED NULL;

ALTER TABLE `reviews` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `reviews` MODIFY `user_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `wishlists` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `wishlists` MODIFY `user_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `price_alerts` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `price_alerts` MODIFY `user_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `price_offers` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `price_history` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `payments` MODIFY `order_id` BIGINT UNSIGNED NOT NULL;

ALTER TABLE `product_store` MODIFY `product_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `product_store` MODIFY `store_id` BIGINT UNSIGNED NOT NULL;
ALTER TABLE `product_store` MODIFY `currency_id` BIGINT UNSIGNED NULL;

-- ============================================
-- Character set and collation alignment
-- ============================================

-- Ensure consistent charset and collation for string FK columns if any
-- (Most FK columns are numeric, but including for completeness)
