# アフィリエイトシステムプロ - 技術実装ガイド

**対象**: 開発者
**前提知識**: PHP, PostgreSQL, Git, Linux基礎

---

## 目次

1. [開発環境セットアップ](#開発環境セットアップ)
2. [文字コード変換詳細](#文字コード変換詳細)
3. [PostgreSQL互換性対応](#postgresql互換性対応)
4. [環境変数設定](#環境変数設定)
5. [デプロイ手順](#デプロイ手順)
6. [トラブルシューティング](#トラブルシューティング)

---

## 開発環境セットアップ

### 必要なソフトウェア

```bash
# PHP 8.2 インストール (Ubuntu/Debian)
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-pgsql php8.2-curl php8.2-mbstring php8.2-xml

# Composer インストール
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# PostgreSQL クライアント
sudo apt install -y postgresql-client

# Node.js (Supabase CLI用)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Supabase CLI
npm install -g supabase

# Git
sudo apt install -y git

# nkf (文字コード変換)
sudo apt install -y nkf
```

### ローカル開発環境構築

```bash
# 1. プロジェクトクローン
git clone <repository-url> affiliate-pro
cd affiliate-pro

# 2. 依存関係インストール
composer install

# 3. 環境変数設定
cp .env.example .env
nano .env

# 4. ローカルPHPサーバー起動
php -S localhost:8000

# 5. ブラウザでアクセス
# http://localhost:8000
```

### Docker環境 (オプション)

```dockerfile
# Dockerfile
FROM php:8.2-apache

# 拡張インストール
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Apache設定
RUN a2enmod rewrite
COPY . /var/www/html/

EXPOSE 80
```

```yaml
# docker-compose.yml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "8000:80"
    volumes:
      - .:/var/www/html
    environment:
      - SUPABASE_DB_HOST=${SUPABASE_DB_HOST}
      - SUPABASE_DB_PASS=${SUPABASE_DB_PASS}
```

---

## 文字コード変換詳細

### 一括変換スクリプト

**tools/convert_encoding.sh**:

```bash
#!/bin/bash
set -e

echo "=== アフィリエイトシステムプロ 文字コード変換 ==="
echo "Shift-JIS → UTF-8"
echo ""

# バックアップ確認
if [ ! -d "../affiliate-pro-backup" ]; then
    echo "エラー: バックアップが見つかりません"
    echo "先に以下を実行してください:"
    echo "cp -r . ../affiliate-pro-backup"
    exit 1
fi

# PHPファイル変換
echo "PHPファイルを変換中..."
find . -name "*.php" -type f -not -path "./vendor/*" | while read file; do
    echo "  - $file"
    nkf -w --overwrite "$file"
done

# HTMLファイル変換
echo "HTMLファイルを変換中..."
find . -name "*.html" -type f | while read file; do
    echo "  - $file"
    nkf -w --overwrite "$file"
done

# CSSファイル変換
echo "CSSファイルを変換中..."
find . -name "*.css" -type f | while read file; do
    echo "  - $file"
    nkf -w --overwrite "$file"
done

# JSファイル変換
echo "JavaScriptファイルを変換中..."
find . -name "*.js" -type f | while read file; do
    echo "  - $file"
    nkf -w --overwrite "$file"
done

# CSVファイル変換
echo "CSVファイルを変換中..."
find ./tdb -name "*.csv" -type f | while read file; do
    echo "  - $file"
    nkf -w --overwrite "$file"
done

find ./lst -name "*.csv" -type f | while read file; do
    echo "  - $file"
    nkf -w --overwrite "$file"
done

echo ""
echo "✓ 変換完了"
echo ""
echo "次のステップ:"
echo "1. custom/conf.php の文字コード設定を確認"
echo "2. git diff で変更内容を確認"
echo "3. ローカルサーバーで動作確認"
```

**実行**:

```bash
chmod +x tools/convert_encoding.sh
./tools/convert_encoding.sh
```

### 変更確認スクリプト

**tools/verify_encoding.sh**:

```bash
#!/bin/bash

echo "=== 文字コード検証 ==="

# UTF-8でないファイルを検出
echo "Shift-JISファイルの検出..."
find . -name "*.php" -type f -not -path "./vendor/*" | while read file; do
    encoding=$(file -b --mime-encoding "$file")
    if [ "$encoding" != "utf-8" ] && [ "$encoding" != "us-ascii" ]; then
        echo "  ⚠ $file: $encoding"
    fi
done

echo ""
echo "✓ 検証完了"
```

### conf.php の変更

**custom/conf.php**:

```php
<?php
// 変更前
// $SYSTEM_CHARACODE = "SJIS";
// $OUTPUT_CHARACODE = $SYSTEM_CHARACODE;
// $LONG_OUTPUT_CHARACODE = "Shift_JIS";

// 変更後
$SYSTEM_CHARACODE = "UTF-8";
$OUTPUT_CHARACODE = $SYSTEM_CHARACODE;
$LONG_OUTPUT_CHARACODE = "UTF-8";
```

### ccProc.php の変更

**include/ccProc.php** (loadFileメソッド付近):

```php
// 変更前
function loadFile($path) {
    $html = file_get_contents($path);
    return mb_convert_encoding($html, 'UTF-8', 'SJIS'); // ← この行を削除または修正
}

// 変更後
function loadFile($path) {
    $html = file_get_contents($path);
    // ファイルは既にUTF-8なので変換不要
    return $html;
}
```

---

## PostgreSQL互換性対応

### MySQL → PostgreSQL 主要な違い

#### 1. AUTO_INCREMENT → SERIAL

**MySQL**:
```sql
CREATE TABLE nuser (
  id INT AUTO_INCREMENT PRIMARY KEY
);
```

**PostgreSQL**:
```sql
CREATE TABLE nuser (
  id SERIAL PRIMARY KEY
);
```

**既存システムでの対応**:
- ID生成は `SystemUtil::getNewId()` で実装済み
- 変更不要

#### 2. 文字列連結

**MySQL**: `CONCAT()`
**PostgreSQL**: `||` または `CONCAT()`

**対応不要**: SQLDatabase.php で抽象化済み

#### 3. LIMIT構文

**MySQL**: `LIMIT 10`
**PostgreSQL**: `LIMIT 10` (同じ)

**対応不要**: 互換性あり

#### 4. DATE関数

**MySQL**: `NOW()`, `CURDATE()`
**PostgreSQL**: `NOW()`, `CURRENT_DATE`

**既存システム**: Unixタイムスタンプ使用のため対応不要

### SQLDatabase.php の確認

**include/base/SQLDatabase.php** のPostgreSQL実装を確認:

```php
// PostgreSQL特有の実装箇所
class PostgreSQLDatabase extends SQLDatabaseBase {

    // LIMIT OFFSET
    function limitOffset($table, $start, $num) {
        // PostgreSQL: LIMIT {$num} OFFSET {$start}
        // 実装済み
    }

    // エスケープ処理
    function escape($str) {
        return pg_escape_string($this->conn, $str);
    }

    // トランザクション
    function begin() {
        return pg_query($this->conn, "BEGIN");
    }

    function commit() {
        return pg_query($this->conn, "COMMIT");
    }

    function rollback() {
        return pg_query($this->conn, "ROLLBACK");
    }
}
```

### カスタムSQL文の洗い出し

```bash
# 生SQL使用箇所を検索
grep -r "pg_query\|mysql_query\|mysqli_query" --include="*.php"

# 結果を確認し、PostgreSQL互換性を検証
```

---

## 環境変数設定

### .env.example

```bash
# .env.example
# アプリケーション設定
APP_ENV=production
APP_DEBUG=false

# Supabase PostgreSQL
SUPABASE_URL=https://xxxxxxxxxxxxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

SUPABASE_DB_HOST=db.xxxxxxxxxxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=your-database-password

# SendGrid
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
USE_SENDGRID=true

# Supabase Storage
USE_SUPABASE_STORAGE=true

# セキュリティ
SQL_PASSWORD_KEY=generate-random-32-chars
SESSION_SECRET=generate-random-64-chars

# システム設定
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=アフィリエイトシステム
```

### 環境変数読み込み

**custom/load_env.php** (新規作成):

```php
<?php
/**
 * .env ファイルから環境変数を読み込む
 */
function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // コメント行をスキップ
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // KEY=VALUE 形式をパース
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // 既存の環境変数を上書きしない
            if (!getenv($key)) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// 環境変数読み込み
loadEnv();
?>
```

**custom/conf.php** の先頭に追加:

```php
<?php
// 環境変数読み込み
require_once __DIR__ . '/load_env.php';
```

---

## デプロイ手順

### 1. Supabase プロジェクト作成

```bash
# ブラウザで https://supabase.com にアクセス
# 1. Sign Up / Log In
# 2. "New Project" クリック
# 3. プロジェクト情報入力
#    - Name: affiliate-pro
#    - Database Password: (強力なパスワード)
#    - Region: Northeast Asia (Tokyo)
# 4. "Create new project" クリック
# 5. プロジェクト作成完了を待つ (2-3分)

# 接続情報取得
# Settings > Database > Connection string
# postgres://postgres:[YOUR-PASSWORD]@db.xxxxxxxxxxxxx.supabase.co:5432/postgres
```

### 2. データベーススキーマ作成

```bash
# Supabase SQL Editor でスキーマ実行

# 1. migration/001_create_tables.sql の内容をコピー
# 2. SQL Editor に貼り付け
# 3. "Run" クリック
# 4. 成功確認
```

### 3. Supabase Storage セットアップ

```bash
# Supabase Dashboard で
# 1. Storage > "New bucket"
# 2. Name: affiliate-images
# 3. Public: ON
# 4. "Create bucket"

# 同様に以下も作成
# - affiliate-files (Public: OFF)
```

### 4. GitHub リポジトリ作成

```bash
cd アフィリエイトシステムプロ＿システム本体003CSS未タッチ

# Git初期化
git init
git add .
git commit -m "Initial commit"

# GitHub リポジトリ作成
# https://github.com/new

# リモート追加
git remote add origin https://github.com/yourusername/affiliate-pro.git
git branch -M main
git push -u origin main
```

### 5. Render Web Service 作成

```bash
# ブラウザで https://render.com にアクセス
# 1. Sign Up / Log In (GitHubアカウント連携推奨)
# 2. Dashboard > "New" > "Web Service"
# 3. GitHub リポジトリ選択: yourusername/affiliate-pro
# 4. 設定入力:
#    - Name: affiliate-pro
#    - Region: Singapore
#    - Branch: main
#    - Runtime: PHP
#    - Build Command: composer install
#    - Start Command: php -S 0.0.0.0:$PORT -t .
# 5. "Advanced" > "Add Environment Variable"
#    (下記参照)
# 6. "Create Web Service"
```

**環境変数設定** (Render):

| Key | Value |
|-----|-------|
| SUPABASE_URL | https://xxxxx.supabase.co |
| SUPABASE_ANON_KEY | eyJhbGciOi... |
| SUPABASE_DB_HOST | db.xxxxx.supabase.co |
| SUPABASE_DB_PORT | 5432 |
| SUPABASE_DB_NAME | postgres |
| SUPABASE_DB_USER | postgres |
| SUPABASE_DB_PASS | your-password |
| SENDGRID_API_KEY | SG.xxxxx |
| USE_SENDGRID | true |
| USE_SUPABASE_STORAGE | true |
| SQL_PASSWORD_KEY | (Generate) |

### 6. デプロイ確認

```bash
# Render Dashboard で
# 1. "Logs" タブを開く
# 2. デプロイログを確認
# 3. "Live" になるまで待機 (3-5分)
# 4. URLをクリックして動作確認
#    https://affiliate-pro.onrender.com
```

### 7. カスタムドメイン設定 (オプション)

```bash
# Render Dashboard で
# 1. Settings > "Custom Domain"
# 2. "Add Custom Domain"
# 3. ドメイン入力: affiliate.yourdomain.com
# 4. DNS設定指示に従う
#    - CNAME: affiliate.yourdomain.com → affiliate-pro.onrender.com
# 5. SSL証明書自動発行を待つ
```

---

## トラブルシューティング

### 問題1: 文字化けが発生する

**症状**: 日本語が「譁ｰ蟄怜喧縺�」のように表示される

**原因**: 文字コード変換が不完全

**解決策**:
```bash
# 1. 該当ファイルの文字コードを確認
file -b --mime-encoding path/to/file.php

# 2. Shift-JISの場合は再変換
nkf -w --overwrite path/to/file.php

# 3. UTF-8 BOMの場合はBOM削除
nkf -w8 --overwrite path/to/file.php
```

### 問題2: データベース接続エラー

**症状**: `SQLSTATE[08006] could not connect to server`

**原因**: 接続情報が誤っている

**解決策**:
```bash
# 1. 環境変数確認
echo $SUPABASE_DB_HOST
echo $SUPABASE_DB_PASS

# 2. psql で直接接続テスト
psql "postgresql://postgres:PASSWORD@db.xxxxx.supabase.co:5432/postgres"

# 3. Supabase ダッシュボードで接続情報再確認
```

### 問題3: ファイルアップロードエラー

**症状**: `Failed to upload to Supabase Storage`

**原因**: Storage権限設定が誤っている

**解決策**:
```sql
-- Supabase SQL Editor で実行
-- affiliate-images バケットを公開設定
INSERT INTO storage.buckets (id, name, public)
VALUES ('affiliate-images', 'affiliate-images', true)
ON CONFLICT (id) DO UPDATE SET public = true;

-- RLS ポリシー設定
CREATE POLICY "Public Access"
ON storage.objects FOR SELECT
USING (bucket_id = 'affiliate-images');

CREATE POLICY "Authenticated Upload"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'affiliate-images');
```

### 問題4: メール送信エラー

**症状**: `SendGrid API returned 401`

**原因**: APIキーが無効

**解決策**:
```bash
# 1. SendGrid ダッシュボードで新しいAPIキー作成
# https://app.sendgrid.com/settings/api_keys

# 2. 環境変数を更新
# Render Dashboard > Settings > Environment

# 3. 再デプロイ
git commit --allow-empty -m "Trigger redeploy"
git push
```

### 問題5: セッションが保持されない

**症状**: ログイン後すぐにログアウトされる

**原因**: セッション保存先の問題

**解決策**:

**custom/session_config.php** (新規作成):
```php
<?php
// Render の一時ディレクトリにセッション保存
$sessionPath = getenv('RENDER') ? '/tmp/sessions' : 'file/tmp/sessions';

if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}

ini_set('session.save_path', $sessionPath);
ini_set('session.gc_maxlifetime', 3600);
session_start();
?>
```

**custom/conf.php** に追加:
```php
require_once 'custom/session_config.php';
```

### 問題6: Render でファイルが消える

**症状**: アップロードしたファイルが再デプロイ後に消える

**原因**: Render はエフェメラルファイルシステム

**解決策**:
- **必須**: Supabase Storage を使用
- `USE_SUPABASE_STORAGE=true` を設定
- ローカルストレージは使用しない

---

## チェックリスト

### デプロイ前

- [ ] バックアップ作成済み
- [ ] 文字コード変換完了
- [ ] ローカル環境で動作確認
- [ ] データベーススキーマ作成済み
- [ ] Supabase Storage設定済み
- [ ] SendGrid API取得済み
- [ ] 環境変数準備完了

### デプロイ後

- [ ] Webサイトにアクセス可能
- [ ] ログイン動作確認
- [ ] ユーザー登録テスト
- [ ] クリック追跡テスト
- [ ] メール送信テスト
- [ ] ファイルアップロードテスト
- [ ] 管理画面動作確認

### 本番移行前

- [ ] 全機能テスト完了
- [ ] パフォーマンステスト実施
- [ ] セキュリティチェック完了
- [ ] ドキュメント整備完了
- [ ] ロールバック手順確認済み
- [ ] 監視設定完了

---

**次のステップ**: [MIGRATION_PLAN.md](./MIGRATION_PLAN.md) を参照して移行を開始
