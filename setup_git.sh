#!/bin/bash
# GitHub リポジトリ初期化スクリプト

echo "========================================="
echo "  GitHub リポジトリ初期化"
echo "========================================="
echo ""

# Gitリポジトリ初期化（まだの場合）
if [ ! -d .git ]; then
    echo "1. Git リポジトリ初期化..."
    git init
    echo "  ✓ Git初期化完了"
    echo ""
else
    echo "1. Git リポジトリ: 既に初期化済み"
    echo ""
fi

# .gitkeepファイル作成（空ディレクトリをGitで管理）
echo "2. .gitkeep ファイル作成..."
mkdir -p file/image file/tmp file/page file/reminder logs tdb
touch file/image/.gitkeep
touch file/tmp/.gitkeep
touch file/page/.gitkeep
touch file/reminder/.gitkeep
touch logs/.gitkeep
touch tdb/.gitkeep
echo "  ✓ .gitkeep作成完了"
echo ""

# Gitステージング
echo "3. ファイルをステージング..."
git add .
echo "  ✓ ステージング完了"
echo ""

# コミット状態確認
echo "4. コミット状態確認..."
git status
echo ""

echo "========================================="
echo "  次のステップ"
echo "========================================="
echo ""
echo "1. GitHubで新しいリポジトリを作成:"
echo "   https://github.com/new"
echo ""
echo "   リポジトリ名: affiliate-system-pro"
echo "   プライベート: ✓ 推奨"
echo ""
echo "2. リモートリポジトリを追加:"
echo "   git remote add origin https://github.com/YOUR_USERNAME/affiliate-system-pro.git"
echo ""
echo "3. コミットしてプッシュ:"
echo "   git commit -m \"Initial commit: Render x Supabase migration complete\""
echo "   git branch -M main"
echo "   git push -u origin main"
echo ""
