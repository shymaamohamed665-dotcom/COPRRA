Param(
    [switch]$VerboseOutput
)

Write-Host "Validating Docker Compose configuration..." -ForegroundColor Cyan
docker compose config | Out-Null
if ($LASTEXITCODE -ne 0) {
    Write-Error "docker compose config failed. Please fix syntax errors."; exit 1
}

Write-Host "Bringing up containers (detached)..." -ForegroundColor Cyan
docker compose up -d
if ($LASTEXITCODE -ne 0) { Write-Error "Failed to start containers."; exit 1 }

Write-Host "Checking service statuses..." -ForegroundColor Cyan
docker compose ps

Write-Host "Checking app health endpoint (/api/health)..." -ForegroundColor Cyan
try {
    $health = Invoke-WebRequest -Uri http://localhost/api/health -UseBasicParsing -TimeoutSec 5
    Write-Host "Health response: $($health.StatusCode) $($health.Content)" -ForegroundColor Green
} catch {
    Write-Warning "Health check failed: $($_.Exception.Message)"
}

Write-Host "Validating database connectivity from app container..." -ForegroundColor Cyan
docker compose exec -T app php -r "try{ new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); echo 'DB OK'; }catch(Exception $e){ echo 'DB FAIL: ' . $e->getMessage(); exit(1);}"
if ($LASTEXITCODE -ne 0) { Write-Warning "Database connection check failed." }

Write-Host "Validating Redis connectivity from app container..." -ForegroundColor Cyan
docker compose exec -T app php -r "try{ $r = new Redis(); $r->connect(getenv('REDIS_HOST'), intval(getenv('REDIS_PORT'))); echo $r->ping(); }catch(Exception $e){ echo 'Redis FAIL: ' . $e->getMessage(); exit(1);}"
if ($LASTEXITCODE -ne 0) { Write-Warning "Redis connection check failed." }

Write-Host "Validation complete." -ForegroundColor Green