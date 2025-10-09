# Phase 2: データベース移行ガイド

**所要時間**: 1-2時間
**難易度**: 中
**前提条件**: Phase 1完了 (文字コードUTF-8変換済み)

---

## 📋 Phase 2 概要

CSV + MySQLベースのシステムをSupabase PostgreSQLに移行します。

### 実施内容

- ✅ **準備完了**: PostgreSQL DDL生成 (21テーブル、233カラム)
- ✅ **準備完了**: データ移行スクリプト作成
- ✅ **準備完了**: データベース設定ファイル更新
- 🔄 **実施待ち**: Supabaseプロジェクト作成
- 🔄 **実施待ち**: スキーマ実行
- 🔄 **実施待ち**: データ移行実行

---

## 🎯 Step 1: Supabaseプロジェクト作成

### 1.1 プロジェクト作成

```bash
# 1. Supabaseにアクセス
https://supabase.com

# 2. New Project をクリック
# 3. 以下を入力:
#    - Project name: affiliate-system-pro
#    - Database password: 強力なパスワード (必ず保存!)
#    - Region: Northeast Asia (Tokyo)
#    - Pricing plan: Free (開発用) または Pro (本番用)

# 4. Create new project をクリック
#    → 約2分でプロジェクト作成完了
```

### 1.2 接続情報取得

```bash
# 1. 左メニュー「Project Settings」→「Database」を開く

# 2. 以下の情報をメモ:
Host:     db.xxxxxxxxxxxxxxxx.supabase.co
Port:     5432
Database: postgres
User:     postgres
Password: (設定したパスワード)

# 3. Connection string (URI形式) もコピー:
postgresql://postgres:[YOUR-PASSWORD]@db.xxxxx.supabase.co:5432/postgres
```

### 1.3 環境変数設定

`.env` ファイルを作成:

```bash
cd /mnt/c/Users/ooxmi/Downloads/アフィリエイトシステムプロ＿システム本体003CSS未タッチ

# .envファイル作成
cat > .env << 'EOF'
# Application
APP_ENV=development
APP_DEBUG=true

# Supabase PostgreSQL
SUPABASE_URL=https://xxxxxxxxxxxxxxxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.xxxxxxxx...
SUPABASE_DB_HOST=db.xxxxxxxxxxxxxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=YOUR_DATABASE_PASSWORD

# SendGrid (Phase 4で設定)
SENDGRID_API_KEY=
USE_SENDGRID=false

# Security
SQL_PASSWORD_KEY=derhymqadbrheng
SESSION_SECRET=
EOF

# ⚠️ 実際の値に置き換えてください！
nano .env
```

---

## 🗄️ Step 2: PostgreSQLスキーマ作成

### 2.1 Supabase SQL Editorで実行

```bash
# 1. Supabaseダッシュボードで「SQL Editor」を開く
# 2. 「New query」をクリック
# 3. 以下のファイル内容をコピー&ペースト:

migration/001_create_all_tables.sql

# 4. 「Run」をクリック
# 5. 成功メッセージ確認: "Success. No rows returned"
```

### 2.2 テーブル作成確認

```bash
# 1. 左メニュー「Table Editor」を開く
# 2. 以下21テーブルが表示されることを確認:

✓ admin              # 管理者
✓ nuser              # ユーザー (3段階親子関係)
✓ adwares            # 広告
✓ access             # アクセス記録
✓ pay                # 報酬 (通常)
✓ click_pay          # 報酬 (クリック)
✓ continue_pay       # 報酬 (継続)
✓ tier               # ティア報酬
✓ sales              # 販売商品
✓ log_pay            # 報酬ログ
✓ returnss           # 返品
✓ category           # カテゴリ
✓ area               # 地域
✓ prefectures        # 都道府県
✓ zenginkyo          # 全銀協銀行マスタ
✓ blacklist          # ブラックリスト
✓ invitation         # 招待
✓ multimail          # メール配信
✓ system             # システム設定
✓ template           # テンプレート
✓ page               # ページ
```

### 2.3 スキーマ構造確認

```sql
-- nuserテーブルの構造確認
SELECT
    column_name,
    data_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'nuser'
ORDER BY ordinal_position;

-- 外部キー制約確認
SELECT
    tc.table_name,
    kcu.column_name,
    ccu.table_name AS foreign_table_name,
    ccu.column_name AS foreign_column_name
FROM information_schema.table_constraints AS tc
JOIN information_schema.key_column_usage AS kcu
    ON tc.constraint_name = kcu.constraint_name
JOIN information_schema.constraint_column_usage AS ccu
    ON ccu.constraint_name = tc.constraint_name
WHERE tc.constraint_type = 'FOREIGN KEY'
ORDER BY tc.table_name, kcu.column_name;
```

---

## 📦 Step 3: データ移行実行

### 3.1 Python依存関係インストール

```bash
# psycopg2インストール
pip3 install psycopg2-binary

# インストール確認
python3 -c "import psycopg2; print('psycopg2 OK')"
```

### 3.2 既存データ確認

