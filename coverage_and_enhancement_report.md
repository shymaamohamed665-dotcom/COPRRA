# Coverage and Enhancement Report

## Overview
- Expanded feature test coverage for `OrderService` and `FinancialTransactionService`.
- Fixed price-offer data mapping and ensured consistent availability defaults.
- Resolved failing tests due to schema constraints and global state issues.

## Changes Implemented
- Updated `app/Services/FinancialTransactionService.php` to:
  - Map `new_price` input to the persisted `price` field for offers.
  - Default `is_available` to `true` when creating/updating offers unless explicitly provided.
- Added feature tests:
  - `tests/Feature/OrderServiceCoverageTest.php`
  - `tests/Feature/FinancialTransactionServiceCoverageTest.php`
- Addressed test failures:
  - Ensured `store_id` is provided for `PriceOffer` inserts matching DB constraints.
  - Isolated `FinancialTransactionServiceCoverageTest` with `@runTestsInSeparateProcesses` to avoid strict global-state errors.

## Tests Added
- `OrderServiceCoverageTest`
  - Creates an order, validates subtotal/tax/shipping and item totals.
  - Confirms allowed status transitions and event dispatching (`OrderStatusChanged`).
  - Cancels pending order and restores product stock (`stock_quantity`).
  - Rejects invalid status transitions from final states.
- `FinancialTransactionServiceCoverageTest`
  - Creates a price offer and updates product price to the new offer.
  - Rejects negative product price updates with an exception.
  - Updates an existing offer and ensures product price reflects the lowest available offer.
  - Deletes a price offer and returns `true`.

## Results
- Feature suite passes locally.
  - Ran: `vendor\bin\phpunit --testsuite Feature -v`
  - Targeted runs also succeed for each new class:
    - `vendor\bin\phpunit --testsuite Feature --filter OrderServiceCoverageTest -v`
    - `vendor\bin\phpunit --testsuite Feature --filter FinancialTransactionServiceCoverageTest -v`
- Resolved previous errors:
  - `NOT NULL constraint failed: price_offers.store_id` by adding `store_id` in tests via `Store::factory()`.
  - Global-state modification warnings mitigated via per-class process isolation.

## Notes and Follow-ups
- If coverage metrics are needed, run with PCOV/Xdebug enabled and `--coverage-html` or `--coverage-text`.
- Consider adding unit tests for `AuditService` to verify log payloads if required.
- Order shipping/tax calculations use defaults; if business rules change, expand tests accordingly.
