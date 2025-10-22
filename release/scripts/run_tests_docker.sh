#!/usr/bin/env bash
set -euo pipefail

echo "Starting containers (detached) for testing..."
docker compose up -d

echo "Running Feature test suite inside app container..."
docker compose exec -T app ./vendor/bin/phpunit --testsuite Feature

echo "Running full composer test target inside app container..."
docker compose exec -T app composer test

echo "All tests executed."