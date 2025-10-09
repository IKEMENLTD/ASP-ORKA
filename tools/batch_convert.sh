#!/bin/bash
# Phase 1: 高速一括変換スクリプト
# Shift-JIS → UTF-8

set -e

echo "文字コード一括変換を開始..."
echo ""

# 変換関数
do_convert() {
    local file="$1"
    local temp="$file.tmp_utf8"

    if [ ! -f "$file" ]; then
        return
    fi

    # iconvで変換
    if iconv -f SHIFT-JIS -t UTF-8 "$file" > "$temp" 2>/dev/null; then
        mv "$temp" "$file"
        echo "✓ $file"
    else
        rm -f "$temp" 2>/dev/null
    fi
}

export -f do_convert

# PHP, HTML, CSS, JS, CSVファイルを一括変換
find . -type f \( -name "*.php" -o -name "*.html" -o -name "*.css" -o -name "*.js" -o -name "*.csv" \) \
    -not -path "*/vendor/*" \
    -not -path "*/node_modules/*" \
    -not -path "*/tools/*" \
    -not -path "*/.git/*" \
    -print0 | xargs -0 -I {} bash -c 'do_convert "$@"' _ {}

echo ""
echo "変換完了"
