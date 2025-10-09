# Phase 2 準備完了レポート

**作成日時**: 2025-10-09
**ステータス**: ✅ Phase 2 準備完了 (実行待ち)
**次のステップ**: Supabaseプロジェクト作成 → スキーマ実行 → データ移行

---

## 📊 実施内容サマリー

### ✅ 完了項目

1. **PostgreSQL DDL生成ツール作成** ✓
   - `tools/lst_to_sql.py` (205行)
   - LST定義 → PostgreSQL DDL 自動変換
   - 21テーブル、233カラムのスキーマ生成完了

2. **PostgreSQLスキーマファイル生成** ✓
   - `migration/schema/*.sql` (21ファイル)
   - `migration/001_create_all_tables.sql` (統合版)
   - 外部キー制約、インデックス、DEFAULT値すべて含む

3. **データ移行ツール作成** ✓
   - `tools/migrate_data.py` (257行)
   - CSV → PostgreSQL 一括移行
   - 外部キー制約を考慮した順序制御
   - エラーハンドリング、ロールバック対応

4. **データベース設定更新** ✓
   - `custom/load_env.php` 作成 (環境変数読み込み)
   - `custom/extends/sqlConf.php` 更新 (PostgreSQL対応)
   - `include/extends/PostgreSQLDatabase.php` 更新 (UTF-8対応)

5. **ドキュメント作成** ✓
   - `PHASE2_DATABASE_MIGRATION.md` (完全な実行ガイド)
   - トラブルシューティング、確認手順含む

---

## 📁 生成されたファイル一覧

### 移行ツール (3ファイル)

```
tools/
├── lst_to_sql.py          # LST → PostgreSQL DDL 変換ツール
├── lst_to_sql.php         # (同上のPHP版、参考用)
└── migrate_data.py        # CSV → PostgreSQL データ移行ツール
```

### PostgreSQLスキーマ (22ファイル)

```
migration/
├── 001_create_all_tables.sql    # 統合スキーマファイル (Supabaseで実行)
└── schema/
    ├── admin.sql                # 管理者
    ├── nuser.sql                # ユーザー (3段階親子関係)
    ├── adwares.sql              # 広告
    ├── access.sql               # アクセス記録
    ├── pay.sql                  # 報酬 (通常)
    ├── click_pay.sql            # 報酬 (クリック)
    ├── continue_pay.sql         # 報酬 (継続)
    ├── tier.sql                 # ティア報酬
    ├── sales.sql                # 販売商品
    ├── log_pay.sql              # 報酬ログ
    ├── returnss.sql             # 返品
    ├── category.sql             # カテゴリ
    ├── area.sql                 # 地域
    ├── prefectures.sql          # 都道府県
    ├── zenginkyo.sql            # 全銀協銀行マスタ
    ├── blacklist.sql            # ブラックリスト
    ├── invitation.sql           # 招待
    ├── multimail.sql            # メール配信
    ├── system.sql               # システム設定
    ├── template.sql             # テンプレート
    └── page.sql                 # ページ
```

### 設定ファイル (更新済み)

```
custom/
├── load_env.php                 # 環境変数読み込み (新規作成)
└── extends/
    └── sqlConf.php              # PostgreSQL接続設定 (更新)

include/extends/
└── PostgreSQLDatabase.php       # UTF-8エンコーディング (更新)
```

---

## 🔧 主要な変更内容

### 1. custom/load_env.php (新規作成)

```php
<?php
/**
 * .env ファイルから環境変数を読み込む
 */
function loadEnv($path = null) {
    if ($path === null) {
        $path = dirname(__DIR__) . '/.env';
    }

    if (!file_exists($path)) {
        // 本番環境では環境変数が既に設定されている想定
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // コメント行をスキップ
        if (strpos($line, '#') === 0) {
            continue;
        }

        // KEY=VALUE 形式をパース
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // クォートを除去
            if (preg_match('/^(["'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            // 既存の環境変数を上書きしない (Renderの環境変数を優先)
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

**重要ポイント**:
- ローカル開発では `.env` ファイルから読み込み
- 本番環境 (Render) では既存の環境変数を優先
- 既存の環境変数を上書きしない設計

---

### 2. custom/extends/sqlConf.php (更新)

**変更前**:
```php
$SQL_MASTER = 'MySQLDatabase';
$SQL_SERVER = 'localhost';
$DB_NAME = 'affiliate';
$SQL_ID = 'root';
$SQL_PASS = '';
```

**変更後**:
```php
// 環境変数読み込み
require_once __DIR__ . '/../load_env.php';

