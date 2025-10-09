#!/bin/bash
# Phase 1: 文字コード一括変換スクリプト
# Shift-JIS → UTF-8

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "=========================================="
echo "  Phase 1: 文字コード変換"
echo "  Shift-JIS → UTF-8"
echo "=========================================="
echo ""
echo "プロジェクトディレクトリ: $PROJECT_DIR"
echo ""

# バックアップ確認
BACKUP_EXISTS=false
for backup in "$PROJECT_DIR"/../affiliate-pro-backup-*; do
    if [ -d "$backup" ]; then
        BACKUP_EXISTS=true
        echo "✓ バックアップ検出: $(basename "$backup")"
        break
    fi
done

if [ "$BACKUP_EXISTS" = false ]; then
    echo "⚠ 警告: バックアップが見つかりません"
    echo ""
    read -p "バックアップなしで続行しますか? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy]es$ ]]; then
        echo "中止しました"
        exit 1
    fi
fi

echo ""
echo "変換を開始します..."
echo ""

# 変換カウンター
PHP_COUNT=0
HTML_COUNT=0
CSS_COUNT=0
JS_COUNT=0
CSV_COUNT=0

# PHPファイル変換
echo "[1/5] PHPファイルを変換中..."
while IFS= read -r -d '' file; do
    echo "  - $file"
    nkf -w --overwrite "$file" 2>/dev/null || true
    ((PHP_COUNT++))
done < <(find "$PROJECT_DIR" -name "*.php" -type f -not -path "*/vendor/*" -not -path "*/tools/*" -print0)
echo "  完了: ${PHP_COUNT}ファイル"
echo ""

# HTMLファイル変換
echo "[2/5] HTMLファイルを変換中..."
while IFS= read -r -d '' file; do
    echo "  - $file"
    nkf -w --overwrite "$file" 2>/dev/null || true
    ((HTML_COUNT++))
done < <(find "$PROJECT_DIR" -name "*.html" -type f -not -path "*/vendor/*" -print0)
echo "  完了: ${HTML_COUNT}ファイル"
echo ""

# CSSファイル変換
echo "[3/5] CSSファイルを変換中..."
while IFS= read -r -d '' file; do
    echo "  - $file"
    nkf -w --overwrite "$file" 2>/dev/null || true
    ((CSS_COUNT++))
done < <(find "$PROJECT_DIR" -name "*.css" -type f -not -path "*/vendor/*" -print0)
echo "  完了: ${CSS_COUNT}ファイル"
echo ""

# JavaScriptファイル変換
echo "[4/5] JavaScriptファイルを変換中..."
while IFS= read -r -d '' file; do
    echo "  - $file"
    nkf -w --overwrite "$file" 2>/dev/null || true
    ((JS_COUNT++))
done < <(find "$PROJECT_DIR" -name "*.js" -type f -not -path "*/vendor/*" -not -path "*/node_modules/*" -print0)
echo "  完了: ${JS_COUNT}ファイル"
echo ""

# CSVファイル変換
echo "[5/5] CSVファイルを変換中..."
for dir in "tdb" "lst"; do
    if [ -d "$PROJECT_DIR/$dir" ]; then
        while IFS= read -r -d '' file; do
            echo "  - $file"
            nkf -w --overwrite "$file" 2>/dev/null || true
            ((CSV_COUNT++))
        done < <(find "$PROJECT_DIR/$dir" -name "*.csv" -type f -print0)
    fi
done
echo "  完了: ${CSV_COUNT}ファイル"
echo ""

# 変換サマリー
TOTAL_COUNT=$((PHP_COUNT + HTML_COUNT + CSS_COUNT + JS_COUNT + CSV_COUNT))

echo "=========================================="
echo "  変換完了"
echo "=========================================="
echo ""
echo "変換ファイル数:"
echo "  PHP:        ${PHP_COUNT}"
echo "  HTML:       ${HTML_COUNT}"
echo "  CSS:        ${CSS_COUNT}"
echo "  JavaScript: ${JS_COUNT}"
echo "  CSV:        ${CSV_COUNT}"
echo "  ---"
echo "  合計:       ${TOTAL_COUNT}"
echo ""
echo "次のステップ:"
echo "  1. tools/verify_encoding.sh で変換結果を検証"
echo "  2. custom/conf.php の文字コード設定を確認"
echo "  3. ローカルサーバーで動作確認"
echo ""
