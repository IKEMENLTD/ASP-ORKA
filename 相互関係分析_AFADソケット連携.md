# AFADソケット連携システム - 相互関係分析レポート

**作成日:** 2025-10-29
**バージョン:** 1.0
**対象設計書:** 詳細設計_AFADソケット連携システム.md v2.0

---

## 目次

1. [コンポーネント依存関係マトリクス](#1-コンポーネント依存関係マトリクス)
2. [ファイルパス整合性チェック](#2-ファイルパス整合性チェック)
3. [データベーススキーマ整合性](#3-データベーススキーマ整合性)
4. [メッセージフォーマット整合性](#4-メッセージフォーマット整合性)
5. [設定値の伝播分析](#5-設定値の伝播分析)
6. [エラーハンドリングフロー](#6-エラーハンドリングフロー)
7. [データフロー詳細分析](#7-データフロー詳細分析)
8. [統合ポイント検証](#8-統合ポイント検証)
9. [発見された問題点と推奨事項](#9-発見された問題点と推奨事項)

---

## 1. コンポーネント依存関係マトリクス

### 1.1 CATS側コンポーネント依存関係

```
SocketEventDispatcher.php
├─ require: SocketClient.php
├─ require: ../Util.php
├─ uses: global $_db
├─ uses: $SOCKET_CONF (from socketConf.php)
└─ depends on: socket_events テーブル

SocketClient.php
├─ require: ../Util.php
├─ require: Exception/SocketException.php
├─ require: Exception/SocketTemporaryException.php
└─ uses: stream_socket_client(), OpenSSL functions

SocketMessageReceiver.php
├─ require: SocketClient.php
├─ uses: global $_db
└─ depends on: adwares テーブル

SocketRetryStrategy.php
├─ uses: SocketAuthException (class check)
└─ uses: SocketTemporaryException (class check)

Exception/SocketAuthException.php
└─ require: SocketException.php

Exception/SocketTemporaryException.php
└─ require: SocketException.php

socketConf.php
└─ defines: $SOCKET_CONF (global variable)
```

### 1.2 Gateway側コンポーネント依存関係

```
SocketServer.php
├─ require: ../vendor/autoload.php (Composer)
├─ require: SocketAuthenticator.php
├─ require: SocketMessageHandler.php
├─ require: SocketRateLimiter.php
├─ uses: Ratchet\MessageComponentInterface
├─ uses: React\EventLoop
└─ uses: Monolog\Logger

SocketMessageHandler.php
├─ require: ../include/base/Initialize.php
├─ uses: global $_db
└─ depends on: socket_messages テーブル

SocketAuthenticator.php
├─ require: ../include/base/Initialize.php
├─ uses: global $_db
├─ uses: getenv('SOCKET_AUTH_TOKEN')
└─ depends on: socket_connections テーブル

SocketLogger.php
├─ uses: Monolog\Logger
├─ uses: Monolog\Handler\RotatingFileHandler
└─ depends on: filesystem (logs/)

SocketRateLimiter.php
└─ uses: internal state (no external dependencies)
```

### 1.3 統合ポイント依存関係

```
add.php
├─ require: include/extends/SocketEventDispatcher.php
├─ uses: $pay_rec (existing)
├─ uses: $access_rec (existing)
├─ uses: $adwares_rec (existing)
├─ uses: $user_rec (existing)
└─ uses: $_tierValue (existing global tier calculation)

link.php
├─ require: include/extends/SocketEventDispatcher.php
├─ uses: $access_rec (existing)
└─ uses: $click_pay_rec (existing, optional)
```

### 1.4 依存関係の検証結果

✅ **正常:** 全ての `require_once` パスが正しい相対パス
✅ **正常:** 循環依存なし
⚠️ **警告:** `SocketRetryStrategy.php` が例外クラスを `instanceof` でチェックするが、`require` していない
⚠️ **警告:** `SocketMessageHandler.php` が `Initialize.php` を require するが、Gateway側から CATS の DB にアクセス

---

## 2. ファイルパス整合性チェック

### 2.1 設計書記載のファイルパス vs 実際の構造

| コンポーネント | 設計書のパス | 検証結果 | 備考 |
|--------------|------------|---------|------|
| **SocketException.php** | `/include/extends/Exception/SocketException.php` | ✅ 正常 | ディレクトリ作成が必要 |
| **SocketAuthException.php** | `/include/extends/Exception/SocketAuthException.php` | ✅ 正常 | 同上 |
| **SocketTemporaryException.php** | `/include/extends/Exception/SocketTemporaryException.php` | ✅ 正常 | 同上 |
| **SocketClient.php** | `/include/extends/SocketClient.php` | ✅ 正常 | - |
| **SocketEventDispatcher.php** | `/include/extends/SocketEventDispatcher.php` | ✅ 正常 | - |
| **SocketMessageReceiver.php** | `/include/extends/SocketMessageReceiver.php` | ✅ 正常 | - |
| **SocketRetryStrategy.php** | `/include/extends/SocketRetryStrategy.php` | ✅ 正常 | - |
| **socketConf.php** | `/custom/extends/socketConf.php` | ✅ 正常 | ディレクトリ作成が必要 |
| **SocketServer.php** | `/socket/SocketServer.php` | ✅ 正常 | ディレクトリ作成が必要 |
| **SocketMessageHandler.php** | `/socket/SocketMessageHandler.php` | ✅ 正常 | 同上 |
| **SocketAuthenticator.php** | `/socket/SocketAuthenticator.php` | ✅ 正常 | 同上 |
| **SocketLogger.php** | `/socket/SocketLogger.php` | ✅ 正常 | 同上 |
| **SocketRateLimiter.php** | `/socket/SocketRateLimiter.php` | ✅ 正常 | 同上 |
| **composer.json** | `/socket/composer.json` | ✅ 正常 | 同上 |
| **start_server.sh** | `/socket/start_server.sh` | ✅ 正常 | 同上 |
| **002_create_socket_tables.php** | `/migration/002_create_socket_tables.php` | ✅ 正常 | - |
| **process_socket_queue.php** | `/tools/process_socket_queue.php` | ✅ 正常 | - |
| **socket-server.service** | `/deployment/socket-server.service` | ✅ 正常 | ディレクトリ作成が必要 |
| **nginx-socket.conf** | `/deployment/nginx-socket.conf` | ✅ 正常 | 同上 |
| **.env.example** | `/.env.example` | ✅ 正常 | - |
| **socket-client.js** | `/js/socket-client.js` | ✅ 正常 | - |

### 2.2 相対パス検証

#### SocketEventDispatcher.php内のrequire文
```php
require_once dirname(__FILE__) . '/SocketClient.php';
require_once dirname(__FILE__) . '/../Util.php';
```
- ✅ `/include/extends/SocketClient.php` → 正しい
- ✅ `/include/Util.php` → 正しい（既存ファイル）

#### SocketClient.php内のrequire文
```php
require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/Exception/SocketException.php';
require_once dirname(__FILE__) . '/Exception/SocketTemporaryException.php';
```
- ✅ `/include/Util.php` → 正しい
- ✅ `/include/extends/Exception/SocketException.php` → 正しい
- ✅ `/include/extends/Exception/SocketTemporaryException.php` → 正しい

#### SocketMessageReceiver.php内のrequire文
```php
require_once dirname(__FILE__) . '/SocketClient.php';
```
- ✅ `/include/extends/SocketClient.php` → 正しい

#### SocketServer.php内のrequire文
```php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/SocketAuthenticator.php';
require_once __DIR__ . '/SocketMessageHandler.php';
require_once __DIR__ . '/SocketRateLimiter.php';
```
- ✅ `/vendor/autoload.php` → Composerによる配置（正しい）
- ✅ `/socket/SocketAuthenticator.php` → 正しい
- ✅ `/socket/SocketMessageHandler.php` → 正しい
- ✅ `/socket/SocketRateLimiter.php` → 正しい

#### SocketMessageHandler.php内のrequire文
```php
require_once __DIR__ . '/../include/base/Initialize.php';
```
- ⚠️ `/include/base/Initialize.php` → Gateway側から CATS の DB に直接アクセス
- **懸念点:** Gateway は別サーバーの想定だが、CATS の Initialize.php に依存
- **推奨:** Gateway 側で独立した DB 接続を構築するか、API 経由でアクセス

#### add.php/link.phpへの統合
```php
require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';
```
- ✅ `/include/extends/SocketEventDispatcher.php` → 正しい

---

## 3. データベーススキーマ整合性

### 3.1 テーブル定義と使用箇所のマッピング

#### 3.1.1 socket_events テーブル

**スキーマ定義（002_create_socket_tables.php）:**
```sql
CREATE TABLE socket_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,
    event_data TEXT,
    target_system VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    retry_count INT DEFAULT 0,
    error_message TEXT,
    created_at DATETIME NOT NULL,
    sent_at DATETIME,
    INDEX idx_status_created (status, created_at),
    INDEX idx_event_type (event_type)
);
```

**使用箇所:**

| ファイル | メソッド | 操作 | カラム使用 |
|---------|---------|------|----------|
| SocketEventDispatcher.php | saveToDatabase() | INSERT | event_type, event_data, target_system, status, retry_count, created_at |
| SocketEventDispatcher.php | updateDatabaseStatus() | SELECT + UPDATE | event_type, event_data, status, sent_at, error_message |
| SocketEventDispatcher.php | processQueue() | *(暗黙的)* | status='FAILED' または 'PENDING' のレコード取得が必要 |

**整合性チェック:**
- ✅ **正常:** 全カラムが定義通りに使用されている
- ⚠️ **問題:** `processQueue()` がデータベースから失敗イベントを取得するロジックが**実装されていない**
  - 現在の実装はメモリ内キュー (`$this->queue`) のみ処理
  - データベースに保存された FAILED/PENDING イベントの再処理がない
  - **推奨:** `processQueue()` に DB からの取得ロジックを追加

#### 3.1.2 socket_connections テーブル

**スキーマ定義:**
```sql
CREATE TABLE socket_connections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    connection_id VARCHAR(64) NOT NULL UNIQUE,
    client_type VARCHAR(20) NOT NULL,
    client_ip VARCHAR(45),
    token VARCHAR(255),
    connected_at DATETIME NOT NULL,
    last_heartbeat DATETIME,
    disconnected_at DATETIME,
    INDEX idx_connection_id (connection_id),
    INDEX idx_client_type (client_type)
);
```

**使用箇所:**

| ファイル | メソッド | 操作 | カラム使用 |
|---------|---------|------|----------|
| SocketAuthenticator.php | recordConnection() | INSERT | connection_id, client_type, client_ip, connected_at, last_heartbeat |
| SocketAuthenticator.php | recordDisconnection() | SELECT + UPDATE | connection_id, disconnected_at |

**整合性チェック:**
- ✅ **正常:** 全カラムが定義通りに使用されている
- ⚠️ **問題:** `token` カラムが定義されているが、使用されていない
- ⚠️ **問題:** `last_heartbeat` の更新ロジックが実装されていない
  - Ping/Pongメッセージ受信時に `last_heartbeat` を更新すべき
  - **推奨:** SocketServer.php の `onMessage()` で Ping 受信時に更新

#### 3.1.3 socket_messages テーブル

**スキーマ定義:**
```sql
CREATE TABLE socket_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    connection_id VARCHAR(64) NOT NULL,
    direction VARCHAR(10) NOT NULL,
    message_type VARCHAR(50) NOT NULL,
    message_data TEXT,
    created_at DATETIME NOT NULL,
    INDEX idx_connection_created (connection_id, created_at),
    INDEX idx_message_type (message_type)
);
```

**使用箇所:**

| ファイル | メソッド | 操作 | カラム使用 |
|---------|---------|------|----------|
| SocketMessageHandler.php | logMessage() | INSERT | connection_id, direction, message_type, message_data, created_at |

**整合性チェック:**
- ✅ **正常:** 全カラムが定義通りに使用されている
- ✅ **正常:** `direction` の値は "INBOUND" / "OUTBOUND" を想定（実装では "INBOUND" のみ）
- ⚠️ **改善提案:** OUTBOUND (送信メッセージ) のログ記録も追加推奨

### 3.2 CSV定義ファイルとの整合性

**CSV定義（002_create_socket_tables.php）:**
```php
$definitions = [
    'socket_events' => "id,event_type,event_data,target_system,status,retry_count,error_message,created_at,sent_at\nINT,VARCHAR(50),TEXT,VARCHAR(20),VARCHAR(20),INT,TEXT,DATETIME,DATETIME",

    'socket_connections' => "id,connection_id,client_type,client_ip,token,connected_at,last_heartbeat,disconnected_at\nINT,VARCHAR(64),VARCHAR(20),VARCHAR(45),VARCHAR(255),DATETIME,DATETIME,DATETIME",

    'socket_messages' => "id,connection_id,direction,message_type,message_data,created_at\nBIGINT,VARCHAR(64),VARCHAR(10),VARCHAR(50),TEXT,DATETIME"
];
```

**検証結果:**
- ✅ **正常:** 全カラム名が SQL スキーマと一致
- ✅ **正常:** データ型が SQL スキーマと一致
- ✅ **正常:** カラム順序が SQL スキーマと一致

---

## 4. メッセージフォーマット整合性

### 4.1 基本メッセージ構造

**定義（設計書 4.2）:**
```json
{
    "version": "1.0",
    "type": "event_type",
    "timestamp": "2025-10-29T12:34:56Z",
    "message_id": "uuid-v4",
    "sender": "CATS",
    "payload": { }
}
```

**送信実装（SocketClient.php - send()メソッド）:**
```php
$message = [
    'version' => '1.0',
    'type' => $type,
    'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
    'message_id' => $this->generateUUID(),
    'sender' => 'CATS',
    'payload' => $payload
];
```
- ✅ **正常:** 完全一致

### 4.2 イベントタイプ別メッセージ検証

#### 4.2.1 conversion イベント

**設計書の payload 構造:**
```json
{
    "pay_id": 12345,
    "access_id": 67890,
    "adware_id": 100,
    "adware_name": "商品A",
    "owner_id": 500,
    "owner_name": "アフィリエイターX",
    "cost": 1000,
    "sales": 5000,
    "state": "PENDING",
    "tier_rewards": [ ... ],
    "created_at": "2025-10-29T12:34:56Z"
}
```

**実装（SocketEventDispatcher.php - dispatchConversion()）:**
```php
$payload = [
    'pay_id' => $pay_data['id'],
    'access_id' => $pay_data['access_id'],
    'adware_id' => $pay_data['adwares'],
    'adware_name' => $adware_data['name'] ?? '',
    'owner_id' => $pay_data['owner'],
    'owner_name' => $user_data['name'] ?? '',
    'cost' => (int)$pay_data['cost'],
    'sales' => (int)$pay_data['sales'],
    'state' => $pay_data['state'],
    'tier_rewards' => $tier_rewards,
    'created_at' => $pay_data['regist']
];
```
- ✅ **正常:** 完全一致

#### 4.2.2 click イベント

**設計書には詳細な payload 定義がない** - 実装のみ存在

**実装（SocketEventDispatcher.php - dispatchClick()）:**
```php
$payload = [
    'access_id' => $access_data['id'],
    'adware_id' => $access_data['adwares'],
    'owner_id' => $access_data['owner'],
    'ipaddress' => $access_data['ipaddress'],
    'useragent' => $access_data['useragent'],
    'referer' => $access_data['referer'],
    'has_reward' => ($click_pay_data !== null),
    'reward_amount' => $click_pay_data ? (int)$click_pay_data['cost'] : 0,
    'created_at' => $access_data['regist']
];
```
- ⚠️ **問題:** 設計書に click イベントの payload 仕様が記載されていない
- **推奨:** 設計書の 4.2 セクションに click イベントの payload 定義を追加

#### 4.2.3 adware_update イベント（AFAD → CATS）

**設計書の payload 構造:**
```json
{
    "adware_id": 100,
    "action": "UPDATE",
    "data": {
        "name": "商品A",
        "money": 1000,
        "click_money": 10,
        "continue_money": 500,
        "url": "https://example.com/product-a",
        "limits": 100000,
        "open": true
    }
}
```

**受信実装（SocketMessageReceiver.php - handleAdwareUpdate()）:**
```php
$adware_id = $payload['adware_id'];
$action = $payload['action'];
$data = $payload['data'];

switch ($action) {
    case 'CREATE':
        $data['id'] = $adware_id;
        $data['regist'] = date('Y-m-d H:i:s');
        $adware_db->add($data);
        break;

    case 'UPDATE':
        // ... 更新処理

    case 'DELETE':
        // ... 削除処理
}
```
- ✅ **正常:** 構造が一致
- ⚠️ **問題:** `data` フィールドのカラム名が CATS の adwares テーブルと一致する必要がある
  - 設計書では `money`, `click_money`, `continue_money`, `url`, `limits`, `open` を使用
  - CATS の adwares テーブル構造を確認する必要がある

#### 4.2.4 その他のイベントタイプ

**設計書 4.3 のイベントタイプ一覧:**

| イベントタイプ | 実装状況 | 備考 |
|---------------|---------|------|
| auth | ✅ 実装済 | SocketClient.php / SocketServer.php |
| auth_success | ✅ 実装済 | SocketServer.php |
| auth_failed | ✅ 実装済 | SocketServer.php (sendError経由) |
| ping | ✅ 実装済 | SocketServer.php |
| pong | ✅ 実装済 | SocketServer.php / SocketClient.php |
| conversion | ✅ 実装済 | SocketEventDispatcher.php |
| click | ✅ 実装済 | SocketEventDispatcher.php |
| tier_reward | ✅ 実装済 | SocketEventDispatcher.php |
| adware_update | ✅ 実装済 | SocketMessageReceiver.php |
| budget_alert | ✅ 実装済 | SocketEventDispatcher.php |
| fraud_alert | ✅ 実装済 | SocketEventDispatcher.php |
| stats_update | ❌ **未実装** | 設計書にのみ記載 |
| user_online | ❌ **未実装** | 設計書にのみ記載 |
| error | ✅ 実装済 | SocketServer.php (sendError) |
| budget_update | ✅ 実装済 | SocketMessageReceiver.php (追加実装) |

**問題点:**
- ⚠️ `stats_update` と `user_online` が設計書にあるが未実装
- ⚠️ `budget_update` が実装されているが設計書のイベント一覧に記載なし

---

## 5. 設定値の伝播分析

### 5.1 環境変数の使用マップ

#### .env.example の定義:
```env
SOCKET_AUTH_TOKEN=your-secret-token-change-this
SOCKET_SERVER_URL=wss://socket.example.com:8080/ws
SOCKET_ENABLED=true
SOCKET_SSL_CERT=/path/to/cert.pem
SOCKET_SSL_KEY=/path/to/privkey.pem
SOCKET_SSL_ENABLED=true
```

#### 使用箇所マッピング:

| 環境変数 | 使用ファイル | 使用箇所 | 検証結果 |
|---------|------------|---------|---------|
| `SOCKET_AUTH_TOKEN` | socketConf.php | `getenv('SOCKET_AUTH_TOKEN')` | ✅ 正常 |
| `SOCKET_AUTH_TOKEN` | SocketAuthenticator.php | `getenv('SOCKET_AUTH_TOKEN')` | ✅ 正常 |
| `SOCKET_SERVER_URL` | *(未使用)* | - | ⚠️ 定義されているが未使用 |
| `SOCKET_ENABLED` | *(未使用)* | - | ⚠️ socketConf.php の 'enabled' と重複 |
| `SOCKET_SSL_CERT` | SocketServer.php | `getenv('SOCKET_SSL_CERT')` | ✅ 正常 |
| `SOCKET_SSL_KEY` | SocketServer.php | `getenv('SOCKET_SSL_KEY')` | ✅ 正常 |
| `SOCKET_SSL_ENABLED` | SocketServer.php | `getenv('SOCKET_SSL_ENABLED')` | ✅ 正常 |

**問題点:**
- ⚠️ `SOCKET_SERVER_URL` が定義されているが、socketConf.php では 'url' が直接記述
  - **推奨:** socketConf.php を以下のように変更:
    ```php
    'url' => getenv('SOCKET_SERVER_URL') ?: 'wss://socket.example.com:8080/ws',
    ```
- ⚠️ `SOCKET_ENABLED` が .env.example に定義されているが、実際は socketConf.php の 'enabled' を使用
  - **推奨:** どちらかに統一

### 5.2 socketConf.php の設定値伝播

#### socketConf.php の定義:
```php
$SOCKET_CONF = [
    'enabled' => true,
    'url' => 'wss://socket.example.com:8080/ws',
    'token' => getenv('SOCKET_AUTH_TOKEN') ?: 'your-secret-token-here',
    'timeout' => 10,
    'auto_reconnect' => true,
    'max_reconnect_attempts' => 5,
    'heartbeat_interval' => 30,
    'queue_process_interval' => 60,
    'debug' => false,
    'log_file' => dirname(__FILE__) . '/../../logs/socket.log'
];
```

#### 使用箇所の検証:

| 設定キー | SocketEventDispatcher | SocketClient | SocketMessageReceiver | 検証結果 |
|---------|---------------------|--------------|---------------------|---------|
| enabled | ✅ 使用 | - | - | 正常 |
| url | ✅ 使用 (initClient) | ✅ 使用 (__construct) | ✅ 使用 (想定) | 正常 |
| token | ✅ 使用 (initClient) | ✅ 使用 (__construct) | - | 正常 |
| timeout | ✅ 使用 (initClient) | ✅ 使用 (__construct) | - | 正常 |
| auto_reconnect | ✅ 使用 (initClient) | ✅ 使用 (__construct) | - | 正常 |
| max_reconnect_attempts | ✅ 使用 (initClient) | ✅ 使用 (__construct) | - | 正常 |
| heartbeat_interval | ❌ **未使用** | ❌ **未使用** | - | ⚠️ 問題 |
| queue_process_interval | ❌ **未使用** | - | - | ⚠️ 問題 |
| debug | ❌ **未使用** | - | - | ⚠️ 問題 |
| log_file | ❌ **未使用** | - | - | ⚠️ 問題 |

**問題点:**
- ⚠️ `heartbeat_interval` が定義されているが、実装されていない
  - SocketClient.php にハートビート自動送信ロジックがない
  - **推奨:** SocketClient に定期的な Ping 送信機能を追加
- ⚠️ `queue_process_interval`, `debug`, `log_file` が未使用
  - **推奨:** 使用しないなら削除、または実装を追加

---

## 6. エラーハンドリングフロー

### 6.1 例外クラス階層

```
Exception (PHP標準)
└─ SocketException (基底)
   ├─ SocketAuthException (認証エラー - リトライ不可)
   └─ SocketTemporaryException (一時エラー - リトライ可)
```

### 6.2 例外の伝播フロー

#### ケース1: 接続失敗（SocketClient.php）

```
SocketClient::connect()
├─ stream_socket_client() 失敗
├─ throw new SocketTemporaryException("Connection failed")
│
▼ キャッチ箇所
SocketEventDispatcher::initClient()
├─ catch (Exception $e)
├─ error_log()
└─ $this->client = null (Gatewayへの送信をスキップ)
```

**検証結果:**
- ✅ **正常:** 例外が適切にキャッチされ、アプリケーションがクラッシュしない
- ✅ **正常:** イベントが socket_events テーブルに保存される
- ⚠️ **問題:** SocketTemporaryException を特定してリトライするロジックがない

#### ケース2: 認証失敗（SocketClient.php）

```
SocketClient::authenticate()
├─ 認証応答が 'auth_success' でない
├─ throw new SocketAuthException('Authentication failed')
│
▼ キャッチ箇所
SocketEventDispatcher::initClient()
├─ catch (Exception $e)
├─ error_log()
└─ $this->client = null
```

**検証結果:**
- ✅ **正常:** 例外が適切にキャッチされる
- ⚠️ **問題:** 認証失敗時にリトライしないロジックがない
  - `SocketRetryStrategy::shouldRetry()` が存在するが、使用されていない

#### ケース3: メッセージ送信失敗（SocketClient.php）

```
SocketClient::sendRaw()
├─ fwrite() 失敗
├─ $this->connected = false
├─ throw new SocketTemporaryException('Failed to send message')
│
▼ キャッチ箇所
SocketEventDispatcher::dispatch()
├─ catch (Exception $e)
├─ error_log()
├─ $this->queue[] に追加 (メモリキュー)
└─ updateDatabaseStatus(..., 'FAILED', ...)
```

**検証結果:**
- ✅ **正常:** 失敗したメッセージがキューに保存される
- ✅ **正常:** データベースに失敗ステータスが記録される
- ⚠️ **問題:** メモリキューはプロセス終了時に消失
  - データベースからの再取得ロジックが必要

### 6.3 エラーコードの使用状況

**設計書 7.1 のエラーコード一覧:**

| エラーコード | 使用箇所 | 実装状況 |
|-------------|---------|---------|
| AUTH_REQUIRED | SocketServer.php | ✅ 実装済 |
| AUTH_FAILED | SocketServer.php | ✅ 実装済 |
| INVALID_MESSAGE | SocketServer.php | ❌ **未使用** (PROCESSING_ERROR を使用) |
| PROCESSING_ERROR | SocketServer.php | ✅ 実装済 |
| CONNECTION_TIMEOUT | - | ❌ **未実装** |
| SEND_FAILED | SocketClient.php | ✅ 実装済 (SocketTemporaryException) |
| RATE_LIMIT_EXCEEDED | SocketServer.php | ✅ 実装済 |

**問題点:**
- ⚠️ `CONNECTION_TIMEOUT` がエラーコード一覧にあるが、実装されていない
- ⚠️ `INVALID_MESSAGE` が定義されているが、実際は `PROCESSING_ERROR` を使用

### 6.4 SocketRetryStrategy の使用状況

**実装:**
```php
class SocketRetryStrategy {
    public static function getDelay($attempt) { ... }
    public static function shouldRetry($e) { ... }
}
```

**使用箇所:**
- ❌ **全く使用されていない**

**問題点:**
- ⚠️ `SocketRetryStrategy` が実装されているが、どこからも呼び出されていない
- ⚠️ `SocketClient::reconnect()` が固定遅延を使用
  - **推奨:** `SocketRetryStrategy::getDelay()` を使用するように変更:
    ```php
    sleep(SocketRetryStrategy::getDelay($this->reconnect_attempts));
    ```
- ⚠️ `shouldRetry()` の判定ロジックが活用されていない
  - **推奨:** `SocketEventDispatcher::processQueue()` で使用

---

## 7. データフロー詳細分析

### 7.1 コンバージョン発生時の完全フロー

```
[ユーザーアクション: コンバージョン完了]
    │
    ▼
1. add.php
   ├─ コンバージョン検証
   ├─ payテーブルに INSERT ($pay_rec)
   ├─ global.php の tier 計算ロジック実行
   │  └─ $_tierValue グローバル変数に tier 報酬データ格納
   │
   ▼
2. add.php - SocketEventDispatcher 統合コード
   ├─ require_once SocketEventDispatcher.php
   ├─ $dispatcher = SocketEventDispatcher::getInstance()
   ├─ $tier_rewards 配列を $_tierValue から構築
   │  └─ foreach ($_tierValue as $tier_level => $tier_data)
   │
   ▼
3. SocketEventDispatcher::dispatchConversion()
   ├─ $payload 配列を構築
   │  ├─ pay_id: $pay_data['id']
   │  ├─ access_id: $pay_data['access_id']
   │  ├─ adware_id: $pay_data['adwares']
   │  ├─ tier_rewards: $tier_rewards 配列
   │  └─ ... その他のフィールド
   │
   ├─ dispatch('conversion', $payload) を呼び出し
   │
   ▼
4. SocketEventDispatcher::dispatch()
   ├─ saveToDatabase('conversion', $payload)
   │  └─ socket_events テーブルに INSERT (status='PENDING')
   │
   ├─ initClient() → SocketClient の接続確立
   │  ├─ socketConf.php から設定読み込み
   │  ├─ new SocketClient([url, token, timeout, ...])
   │  └─ $client->connect()
   │
   ├─ $client->send('conversion', $payload)
   │  │
   │  ▼
   5. SocketClient::send()
      ├─ メッセージ構築:
      │  {
      │    "version": "1.0",
      │    "type": "conversion",
      │    "timestamp": "...",
      │    "message_id": "uuid",
      │    "sender": "CATS",
      │    "payload": { ... }
      │  }
      │
      ├─ sendRaw() → JSON エンコード
      ├─ encodeFrame() → WebSocket フレーム化
      │  ├─ オペコード: 0x81 (テキストフレーム)
      │  ├─ マスキング: 4バイトランダムキー
      │  └─ ペイロード XOR マスク
      │
      └─ fwrite($this->socket, $frame) → 送信
   │
   ├─ updateDatabaseStatus(..., 'SENT')
   │  └─ socket_events テーブル UPDATE (status='SENT', sent_at=NOW())
   │
   └─ return
    │
    ▼
6. Socket Gateway (SocketServer.php)
   ├─ ReactPHP EventLoop が接続を受信
   ├─ onMessage(ConnectionInterface $from, $msg)
   │  ├─ JSON デコード
   │  ├─ 認証チェック ($this->connections[$connection_id]['authenticated'])
   │  ├─ レート制限チェック ($rateLimiter->isAllowed())
   │  │
   │  ▼
   7. SocketMessageHandler::handle('conversion', $data, $connection_info)
      ├─ logMessage() → socket_messages テーブルに INSERT
      │  └─ (connection_id, direction='INBOUND', message_type='conversion', message_data=JSON, created_at)
      │
      ├─ handleConversion() 実行
      │  └─ return [
      │       'broadcast' => true,
      │       'message' => { type: 'conversion', payload: ..., timestamp: ... }
      │     ]
      │
      └─ return to SocketServer
   │
   ├─ broadcast($response['message'], $from)
   │  └─ 全ての認証済みクライアント (AFAD, Dashboard) へ送信
   │     ├─ foreach ($this->clients as $client)
   │     ├─ if ($client !== $from && authenticated)
   │     └─ $client->send(json_encode($message))
   │
   └─ AFAD側クライアントが受信
    │
    ▼
8. AFADシステム
   ├─ WebSocket メッセージ受信
   ├─ JSON デコード
   ├─ type='conversion' を検知
   ├─ payload からコンバージョンデータ抽出
   │  └─ pay_id, adware_id, cost, sales, tier_rewards 等
   │
   ├─ AFAD内部のレポートテーブル更新
   ├─ リアルタイムダッシュボード更新
   └─ 予算残高チェック → 必要に応じてアラート送信
```

**フロー検証結果:**
- ✅ **正常:** 全ステップが論理的につながっている
- ✅ **正常:** エラー時のフォールバック (DB保存 + キュー) が機能する
- ⚠️ **改善提案:** ステップ4で送信に失敗した場合、メモリキューに追加されるが、processQueue() がDBからの取得をしないため、プロセス終了時に失われる

### 7.2 広告更新時のフロー (AFAD → CATS)

```
[AFAD管理画面: 広告情報を更新]
    │
    ▼
1. AFADSocketClient (AFAD側実装)
   ├─ 広告更新イベント送信
   ├─ send('adware_update', {
   │     adware_id: 100,
   │     action: 'UPDATE',
   │     data: { name: '...', money: 1000, ... }
   │   })
   │
   └─ WebSocket経由で Gateway へ送信
    │
    ▼
2. Socket Gateway (SocketServer.php)
   ├─ onMessage() でメッセージ受信
   ├─ SocketMessageHandler::handle('adware_update', ...)
   │  ├─ logMessage() → socket_messages テーブルに記録
   │  │
   │  ▼
   3. SocketMessageHandler::handleAdwareUpdate()
      └─ return [
           'broadcast' => false,  ← CATS側のみに送信
           'type' => 'adware_update',
           'payload' => $data['payload'],
           'timestamp' => '...'
         ]
   │
   └─ $from->send(json_encode($response))
      └─ CATS側クライアント (SocketMessageReceiver) へ送信
    │
    ▼
4. CATS側 (SocketMessageReceiver.php)
   ├─ listen() ループで受信
   ├─ $message = $this->client->receive()
   ├─ processMessage($message)
   │  └─ type='adware_update' を検知
   │
   ▼
5. SocketMessageReceiver::handleAdwareUpdate()
   ├─ global $_db から adwares テーブル取得
   ├─ $adware_id = $payload['adware_id']
   ├─ $action = $payload['action']  // 'CREATE', 'UPDATE', 'DELETE'
   │
   ├─ switch ($action):
   │  ├─ case 'CREATE':
   │  │  └─ $adware_db->add($data)
   │  │
   │  ├─ case 'UPDATE':
   │  │  ├─ $adware_rec = $adware_db->select(['id' => $adware_id], 'first')
   │  │  ├─ foreach ($data as $key => $value)
   │  │  │     $adware_rec[$key] = $value
   │  │  └─ $adware_db->edit($adware_rec)
   │  │
   │  └─ case 'DELETE':
   │     ├─ $adware_rec = $adware_db->select(['id' => $adware_id], 'first')
   │     └─ $adware_db->remove($adware_rec)
   │
   └─ error_log("Adware {$action}: ID {$adware_id}")
```

**フロー検証結果:**
- ✅ **正常:** AFAD → Gateway → CATS の流れが明確
- ⚠️ **問題:** SocketMessageReceiver::listen() を常時実行するデーモンプロセスが必要
  - 現在の設計では、このプロセスの起動方法が明記されていない
  - **推奨:** systemd サービスまたは cron での定期実行を追加
- ⚠️ **問題:** `handleAdwareUpdate()` でデータベース操作が失敗した場合の例外処理がない
  - **推奨:** try-catch を追加し、エラー時に AFAD へ通知

---

## 8. 統合ポイント検証

### 8.1 add.php への統合

**設計書の統合コード:**
```php
require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';

if (isset($pay_rec['id']) && $pay_rec['id']) {
    $dispatcher = SocketEventDispatcher::getInstance();

    $tier_rewards = [];
    if (!empty($_tierValue)) {
        foreach ($_tierValue as $tier_level => $tier_data) {
            if (!empty($tier_data)) {
                $tier_rewards[] = [
                    'tier_level' => $tier_level,
                    'user_id' => $tier_data['user_id'] ?? 0,
                    'user_name' => $tier_data['user_name'] ?? '',
                    'rate' => $tier_data['rate'] ?? 0,
                    'amount' => $tier_data['amount'] ?? 0
                ];
            }
        }
    }

    $dispatcher->dispatchConversion(
        $pay_rec,
        $access_rec ?? [],
        $adwares_rec ?? [],
        $user_rec ?? [],
        $tier_rewards
    );
}
```

**検証項目:**

| 項目 | 検証内容 | 結果 |
|------|---------|------|
| **変数の存在** | `$pay_rec` が add.php に存在するか | ⚠️ 要確認 (add.php の実装による) |
| **変数の存在** | `$access_rec` が add.php に存在するか | ⚠️ 要確認 |
| **変数の存在** | `$adwares_rec` が add.php に存在するか | ⚠️ 要確認 |
| **変数の存在** | `$user_rec` が add.php に存在するか | ⚠️ 要確認 |
| **グローバル変数** | `$_tierValue` が global.php で定義されているか | ⚠️ 要確認 |
| **配列構造** | `$_tierValue` の構造が想定通りか | ⚠️ 要確認 |
| **挿入位置** | コンバージョン記録**後**に配置されているか | ✅ 設計上正しい |
| **非ブロッキング** | ソケット送信がメイン処理をブロックしないか | ✅ 正常 (例外キャッチ済み) |

**推奨事項:**
- add.php の実際の実装を確認し、変数名が一致しているか検証が必要
- `$_tierValue` の実際の構造を確認し、必要に応じてデータ変換ロジックを追加

### 8.2 link.php への統合

**設計書の統合コード:**
```php
require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';

if (isset($access_rec['id']) && $access_rec['id']) {
    $dispatcher = SocketEventDispatcher::getInstance();

    $dispatcher->dispatchClick(
        $access_rec,
        isset($click_pay_rec) ? $click_pay_rec : null
    );
}
```

**検証項目:**

| 項目 | 検証内容 | 結果 |
|------|---------|------|
| **変数の存在** | `$access_rec` が link.php に存在するか | ⚠️ 要確認 |
| **変数の存在** | `$click_pay_rec` が link.php に存在するか | ⚠️ 要確認 (クリック報酬がある場合のみ) |
| **挿入位置** | クリック記録**後**に配置されているか | ✅ 設計上正しい |
| **非ブロッキング** | ソケット送信がリダイレクトをブロックしないか | ✅ 正常 |

### 8.3 既存データベースとの互換性

**想定される既存テーブル:**

| テーブル | 使用箇所 | 必要なカラム | 検証結果 |
|---------|---------|------------|---------|
| **pay** | SocketEventDispatcher::dispatchConversion() | id, access_id, adwares, owner, cost, sales, state, regist | ⚠️ 要確認 |
| **access** | SocketEventDispatcher::dispatchClick() | id, adwares, owner, ipaddress, useragent, referer, regist | ⚠️ 要確認 |
| **adwares** | SocketMessageReceiver::handleAdwareUpdate() | id, name, money, click_money, continue_money, url, limits, open | ⚠️ 要確認 |
| **users** | SocketEventDispatcher::dispatchConversion() | name (user_recとして使用) | ⚠️ 要確認 |
| **tier** | *(参照のみ)* | *(dispatchConversion の tier_rewards)* | ⚠️ 要確認 |

**推奨事項:**
- 実際の CATS データベーススキーマを確認し、カラム名が一致しているか検証
- 既存のテーブル構造定義 (CSV ファイルや Schema.php) を参照

---

## 9. 発見された問題点と推奨事項

### 9.1 重大な問題（実装前に対応必須）

#### 問題1: processQueue() がデータベースから失敗イベントを取得しない

**現状:**
- `SocketEventDispatcher::processQueue()` はメモリ内キュー (`$this->queue`) のみ処理
- データベースに保存された FAILED/PENDING イベントが再処理されない

**影響:**
- プロセス終了後、失敗したイベントが永久に失われる
- 信頼性の要件を満たさない

**推奨修正:**
```php
public function processQueue() {
    global $_db;

    // 1. データベースから失敗イベントを取得
    if (isset($_db['socket_events'])) {
        $event_db = &$_db['socket_events'];
        $failed_events = $event_db->select([
            'status' => ['FAILED', 'PENDING'],
            'retry_count' => ['<', 5]  // 最大5回までリトライ
        ]);

        foreach ($failed_events as $event_rec) {
            $this->queue[] = [
                'type' => $event_rec['event_type'],
                'payload' => json_decode($event_rec['event_data'], true),
                'event_id' => $event_rec['id']  // DB更新用
            ];
        }
    }

    // 2. 既存のメモリキュー処理
    if (empty($this->queue)) {
        return;
    }

    // ... 既存の処理を継続
}
```

#### 問題2: SocketMessageReceiver の実行方法が未定義

**現状:**
- `SocketMessageReceiver::listen()` を常時実行するプロセスが必要だが、起動方法が設計書に記載なし

**影響:**
- AFAD → CATS 方向のメッセージを受信できない

**推奨対応:**

**オプション A: 専用デーモンプロセス**
```bash
# tools/socket_receiver_daemon.php
<?php
require_once dirname(__FILE__) . '/../include/extends/SocketClient.php';
require_once dirname(__FILE__) . '/../include/extends/SocketMessageReceiver.php';
require_once dirname(__FILE__) . '/../custom/extends/socketConf.php';

$client = new SocketClient([
    'url' => $SOCKET_CONF['url'],
    'token' => $SOCKET_CONF['token'],
    'timeout' => $SOCKET_CONF['timeout'],
    'auto_reconnect' => true
]);

$client->connect();

$receiver = new SocketMessageReceiver($client);
$receiver->listen();  // 無限ループ
```

**systemd サービス設定:**
```ini
# /etc/systemd/system/socket-receiver.service
[Unit]
Description=CATS Socket Message Receiver
After=network.target

[Service]
Type=simple
User=www-data
ExecStart=/usr/bin/php /home/user/ASP-ORKA/tools/socket_receiver_daemon.php
Restart=always

[Install]
WantedBy=multi-user.target
```

**オプション B: cron による定期実行**
```bash
# 1分ごとに1回だけ受信チェック
* * * * * /usr/bin/php /home/user/ASP-ORKA/tools/socket_receiver_once.php
```

#### 問題3: last_heartbeat の更新ロジックがない

**現状:**
- `socket_connections` テーブルの `last_heartbeat` カラムが更新されない

**影響:**
- 接続の生存確認ができない
- タイムアウト検出ができない

**推奨修正:**
```php
// SocketServer.php の onMessage() 内に追加
if ($message_type === 'ping') {
    // last_heartbeat を更新
    $this->authenticator->updateHeartbeat($connection_id);
    $this->sendPong($from);
    return;
}
```

```php
// SocketAuthenticator.php に追加
public function updateHeartbeat($connection_id) {
    if (!isset($this->db['socket_connections'])) {
        return;
    }

    $conn_db = &$this->db['socket_connections'];
    $conn_rec = $conn_db->select(['connection_id' => $connection_id], 'first');

    if ($conn_rec) {
        $conn_rec['last_heartbeat'] = date('Y-m-d H:i:s');
        $conn_db->edit($conn_rec);
    }
}
```

#### 問題4: SocketRetryStrategy が全く使用されていない

**現状:**
- 実装されているが、どこからも呼び出されていない

**推奨修正:**

**SocketClient.php:**
```php
// require を追加
require_once dirname(__FILE__) . '/SocketRetryStrategy.php';

// reconnect() メソッドを修正
private function reconnect() {
    if ($this->reconnect_attempts >= $this->max_reconnect_attempts) {
        throw new SocketException('Max reconnection attempts exceeded', 'MAX_RECONNECT_EXCEEDED');
    }

    $this->reconnect_attempts++;

    // SocketRetryStrategy を使用
    $delay = SocketRetryStrategy::getDelay($this->reconnect_attempts);
    sleep($delay);

    $this->connect();
}
```

**SocketEventDispatcher.php:**
```php
// processQueue() で shouldRetry() を使用
foreach ($this->queue as $index => $item) {
    try {
        $this->client->send($item['type'], $item['payload']);
        // ... 成功処理
    } catch (Exception $e) {
        if (SocketRetryStrategy::shouldRetry($e)) {
            error_log('Queue processing failed (will retry): ' . $e->getMessage());
            // retry_count を増やす
        } else {
            error_log('Queue processing failed (non-retryable): ' . $e->getMessage());
            $processed[] = $index;  // キューから削除
        }
        break;
    }
}
```

### 9.2 中程度の問題（実装後の改善推奨）

#### 問題5: Gateway が CATS の Initialize.php に依存

**現状:**
- `SocketMessageHandler.php` と `SocketAuthenticator.php` が `Initialize.php` を require
- Gateway を別サーバーに配置した場合、CATS の DB に直接アクセスできない

**推奨対応:**
- Gateway 側で独立した DB 接続を構築
- または、Gateway を CATS と同じサーバーに配置し、共有 DB 接続を使用

#### 問題6: 環境変数の命名に一貫性がない

**現状:**
- `SOCKET_SERVER_URL` が .env.example に定義されているが、socketConf.php では 'url' を直接記述
- `SOCKET_ENABLED` と socketConf.php の 'enabled' が重複

**推奨対応:**
- socketConf.php を環境変数優先に変更
- 使用しない環境変数を削除

#### 問題7: 設計書に記載されているがイベントタイプが未実装

**未実装イベント:**
- `stats_update`
- `user_online`

**推奨対応:**
- 実装予定がない場合は設計書から削除
- 実装する場合は SocketEventDispatcher にメソッドを追加

### 9.3 軽微な問題（任意対応）

#### 問題8: socketConf.php の未使用設定

**未使用設定:**
- `heartbeat_interval`
- `queue_process_interval`
- `debug`
- `log_file`

**推奨対応:**
- 使用予定がない場合は削除
- または、実装を追加

#### 問題9: click イベントの payload 仕様が設計書に記載なし

**推奨対応:**
- 設計書の 4.2 セクションに click イベントの payload 定義を追加

#### 問題10: OUTBOUND メッセージのログ記録がない

**現状:**
- `socket_messages` テーブルに INBOUND メッセージのみ記録

**推奨対応:**
- SocketServer の broadcast() や unicast 送信時に OUTBOUND ログを記録

---

## 10. 実装チェックリスト（優先順位付き）

### 10.1 クリティカル（実装前に必須）

- [ ] **問題1対応:** processQueue() に DB からの失敗イベント取得ロジックを追加
- [ ] **問題2対応:** SocketMessageReceiver の実行方法を決定し、デーモンまたは cron を設定
- [ ] **問題3対応:** last_heartbeat の更新ロジックを実装
- [ ] **問題4対応:** SocketRetryStrategy を実際に使用するコードに修正
- [ ] **検証:** add.php と link.php の実際の変数名を確認し、統合コードを調整
- [ ] **検証:** CATS の既存テーブル構造を確認し、カラム名の整合性を検証

### 10.2 重要（早期対応推奨）

- [ ] **問題5対応:** Gateway の DB 接続方法を決定
- [ ] **問題6対応:** 環境変数の命名を統一
- [ ] **問題7対応:** 未実装イベントタイプの方針決定
- [ ] add.php への統合コード実装
- [ ] link.php への統合コード実装
- [ ] マイグレーションスクリプト実行とテーブル作成

### 10.3 推奨（リリース前に対応）

- [ ] **問題8対応:** 未使用設定の削除または実装
- [ ] **問題9対応:** click イベントの payload 仕様を設計書に追加
- [ ] **問題10対応:** OUTBOUND メッセージのログ記録を追加
- [ ] WebSocket フレーム処理のユニットテスト作成
- [ ] 統合テスト環境の構築

---

## まとめ

### 全体評価

✅ **優れている点:**
- 全体的なアーキテクチャ設計が論理的で一貫性がある
- コンポーネントの責務分離が明確
- WebSocket の RFC 6455 準拠実装
- エラーハンドリングの基本構造が整っている
- データベース永続化による信頼性確保の仕組み

⚠️ **改善が必要な点:**
- データベースからのイベント再取得ロジックが欠落
- SocketMessageReceiver の実行方法が未定義
- 一部の設計要素が実装されていない (SocketRetryStrategy の未使用等)
- 既存 CATS コードとの統合部分の検証が未完了

### 推奨アクション

1. **即座に対応すべき事項 (クリティカル):**
   - processQueue() の DB 連携実装
   - SocketMessageReceiver デーモンの起動設計
   - 既存 CATS コード (add.php, link.php) の実装確認

2. **実装開始前に対応すべき事項:**
   - Gateway の DB 接続方式の決定
   - 環境変数の命名統一
   - SocketRetryStrategy の使用実装

3. **実装後のテストで確認すべき事項:**
   - エンドツーエンドのメッセージフロー
   - 失敗時のリトライ動作
   - 長時間接続の安定性

---

**作成者:** Claude
**レビュー状況:** 初版作成完了
**最終更新:** 2025-10-29
