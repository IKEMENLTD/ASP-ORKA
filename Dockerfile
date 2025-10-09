# Render用Dockerfile - PHP 8.2 + Apache
FROM php:8.2-apache

# 必要なシステムパッケージをインストール
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
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
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/execution.ini

# ポート設定（Renderの環境変数$PORTを使用）
ENV PORT 10000
EXPOSE $PORT

# Apache起動スクリプトを作成
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Renderのポート番号に合わせてApache設定を更新\n\
if [ ! -z "$PORT" ]; then\n\
    echo "Listen $PORT" > /etc/apache2/ports.conf\n\
    sed -i "s/<VirtualHost \*:80>/<VirtualHost *:$PORT>/g" /etc/apache2/sites-available/000-default.conf\n\
fi\n\
\n\
# 環境変数確認\n\
echo "=== Environment Check ==="\n\
echo "SUPABASE_DB_HOST: ${SUPABASE_DB_HOST}"\n\
echo "SENDGRID_API_KEY: $([ ! -z "$SENDGRID_API_KEY" ] && echo "Set" || echo "Not set")"\n\
echo "PORT: $PORT"\n\
echo "========================"\n\
\n\
# Apache起動\n\
exec apache2-foreground\n\
' > /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