// PostgreSQL使用
$SQL_MASTER = 'PostgreSQLDatabase';
$SQL_SERVER = getenv('SUPABASE_DB_HOST') ?: 'localhost';
$SQL_PORT = getenv('SUPABASE_DB_PORT') ?: '5432';
$DB_NAME = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$SQL_ID = getenv('SUPABASE_DB_USER') ?: 'postgres';
$SQL_PASS = getenv('SUPABASE_DB_PASS') ?: '';
$CONFIG_SQL_PASSWORD_KEY = getenv('SQL_PASSWORD_KEY') ?: 'derhymqadbrheng';
```

**重要ポイント**:
- MySQL → PostgreSQL に変更
- 環境変数から接続情報取得
- デフォルト値設定済み (開発環境で動作)

---

### 3. include/extends/PostgreSQLDatabase.php (更新)

**変更箇所** (Line 42-44):

**変更前**:
```php
pg_set_client_encoding('SJIS');
$this->sql_char_code = pg_client_encoding();
```

**変更後**:
```php
// UTF-8 encoding for PostgreSQL
pg_set_client_encoding('UTF8');
$this->sql_char_code = pg_client_encoding();
```

**重要ポイント**:
- Shift-JIS → UTF-8 に変更
- Phase 1の文字コード変換と整合

---

## 📊 生成されたスキーマ統計

### テーブル数: 21
### カラム総数: 233

### 主要テーブル構造

#### nuser (ユーザー) - 37カラム

```sql
CREATE TABLE nuser (
  id CHAR(8),
  name VARCHAR(32) NOT NULL,
  mail VARCHAR(128) NOT NULL UNIQUE,
  parent CHAR(8),                    -- 親 (tier1)
  grandparent CHAR(8),               -- 祖父母 (tier2)
  greatgrandparent CHAR(8),          -- 曽祖父母 (tier3)
  pass VARCHAR(128) NOT NULL,
  activate INTEGER DEFAULT 0,
  pay INTEGER DEFAULT 0,
  tier INTEGER DEFAULT 0,
  -- ... (他27カラム)
  PRIMARY KEY (id),
  FOREIGN KEY (parent) REFERENCES nuser(id) ON DELETE SET NULL,
  FOREIGN KEY (grandparent) REFERENCES nuser(id) ON DELETE SET NULL,
  FOREIGN KEY (greatgrandparent) REFERENCES nuser(id) ON DELETE SET NULL
);

CREATE INDEX idx_nuser_mail ON nuser(mail);
CREATE INDEX idx_nuser_parent ON nuser(parent);
```

**特徴**:
- 自己参照外部キー (3段階の親子関係)
- メールアドレスUNIQUE制約
- 報酬カウンタ (activate, pay, tier) DEFAULT 0

#### pay (報酬) - 15カラム

```sql
CREATE TABLE pay (
  id CHAR(8),
  owner CHAR(8) NOT NULL,            -- ユーザーID
  access_id CHAR(8),                 -- アクセスID
  price INTEGER DEFAULT 0,
  state INTEGER DEFAULT 0,
  regist BIGINT,
  -- ... (他9カラム)
  PRIMARY KEY (id),
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE,
  FOREIGN KEY (access_id) REFERENCES access(id) ON DELETE SET NULL
);

