# Render用Dockerfile - PHP 8.2 + Apache
FROM php:8.2-apache

# 必要なシステムパッケージをインストール
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能をインストール
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pgsql \
        pdo_pgsql \
        mysqli \
        pdo_mysql \
        curl \
        gd \
        exif \
        mbstring

# Apacheモジュールを有効化
RUN a2enmod rewrite headers

# 作業ディレクトリを設定
WORKDIR /var/www/html

# プロジェクトファイルをコピー
COPY . /var/www/html/

# 必要なディレクトリを作成
RUN mkdir -p file/image file/tmp file/page file/reminder logs tdb \
    && chmod -R 755 file/ logs/ tdb/

# Apache設定をコピー
COPY .htaccess /var/www/html/.htaccess

# DocumentRootを設定
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Apacheの設定を更新
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP設定
RUN echo "upload_max_filesize = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 10M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/execution.ini \
    && echo "display_errors = On" >> /usr/local/etc/php/conf.d/error.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/error.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/error.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/error.ini \
    && echo "error_log = /var/log/apache2/php_error.log" >> /usr/local/etc/php/conf.d/error.ini

# ポート設定（Renderの環境変数$PORTを使用）
ENV PORT 10000
EXPOSE $PORT

# Apache起動スクリプトを作成
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "========================================"\n\
echo "  ASP-ORKA Starting..."\n\
echo "========================================"\n\
\n\
# .envファイルを環境変数から生成\n\
echo ""\n\
echo "📝 Generating .env file from environment variables..."\n\
cat > /var/www/html/.env <<EOF\n\
# Auto-generated from Render environment variables\n\
# Generated at: $(date)\n\
\n\
# アプリケーション設定\n\
APP_ENV=${APP_ENV:-production}\n\
APP_DEBUG=${APP_DEBUG:-false}\n\
\n\
# Supabase Database\n\
SUPABASE_DB_HOST=${SUPABASE_DB_HOST}\n\
SUPABASE_DB_PORT=${SUPABASE_DB_PORT:-5432}\n\
SUPABASE_DB_NAME=${SUPABASE_DB_NAME:-postgres}\n\
SUPABASE_DB_USER=${SUPABASE_DB_USER}\n\
SUPABASE_DB_PASS=${SUPABASE_DB_PASS}\n\
\n\
# Supabase API\n\
SUPABASE_URL=${SUPABASE_URL}\n\
SUPABASE_ANON_KEY=${SUPABASE_ANON_KEY}\n\
\n\
# SendGrid\n\
SENDGRID_API_KEY=${SENDGRID_API_KEY}\n\
USE_SENDGRID=${USE_SENDGRID:-true}\n\
MAIL_FROM=${MAIL_FROM:-noreply@orkaasp.com}\n\
MAIL_FROM_NAME=${MAIL_FROM_NAME:-ASP-ORKA}\n\
\n\
# Storage\n\
USE_SUPABASE_STORAGE=${USE_SUPABASE_STORAGE:-true}\n\
SUPABASE_STORAGE_BUCKET=${SUPABASE_STORAGE_BUCKET:-affiliate-images}\n\
\n\
# Security\n\
SQL_PASSWORD_KEY=${SQL_PASSWORD_KEY}\n\
SESSION_SECRET=${SESSION_SECRET}\n\
\n\
# PHP Settings\n\
PHP_MAX_EXECUTION_TIME=${PHP_MAX_EXECUTION_TIME:-300}\n\
PHP_MEMORY_LIMIT=${PHP_MEMORY_LIMIT:-256M}\n\
EOF\n\
\n\
echo "✓ .env file created at /var/www/html/.env"\n\
echo ""\n\
\n\
# Renderのポート番号に合わせてApache設定を更新\n\
if [ ! -z "$PORT" ]; then\n\
    echo "Listen $PORT" > /etc/apache2/ports.conf\n\
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf\n\
    echo "✓ Apache configured for port: $PORT"\n\
fi\n\
\n\
# .envファイルの内容確認（デバッグ用）\n\
echo ""\n\
echo "=== .env File Contents ==="\n\
echo "APP_ENV: ${APP_ENV:-NOT SET}"\n\
echo "APP_DEBUG: ${APP_DEBUG:-NOT SET}"\n\
echo ""\n\
echo "--- Database ---"\n\
echo "SUPABASE_DB_HOST: ${SUPABASE_DB_HOST:-NOT SET}"\n\
echo "SUPABASE_DB_PORT: ${SUPABASE_DB_PORT:-NOT SET}"\n\
echo "SUPABASE_DB_NAME: ${SUPABASE_DB_NAME:-NOT SET}"\n\
echo "SUPABASE_DB_USER: ${SUPABASE_DB_USER:-NOT SET}"\n\
echo "SUPABASE_DB_PASS: $([ ! -z "$SUPABASE_DB_PASS" ] && echo "Set (${#SUPABASE_DB_PASS} chars)" || echo "NOT SET")"\n\
echo ""\n\
echo "--- SendGrid ---"\n\
echo "SENDGRID_API_KEY: $([ ! -z "$SENDGRID_API_KEY" ] && echo "Set (${#SENDGRID_API_KEY} chars)" || echo "NOT SET")"\n\
echo "USE_SENDGRID: ${USE_SENDGRID:-NOT SET}"\n\
echo "MAIL_FROM: ${MAIL_FROM:-NOT SET}"\n\
echo ""\n\
echo "--- Storage ---"\n\
echo "USE_SUPABASE_STORAGE: ${USE_SUPABASE_STORAGE:-NOT SET}"\n\
echo "SUPABASE_STORAGE_BUCKET: ${SUPABASE_STORAGE_BUCKET:-NOT SET}"\n\
echo ""\n\
echo "--- Security ---"\n\
echo "SQL_PASSWORD_KEY: $([ ! -z "$SQL_PASSWORD_KEY" ] && echo "Set" || echo "NOT SET")"\n\
echo "SESSION_SECRET: $([ ! -z "$SESSION_SECRET" ] && echo "Set" || echo "NOT SET")"\n\
echo "================================"\n\
echo ""\n\
\n\
# 環境変数チェック\n\
MISSING_VARS=0\n\
if [ -z "$SUPABASE_DB_HOST" ]; then\n\
    echo "⚠️  WARNING: SUPABASE_DB_HOST is not set!"\n\
    MISSING_VARS=1\n\
fi\n\
if [ -z "$SENDGRID_API_KEY" ]; then\n\
    echo "⚠️  WARNING: SENDGRID_API_KEY is not set!"\n\
    MISSING_VARS=1\n\
fi\n\
\n\
if [ $MISSING_VARS -eq 1 ]; then\n\
    echo ""\n\
    echo "❌ ERROR: Critical environment variables are missing!"\n\
    echo "Please configure environment variables in Render Dashboard:"\n\
    echo "Settings → Environment → Add Key"\n\
    echo ""\n\
    echo "Continuing anyway... (errors may occur)"\n\
    echo ""\n\
fi\n\
\n\
# PHPエラーログの場所を表示\n\
echo "PHP Error Log: /var/log/apache2/php_error.log"\n\
echo "Apache Error Log: /var/log/apache2/error.log"\n\
echo ""\n\
\n\
# Apache起動\n\
echo "Starting Apache..."\n\
exec apache2-foreground\n\
' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