```bash
# CSVデータ件数確認
echo "=== CSV データ件数 ==="
for file in tdb/*.csv; do
    count=$(wc -l < "$file" | tr -d ' ')
    echo "$(basename $file): $count 行"
done

# LST定義確認
echo ""
echo "=== LST カラム定義 ==="
for file in lst/*.csv; do
    count=$(wc -l < "$file" | tr -d ' ')
    echo "$(basename $file): $count カラム"
done
```

### 3.3 データ移行実行

```bash
# 環境変数エクスポート (.envから読み込み)
export $(cat .env | grep -v '^#' | xargs)

# データ移行実行
python3 tools/migrate_data.py

# 実行結果例:
# ========================================
#   CSV → PostgreSQL データ移行
# ========================================
#
# 接続先: db.xxxxx.supabase.co:5432
#
# ✓ 接続成功
#
# [1/21] admin...
#   ✓ 5件
# [2/21] nuser...
#   ✓ 127件
# [3/21] adwares...
#   ✓ 43件
# ...
#
# ✓ コミット完了
#
# ========================================
#   移行完了
# ========================================
# 成功: 1,234件
# エラー: 0件
```

### 3.4 データ移行確認

```sql
-- Supabase SQL Editorで実行

-- 各テーブルのレコード数確認
SELECT 'admin' as table_name, COUNT(*) as count FROM admin
UNION ALL
SELECT 'nuser', COUNT(*) FROM nuser
UNION ALL
SELECT 'adwares', COUNT(*) FROM adwares
UNION ALL
SELECT 'access', COUNT(*) FROM access
UNION ALL
SELECT 'pay', COUNT(*) FROM pay
ORDER BY table_name;

-- nuser の親子関係確認
SELECT
    n1.id,
    n1.name,
    n1.parent,
    n2.name as parent_name,
    n1.grandparent,
    n3.name as grandparent_name
FROM nuser n1
LEFT JOIN nuser n2 ON n1.parent = n2.id
LEFT JOIN nuser n3 ON n1.grandparent = n3.id
WHERE n1.parent IS NOT NULL
LIMIT 10;
```

---

## 🪣 Step 4: Storageバケット作成

### 4.1 画像用バケット

```bash
# 1. Supabaseダッシュボードで「Storage」を開く
# 2. 「New bucket」をクリック
# 3. 以下を入力:
Bucket name: affiliate-images
Public bucket: ✓ チェック (公開アクセス許可)
File size limit: 5 MB
Allowed MIME types: image/jpeg, image/png, image/gif

# 4. 「Create bucket」をクリック
```

### 4.2 ファイル用バケット

```bash
# 1. 「New bucket」をクリック
# 2. 以下を入力:
Bucket name: affiliate-files
Public bucket: ✓ チェック
File size limit: 10 MB
Allowed MIME types: application/pdf, text/csv, application/zip

# 3. 「Create bucket」をクリック
```

### 4.3 バケットポリシー設定

```sql
-- Supabase SQL Editorで実行

-- 画像バケット: 全ユーザー読み取り可、認証ユーザー書き込み可
CREATE POLICY "Public read access"
ON storage.objects FOR SELECT
USING (bucket_id = 'affiliate-images');

CREATE POLICY "Authenticated upload"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'affiliate-images' AND auth.role() = 'authenticated');

-- ファイルバケット: 同様のポリシー
CREATE POLICY "Public read access"
ON storage.objects FOR SELECT
USING (bucket_id = 'affiliate-files');

CREATE POLICY "Authenticated upload"
ON storage.objects FOR INSERT
WITH CHECK (bucket_id = 'affiliate-files' AND auth.role() = 'authenticated');
```

---

## ✅ Step 5: 動作確認

### 5.1 PHP接続テスト

`test_db_connection.php` を作成:

```php
<?php
require_once 'custom/load_env.php';
require_once 'custom/extends/sqlConf.php';
require_once 'include/extends/PostgreSQLDatabase.php';

try {
    // テーブル定義読み込み
    $lstFile = 'lst/admin.csv';
    $columns = [];
    $types = [];
    $sizes = [];

    $lines = file($lstFile, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        if (count($cols) >= 2 && $cols[0]) {
            $columns[] = $cols[0];
            $types[] = $cols[1];
            $sizes[] = isset($cols[2]) ? $cols[2] : '';
        }
    }

    // DB接続
    $db = new SQLDatabase($DB_NAME, 'admin', $columns, $types, $sizes, []);

    echo "✓ PostgreSQL接続成功\n";
    echo "  Host: " . $SQL_SERVER . "\n";
    echo "  Database: " . $DB_NAME . "\n";
    echo "  Encoding: " . pg_client_encoding($db->connect) . "\n\n";

    // データ取得テスト
    $table = new Table('admin');
    $table->limit = 5;
    $result = $db->select($table);

    echo "✓ データ取得成功 (" . count($result) . "件)\n";
    foreach ($result as $row) {
        echo "  - ID: " . $row['id'] . ", Name: " . $row['name'] . "\n";
    }

} catch (Exception $e) {
    echo "✗ エラー: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

実行:

```bash
php test_db_connection.php

