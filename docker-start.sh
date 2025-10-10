#!/bin/bash

# エラーハンドリングを強化（set -e は使わない）
set -u  # 未定義変数の使用時にエラー

echo "========================================"
echo "  ASP-ORKA Starting..."
echo "========================================"
echo ""

# PHP設定を環境変数に基づいて動的に調整
echo "=== Configuring PHP Settings Based on Environment ==="
if [ "${APP_DEBUG:-false}" = "false" ]; then
    echo "Production mode: Disabling error display"
    cat > /usr/local/etc/php/conf.d/environment.ini <<'EOF'
display_errors = Off
display_startup_errors = Off
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT
EOF
else
    echo "Debug mode: Error display enabled"
    cat > /usr/local/etc/php/conf.d/environment.ini <<'EOF'
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
EOF
fi
echo "✓ PHP environment settings configured"
echo ""

# .envファイルを環境変数から生成
echo "📝 Generating .env file from environment variables..."
cat > /var/www/html/.env <<'EOF_MARKER'
# Auto-generated from Render environment variables
# Generated at: $(date)

# アプリケーション設定
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}

# Supabase Database
SUPABASE_DB_HOST=${SUPABASE_DB_HOST}
SUPABASE_DB_PORT=${SUPABASE_DB_PORT:-5432}
SUPABASE_DB_NAME=${SUPABASE_DB_NAME:-postgres}
SUPABASE_DB_USER=${SUPABASE_DB_USER}
SUPABASE_DB_PASS=${SUPABASE_DB_PASS}

# Supabase API
SUPABASE_URL=${SUPABASE_URL}
SUPABASE_ANON_KEY=${SUPABASE_ANON_KEY}

# SendGrid
SENDGRID_API_KEY=${SENDGRID_API_KEY}
USE_SENDGRID=${USE_SENDGRID:-true}
MAIL_FROM=${MAIL_FROM:-noreply@orkaasp.com}
MAIL_FROM_NAME=${MAIL_FROM_NAME:-ASP-ORKA}

# Storage
USE_SUPABASE_STORAGE=${USE_SUPABASE_STORAGE:-true}
SUPABASE_STORAGE_BUCKET=${SUPABASE_STORAGE_BUCKET:-affiliate-images}

# Security
SQL_PASSWORD_KEY=${SQL_PASSWORD_KEY}
SESSION_SECRET=${SESSION_SECRET}

# PHP Settings
PHP_MAX_EXECUTION_TIME=${PHP_MAX_EXECUTION_TIME:-300}
PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-256M}
EOF_MARKER

# 環境変数を展開して.envファイルに書き込む
eval "cat > /var/www/html/.env <<EOF
# Auto-generated from Render environment variables
# Generated at: $(date)

# アプリケーション設定
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}

# Supabase Database
SUPABASE_DB_HOST=${SUPABASE_DB_HOST:-NOT_SET}
SUPABASE_DB_PORT=${SUPABASE_DB_PORT:-5432}
SUPABASE_DB_NAME=${SUPABASE_DB_NAME:-postgres}
SUPABASE_DB_USER=${SUPABASE_DB_USER:-NOT_SET}
SUPABASE_DB_PASS=${SUPABASE_DB_PASS:-NOT_SET}

# Supabase API
SUPABASE_URL=${SUPABASE_URL:-NOT_SET}
SUPABASE_ANON_KEY=${SUPABASE_ANON_KEY:-NOT_SET}

# SendGrid
SENDGRID_API_KEY=${SENDGRID_API_KEY:-NOT_SET}
USE_SENDGRID=${USE_SENDGRID:-true}
MAIL_FROM=${MAIL_FROM:-noreply@orkaasp.com}
MAIL_FROM_NAME=${MAIL_FROM_NAME:-ASP-ORKA}

# Storage
USE_SUPABASE_STORAGE=${USE_SUPABASE_STORAGE:-true}
SUPABASE_STORAGE_BUCKET=${SUPABASE_STORAGE_BUCKET:-affiliate-images}

# Security
SQL_PASSWORD_KEY=${SQL_PASSWORD_KEY:-NOT_SET}
SESSION_SECRET=${SESSION_SECRET:-NOT_SET}

# PHP Settings
PHP_MAX_EXECUTION_TIME=${PHP_MAX_EXECUTION_TIME:-300}
PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-256M}
EOF
"

echo "✓ .env file created at /var/www/html/.env"
echo ""

# Renderのポート番号に合わせてApache設定を更新
if [ ! -z "${PORT:-}" ]; then
    echo "Listen $PORT" > /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf
    echo "✓ Apache configured for port: $PORT"
fi

# AllowOverride設定を追加（.htaccessを有効にするため）
echo "=== Configuring AllowOverride ==="
sed -i '/<VirtualHost/a\    <Directory /var/www/html>\n        AllowOverride All\n        Require all granted\n    </Directory>' /etc/apache2/sites-available/000-default.conf
echo "✓ AllowOverride All configured"

# ServerName設定を追加（警告を抑制）
echo "=== Configuring ServerName ==="
echo "ServerName asp-orka.onrender.com" >> /etc/apache2/apache2.conf
echo "✓ ServerName configured"

# Apache性能最適化設定
echo "=== Configuring Apache Performance ==="
cat >> /etc/apache2/apache2.conf <<'EOF'

# Performance optimizations
KeepAlive On
KeepAliveTimeout 5
MaxKeepAliveRequests 100

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json
</IfModule>
EOF
echo "✓ Apache performance settings configured"

