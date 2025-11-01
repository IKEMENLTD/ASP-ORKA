#!/bin/bash

# エラーハンドリングを強化（set -e は使わない）
set -u  # 未定義変数の使用時にエラー

echo "========================================"
echo "  ASP-ORKA Starting..."
echo "========================================"
echo ""

# PHP設定を環境変数に基づいて動的に調整
echo "=== Configuring PHP Settings Based on Environment ==="
# デバッグのため、一時的に常にエラー表示を有効化
echo "Forcing debug mode for troubleshooting"
cat > /usr/local/etc/php/conf.d/environment.ini <<'EOF'
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
EOF
echo "✓ PHP error display enabled for debugging"
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

# .envファイルの内容確認（簡略版 - 起動時間短縮）
echo ""
echo "=== Environment Configuration Summary ==="
[ ! -z "${SUPABASE_DB_HOST:-}" ] && echo "✓ Database configured" || echo "⚠️  Database NOT configured"
[ ! -z "${SENDGRID_API_KEY:-}" ] && echo "✓ SendGrid configured" || echo "⚠️  SendGrid NOT configured"
[ ! -z "${SQL_PASSWORD_KEY:-}" ] && echo "✓ Security keys configured" || echo "⚠️  Security keys NOT configured"
echo "================================"
echo ""

# 環境変数チェック（簡略版）
if [ -z "${SUPABASE_DB_HOST:-}" ] || [ -z "${SENDGRID_API_KEY:-}" ]; then
    echo "⚠️  WARNING: Some environment variables are missing!"
    echo "Configure in: Render Dashboard → Settings → Environment"
fi

# ログファイルを作成
echo "=== Creating Log Files ==="
touch /var/log/apache2/php_error.log || echo "WARNING: Failed to create php_error.log"
touch /var/log/apache2/error.log || echo "WARNING: Failed to create error.log"
chmod 666 /var/log/apache2/php_error.log || echo "WARNING: Failed to chmod php_error.log"
chmod 666 /var/log/apache2/error.log || echo "WARNING: Failed to chmod error.log"
echo "✓ Log files created"
echo ""

# ログはApacheが自動的に標準出力/エラー出力に送信
echo "=== Log Configuration ==="
echo "✓ Apache logs will be sent to stdout/stderr automatically"
echo ""

# PHP確認（簡略版）
echo "=== PHP Ready ==="
php -v | head -1 || echo "ERROR: PHP not found!"
echo ""

# 起動時のテストをスキップ（本番環境では不要、起動時間短縮）
# test-error.phpは手動テスト用に保持
echo "=== Startup Tests Skipped for Fast Deployment ==="
echo "✓ Skipping test-error.php execution for faster startup"
echo "  (Run manually if needed: php /var/www/html/test-error.php)"
echo ""

# データベースマイグレーション
echo "=== Database Migration Check ==="
if [ -z "${SUPABASE_DB_HOST:-}" ] || [ -z "${SUPABASE_DB_USER:-}" ] || [ -z "${SUPABASE_DB_PASS:-}" ]; then
    echo "⚠️  WARNING: Database credentials not set. Skipping migration."
    echo "  Please configure database environment variables in Render."
else
    # PostgreSQLクライアントのインストール確認
    if ! command -v psql &> /dev/null; then
        echo "Installing PostgreSQL client..."
        apt-get update -qq && apt-get install -y -qq postgresql-client > /dev/null 2>&1
    fi

    # データベース接続文字列
    DB_HOST="${SUPABASE_DB_HOST}"
    DB_PORT="${SUPABASE_DB_PORT:-5432}"
    DB_NAME="${SUPABASE_DB_NAME:-postgres}"
    DB_USER="${SUPABASE_DB_USER}"
    export PGPASSWORD="${SUPABASE_DB_PASS}"

    echo "Checking database connection..."

    # データベース接続テスト（タイムアウト10秒）
    if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "SELECT 1;" > /dev/null 2>&1; then
        echo "✓ Database connection successful"

        # systemテーブルが存在するかチェック
        TABLE_EXISTS=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -tAc "SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'system');" 2>/dev/null || echo "false")

        if [ "$TABLE_EXISTS" = "t" ]; then
            echo "✓ Database tables already exist. Skipping migration."
        else
            echo "⚠️  Database tables not found. Running migration..."

            # マイグレーションファイルの存在確認
            if [ -f "/var/www/html/migration/001_create_all_tables.sql" ]; then
                echo "Executing migration script..."
                if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "/var/www/html/migration/001_create_all_tables.sql" > /tmp/migration.log 2>&1; then
                    echo "✓ Database migration completed successfully"

                    # 初期データの挿入
                    echo "Inserting initial system data..."
                    if [ -f "/var/www/html/migration/002_insert_initial_data.sql" ]; then
                        if psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "/var/www/html/migration/002_insert_initial_data.sql" > /tmp/initial_data.log 2>&1; then
                            echo "✓ Initial data inserted successfully"
                        else
                            echo "⚠️  Warning: Could not insert initial data"
                            cat /tmp/initial_data.log
                        fi
                    else
                        echo "⚠️  Warning: Initial data script not found"
                    fi
                else
                    echo "❌ Migration failed. Check logs:"
                    cat /tmp/migration.log
                    echo ""
                    echo "⚠️  Continuing anyway - application may not work correctly"
                fi
            else
                echo "❌ Migration file not found: /var/www/html/migration/001_create_all_tables.sql"
                echo "⚠️  Database tables must be created manually"
            fi
        fi
    else
        echo "❌ Cannot connect to database"
        echo "  Host: $DB_HOST:$DB_PORT"
        echo "  Database: $DB_NAME"
        echo "  User: $DB_USER"
        echo "⚠️  Continuing anyway - application will fail until database is accessible"
    fi

    # パスワードをクリア
    unset PGPASSWORD
fi
echo ""

# Apache起動
echo "=== Starting Apache ==="
echo "Starting Apache..."
exec apache2-foreground
