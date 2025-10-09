#!/bin/bash
# Phase 1: 文字コード検証スクリプト
# UTF-8以外のファイルを検出

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "=========================================="
echo "  文字コード検証"
echo "=========================================="
echo ""

NON_UTF8_COUNT=0
CHECKED_COUNT=0

echo "PHPファイルをチェック中..."
while IFS= read -r -d '' file; do
    ((CHECKED_COUNT++))
    encoding=$(file -b --mime-encoding "$file")

    # UTF-8またはASCII以外を検出
    if [ "$encoding" != "utf-8" ] && [ "$encoding" != "us-ascii" ]; then
        echo "  ⚠ $(basename "$file"): $encoding"
        ((NON_UTF8_COUNT++))
    fi
done < <(find "$PROJECT_DIR" -name "*.php" -type f -not -path "*/vendor/*" -not -path "*/tools/*" -print0)

echo ""
echo "HTMLファイルをチェック中..."
while IFS= read -r -d '' file; do
    ((CHECKED_COUNT++))
    encoding=$(file -b --mime-encoding "$file")

    if [ "$encoding" != "utf-8" ] && [ "$encoding" != "us-ascii" ]; then
        echo "  ⚠ $(basename "$file"): $encoding"
        ((NON_UTF8_COUNT++))
    fi
done < <(find "$PROJECT_DIR" -name "*.html" -type f -not -path "*/vendor/*" -print0)

echo ""
echo "CSVファイルをチェック中..."
for dir in "tdb" "lst"; do
    if [ -d "$PROJECT_DIR/$dir" ]; then
        while IFS= read -r -d '' file; do
            ((CHECKED_COUNT++))
            encoding=$(file -b --mime-encoding "$file")

            if [ "$encoding" != "utf-8" ] && [ "$encoding" != "us-ascii" ]; then
                echo "  ⚠ $(basename "$file"): $encoding"
                ((NON_UTF8_COUNT++))
            fi
        done < <(find "$PROJECT_DIR/$dir" -name "*.csv" -type f -print0)
    fi
done

echo ""
echo "=========================================="
echo "  検証結果"
echo "=========================================="
echo ""
echo "チェックしたファイル: ${CHECKED_COUNT}"
echo "UTF-8以外のファイル: ${NON_UTF8_COUNT}"
echo ""

if [ $NON_UTF8_COUNT -eq 0 ]; then
    echo "✓ すべてのファイルがUTF-8に変換されています"
    echo ""
    echo "次のステップ:"
    echo "  1. custom/conf.php を編集して文字コード設定を変更"
    echo "  2. include/ccProc.php の文字コード変換処理を削除"
    echo "  3. git diff で変更内容を確認"
else
    echo "⚠ UTF-8以外のファイルが見つかりました"
    echo "  再度 tools/convert_encoding.sh を実行してください"
fi

echo ""
