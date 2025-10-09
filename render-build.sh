#!/bin/bash
# Render Build Script

set -e

echo "========================================="
echo "  アフィリエイトシステムプロ - ビルド"
echo "========================================="
echo ""

echo "1. PHP拡張確認..."
php -m | grep -E "pgsql|mbstring|curl|gd|exif|json" || true
echo ""

echo "2. ディレクトリ作成..."
mkdir -p file/image file/tmp file/page file/reminder
mkdir -p logs
echo "  ✓ ディレクトリ作成完了"
echo ""

echo "3. パーミッション設定..."
chmod -R 755 file/ logs/
echo "  ✓ パーミッション設定完了"
echo ""

echo "4. 環境変数確認..."
if [ -z "$SUPABASE_DB_HOST" ]; then
    echo "  ⚠️  警告: SUPABASE_DB_HOST が設定されていません"
else
    echo "  ✓ SUPABASE_DB_HOST: $SUPABASE_DB_HOST"
fi

if [ -z "$SENDGRID_API_KEY" ]; then
    echo "  ⚠️  警告: SENDGRID_API_KEY が設定されていません"
else
    echo "  ✓ SENDGRID_API_KEY: 設定済み"
fi
echo ""

echo "========================================="
echo "  ビルド完了"
echo "========================================="
