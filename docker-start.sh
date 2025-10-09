#!/bin/bash
set -e

echo "========================================"
echo "  ASP-ORKA Starting..."
echo "========================================"

# .envファイルを環境変数から生成
echo ""
echo "📝 Generating .env file from environment variables..."
cat > /var/www/html/.env <<EOF
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
EOF

echo "✓ .env file created at /var/www/html/.env"
echo ""

# Renderのポート番号に合わせてApache設定を更新
if [ ! -z "$PORT" ]; then
    echo "Listen $PORT" > /etc/apache2/ports.conf
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf
    echo "✓ Apache configured for port: $PORT"
fi

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
echo "SUPABASE_DB_PASS: $([ ! -z "$SUPABASE_DB_PASS" ] && echo "Set (${#SUPABASE_DB_PASS} chars)" || echo "NOT SET")"
echo ""
echo "--- SendGrid ---"
echo "SENDGRID_API_KEY: $([ ! -z "$SENDGRID_API_KEY" ] && echo "Set (${#SENDGRID_API_KEY} chars)" || echo "NOT SET")"
echo "USE_SENDGRID: ${USE_SENDGRID:-NOT SET}"
echo "MAIL_FROM: ${MAIL_FROM:-NOT SET}"
echo ""
echo "--- Storage ---"
echo "USE_SUPABASE_STORAGE: ${USE_SUPABASE_STORAGE:-NOT SET}"
echo "SUPABASE_STORAGE_BUCKET: ${SUPABASE_STORAGE_BUCKET:-NOT SET}"
echo ""
echo "--- Security ---"
echo "SQL_PASSWORD_KEY: $([ ! -z "$SQL_PASSWORD_KEY" ] && echo "Set" || echo "NOT SET")"
echo "SESSION_SECRET: $([ ! -z "$SESSION_SECRET" ] && echo "Set" || echo "NOT SET")"
echo "================================"
echo ""

# 環境変数チェック
MISSING_VARS=0
if [ -z "$SUPABASE_DB_HOST" ]; then
    echo "⚠️  WARNING: SUPABASE_DB_HOST is not set!"
    MISSING_VARS=1
fi
if [ -z "$SENDGRID_API_KEY" ]; then
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
touch /var/log/apache2/php_error.log
touch /var/log/apache2/error.log

# エラーログを標準エラー出力にリダイレクト（強制）
ln -sf /proc/self/fd/2 /var/log/apache2/php_error.log
ln -sf /proc/self/fd/2 /var/log/apache2/error.log

echo "PHP errors will be logged to stderr"
echo ""

# PHPエラーログ設定を確認
echo "=== PHP Error Log Configuration ==="
php -i | grep -i "error"
echo ""

# テストスクリプトを実行してデバッグ情報を出力
echo "=== Running Test Script ==="
php /var/www/html/test-error.php
echo ""

# エラーログを監視（バックグラウンド）
tail -f /var/log/apache2/php_error.log /var/log/apache2/error.log 2>/dev/null &

# Apache起動
echo "Starting Apache..."
exec apache2-foreground