CREATE INDEX idx_pay_owner ON pay(owner);
CREATE INDEX idx_pay_state ON pay(state);
```

**特徴**:
- ユーザーへの外部キー (CASCADE削除)
- アクセス記録への外部キー (NULL設定)
- 状態管理用インデックス

---

## 🗄️ データ移行の仕組み

### tools/migrate_data.py の動作

```python
# 1. 外部キー制約を考慮した移行順序
MIGRATION_ORDER = [
    # マスタデータ（依存なし）
    'area', 'prefectures', 'zenginkyo', 'category', 'sales',
    'blacklist', 'template', 'page', 'system',

    # ユーザーデータ（自己参照あり）
    'admin', 'nuser',

    # 広告データ
    'adwares',

    # トランザクションデータ
    'access', 'pay', 'click_pay', 'continue_pay',
    'tier', 'log_pay', 'returnss',

    # その他
    'invitation', 'multimail'
]

# 2. 型変換処理
def convert_value(value, column_type):
    if column_type in ['INTEGER', 'BIGINT']:
        return int(value) if value else None

    if column_type == 'DOUBLE PRECISION':
        return float(value) if value else None

    if column_type == 'BOOLEAN':
        return value in ['1', 'true', 'TRUE', 't']

    return value

# 3. 移行処理
for table_name in MIGRATION_ORDER:
    # LST定義からカラム名取得
    columns = get_columns_from_lst(lst_file)

    # PostgreSQLからカラム型取得
    column_types = get_column_types(cursor, table_name)

    # CSVデータ読み込み
    rows = csv.reader(csv_file)

    # 型変換してINSERT
    for row in rows:
        values = [convert_value(row[i], column_types[col])
                  for i, col in enumerate(columns)]
        cursor.execute(INSERT_QUERY, values)

    conn.commit()
```

**重要ポイント**:
- 外部キー制約を考慮した順序で移行
- LST定義とPostgreSQLスキーマの型情報を使用
- エラー時はロールバック

---

## ✅ Phase 2 準備完了チェックリスト

### 開発環境準備

- [x] **Python環境**
  - [x] Python 3.x インストール済み
  - [x] tools/lst_to_sql.py 作成済み
  - [x] tools/migrate_data.py 作成済み

- [x] **PostgreSQLスキーマ生成**
  - [x] LST定義 → PostgreSQL DDL 変換完了
  - [x] 21テーブル、233カラムのスキーマ生成
  - [x] 外部キー制約、インデックス定義完了

- [x] **PHP設定更新**
  - [x] custom/load_env.php 作成
  - [x] custom/extends/sqlConf.php 更新 (PostgreSQL対応)
  - [x] include/extends/PostgreSQLDatabase.php 更新 (UTF-8対応)

- [x] **ドキュメント整備**
  - [x] PHASE2_DATABASE_MIGRATION.md 作成
  - [x] .env.example 確認済み
  - [x] PHASE2_PREP_COMPLETE.md 作成 (本ファイル)

### 次のステップ (実行待ち)

- [ ] **Supabaseプロジェクト作成**
  - [ ] https://supabase.com でプロジェクト作成
  - [ ] プロジェクト名: affiliate-system-pro
  - [ ] リージョン: Northeast Asia (Tokyo)
  - [ ] データベースパスワード設定・保存

- [ ] **環境変数設定**
  - [ ] .env ファイル作成
  - [ ] Supabase接続情報記入
  - [ ] セキュリティキー生成

- [ ] **スキーマ実行**
  - [ ] Supabase SQL Editorで migration/001_create_all_tables.sql 実行
  - [ ] 21テーブル作成確認

- [ ] **データ移行実行**
  - [ ] psycopg2-binary インストール
  - [ ] python3 tools/migrate_data.py 実行
  - [ ] データ整合性確認

- [ ] **Storageバケット作成**
  - [ ] affiliate-images バケット作成
  - [ ] affiliate-files バケット作成
  - [ ] バケットポリシー設定

---

## 📖 実行手順

Phase 2の実行は `PHASE2_DATABASE_MIGRATION.md` を参照してください。

### クイックスタート

```bash
# 1. Supabaseプロジェクト作成
https://supabase.com

