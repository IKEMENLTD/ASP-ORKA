#!/bin/bash
# Render Start Script

echo "========================================="
echo "  アフィリエイトシステムプロ - 起動"
echo "========================================="
echo ""

echo "環境: $APP_ENV"
echo "ポート: $PORT"
echo ""

# Apache設定をポート番号に合わせる
if [ ! -z "$PORT" ]; then
    echo "Listen $PORT" > /etc/apache2/ports.conf
    echo "<VirtualHost *:$PORT>" > /etc/apache2/sites-available/000-default.conf
    echo "    DocumentRoot /opt/render/project/src" >> /etc/apache2/sites-available/000-default.conf
    echo "    <Directory /opt/render/project/src>" >> /etc/apache2/sites-available/000-default.conf
    echo "        AllowOverride All" >> /etc/apache2/sites-available/000-default.conf
    echo "        Require all granted" >> /etc/apache2/sites-available/000-default.conf
    echo "    </Directory>" >> /etc/apache2/sites-available/000-default.conf
    echo "</VirtualHost>" >> /etc/apache2/sites-available/000-default.conf
fi

# Apacheモジュール有効化
a2enmod rewrite headers
a2enmod php

# Apache起動
echo "Apache起動中..."
apache2-foreground
