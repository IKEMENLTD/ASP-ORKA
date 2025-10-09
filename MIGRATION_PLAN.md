# アフィリエイトシステムプロ - Render × Supabase 移行計画書

**作成日**: 2025-01-XX
**対象システム**: アフィリエイトシステムプロ v1.6.25
**移行先**: Render (Web Service) + Supabase (PostgreSQL + Storage + Auth)
**移行方式**: 段階的移行（既存コード活用型）

---

## 目次

1. [移行方針](#移行方針)
2. [移行アーキテクチャ](#移行アーキテクチャ)
3. [移行フェーズ](#移行フェーズ)
4. [詳細作業計画](#詳細作業計画)
5. [技術スタック](#技術スタック)
6. [リスクと対策](#リスクと対策)
7. [テスト計画](#テスト計画)
8. [ロールバック計画](#ロールバック計画)

---

## 移行方針

### 基本方針

**既存PHPコードを最大限活用し、段階的にクラウドネイティブ化する**

### 理由

1. ✅ **リスク最小化**: 動作実績のあるビジネスロジックを保持
2. ✅ **開発期間短縮**: フルリライト不要
3. ✅ **段階的検証**: フェーズごとに動作確認可能
4. ✅ **既存機能維持**: 3段階ティア報酬等の複雑なロジックをそのまま利用

### 移行スコープ

#### 対象
- ✅ すべてのPHPファイル (138ファイル)
- ✅ すべてのデータベーステーブル (21テーブル)
- ✅ すべてのテンプレートファイル
- ✅ 画像・ファイルアップロード機能
- ✅ メール送信機能
- ✅ セッション管理

#### 対象外（将来的に改善）
- ❌ ガラケー対応コード（削除予定）
- ❌ レガシーなモバイル対応

---

## 移行アーキテクチャ

### 移行前 (現状)

```
┌─────────────────────────────────────┐
│   レンタルサーバー (LAMP環境)        │
│                                     │
│   ┌─────────────────────────────┐   │
│   │  Apache + PHP 5.x/7.x       │   │
│   └─────────────────────────────┘   │
│              ↓                      │
│   ┌─────────────────────────────┐   │
│   │  MySQL (localhost)          │   │
│   │  - 21テーブル                │   │
│   └─────────────────────────────┘   │
│              ↓                      │
│   ┌─────────────────────────────┐   │
│   │  ローカルファイルシステム    │   │
│   │  - file/image/              │   │
│   │  - tdb/ (CSVバックアップ)   │   │
│   └─────────────────────────────┘   │
└─────────────────────────────────────┘
```

### 移行後 (目標)

```
┌─────────────────────────────────────────────────────────┐
│                    Render                               │
│                                                         │
│  ┌───────────────────────────────────────────────────┐  │
│  │  Web Service (PHP 8.2)                           │  │
│  │  - 138 PHP Files                                  │  │
│  │  - Custom Framework (ccProc, GUIManager)         │  │
│  │  - Environment Variables                         │  │
│  └───────────────────────────────────────────────────┘  │
│                       ↓                                 │
└───────────────────────┼─────────────────────────────────┘
                        ↓
        ┌───────────────┴────────────────┐
        ↓                                ↓
┌───────────────────┐         ┌──────────────────────┐
│   Supabase        │         │   SendGrid/Resend    │
│                   │         │                      │
│  ┌─────────────┐  │         │  - メール送信        │
│  │ PostgreSQL  │  │         └──────────────────────┘
│  │ - 21 Tables │  │
│  └─────────────┘  │
│                   │
│  ┌─────────────┐  │
│  │  Storage    │  │
│  │  - images/  │  │
│  │  - files/   │  │
│  └─────────────┘  │
│                   │
│  ┌─────────────┐  │
│  │   Auth      │  │
│  │  (Optional) │  │
│  └─────────────┘  │
└───────────────────┘
```

---

## 移行フェーズ

### Phase 0: 準備 (1-2日)
- [ ] 既存コードのバックアップ
- [ ] Supabase プロジェクト作成
- [ ] Render アカウント準備
- [ ] 開発環境構築 (ローカルPHP環境)

### Phase 1: 文字コード変換 (2-3日)
- [ ] Shift-JIS → UTF-8 一括変換
- [ ] データベース文字コード設定変更
- [ ] テンプレートファイルの変換
- [ ] 動作確認

### Phase 2: データベース移行 (3-5日)
- [ ] Supabase PostgreSQL セットアップ
- [ ] スキーマ定義変換 (LST → SQL DDL)
- [ ] データマイグレーション (CSV → PostgreSQL)
- [ ] sqlConf.php 設定変更
- [ ] 接続テスト

### Phase 3: ファイルストレージ移行 (2-3日)
- [ ] Supabase Storage バケット作成
- [ ] FileBase.php 改修
- [ ] 既存ファイルアップロード
- [ ] 画像表示テスト

### Phase 4: 外部サービス統合 (2-3日)
- [ ] SendGrid/Resend API統合
- [ ] Mail.php 改修
- [ ] メール送信テスト

### Phase 5: Render デプロイ (2-3日)
- [ ] render.yaml 作成
- [ ] 環境変数設定
- [ ] 初回デプロイ
- [ ] 動作確認

### Phase 6: 本番移行準備 (3-5日)
- [ ] 全機能統合テスト
- [ ] パフォーマンステスト
- [ ] セキュリティチェック
- [ ] ドキュメント整備

### Phase 7: 本番移行 (1日)
- [ ] DNS切り替え
- [ ] データ最終同期
- [ ] 監視設定
- [ ] 稼働確認

**総所要期間**: 約 15-25日

---

## 詳細作業計画

### Phase 1: 文字コード変換

#### 1.1 PHPファイルの一括変換

**目的**: Shift-JIS → UTF-8 変換

**作業内容**:

```bash
# 1. バックアップ作成
cp -r アフィリエイトシステムプロ＿システム本体003CSS未タッチ アフィリエイトシステムプロ_BACKUP

# 2. 文字コード一括変換スクリプト
find . -name "*.php" -type f -exec nkf -w --overwrite {} \;
find . -name "*.html" -type f -exec nkf -w --overwrite {} \;
find . -name "*.css" -type f -exec nkf -w --overwrite {} \;
find . -name "*.js" -type f -exec nkf -w --overwrite {} \;

# 3. CSV ファイルも変換
find ./tdb -name "*.csv" -type f -exec nkf -w --overwrite {} \;
find ./lst -name "*.csv" -type f -exec nkf -w --overwrite {} \;
```

**変更箇所**:

1. **custom/conf.php**
```php
// 変更前
$SYSTEM_CHARACODE = "SJIS";
$OUTPUT_CHARACODE = $SYSTEM_CHARACODE;
$LONG_OUTPUT_CHARACODE = "Shift_JIS";

// 変更後
$SYSTEM_CHARACODE = "UTF-8";
$OUTPUT_CHARACODE = $SYSTEM_CHARACODE;
$LONG_OUTPUT_CHARACODE = "UTF-8";
```

2. **include/ccProc.php** (テンプレートエンジン)
```php
// loadFile() メソッド内の変換処理を削除または修正
// 変更前: mb_convert_encoding($html, 'UTF-8', 'SJIS')
// 変更後: そのまま使用 (既にUTF-8のため)
```

**テスト項目**:
- [ ] 日本語文字の表示確認
- [ ] フォーム入力・保存の確認
- [ ] メール件名・本文の確認
- [ ] CSVインポート・エクスポートの確認

---

### Phase 2: データベース移行

#### 2.1 Supabase プロジェクト作成

```bash
# 1. Supabase CLI インストール
npm install -g supabase

# 2. ログイン
supabase login

# 3. プロジェクト作成
supabase projects create affiliate-pro --region ap-northeast-1
```

#### 2.2 スキーマ定義生成

**LST定義から SQL DDL への変換ツール作成**:

```php
// tools/lst_to_sql.php (新規作成)
<?php
/**
 * LST定義ファイルをPostgreSQL DDLに変換
 */

$tables = [
    'admin', 'nuser', 'adwares', 'access', 'pay',
    'click_pay', 'continue_pay', 'tier', 'sales',
    'log_pay', 'returnss', 'category', 'area',
    'prefectures', 'zenginkyo', 'blacklist',
    'invitation', 'multimail', 'system', 'template', 'page'
];

foreach ($tables as $table) {
    $lstFile = "lst/{$table}.csv";
    $sqlFile = "migration/schema/{$table}.sql";

    convertLstToSql($lstFile, $sqlFile, $table);
}

function convertLstToSql($lstFile, $sqlFile, $tableName) {
    $lines = file($lstFile);
    $sql = "CREATE TABLE {$tableName} (\n";

    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        if (count($cols) < 2) continue;

        $name = $cols[0];
        $type = mapType($cols[1], $cols[2] ?? null);
        $constraint = mapConstraint($cols[3] ?? '', $cols[4] ?? '');

        $sql .= "  {$name} {$type} {$constraint},\n";
    }

    $sql .= "  PRIMARY KEY (id)\n";
    $sql .= ");\n\n";

    file_put_contents($sqlFile, $sql);
}

function mapType($phpType, $size) {
    switch ($phpType) {
        case 'char':
            return $size ? "VARCHAR({$size})" : "VARCHAR(255)";
        case 'varchar':
            return $size ? "VARCHAR({$size})" : "VARCHAR(255)";
        case 'string':
        case 'text':
            return "TEXT";
        case 'int':
            return "INTEGER";
        case 'double':
            return "DOUBLE PRECISION";
        case 'boolean':
            return "BOOLEAN";
        case 'timestamp':
            return "BIGINT"; // Unixタイムスタンプを保存
        case 'image':
        case 'file':
            return "VARCHAR(255)"; // ファイルパスを保存
        default:
            return "TEXT";
    }
}

function mapConstraint($inputRule, $updateRule) {
    if (strpos($inputRule, 'Null') !== false || strpos($updateRule, 'Null') !== false) {
        return "NOT NULL";
    }
    if (strpos($inputRule, 'Const') !== false) {
        return ""; // 変更不可は制約なし
    }
    return "";
}
?>
```

**実行**:
```bash
php tools/lst_to_sql.php
# → migration/schema/*.sql が生成される
```

#### 2.3 マイグレーションファイル作成

```sql
-- migration/001_create_tables.sql

-- nuser テーブル
CREATE TABLE nuser (
  id VARCHAR(8) PRIMARY KEY,
  name VARCHAR(32) NOT NULL,
  zip1 CHAR(3),
  zip2 CHAR(4),
  adds CHAR(4),
  add_sub VARCHAR(255),
  tel VARCHAR(15),
  fax VARCHAR(15),
  url VARCHAR(255),
  mail VARCHAR(128) NOT NULL UNIQUE,
  bank_code VARCHAR(4),
  bank VARCHAR(128),
  branch_code VARCHAR(3),
  branch VARCHAR(128),
  bank_type VARCHAR(2),
  number VARCHAR(32),
  bank_name VARCHAR(32),
  parent VARCHAR(8),
  grandparent VARCHAR(8),
  greatgrandparent VARCHAR(8),
  pass VARCHAR(128) NOT NULL,
  terminal VARCHAR(255),
  activate INTEGER DEFAULT 1,
  pay INTEGER DEFAULT 0,
  tier INTEGER DEFAULT 0,
  rank CHAR(4),
  personal_rate DOUBLE PRECISION,
  magni DOUBLE PRECISION,
  mail_reception VARCHAR(32),
  is_mobile BOOLEAN DEFAULT FALSE,
  limits BIGINT,
  regist BIGINT NOT NULL,
  logout BIGINT,
  FOREIGN KEY (parent) REFERENCES nuser(id) ON DELETE SET NULL,
  FOREIGN KEY (grandparent) REFERENCES nuser(id) ON DELETE SET NULL,
  FOREIGN KEY (greatgrandparent) REFERENCES nuser(id) ON DELETE SET NULL
);

CREATE INDEX idx_nuser_parent ON nuser(parent);
CREATE INDEX idx_nuser_mail ON nuser(mail);

-- adwares テーブル
CREATE TABLE adwares (
  id VARCHAR(8) PRIMARY KEY,
  comment TEXT,
  ad_text VARCHAR(128),
  category VARCHAR(8),
  banner VARCHAR(255),
  banner2 VARCHAR(255),
  banner3 VARCHAR(255),
  banner_m VARCHAR(255),
  banner_m2 VARCHAR(255),
  banner_m3 VARCHAR(255),
  url VARCHAR(255),
  url_m VARCHAR(255),
  url_over VARCHAR(255),
  url_users BOOLEAN,
  name VARCHAR(128),
  money VARCHAR(10),
  ad_type VARCHAR(10),
  click_money VARCHAR(10),
  continue_money VARCHAR(10),
  continue_type VARCHAR(10),
  limits INTEGER,
  limit_type CHAR(1),
  money_count INTEGER DEFAULT 0,
  pay_count INTEGER DEFAULT 0,
  click_money_count INTEGER DEFAULT 0,
  continue_money_count INTEGER DEFAULT 0,
  span INTEGER,
  span_type CHAR(1),
  use_cookie_interval BOOLEAN,
  pay_span INTEGER,
  pay_span_type CHAR(1),
  auto CHAR(1),
  click_auto CHAR(1),
  continue_auto CHAR(1),
  check_type VARCHAR(10),
  open BOOLEAN,
  regist BIGINT NOT NULL
);

-- access テーブル
CREATE TABLE access (
  id VARCHAR(32) PRIMARY KEY,
  ipaddress VARCHAR(16),
  cookie VARCHAR(32),
  adwares_type VARCHAR(32),
  adwares VARCHAR(8),
  owner VARCHAR(8),
  useragent TEXT,
  referer TEXT,
  state INTEGER,
  utn VARCHAR(128),
  regist BIGINT NOT NULL
);

CREATE INDEX idx_access_cookie ON access(cookie);
CREATE INDEX idx_access_owner ON access(owner);
CREATE INDEX idx_access_adwares ON access(adwares);

-- pay テーブル
CREATE TABLE pay (
  id VARCHAR(32) PRIMARY KEY,
  access_id VARCHAR(32),
  ipaddress VARCHAR(16),
  cookie VARCHAR(32),
  owner VARCHAR(8),
  adwares_type VARCHAR(32),
  adwares VARCHAR(8),
  cost INTEGER,
  tier1_rate INTEGER,
  tier2_rate INTEGER,
  tier3_rate INTEGER,
  sales INTEGER,
  froms TEXT,
  froms_sub TEXT,
  state INTEGER,
  is_notice BOOLEAN DEFAULT FALSE,
  utn VARCHAR(128),
  useragent TEXT,
  continue_uid VARCHAR(128),
  regist BIGINT NOT NULL,
  FOREIGN KEY (access_id) REFERENCES access(id),
  FOREIGN KEY (owner) REFERENCES nuser(id)
);

CREATE INDEX idx_pay_owner ON pay(owner);
CREATE INDEX idx_pay_state ON pay(state);

-- 他のテーブルも同様に定義...
```

#### 2.4 データマイグレーション

**CSVからPostgreSQLへのデータ移行スクリプト**:

```php
// tools/migrate_data.php
<?php
require_once 'custom/conf.php';
require_once 'custom/extends/sqlConf.php';

// Supabase PostgreSQL接続情報
$SUPABASE_HOST = getenv('SUPABASE_DB_HOST');
$SUPABASE_PORT = getenv('SUPABASE_DB_PORT');
$SUPABASE_DB = getenv('SUPABASE_DB_NAME');
$SUPABASE_USER = getenv('SUPABASE_DB_USER');
$SUPABASE_PASS = getenv('SUPABASE_DB_PASS');

$conn = pg_connect("host={$SUPABASE_HOST} port={$SUPABASE_PORT} dbname={$SUPABASE_DB} user={$SUPABASE_USER} password={$SUPABASE_PASS}");

if (!$conn) {
    die("PostgreSQL connection failed\n");
}

$tables = ['nuser', 'admin', 'adwares', 'access', 'pay', /* ... */];

foreach ($tables as $table) {
    echo "Migrating {$table}...\n";

    $csvFile = "tdb/{$table}.csv";
    $lstFile = "lst/{$table}.csv";

    // LST定義を読み込んでカラム名取得
    $columns = getColumnsFromLst($lstFile);

    // CSVデータ読み込み
    $rows = array_map('str_getcsv', file($csvFile));

    // データ挿入
    foreach ($rows as $row) {
        if (empty($row[0])) continue;

        $placeholders = [];
        $values = [];

        for ($i = 0; $i < count($columns); $i++) {
            $placeholders[] = "$" . ($i + 1);
            $values[] = $row[$i] ?? null;
        }

        $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";

        $result = pg_query_params($conn, $sql, $values);

        if (!$result) {
            echo "Error: " . pg_last_error($conn) . "\n";
        }
    }

    echo "{$table} migration completed\n";
}

pg_close($conn);

function getColumnsFromLst($lstFile) {
    $lines = file($lstFile);
    $columns = [];

    foreach ($lines as $line) {
        $cols = str_getcsv($line);
        if (count($cols) >= 1 && !empty($cols[0])) {
            $columns[] = $cols[0];
        }
    }

    return $columns;
}
?>
```

**実行**:
```bash
# 環境変数設定
export SUPABASE_DB_HOST="db.xxxxxxxxxxxxx.supabase.co"
export SUPABASE_DB_PORT="5432"
export SUPABASE_DB_NAME="postgres"
export SUPABASE_DB_USER="postgres"
export SUPABASE_DB_PASS="your-password"

# マイグレーション実行
php tools/migrate_data.php
```

#### 2.5 接続設定変更

**custom/extends/sqlConf.php**:

```php
<?php
// PostgreSQL接続設定 (Supabase)
$SQL = true;
$SQL_MASTER = 'PostgreSQLDatabase'; // ← 変更

// 環境変数から取得
$SQL_SERVER = getenv('SUPABASE_DB_HOST') ?: 'localhost';
$SQL_PORT = getenv('SUPABASE_DB_PORT') ?: '5432';
$DB_NAME = getenv('SUPABASE_DB_NAME') ?: 'postgres';
$SQL_ID = getenv('SUPABASE_DB_USER') ?: 'postgres';
$SQL_PASS = getenv('SUPABASE_DB_PASS') ?: '';

$TABLE_PREFIX = '';
$CONFIG_SQL_FILE_TYPES = Array('image','file');
$CONFIG_SQL_DATABASE_SESSION = false;
$CONFIG_SQL_PASSWORD_KEY = getenv('SQL_PASSWORD_KEY') ?: 'derhymqadbrheng';
?>
```

---

### Phase 3: ファイルストレージ移行

#### 3.1 Supabase Storage セットアップ

```bash
# Supabaseダッシュボードで以下のバケット作成
# - affiliate-images (public)
# - affiliate-files (private)
```

#### 3.2 FileBase.php 改修

**include/base/FileBase.php** に Supabase Storage対応を追加:

```php
<?php
// 新規作成: include/base/SupabaseStorage.php
class SupabaseStorage {
    private $supabaseUrl;
    private $supabaseKey;

    public function __construct() {
        $this->supabaseUrl = getenv('SUPABASE_URL');
        $this->supabaseKey = getenv('SUPABASE_ANON_KEY');
    }

    /**
     * ファイルアップロード
     */
    public function upload($bucket, $path, $file) {
        $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file['tmp_name']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->supabaseKey}",
            "Content-Type: {$file['type']}"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
        }

        return false;
    }

    /**
     * ファイル削除
     */
    public function delete($bucket, $path) {
        $url = "{$this->supabaseUrl}/storage/v1/object/{$bucket}/{$path}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$this->supabaseKey}"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    /**
     * 公開URLを取得
     */
    public function getPublicUrl($bucket, $path) {
        return "{$this->supabaseUrl}/storage/v1/object/public/{$bucket}/{$path}";
    }
}
?>
```

**FileBase.php の改修**:

```php
// include/base/FileBase.php に以下を追加

require_once 'include/base/SupabaseStorage.php';

class FileBase {
    private $storage;

    public function __construct() {
        // Supabase Storageを使用
        if (getenv('USE_SUPABASE_STORAGE') === 'true') {
            $this->storage = new SupabaseStorage();
        }
    }

    public function upload($rec, $colname) {
        global $MAX_FILE_SIZE;

        $file = $_FILES[$colname];

        if ($file['size'] > $MAX_FILE_SIZE) {
            return false;
        }

        // Supabase Storage にアップロード
        if ($this->storage) {
            $bucket = 'affiliate-images';
            $path = $this->generatePath($file);

            $url = $this->storage->upload($bucket, $path, $file);

            if ($url) {
                // URLをDBに保存
                return $url;
            }
        }

        // フォールバック: ローカル保存
        return $this->localUpload($file, $colname);
    }

    private function generatePath($file) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        return date('Y/m/d') . '/' . uniqid() . '.' . $ext;
    }
}
```

#### 3.3 既存ファイルの移行

```php
// tools/migrate_files.php
<?php
require_once 'include/base/SupabaseStorage.php';

$storage = new SupabaseStorage();
$sourceDir = 'file/image';

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDir)
);

foreach ($files as $file) {
    if ($file->isFile()) {
        $relativePath = str_replace($sourceDir . '/', '', $file->getPathname());

        echo "Uploading {$relativePath}...\n";

        $url = $storage->upload('affiliate-images', $relativePath, [
            'tmp_name' => $file->getPathname(),
            'type' => mime_content_type($file->getPathname())
        ]);

        if ($url) {
            echo "Success: {$url}\n";
        } else {
            echo "Failed\n";
        }
    }
}
?>
```

---

### Phase 4: 外部サービス統合

#### 4.1 SendGrid セットアップ

```bash
# SendGrid APIキー取得
# https://app.sendgrid.com/settings/api_keys
```

#### 4.2 Mail.php 改修

**include/Mail.php**:

```php
<?php
class Mail {
    /**
     * メール送信 (SendGrid API使用)
     */
    static function send($template, $from, $to, $gm, $rec = null, $fromName = null) {
        global $MAILSEND_ADDRES;
        global $MAILSEND_NAMES;

        // テンプレート処理
        $html = IncludeObject::get($template);
        if ($rec && $gm) {
            $html = $gm->getString($template, $rec);
        }

        // 件名と本文を分離
        preg_match('/Subject:\s*(.+)\n/', $html, $matches);
        $subject = $matches[1] ?? 'お知らせ';
        $body = preg_replace('/Subject:\s*.+\n/', '', $html);

        // SendGrid API使用
        if (getenv('USE_SENDGRID') === 'true') {
            return self::sendViaSendGrid($from, $to, $subject, $body, $fromName);
        }

        // フォールバック: PHP mail()
        return self::sendViaPHPMail($from, $to, $subject, $body, $fromName);
    }

    /**
     * SendGrid API経由でメール送信
     */
    private static function sendViaSendGrid($from, $to, $subject, $body, $fromName) {
        $apiKey = getenv('SENDGRID_API_KEY');

        $data = [
            'personalizations' => [
                [
                    'to' => [['email' => $to]],
                    'subject' => $subject
                ]
            ],
            'from' => [
                'email' => $from,
                'name' => $fromName ?? $from
            ],
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $body
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$apiKey}",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 202;
    }

    /**
     * PHP mail()関数でメール送信 (フォールバック)
     */
    private static function sendViaPHPMail($from, $to, $subject, $body, $fromName) {
        $headers = "From: {$fromName} <{$from}>\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        return mail($to, $subject, $body, $headers);
    }
}
?>
```

---

### Phase 5: Render デプロイ

#### 5.1 render.yaml 作成

```yaml
# render.yaml
services:
  - type: web
    name: affiliate-pro
    runtime: php
    plan: starter
    region: singapore
    buildCommand: composer install
    startCommand: php -S 0.0.0.0:$PORT -t .
    envVars:
      - key: PHP_VERSION
        value: "8.2"

      # Supabase接続情報
      - key: SUPABASE_URL
        sync: false
      - key: SUPABASE_ANON_KEY
        sync: false
      - key: SUPABASE_DB_HOST
        sync: false
      - key: SUPABASE_DB_PORT
        value: "5432"
      - key: SUPABASE_DB_NAME
        value: "postgres"
      - key: SUPABASE_DB_USER
        value: "postgres"
      - key: SUPABASE_DB_PASS
        sync: false

      # SendGrid
      - key: SENDGRID_API_KEY
        sync: false
      - key: USE_SENDGRID
        value: "true"

      # Supabase Storage
      - key: USE_SUPABASE_STORAGE
        value: "true"

      # システム設定
      - key: SQL_PASSWORD_KEY
        generateValue: true
      - key: SESSION_SECRET
        generateValue: true
```

#### 5.2 composer.json 作成

```json
{
  "name": "affiliate-pro",
  "description": "Affiliate System Pro",
  "require": {
    "php": "^8.2",
    "ext-pgsql": "*",
    "ext-curl": "*",
    "ext-mbstring": "*"
  },
  "autoload": {
    "files": [
      "custom/conf.php",
      "custom/global.php"
    ]
  }
}
```

#### 5.3 .gitignore 作成

```
# .gitignore
vendor/
tdb/*.csv
logs/*.log
file/tmp/*
.env
```

#### 5.4 Git リポジトリ初期化

```bash
cd アフィリエイトシステムプロ＿システム本体003CSS未タッチ

git init
git add .
git commit -m "Initial commit: Migrate to Render + Supabase"

# GitHub リポジトリ作成後
git remote add origin https://github.com/yourusername/affiliate-pro.git
git push -u origin main
```

#### 5.5 Render デプロイ

```bash
# Render ダッシュボードで
# 1. New > Web Service
# 2. GitHub リポジトリ接続
# 3. render.yaml 検出
# 4. 環境変数設定
# 5. Deploy
```

---

## 技術スタック

### インフラ
| コンポーネント | 技術 | 用途 |
|--------------|------|------|
| アプリケーションホスティング | Render Web Service | PHPアプリケーション実行 |
| データベース | Supabase PostgreSQL | データ永続化 |
| ファイルストレージ | Supabase Storage | 画像・ファイル保存 |
| メール送信 | SendGrid | トランザクションメール |
| ドメイン | Render Custom Domain | 独自ドメイン設定 |
| SSL証明書 | Render (自動) | HTTPS対応 |

### アプリケーション
| レイヤー | 技術 |
|---------|------|
| 言語 | PHP 8.2 |
| テンプレートエンジン | ccProc (カスタム) |
| ORM | GUIManager (カスタム) |
| データベースドライバ | pgsql (PostgreSQL) |
| HTTPクライアント | cURL |

---

## リスクと対策

### リスク1: 文字コード変換による文字化け

**影響度**: 高
**発生確率**: 中

**対策**:
- [ ] バックアップ必須
- [ ] 変換前後の差分確認ツール作成
- [ ] サンプルデータで事前テスト
- [ ] ロールバック手順の準備

### リスク2: データベース移行時のデータ損失

**影響度**: 高
**発生確率**: 低

**対策**:
- [ ] CSVバックアップ保持
- [ ] マイグレーション前のダンプ取得
- [ ] レコード数の照合確認
- [ ] 重要テーブルの内容サンプリング確認

### リスク3: PostgreSQL互換性問題

**影響度**: 中
**発生確率**: 中

**既知の問題**:
- MySQL特有の関数使用箇所
- AUTO_INCREMENT → SERIAL変換
- 日付関数の差異

**対策**:
- [ ] SQLDatabase.php のPostgreSQL実装確認
- [ ] カスタムSQL文の洗い出し
- [ ] 互換性テストの実施

### リスク4: ファイルストレージ移行の遅延

**影響度**: 中
**発生確率**: 低

**対策**:
- [ ] 段階的移行 (新規ファイルのみ先行)
- [ ] CDN設定による高速化
- [ ] 並列アップロードスクリプト

### リスク5: メール送信制限

**影響度**: 中
**発生確率**: 低

**対策**:
- [ ] SendGrid無料枠確認 (月100通)
- [ ] 必要に応じて有料プラン
- [ ] メール送信キュー実装

### リスク6: パフォーマンス劣化

**影響度**: 中
**発生確率**: 中

**対策**:
- [ ] Render Starterプランで開始
- [ ] 負荷テスト実施
- [ ] 必要に応じてプランアップグレード
- [ ] クエリ最適化

---

## テスト計画

### 単体テスト (Phase 1-4)

| テスト項目 | 確認内容 |
|-----------|---------|
| 文字コード | 日本語表示・入力・保存 |
| データベース接続 | SELECT/INSERT/UPDATE/DELETE |
| ファイルアップロード | 画像・PDFアップロード |
| メール送信 | 各種通知メール |

### 統合テスト (Phase 6)

#### シナリオ1: ユーザー登録フロー
1. [ ] 登録フォーム表示
2. [ ] 入力検証
3. [ ] 確認画面表示
4. [ ] データベース登録
5. [ ] アクティベーションメール送信
6. [ ] アクティベーション完了

#### シナリオ2: アフィリエイトリンククリック
1. [ ] link.php アクセス
2. [ ] アクセスログ記録
3. [ ] Cookie設定
4. [ ] クリック報酬記録 (該当する場合)
5. [ ] リダイレクト

#### シナリオ3: コンバージョン発生
1. [ ] add.php トリガー
2. [ ] アクセス記録検索
3. [ ] 成果記録
4. [ ] 管理者承認
5. [ ] 3段階ティア報酬加算
6. [ ] 通知メール送信

#### シナリオ4: 広告管理
1. [ ] 広告登録
2. [ ] バナーアップロード
3. [ ] 広告編集
4. [ ] 予算管理

### パフォーマンステスト

| 項目 | 目標値 | 測定方法 |
|-----|-------|---------|
| レスポンスタイム | < 500ms | Apache Bench |
| 同時接続数 | 100 | JMeter |
| データベースクエリ | < 100ms | PostgreSQL EXPLAIN |

### セキュリティテスト

- [ ] SQLインジェクション対策確認
- [ ] XSS対策確認
- [ ] CSRF対策確認
- [ ] セッションハイジャック対策
- [ ] ファイルアップロード制限

---

## ロールバック計画

### Phase 1-4 でのロールバック

**バックアップから復元**:
```bash
# 1. バックアップディレクトリに戻す
rm -rf アフィリエイトシステムプロ＿システム本体003CSS未タッチ
cp -r アフィリエイトシステムプロ_BACKUP アフィリエイトシステムプロ＿システム本体003CSS未タッチ

# 2. 既存サーバーで稼働確認
```

### Phase 5-7 でのロールバック

**DNS切り戻し**:
```bash
# 1. DNS設定を旧サーバーに戻す
# 2. Render デプロイを一時停止
# 3. データベースを旧MySQL に切り戻し
```

**データベース同期**:
```php
// tools/rollback_db.php
<?php
// Supabase → MySQL へのデータ逆同期
// (事前に同期スクリプト準備)
?>
```

---

## 次のアクション

### 即座に開始可能な作業

1. **Phase 0: 準備**
   ```bash
   # バックアップ作成
   cp -r アフィリエイトシステムプロ＿システム本体003CSS未タッチ ../affiliate-pro-backup-$(date +%Y%m%d)

   # Supabase プロジェクト作成
   # https://supabase.com/dashboard

   # Render アカウント作成
   # https://render.com/
   ```

2. **Phase 1: 文字コード変換スクリプト作成**
   - tools/convert_encoding.sh を作成
   - テスト実行

3. **Phase 2: スキーマ変換ツール作成**
   - tools/lst_to_sql.php を作成
   - SQL DDL生成

### 推奨実施順序

```
Week 1: Phase 0-1 (準備・文字コード変換)
Week 2: Phase 2 (データベース移行)
Week 3: Phase 3-4 (ストレージ・メール)
Week 4: Phase 5-6 (デプロイ・テスト)
Week 5: Phase 7 (本番移行)
```

---

**最終更新**: 2025-01-XX
**作成者**: Claude Code
**ドキュメントバージョン**: 1.0
