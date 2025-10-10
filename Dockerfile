# Render用Dockerfile - PHP 8.2 + Apache（最適化版）
FROM php:8.2-apache

# 必要なシステムパッケージとPHP拡張を一度にインストール（ビルド時間短縮）
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libcurl4-openssl-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pgsql \
        pdo_pgsql \
        mysqli \
        pdo_mysql \
        curl \
        gd \
        exif \
        mbstring \
    && a2enmod rewrite headers deflate expires \
    && rm -rf /var/lib/apt/lists/*

# PHP設定（1つのファイルにまとめて高速化）
RUN { \
    echo 'upload_max_filesize = 10M'; \
    echo 'post_max_size = 10M'; \
    echo 'memory_limit = 256M'; \
    echo 'max_execution_time = 300'; \
    echo 'display_errors = On'; \
    echo 'display_startup_errors = On'; \
    echo 'error_reporting = E_ALL'; \
    echo 'log_errors = On'; \
    echo 'error_log = /var/log/apache2/php_error.log'; \
    echo ''; \
    echo '; Character encoding settings (UTF-8)'; \
    echo 'default_charset = "UTF-8"'; \
    echo 'mbstring.language = Japanese'; \
    echo 'mbstring.encoding_translation = Off'; \
    echo 'mbstring.detect_order = auto'; \
    echo 'mbstring.substitute_character = none'; \
    echo ''; \
    echo '; Date and timezone settings'; \
    echo 'date.timezone = Asia/Tokyo'; \
    echo ''; \
    echo '; Session security settings'; \
    echo 'session.cookie_httponly = 1'; \
    echo 'session.cookie_secure = 0'; \
    echo 'session.cookie_samesite = Lax'; \
    echo 'session.use_strict_mode = 1'; \
    echo 'session.use_only_cookies = 1'; \
    echo 'session.cookie_lifetime = 0'; \
    echo 'session.gc_maxlifetime = 3600'; \
    echo ''; \
    echo '; OPcache settings for performance'; \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
} > /usr/local/etc/php/conf.d/custom.ini

# Apache設定を更新
RUN sed -ri -e 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 作業ディレクトリを設定
WORKDIR /var/www/html

# 起動スクリプトをコピー（これは変更が少ないので先に）
COPY docker-start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# .htaccessをコピー（これも変更が少ない）
COPY .htaccess /var/www/html/.htaccess

# 必要なディレクトリを作成
RUN mkdir -p file/image file/tmp file/page file/reminder logs tdb \
    && chmod -R 755 file/ logs/ tdb/ \
    && chown -R www-data:www-data file/ logs/ tdb/

# プロジェクトファイルをコピー（最後にして、コード変更時のみ再ビルド）
COPY . /var/www/html/

# ファイル所有権をApacheユーザーに設定
RUN chown -R www-data:www-data /var/www/html

# ポート設定
ENV PORT=10000
EXPOSE $PORT

CMD ["/usr/local/bin/start.sh"]