# 期待される出力:
# ✓ PostgreSQL接続成功
#   Host: db.xxxxx.supabase.co
#   Database: postgres
#   Encoding: UTF8
#
# ✓ データ取得成功 (5件)
#   - ID: 00000001, Name: admin
#   - ID: 00000002, Name: operator
#   ...
```

### 5.2 ストレージ接続テスト

```php
<?php
require_once 'custom/load_env.php';

$supabaseUrl = getenv('SUPABASE_URL');
$supabaseKey = getenv('SUPABASE_ANON_KEY');

// Storage API テスト
$ch = curl_init($supabaseUrl . '/storage/v1/bucket/affiliate-images');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Authorization: Bearer ' . $supabaseKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✓ Storage接続成功\n";
    echo "  Bucket: affiliate-images\n";
} else {
    echo "✗ Storage接続失敗 (HTTP $httpCode)\n";
    echo "  Response: $response\n";
}
?>
```

---

## 🔧 トラブルシューティング

### エラー1: psycopg2インストールエラー

```bash
# エラー: pg_config executable not found
sudo apt-get update
sudo apt-get install -y libpq-dev python3-dev

# 再インストール
pip3 install psycopg2-binary
```

### エラー2: 接続タイムアウト

```bash
# .env ファイル確認
cat .env | grep SUPABASE_DB_HOST

# Supabaseプロジェクトの一時停止チェック
# → Supabaseダッシュボードで「Resume」をクリック
```

### エラー3: 外部キー制約エラー

```bash
# 移行順序の問題
# tools/migrate_data.py の MIGRATION_ORDER を確認

# 依存関係:
# 1. マスタデータ (area, prefectures, zenginkyo, category, sales)
# 2. ユーザーデータ (admin, nuser) ← 自己参照あり
# 3. 広告データ (adwares)
# 4. トランザクション (access, pay, click_pay, continue_pay, tier)
```

### エラー4: 文字化け

```bash
# PostgreSQL クライアントエンコーディング確認
psql "postgresql://postgres:PASSWORD@db.xxxxx.supabase.co:5432/postgres" -c "SHOW client_encoding;"

# UTF8 でない場合:
# include/extends/PostgreSQLDatabase.php を確認
# → pg_set_client_encoding('UTF8'); が設定されているか
```

---

## 📊 Phase 2 完了チェックリスト

- [ ] **Supabaseプロジェクト作成完了**
  - [ ] プロジェクト名: affiliate-system-pro
  - [ ] リージョン: Northeast Asia (Tokyo)
  - [ ] データベースパスワード保存済み

- [ ] **PostgreSQLスキーマ作成完了**
  - [ ] 21テーブル作成確認
  - [ ] 外部キー制約確認
  - [ ] インデックス作成確認

- [ ] **データ移行完了**
  - [ ] CSV → PostgreSQL 移行成功
  - [ ] レコード数一致確認
  - [ ] 親子関係整合性確認

- [ ] **Storageバケット作成完了**
  - [ ] affiliate-images バケット作成
  - [ ] affiliate-files バケット作成
  - [ ] バケットポリシー設定

- [ ] **動作確認完了**
  - [ ] PHP接続テスト成功
  - [ ] データ取得テスト成功
  - [ ] Storage接続テスト成功

---

## 📝 変更されたファイル

### 設定ファイル

1. **custom/extends/sqlConf.php**
   - MySQL → PostgreSQL に変更
   - 環境変数から接続情報取得
   - `$SQL_MASTER = 'PostgreSQLDatabase'`

2. **include/extends/PostgreSQLDatabase.php**
   - Line 43: `pg_set_client_encoding('UTF8')`
   - Shift-JIS → UTF-8 に変更

3. **custom/load_env.php** (新規作成)
   - .env ファイル読み込み機能
   - 環境変数設定

### 移行ツール

1. **tools/lst_to_sql.py** (新規作成)
   - LST定義 → PostgreSQL DDL 変換
   - 21テーブル、233カラム生成

2. **tools/migrate_data.py** (新規作成)
   - CSV → PostgreSQL データ移行
   - 外部キー制約を考慮した順序制御

3. **migration/001_create_all_tables.sql** (自動生成)
   - 全テーブル定義統合ファイル
   - Supabaseで直接実行可能

---

## 🎯 次のステップ (Phase 3)

Phase 2完了後、Phase 3に進みます:

### Phase 3: ファイルストレージ移行

```bash
# 1. file/image/* → Supabase Storage (affiliate-images) に移行
# 2. file/tmp/* → Supabase Storage (affiliate-files) に移行
# 3. PHPコードでのファイルパス書き換え
```

Phase 3の詳細は **PHASE3_STORAGE_MIGRATION.md** を参照してください。

---

## 📞 サポート

問題が発生した場合:

1. **ログ確認**: `logs/debug.log` を確認
2. **環境変数確認**: `.env` ファイルの内容確認
3. **Supabaseログ**: Supabaseダッシュボード → Logs を確認

---

**Phase 2 完了**: データベース移行完了後、このファイルを `PHASE2_COMPLETE.md` にリネームしてください。