# 2. 環境変数設定
cp .env.example .env
nano .env  # Supabase接続情報を記入

# 3. スキーマ実行
# Supabase SQL Editor で migration/001_create_all_tables.sql を実行

# 4. データ移行
pip3 install psycopg2-binary
export $(cat .env | grep -v '^#' | xargs)
python3 tools/migrate_data.py

# 5. 動作確認
php test_db_connection.php
```

---

## 🎯 Phase 3 プレビュー

Phase 2完了後、Phase 3でファイルストレージ移行を実施します:

### Phase 3: ファイルストレージ移行

1. **画像ファイル移行**
   - `file/image/*` → Supabase Storage (affiliate-images)
   - PHPコードでのパス書き換え

2. **一時ファイル移行**
   - `file/tmp/*` → Supabase Storage (affiliate-files)
   - アップロード処理の更新

3. **Storage APIラッパー作成**
   - Supabase Storage PHP クライアント
   - 既存コードとの互換性維持

---

## 📊 全体進捗

```
Phase 0: 準備                    ✅ 完了
Phase 1: 文字コード変換           ✅ 完了
Phase 2: データベース移行         🔄 準備完了 (実行待ち)
Phase 3: ファイルストレージ移行    ⏳ 未着手
Phase 4: SendGrid統合            ⏳ 未着手
Phase 5: Renderデプロイ          ⏳ 未着手
Phase 6: 最終テスト              ⏳ 未着手
Phase 7: 本番移行                ⏳ 未着手
```

**全体進捗**: 25% (7フェーズ中2フェーズ完了、1フェーズ準備完了)

---

## 🔍 技術詳細

### LST → PostgreSQL 型マッピング

| LST型 | PostgreSQL型 | 備考 |
|-------|-------------|------|
| char | CHAR(n) | 固定長文字列 |
| varchar | VARCHAR(n) | 可変長文字列 |
| string | TEXT | 無制限テキスト |
| int | INTEGER | 32bit整数 |
| double | DOUBLE PRECISION | 浮動小数点 |
| boolean | BOOLEAN | 真偽値 |
| timestamp | BIGINT | Unixタイムスタンプ |
| image | VARCHAR(255) | 画像パス/URL |
| file | VARCHAR(255) | ファイルパス/URL |

### 外部キー制約の検出ロジック

```python
# tools/lst_to_sql.py の外部キー検出

# 1. nuser テーブルの自己参照
if col_name in ['parent', 'grandparent', 'greatgrandparent'] and table_name == 'nuser':
    foreign_keys.append(f"FOREIGN KEY ({col_name}) REFERENCES nuser(id) ON DELETE SET NULL")

# 2. access テーブルへの参照
if col_name == 'access_id' and table_name in ['pay', 'click_pay']:
    foreign_keys.append(f"FOREIGN KEY ({col_name}) REFERENCES access(id) ON DELETE SET NULL")

# 3. nuser テーブルへの参照 (所有者)
if col_name == 'owner' and table_name in ['pay', 'click_pay', 'continue_pay', 'returnss', 'invitation']:
    foreign_keys.append(f"FOREIGN KEY ({col_name}) REFERENCES nuser(id) ON DELETE CASCADE")
```

### インデックス作成の基準

```python
# 頻繁に検索されるカラムにインデックス作成

# 1. 識別子・キー
if col_name in ['cookie', 'owner', 'adwares', 'parent', 'mail']:
    indexes.append(f"CREATE INDEX idx_{table_name}_{col_name} ON {table_name}({col_name});")

# 2. 状態管理
if col_name == 'state' and table_name in ['pay', 'click_pay', 'continue_pay']:
    indexes.append(f"CREATE INDEX idx_{table_name}_{col_name} ON {table_name}({col_name});")
```

---

**次の作業**: `PHASE2_DATABASE_MIGRATION.md` の手順に従ってPhase 2を実行してください。

**完了予定**: 1-2時間 (Supabaseプロジェクト作成 → スキーマ実行 → データ移行)
