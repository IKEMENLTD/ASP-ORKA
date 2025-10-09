#!/bin/bash
# Phase 1: 文字コード一括変換スクリプト (iconv使用)
# Shift-JIS → UTF-8

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "=========================================="
echo "  Phase 1: 文字コード変換 (iconv)"
echo "  Shift-JIS → UTF-8"
echo "=========================================="
echo ""

# 変換カウンター
TOTAL_COUNT=0
SUCCESS_COUNT=0
SKIP_COUNT=0

# 変換関数
convert_file() {
    local file="$1"
    local temp_file="${file}.tmp"

    # 既にUTF-8の場合はスキップ
    encoding=$(file -b --mime-encoding "$file")
    if [ "$encoding" = "utf-8" ] || [ "$encoding" = "us-ascii" ]; then
        ((SKIP_COUNT++))
        return 0
    fi

    # Shift-JIS → UTF-8 変換
    if iconv -f SHIFT-JIS -t UTF-8 "$file" > "$temp_file" 2>/dev/null; then
        mv "$temp_file" "$file"
        ((SUCCESS_COUNT++))
        echo "  ✓ $(basename "$file")"
    else
        # 変換失敗時は元のファイルを保持
        rm -f "$temp_file"
        echo "  ⚠ $(basename "$file") - 変換スキップ"
    fi

    ((TOTAL_COUNT++))
}

# PHPファイル変換
echo "[1/5] PHPファイルを変換中..."
find "$PROJECT_DIR" -name "*.php" -type f -not -path "*/vendor/*" -not -path "*/tools/*" | while read file; do
    convert_file "$file"
done
echo ""

# HTMLファイル変換
echo "[2/5] HTMLファイルを変換中..."
find "$PROJECT_DIR" -name "*.html" -type f -not -path "*/vendor/*" | while read file; do
    convert_file "$file"
done
echo ""

# CSSファイル変換
echo "[3/5] CSSファイルを変換中..."
find "$PROJECT_DIR" -name "*.css" -type f -not -path "*/vendor/*" | while read file; do
    convert_file "$file"
done
echo ""

# JavaScriptファイル変換
echo "[4/5] JavaScriptファイルを変換中..."
find "$PROJECT_DIR" -name "*.js" -type f -not -path "*/vendor/*" -not -path "*/node_modules/*" | while read file; do
    convert_file "$file"
done
echo ""

# CSVファイル変換
echo "[5/5] CSVファイルを変換中..."
for dir in "tdb" "lst"; do
    if [ -d "$PROJECT_DIR/$dir" ]; then
        find "$PROJECT_DIR/$dir" -name "*.csv" -type f | while read file; do
            convert_file "$file"
        done
    fi
done
echo ""

echo "=========================================="
echo "  変換完了"
echo "=========================================="
echo ""
echo "処理ファイル数: ${TOTAL_COUNT}"
echo "変換成功:       ${SUCCESS_COUNT}"
echo "スキップ:       ${SKIP_COUNT}"
echo ""
echo "次のステップ:"
echo "  ./tools/verify_encoding.sh で検証"
echo ""