# .envファイルの内容確認（デバッグ用）
echo ""
echo "=== .env File Contents ==="
echo "APP_ENV: ${APP_ENV:-NOT SET}"
echo "APP_DEBUG: ${APP_DEBUG:-NOT SET}"
echo ""
echo "--- Database ---"
echo "SUPABASE_DB_HOST: ${SUPABASE_DB_HOST:-NOT SET}"
echo "SUPABASE_DB_PORT: ${SUPABASE_DB_PORT:-NOT SET}"
echo "SUPABASE_DB_NAME: ${SUPABASE_DB_NAME:-NOT SET}"
echo "SUPABASE_DB_USER: ${SUPABASE_DB_USER:-NOT SET}"
if [ ! -z "${SUPABASE_DB_PASS:-}" ]; then
    echo "SUPABASE_DB_PASS: Set (${#SUPABASE_DB_PASS} chars)"
else
    echo "SUPABASE_DB_PASS: NOT SET"
fi
echo ""
echo "--- SendGrid ---"
if [ ! -z "${SENDGRID_API_KEY:-}" ]; then
    echo "SENDGRID_API_KEY: Set (${#SENDGRID_API_KEY} chars)"
else
    echo "SENDGRID_API_KEY: NOT SET"
fi
echo "USE_SENDGRID: ${USE_SENDGRID:-NOT SET}"
echo "MAIL_FROM: ${MAIL_FROM:-NOT SET}"
echo ""
echo "--- Storage ---"
echo "USE_SUPABASE_STORAGE: ${USE_SUPABASE_STORAGE:-NOT SET}"
echo "SUPABASE_STORAGE_BUCKET: ${SUPABASE_STORAGE_BUCKET:-NOT SET}"
echo ""
echo "--- Security ---"
if [ ! -z "${SQL_PASSWORD_KEY:-}" ]; then
    echo "SQL_PASSWORD_KEY: Set"
else
    echo "SQL_PASSWORD_KEY: NOT SET"
fi
if [ ! -z "${SESSION_SECRET:-}" ]; then
    echo "SESSION_SECRET: Set"
else
    echo "SESSION_SECRET: NOT SET"
fi
echo "================================"
echo ""

# 環境変数チェック
MISSING_VARS=0
if [ -z "${SUPABASE_DB_HOST:-}" ]; then
    echo "⚠️  WARNING: SUPABASE_DB_HOST is not set!"
    MISSING_VARS=1
fi
if [ -z "${SENDGRID_API_KEY:-}" ]; then
    echo "⚠️  WARNING: SENDGRID_API_KEY is not set!"
    MISSING_VARS=1
fi

if [ $MISSING_VARS -eq 1 ]; then
    echo ""
    echo "❌ ERROR: Critical environment variables are missing!"
    echo "Please configure environment variables in Render Dashboard:"
    echo "Settings → Environment → Add Key"
    echo ""
    echo "Continuing anyway... (errors may occur)"
    echo ""
fi

# ログファイルを作成
echo "=== Creating Log Files ==="
touch /var/log/apache2/php_error.log || echo "WARNING: Failed to create php_error.log"
touch /var/log/apache2/error.log || echo "WARNING: Failed to create error.log"
chmod 666 /var/log/apache2/php_error.log || echo "WARNING: Failed to chmod php_error.log"
chmod 666 /var/log/apache2/error.log || echo "WARNING: Failed to chmod error.log"
echo "✓ Log files created"
echo ""

# エラーログを標準エラー出力にリダイレクト
echo "=== Redirecting Logs to stderr ==="
if ln -sf /proc/self/fd/2 /var/log/apache2/php_error.log; then
    echo "✓ PHP error log redirected to stderr"
else
    echo "WARNING: Failed to redirect PHP error log"
fi

if ln -sf /proc/self/fd/2 /var/log/apache2/error.log; then
    echo "✓ Apache error log redirected to stderr"
else
    echo "WARNING: Failed to redirect Apache error log"
fi
echo ""

# PHPバージョンとパス確認
echo "=== PHP Information ==="
which php || echo "ERROR: PHP not found in PATH!"
php -v || echo "ERROR: Failed to get PHP version!"
echo ""

# PHPエラーログ設定を確認
echo "=== PHP Error Log Configuration ==="
php -r 'phpinfo();' | grep -i "error" || echo "WARNING: Failed to get PHP error configuration"
echo ""

# test-error.phpの存在確認
echo "=== Checking test-error.php ==="
if [ -f /var/www/html/test-error.php ]; then
    echo "✓ test-error.php exists"
    ls -lah /var/www/html/test-error.php
else
    echo "ERROR: test-error.php NOT FOUND!"
    echo "Current directory files:"
    ls -lah /var/www/html/ | head -20
fi
echo ""

# テストスクリプトを実行してデバッグ情報を出力
echo "=== Running Test Script ==="
if php /var/www/html/test-error.php; then
    echo "✓ Test script completed successfully"
else
    echo "ERROR: Test script failed with exit code: $?"
fi
echo ""

# エラーログを監視（バックグラウンド）
echo "=== Starting Log Monitoring ==="
tail -f /var/log/apache2/php_error.log /var/log/apache2/error.log 2>/dev/null &
TAIL_PID=$!
echo "✓ Log monitoring started (PID: $TAIL_PID)"
echo ""

# Apache起動
echo "=== Starting Apache ==="
echo "Starting Apache..."
exec apache2-foreground
