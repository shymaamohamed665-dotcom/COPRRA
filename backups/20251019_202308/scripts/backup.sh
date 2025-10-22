#!/bin/sh
set -e

DATE=$(date +%Y%m%d_%H%M%S)
# Map environment variables: prefer MYSQL_* but fallback to DB_*
MYSQL_HOST=${MYSQL_HOST:-${DB_HOST:-localhost}}
MYSQL_USER=${MYSQL_USER:-${DB_USERNAME:-root}}
MYSQL_PASSWORD=${MYSQL_PASSWORD:-${DB_PASSWORD:-}}
MYSQL_DATABASE=${MYSQL_DATABASE:-${DB_DATABASE:-laravel}}

mysqldump -h"$MYSQL_HOST" -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" | gzip > "/backups/backup_${DATE}.sql.gz"

# Keep only last 7 days
find /backups -name "backup_*.sql.gz" -mtime +7 -delete