#!/bin/bash

echo "🔍 Validating Docker Setup..."

# Check compose file syntax
echo "Checking docker-compose.yml..."
docker-compose config --quiet && echo "✅ Compose file valid" || echo "❌ Compose file invalid"

# Check if services are healthy
echo "Checking service health..."
docker-compose ps

# Test database connection
echo "Testing database..."
docker-compose exec app php artisan db:show

# Test Redis connection
echo "Testing Redis..."
docker-compose exec app php artisan tinker --execute="Cache::store('redis')->put('test', 'ok', 10); echo Cache::get('test');"

# Test queue
echo "Testing queue..."
docker-compose exec app php artisan queue:work --once --tries=1

echo "✅ All validations complete!"