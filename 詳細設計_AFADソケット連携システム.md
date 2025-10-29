# CATSシステム - AFADソケット連携システム 完全設計書

**バージョン:** 2.0 (完全版)
**作成日:** 2025-10-29
**対象ブランチ:** `claude/cats-afad-socket-integration-011CUZuJVtvVu9LFctZqVLqc`

---

## 目次

1. [概要](#1-概要)
2. [システム要件](#2-システム要件)
3. [アーキテクチャ設計](#3-アーキテクチャ設計)
4. [ソケット通信仕様](#4-ソケット通信仕様)
5. [実装設計](#5-実装設計)
6. [データフロー](#6-データフロー)
7. [エラーハンドリング](#7-エラーハンドリング)
8. [セキュリティ設計](#8-セキュリティ設計)
9. [デプロイ・運用](#9-デプロイ運用)
10. [実装計画](#10-実装計画)
11. [テスト計画](#11-テスト計画)
12. [実装チェックリスト](#12-実装チェックリスト)

---

## 1. 概要

### 1.1 目的

CATSアフィリエイトシステムと外部AFAD（Affiliate Advertising）システムとのリアルタイムソケット通信を実現し、以下の機能を提供する：

- **リアルタイムコンバージョン通知**
- **双方向イベント連携**
- **非同期データ同期**
- **WebSocketベースのダッシュボード更新**

### 1.2 現状分析

#### 既存の通信機能
```
/home/user/ASP-ORKA/include/extends/HttpUtil.php
```

- `fsockopen()` による同期HTTP通信のみ
- タイムアウト設定: 送信2秒、受信4秒
- HTTPS対応が不十分
- エラーハンドリングが基本的

#### 課題
1. 同期通信のため、外部システム遅延時にレスポンスが遅くなる
2. リアルタイム通知ができない
3. 双方向通信が不可能
4. 接続プールや再利用の仕組みがない

### 1.3 目標

| 項目 | 目標値 |
|------|--------|
| **接続確立時間** | < 500ms |
| **メッセージ遅延** | < 100ms |
| **同時接続数** | 1,000+ |
| **メッセージスループット** | 10,000 msg/sec |
| **可用性** | 99.9% |

---

## 2. システム要件

### 2.1 機能要件

#### FR-1: WebSocketサーバー
- [ ] PHP WebSocketサーバーの実装
- [ ] クライアント認証機能
- [ ] ハートビート（Ping/Pong）による接続維持
- [ ] 自動再接続機能

#### FR-2: イベント通知
- [ ] コンバージョン発生時の即座通知
- [ ] ティア報酬計算結果の通知
- [ ] 広告予算残高アラート
- [ ] 不正検知アラート

#### FR-3: データ同期
- [ ] AFAD側の広告マスタ更新の受信
- [ ] CATS側のコンバージョンデータ送信
- [ ] バッチ同期のフォールバック機能

#### FR-4: リアルタイムダッシュボード
- [ ] 管理者ダッシュボードへのリアルタイム更新
- [ ] アフィリエイター向けリアルタイム成果表示

### 2.2 非機能要件

#### NFR-1: パフォーマンス
- メッセージ配信遅延: 100ms以内
- 同時接続: 1,000クライアント以上
- CPU使用率: 70%以下

#### NFR-2: 可用性
- サービス稼働率: 99.9%
- 自動フェイルオーバー
- グレースフルシャットダウン

#### NFR-3: スケーラビリティ
- 水平スケーリング対応
- メッセージキューイング
- 接続プール管理

#### NFR-4: セキュリティ
- TLS/SSL暗号化（wss://）
- トークンベース認証
- IPホワイトリスト
- レート制限

---

## 3. アーキテクチャ設計

### 3.1 システム構成図

```
┌─────────────────────────────────────────────────────────────────┐
│                         CATS システム                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐         ┌──────────────┐                     │
│  │  link.php    │         │   add.php    │                     │
│  │ (クリック追跡) │◄────────┤(コンバージョン)│                     │
│  └──────┬───────┘         └──────┬───────┘                     │
│         │                        │                              │
│         │  ┌─────────────────────▼─────────────────┐           │
│         │  │   SocketEventDispatcher.php           │           │
│         │  │   - イベント検知                       │           │
│         │  │   - メッセージキューイング              │           │
│         │  │   - 非同期送信                         │           │
│         │  └─────────────┬───────────────────────┘           │
│         │                │                                     │
│         └────────────────┤                                     │
│                          │                                     │
│         ┌────────────────▼────────────────┐                   │
│         │   SocketClient.php               │                   │
│         │   - WebSocket クライアント        │                   │
│         │   - 再接続ロジック                │                   │
│         │   - メッセージ送受信               │                   │
│         └────────────────┬────────────────┘                   │
│                          │                                     │
│         ┌────────────────▼────────────────┐                   │
│         │   SocketMessageReceiver.php      │                   │
│         │   - AFAD受信処理                 │                   │
│         │   - adwares更新                  │                   │
│         └──────────────────────────────────┘                   │
│                          │                                     │
└──────────────────────────┼─────────────────────────────────────┘
                           │
                           │ WebSocket (wss://)
                           │ JSON メッセージング
                           │
┌──────────────────────────▼─────────────────────────────────────┐
│                    Socket Gateway                               │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  SocketServer.php (Ratchet/ReactPHP)                     │  │
│  │  - WebSocketサーバー                                      │  │
│  │  - 接続管理                                               │  │
│  │  - ルーティング                                           │  │
│  │  - 認証・認可                                             │  │
│  └──────────────┬───────────────────────────────────────────┘  │
│                 │                                               │
│  ┌──────────────▼───────────────────────────────────────────┐  │
│  │  SocketMessageHandler.php                                │  │
│  │  - メッセージ検証                                         │  │
│  │  - ビジネスロジック呼び出し                                │  │
│  │  - レスポンス生成                                         │  │
│  └──────────────┬───────────────────────────────────────────┘  │
│                 │                                               │
│  ┌──────────────▼───────────────────────────────────────────┐  │
│  │  SocketLogger.php                                        │  │
│  │  - 通信ログ記録                                           │  │
│  │  - メトリクス収集                                         │  │
│  └──────────────┬───────────────────────────────────────────┘  │
│                 │                                               │
│  ┌──────────────▼───────────────────────────────────────────┐  │
│  │  SocketRateLimiter.php                                   │  │
│  │  - レート制限                                             │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           │ WebSocket (wss://)
                           │ JSON メッセージング
                           │
┌──────────────────────────▼─────────────────────────────────────┐
│                      AFAD システム                              │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  AFADSocketClient.php                                    │  │
│  │  - WebSocket クライアント                                 │  │
│  │  - イベント送受信                                         │  │
│  │  - データ同期                                             │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  広告配信システム                                         │  │
│  │  - 広告マスタ管理                                         │  │
│  │  - 予算管理                                               │  │
│  │  - レポート生成                                           │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 コンポーネント設計

#### 3.2.1 CATS側コンポーネント

| コンポーネント | 責務 | ファイルパス |
|---------------|------|-------------|
| **SocketEventDispatcher** | イベント検知・キューイング | `/include/extends/SocketEventDispatcher.php` |
| **SocketClient** | WebSocketクライアント | `/include/extends/SocketClient.php` |
| **SocketMessageReceiver** | AFAD受信処理 | `/include/extends/SocketMessageReceiver.php` |
| **SocketConfig** | 設定管理 | `/custom/extends/socketConf.php` |
| **SocketException** | 例外処理 | `/include/extends/Exception/SocketException.php` |
| **SocketRetryStrategy** | リトライ戦略 | `/include/extends/SocketRetryStrategy.php` |

#### 3.2.2 Gateway コンポーネント

| コンポーネント | 責務 | ファイルパス |
|---------------|------|-------------|
| **SocketServer** | WebSocketサーバー | `/socket/SocketServer.php` |
| **SocketMessageHandler** | メッセージ処理 | `/socket/SocketMessageHandler.php` |
| **SocketAuthenticator** | 認証処理 | `/socket/SocketAuthenticator.php` |
| **SocketLogger** | ログ記録 | `/socket/SocketLogger.php` |
| **SocketRateLimiter** | レート制限 | `/socket/SocketRateLimiter.php` |

#### 3.2.3 デプロイ・運用スクリプト

| スクリプト | 責務 | ファイルパス |
|-----------|------|-------------|
| **マイグレーション** | DBスキーマ作成 | `/migration/002_create_socket_tables.php` |
| **キュー処理** | バッチ処理 | `/tools/process_socket_queue.php` |
| **サーバー起動** | Gateway起動 | `/socket/start_server.sh` |
| **systemd設定** | サービス管理 | `/deployment/socket-server.service` |

### 3.3 データベース拡張

#### 新規テーブル: `socket_events`

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

#### 新規テーブル: `socket_connections`

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

#### 新規テーブル: `socket_messages`

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

---

## 4. ソケット通信仕様

### 4.1 接続フロー

```
Client (CATS/AFAD)                    Gateway (SocketServer)
    │                                        │
    ├──────── WebSocket Handshake ──────────>│
    │                                        │
    │<─────── 101 Switching Protocols ───────┤
    │                                        │
    ├──────── AUTH Message ─────────────────>│
    │         {                              │
    │           "type": "auth",              │
    │           "token": "xxx",              │
    │           "client_type": "CATS"        │
    │         }                              │
    │                                        │
    │<─────── AUTH_SUCCESS ──────────────────┤
    │         {                              │
    │           "type": "auth_success",      │
    │           "connection_id": "xxx"       │
    │         }                              │
    │                                        │
    ├──────── PING ─────────────────────────>│
    │                                        │
    │<─────── PONG ──────────────────────────┤
    │                                        │
    │       (30秒ごとにハートビート)          │
    │                                        │
```

### 4.2 メッセージフォーマット

#### 基本構造

```json
{
    "version": "1.0",
    "type": "event_type",
    "timestamp": "2025-10-29T12:34:56Z",
    "message_id": "uuid-v4",
    "sender": "CATS",
    "payload": {
        // イベント固有のデータ
    }
}
```

#### 4.2.1 コンバージョン通知（CATS → AFAD）

```json
{
    "version": "1.0",
    "type": "conversion",
    "timestamp": "2025-10-29T12:34:56Z",
    "message_id": "550e8400-e29b-41d4-a716-446655440000",
    "sender": "CATS",
    "payload": {
        "pay_id": 12345,
        "access_id": 67890,
        "adware_id": 100,
        "adware_name": "商品A",
        "owner_id": 500,
        "owner_name": "アフィリエイターX",
        "cost": 1000,
        "sales": 5000,
        "state": "PENDING",
        "tier_rewards": [
            {
                "tier_level": 1,
                "user_id": 499,
                "user_name": "親アフィリエイター",
                "rate": 10,
                "amount": 100
            }
        ],
        "created_at": "2025-10-29T12:34:56Z"
    }
}
```

#### 4.2.2 広告更新通知（AFAD → CATS）

```json
{
    "version": "1.0",
    "type": "adware_update",
    "timestamp": "2025-10-29T12:34:56Z",
    "message_id": "550e8400-e29b-41d4-a716-446655440001",
    "sender": "AFAD",
    "payload": {
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
}
```

### 4.3 イベントタイプ一覧

| イベントタイプ | 方向 | 説明 |
|---------------|------|------|
| `auth` | Client → Gateway | 認証リクエスト |
| `auth_success` | Gateway → Client | 認証成功 |
| `auth_failed` | Gateway → Client | 認証失敗 |
| `ping` | Bidirectional | ハートビート |
| `pong` | Bidirectional | ハートビート応答 |
| `conversion` | CATS → AFAD | コンバージョン通知 |
| `click` | CATS → AFAD | クリック通知 |
| `tier_reward` | CATS → AFAD | ティア報酬計算完了 |
| `adware_update` | AFAD → CATS | 広告情報更新 |
| `budget_alert` | CATS → AFAD | 予算アラート |
| `fraud_alert` | CATS → AFAD | 不正検知アラート |
| `stats_update` | CATS → Dashboard | リアルタイム統計 |
| `user_online` | CATS → Dashboard | ユーザーオンライン通知 |
| `error` | Bidirectional | エラー通知 |

---

## 5. 実装設計

### 5.1 例外クラス群

#### 5.1.1 SocketException.php

```php
<?php
/**
 * Socket基底例外クラス
 *
 * @package include/extends/Exception
 */

class SocketException extends Exception {

    protected $error_code;

    public function __construct($message = "", $error_code = "SOCKET_ERROR", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->error_code = $error_code;
    }

    public function getErrorCode() {
        return $this->error_code;
    }
}
```

#### 5.1.2 SocketAuthException.php

```php
<?php
/**
 * Socket認証例外
 *
 * @package include/extends/Exception
 */

require_once dirname(__FILE__) . '/SocketException.php';

class SocketAuthException extends SocketException {

    public function __construct($message = "Authentication failed", $code = 0, Throwable $previous = null) {
        parent::__construct($message, "AUTH_FAILED", $code, $previous);
    }
}
```

#### 5.1.3 SocketTemporaryException.php

```php
<?php
/**
 * Socket一時的エラー例外
 *
 * @package include/extends/Exception
 */

require_once dirname(__FILE__) . '/SocketException.php';

class SocketTemporaryException extends SocketException {

    public function __construct($message = "Temporary error", $error_code = "TEMPORARY_ERROR", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $error_code, $code, $previous);
    }
}
```

### 5.2 SocketClient.php（CATS側クライアント）

```php
<?php
/**
 * WebSocketクライアント
 *
 * @package include/extends
 * @version 1.0
 */

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/Exception/SocketException.php';
require_once dirname(__FILE__) . '/Exception/SocketTemporaryException.php';

class SocketClient {

    private $config;
    private $socket;
    private $connected = false;
    private $connection_id;
    private $reconnect_attempts = 0;
    private $max_reconnect_attempts = 5;
    private $reconnect_delay = 2;

    public function __construct($config) {
        $this->config = array_merge([
            'url' => '',
            'token' => '',
            'timeout' => 10,
            'auto_reconnect' => true,
            'client_type' => 'CATS'
        ], $config);

        if (empty($this->config['url'])) {
            throw new SocketException('WebSocket URL is required');
        }

        $this->max_reconnect_attempts = $this->config['max_reconnect_attempts'] ?? 5;
    }

    public function connect() {
        $url_parts = parse_url($this->config['url']);

        $host = $url_parts['host'];
        $port = isset($url_parts['port']) ? $url_parts['port'] : 443;
        $path = isset($url_parts['path']) ? $url_parts['path'] : '/';
        $scheme = $url_parts['scheme'];

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            ]
        ]);

        $socket_url = ($scheme === 'wss' ? 'ssl://' : '') . $host . ':' . $port;

        $this->socket = @stream_socket_client(
            $socket_url,
            $errno,
            $errstr,
            $this->config['timeout'],
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new SocketTemporaryException("Connection failed: {$errstr} ({$errno})", "CONNECTION_FAILED");
        }

        $this->performHandshake($host, $path);
        $this->authenticate();

        $this->connected = true;
        $this->reconnect_attempts = 0;

        return true;
    }

    private function performHandshake($host, $path) {
        $key = base64_encode(openssl_random_pseudo_bytes(16));

        $headers = "GET {$path} HTTP/1.1\r\n";
        $headers .= "Host: {$host}\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Key: {$key}\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "\r\n";

        fwrite($this->socket, $headers);

        $response = '';
        while (($line = fgets($this->socket)) !== false) {
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        if (strpos($response, '101 Switching Protocols') === false) {
            throw new SocketException('WebSocket handshake failed', 'HANDSHAKE_FAILED');
        }
    }

    private function authenticate() {
        $auth_message = [
            'type' => 'auth',
            'token' => $this->config['token'],
            'client_type' => $this->config['client_type']
        ];

        $this->sendRaw($auth_message);
        $response = $this->receiveRaw();

        if (!$response || $response['type'] !== 'auth_success') {
            throw new SocketAuthException('Authentication failed');
        }

        $this->connection_id = $response['connection_id'];
    }

    public function send($type, $payload) {
        if (!$this->connected) {
            if ($this->config['auto_reconnect']) {
                $this->reconnect();
            } else {
                throw new SocketException('Not connected to WebSocket server', 'NOT_CONNECTED');
            }
        }

        $message = [
            'version' => '1.0',
            'type' => $type,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'message_id' => $this->generateUUID(),
            'sender' => 'CATS',
            'payload' => $payload
        ];

        return $this->sendRaw($message);
    }

    private function sendRaw($message) {
        $json = json_encode($message);
        $frame = $this->encodeFrame($json);

        $written = @fwrite($this->socket, $frame);

        if ($written === false) {
            $this->connected = false;
            throw new SocketTemporaryException('Failed to send message', 'SEND_FAILED');
        }

        return true;
    }

    public function receive() {
        if (!$this->connected) {
            return null;
        }

        return $this->receiveRaw();
    }

    private function receiveRaw() {
        $frame = $this->decodeFrame();

        if (!$frame) {
            return null;
        }

        return json_decode($frame, true);
    }

    private function encodeFrame($data) {
        $length = strlen($data);
        $frame = chr(0x81);

        if ($length <= 125) {
            $frame .= chr($length | 0x80);
        } elseif ($length <= 65535) {
            $frame .= chr(126 | 0x80);
            $frame .= pack('n', $length);
        } else {
            $frame .= chr(127 | 0x80);
            $frame .= pack('J', $length);
        }

        $mask = openssl_random_pseudo_bytes(4);
        $frame .= $mask;

        for ($i = 0; $i < $length; $i++) {
            $frame .= $data[$i] ^ $mask[$i % 4];
        }

        return $frame;
    }

    private function decodeFrame() {
        $header = @fread($this->socket, 2);

        if (strlen($header) < 2) {
            return null;
        }

        $byte1 = ord($header[0]);
        $byte2 = ord($header[1]);

        $opcode = $byte1 & 0x0F;
        $masked = ($byte2 & 0x80) !== 0;
        $length = $byte2 & 0x7F;

        if ($opcode === 0x08) {
            $this->connected = false;
            return null;
        }

        if ($opcode === 0x09) {
            $this->sendPong();
            return $this->decodeFrame();
        }

        if ($length === 126) {
            $extended = fread($this->socket, 2);
            $length = unpack('n', $extended)[1];
        } elseif ($length === 127) {
            $extended = fread($this->socket, 8);
            $length = unpack('J', $extended)[1];
        }

        if ($masked) {
            $mask = fread($this->socket, 4);
        }

        $data = '';
        $remaining = $length;
        while ($remaining > 0) {
            $chunk = fread($this->socket, $remaining);
            if ($chunk === false) {
                break;
            }
            $data .= $chunk;
            $remaining -= strlen($chunk);
        }

        if ($masked) {
            for ($i = 0; $i < strlen($data); $i++) {
                $data[$i] = $data[$i] ^ $mask[$i % 4];
            }
        }

        return $data;
    }

    private function sendPong() {
        $frame = chr(0x8A) . chr(0x00);
        fwrite($this->socket, $frame);
    }

    private function reconnect() {
        if ($this->reconnect_attempts >= $this->max_reconnect_attempts) {
            throw new SocketException('Max reconnection attempts exceeded', 'MAX_RECONNECT_EXCEEDED');
        }

        $this->reconnect_attempts++;
        sleep($this->reconnect_delay * $this->reconnect_attempts);

        $this->connect();
    }

    public function close() {
        if ($this->socket) {
            $frame = chr(0x88) . chr(0x00);
            @fwrite($this->socket, $frame);

            fclose($this->socket);
            $this->connected = false;
        }
    }

    private function generateUUID() {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function isConnected() {
        return $this->connected;
    }

    public function __destruct() {
        $this->close();
    }
}
```

### 5.3 SocketEventDispatcher.php

```php
<?php
/**
 * Socket イベントディスパッチャー
 *
 * @package include/extends
 * @version 1.0
 */

require_once dirname(__FILE__) . '/SocketClient.php';
require_once dirname(__FILE__) . '/../Util.php';

class SocketEventDispatcher {

    private static $instance = null;
    private $client = null;
    private $config = null;
    private $enabled = false;
    private $queue = [];

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $conf_file = dirname(__FILE__) . '/../../custom/extends/socketConf.php';
        if (file_exists($conf_file)) {
            require_once $conf_file;
            if (isset($SOCKET_CONF)) {
                $this->config = $SOCKET_CONF;
                $this->enabled = isset($this->config['enabled']) ? $this->config['enabled'] : false;
            }
        }
    }

    private function initClient() {
        if ($this->client === null && $this->enabled && $this->config) {
            try {
                $this->client = new SocketClient([
                    'url' => $this->config['url'],
                    'token' => $this->config['token'],
                    'timeout' => $this->config['timeout'] ?? 10,
                    'auto_reconnect' => $this->config['auto_reconnect'] ?? true,
                    'max_reconnect_attempts' => $this->config['max_reconnect_attempts'] ?? 5
                ]);
                $this->client->connect();
            } catch (Exception $e) {
                error_log('SocketClient initialization failed: ' . $e->getMessage());
                $this->client = null;
            }
        }
    }

    public function dispatchConversion($pay_data, $access_data, $adware_data, $user_data, $tier_rewards = []) {
        if (!$this->enabled) {
            return;
        }

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

        $this->dispatch('conversion', $payload);
    }

    public function dispatchClick($access_data, $click_pay_data = null) {
        if (!$this->enabled) {
            return;
        }

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

        $this->dispatch('click', $payload);
    }

    public function dispatchTierReward($tier_data) {
        if (!$this->enabled) {
            return;
        }

        $payload = [
            'tier_id' => $tier_data['id'],
            'owner_id' => $tier_data['owner'],
            'tier_level' => (int)$tier_data['tier'],
            'adware_id' => $tier_data['adwares'],
            'cost' => (int)$tier_data['cost'],
            'tier1_amount' => (int)$tier_data['tier1'],
            'tier2_amount' => (int)$tier_data['tier2'],
            'tier3_amount' => (int)$tier_data['tier3'],
            'created_at' => $tier_data['regist']
        ];

        $this->dispatch('tier_reward', $payload);
    }

    public function dispatchBudgetAlert($adware_id, $current_budget, $limit) {
        if (!$this->enabled) {
            return;
        }

        $payload = [
            'adware_id' => $adware_id,
            'current_budget' => (int)$current_budget,
            'limit' => (int)$limit,
            'usage_percentage' => round(($current_budget / $limit) * 100, 2)
        ];

        $this->dispatch('budget_alert', $payload);
    }

    public function dispatchFraudAlert($fraud_type, $details) {
        if (!$this->enabled) {
            return;
        }

        $payload = [
            'fraud_type' => $fraud_type,
            'details' => $details,
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        $this->dispatch('fraud_alert', $payload);
    }

    private function dispatch($type, $payload) {
        $this->saveToDatabase($type, $payload);

        try {
            $this->initClient();

            if ($this->client && $this->client->isConnected()) {
                $this->client->send($type, $payload);
                $this->updateDatabaseStatus($type, $payload, 'SENT');
            } else {
                $this->queue[] = ['type' => $type, 'payload' => $payload];
            }
        } catch (Exception $e) {
            error_log('Socket dispatch failed: ' . $e->getMessage());
            $this->queue[] = ['type' => $type, 'payload' => $payload];
            $this->updateDatabaseStatus($type, $payload, 'FAILED', $e->getMessage());
        }
    }

    private function saveToDatabase($type, $payload) {
        global $_db;

        if (!isset($_db['socket_events'])) {
            return;
        }

        $event_db = &$_db['socket_events'];

        $event_rec = [
            'event_type' => $type,
            'event_data' => json_encode($payload),
            'target_system' => 'AFAD',
            'status' => 'PENDING',
            'retry_count' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $event_db->add($event_rec);
    }

    private function updateDatabaseStatus($type, $payload, $status, $error_message = null) {
        global $_db;

        if (!isset($_db['socket_events'])) {
            return;
        }

        $event_db = &$_db['socket_events'];
        $payload_json = json_encode($payload);

        $event_rec = $event_db->select([
            'event_type' => $type,
            'event_data' => $payload_json,
            'status' => 'PENDING'
        ], 'first');

        if ($event_rec) {
            $event_rec['status'] = $status;
            $event_rec['sent_at'] = date('Y-m-d H:i:s');
            if ($error_message) {
                $event_rec['error_message'] = $error_message;
            }
            $event_db->edit($event_rec);
        }
    }

    public function processQueue() {
        if (empty($this->queue)) {
            return;
        }

        $this->initClient();

        if (!$this->client || !$this->client->isConnected()) {
            return;
        }

        $processed = [];

        foreach ($this->queue as $index => $item) {
            try {
                $this->client->send($item['type'], $item['payload']);
                $this->updateDatabaseStatus($item['type'], $item['payload'], 'SENT');
                $processed[] = $index;
            } catch (Exception $e) {
                error_log('Queue processing failed: ' . $e->getMessage());
                break;
            }
        }

        foreach (array_reverse($processed) as $index) {
            array_splice($this->queue, $index, 1);
        }
    }

    public function __destruct() {
        if ($this->client) {
            $this->client->close();
        }
    }
}
```

### 5.4 SocketMessageReceiver.php（AFAD受信処理）

```php
<?php
/**
 * Socket メッセージ受信処理
 *
 * AFADからの広告更新などを受信して処理
 *
 * @package include/extends
 * @version 1.0
 */

require_once dirname(__FILE__) . '/SocketClient.php';

class SocketMessageReceiver {

    private $client;
    private $handlers = [];

    public function __construct($client) {
        $this->client = $client;
        $this->registerDefaultHandlers();
    }

    private function registerDefaultHandlers() {
        $this->registerHandler('adware_update', [$this, 'handleAdwareUpdate']);
        $this->registerHandler('budget_update', [$this, 'handleBudgetUpdate']);
    }

    public function registerHandler($message_type, $callback) {
        $this->handlers[$message_type] = $callback;
    }

    public function listen() {
        while ($this->client->isConnected()) {
            $message = $this->client->receive();

            if ($message) {
                $this->processMessage($message);
            }

            usleep(100000); // 100ms待機
        }
    }

    private function processMessage($message) {
        $type = $message['type'] ?? 'unknown';

        if (isset($this->handlers[$type])) {
            try {
                call_user_func($this->handlers[$type], $message);
            } catch (Exception $e) {
                error_log("Message handler error for type {$type}: " . $e->getMessage());
            }
        }
    }

    private function handleAdwareUpdate($message) {
        global $_db;

        if (!isset($_db['adwares'])) {
            return;
        }

        $adware_db = &$_db['adwares'];
        $payload = $message['payload'];

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
                $adware_rec = $adware_db->select(['id' => $adware_id], 'first');
                if ($adware_rec) {
                    foreach ($data as $key => $value) {
                        $adware_rec[$key] = $value;
                    }
                    $adware_db->edit($adware_rec);
                }
                break;

            case 'DELETE':
                $adware_rec = $adware_db->select(['id' => $adware_id], 'first');
                if ($adware_rec) {
                    $adware_db->remove($adware_rec);
                }
                break;
        }

        error_log("Adware {$action}: ID {$adware_id}");
    }

    private function handleBudgetUpdate($message) {
        global $_db;

        if (!isset($_db['adwares'])) {
            return;
        }

        $adware_db = &$_db['adwares'];
        $payload = $message['payload'];

        $adware_id = $payload['adware_id'];
        $new_limit = $payload['limit'];

        $adware_rec = $adware_db->select(['id' => $adware_id], 'first');
        if ($adware_rec) {
            $adware_rec['limits'] = $new_limit;
            $adware_db->edit($adware_rec);
        }

        error_log("Budget update: Adware ID {$adware_id}, New limit {$new_limit}");
    }
}
```

### 5.5 SocketRetryStrategy.php

```php
<?php
/**
 * Socket リトライ戦略
 *
 * @package include/extends
 */

class SocketRetryStrategy {

    public static function getDelay($attempt) {
        $base_delay = 2;
        $max_delay = 60;

        $delay = min($base_delay * pow(2, $attempt - 1), $max_delay);

        $jitter = $delay * 0.2 * (mt_rand(80, 120) / 100);

        return (int)($delay + $jitter);
    }

    public static function shouldRetry($e) {
        if ($e instanceof SocketAuthException) {
            return false;
        }

        if ($e instanceof SocketTemporaryException) {
            return true;
        }

        return true;
    }
}
```

### 5.6 socketConf.php（設定ファイル）

```php
<?php
/**
 * ソケット通信設定
 *
 * @package custom/extends
 */

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

### 5.7 .env.example（環境変数テンプレート）

```env
# Socket通信設定
SOCKET_AUTH_TOKEN=your-secret-token-change-this
SOCKET_SERVER_URL=wss://socket.example.com:8080/ws
SOCKET_ENABLED=true

# SSL証明書パス
SOCKET_SSL_CERT=/path/to/cert.pem
SOCKET_SSL_KEY=/path/to/privkey.pem

# データベース設定（既存）
DB_TYPE=mysql
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cats_db
DB_USER=cats_user
DB_PASS=cats_password
```

### 5.8 既存ファイルへの統合

#### 5.8.1 add.php への統合

```php
// add.php の既存コード内に追加（コンバージョン記録後）

require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';

// コンバージョン記録後にイベント送信
if (isset($pay_rec['id']) && $pay_rec['id']) {
    $dispatcher = SocketEventDispatcher::getInstance();

    // ティア報酬情報の収集
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

    // コンバージョンイベント送信
    $dispatcher->dispatchConversion(
        $pay_rec,
        $access_rec ?? [],
        $adwares_rec ?? [],
        $user_rec ?? [],
        $tier_rewards
    );
}
```

#### 5.8.2 link.php への統合

```php
// link.php の既存コード内に追加（クリック記録後）

require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';

// クリック記録後にイベント送信
if (isset($access_rec['id']) && $access_rec['id']) {
    $dispatcher = SocketEventDispatcher::getInstance();

    $dispatcher->dispatchClick(
        $access_rec,
        isset($click_pay_rec) ? $click_pay_rec : null
    );
}
```

### 5.9 SocketServer.php（WebSocketサーバー）

#### composer.json

```json
{
    "name": "cats/socket-gateway",
    "description": "CATS WebSocket Gateway Server",
    "require": {
        "php": ">=7.4",
        "cboden/ratchet": "^0.4",
        "react/socket": "^1.12",
        "monolog/monolog": "^2.0",
        "vlucas/phpdotenv": "^5.0"
    },
    "autoload": {
        "psr-4": {
            "Cats\\Socket\\": "socket/"
        }
    }
}
```

#### SocketServer.php

```php
<?php
/**
 * WebSocketサーバー
 *
 * @package socket
 * @version 1.0
 */

require __DIR__ . '/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\SecureServer;
use React\Socket\Server as ReactServer;
use React\EventLoop\Factory as LoopFactory;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class SocketServer implements MessageComponentInterface {

    protected $clients;
    protected $connections;
    protected $logger;
    protected $authenticator;
    protected $messageHandler;
    protected $rateLimiter;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->connections = [];

        $this->logger = new Logger('socket_server');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Logger::DEBUG));

        require_once __DIR__ . '/SocketAuthenticator.php';
        require_once __DIR__ . '/SocketMessageHandler.php';
        require_once __DIR__ . '/SocketRateLimiter.php';

        $this->authenticator = new SocketAuthenticator();
        $this->messageHandler = new SocketMessageHandler($this->logger);
        $this->rateLimiter = new SocketRateLimiter();

        $this->logger->info('SocketServer initialized');
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);

        $connection_id = uniqid('conn_', true);
        $this->connections[$connection_id] = [
            'conn' => $conn,
            'authenticated' => false,
            'client_type' => null,
            'connected_at' => time()
        ];

        $conn->connection_id = $connection_id;

        $this->logger->info("New connection: {$connection_id}", [
            'remote_address' => $conn->remoteAddress
        ]);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $connection_id = $from->connection_id;

        $this->logger->debug("Message received from {$connection_id}");

        try {
            $data = json_decode($msg, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format');
            }

            $message_type = $data['type'] ?? 'unknown';

            if ($message_type === 'auth') {
                $this->handleAuth($from, $data);
                return;
            }

            if (!$this->connections[$connection_id]['authenticated']) {
                $this->sendError($from, 'AUTH_REQUIRED', 'Authentication required');
                return;
            }

            // レート制限チェック
            $client_type = $this->connections[$connection_id]['client_type'];
            if (!$this->rateLimiter->isAllowed($connection_id, $client_type)) {
                $this->sendError($from, 'RATE_LIMIT_EXCEEDED', 'Rate limit exceeded');
                return;
            }

            if ($message_type === 'ping') {
                $this->sendPong($from);
                return;
            }

            $response = $this->messageHandler->handle($message_type, $data, $this->connections[$connection_id]);

            if ($response) {
                if (isset($response['broadcast']) && $response['broadcast']) {
                    $this->broadcast($response['message'], $from);
                } else {
                    $from->send(json_encode($response));
                }
            }

        } catch (\Exception $e) {
            $this->logger->error("Message processing error: " . $e->getMessage(), [
                'connection_id' => $connection_id
            ]);

            $this->sendError($from, 'PROCESSING_ERROR', $e->getMessage());
        }
    }

    protected function handleAuth(ConnectionInterface $conn, $data) {
        $connection_id = $conn->connection_id;

        $token = $data['token'] ?? '';
        $client_type = $data['client_type'] ?? 'UNKNOWN';

        if ($this->authenticator->validate($token, $client_type)) {
            $this->connections[$connection_id]['authenticated'] = true;
            $this->connections[$connection_id]['client_type'] = $client_type;

            $response = [
                'type' => 'auth_success',
                'connection_id' => $connection_id,
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
            ];

            $conn->send(json_encode($response));

            $this->logger->info("Authentication success: {$connection_id}", [
                'client_type' => $client_type
            ]);

            $this->authenticator->recordConnection($connection_id, $client_type, $conn->remoteAddress);

        } else {
            $this->sendError($conn, 'AUTH_FAILED', 'Invalid authentication token');
            $conn->close();
        }
    }

    protected function sendPong(ConnectionInterface $conn) {
        $response = [
            'type' => 'pong',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        $conn->send(json_encode($response));
    }

    protected function sendError(ConnectionInterface $conn, $error_code, $error_message) {
        $response = [
            'type' => 'error',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'payload' => [
                'error_code' => $error_code,
                'error_message' => $error_message
            ]
        ];

        $conn->send(json_encode($response));
    }

    protected function broadcast($message, ConnectionInterface $from = null) {
        $json = json_encode($message);

        foreach ($this->clients as $client) {
            if ($from !== null && $from === $client) {
                continue;
            }

            $connection_id = $client->connection_id;

            if ($this->connections[$connection_id]['authenticated']) {
                $client->send($json);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $connection_id = $conn->connection_id;

        $this->clients->detach($conn);
        unset($this->connections[$connection_id]);

        $this->logger->info("Connection closed: {$connection_id}");

        $this->authenticator->recordDisconnection($connection_id);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        $connection_id = $conn->connection_id ?? 'unknown';

        $this->logger->error("Connection error: {$connection_id}", [
            'error' => $e->getMessage()
        ]);

        $conn->close();
    }
}

// 環境変数読み込み
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// サーバー起動
$loop = LoopFactory::create();

$webSock = new WsServer(new SocketServer());
$webSock->disableVersion(0);

$webServer = new HttpServer($webSock);

$socketServer = new ReactServer('0.0.0.0:8080', $loop);

// SSL/TLS設定
if (getenv('SOCKET_SSL_ENABLED') === 'true') {
    $secureServer = new SecureServer($socketServer, $loop, [
        'local_cert' => getenv('SOCKET_SSL_CERT'),
        'local_pk' => getenv('SOCKET_SSL_KEY'),
        'verify_peer' => false
    ]);

    $server = new IoServer($webServer, $secureServer, $loop);
    echo "WebSocket server started on wss://0.0.0.0:8080\n";
} else {
    $server = new IoServer($webServer, $socketServer, $loop);
    echo "WebSocket server started on ws://0.0.0.0:8080\n";
}

$server->run();
```

### 5.10 SocketMessageHandler.php

```php
<?php
/**
 * Socket メッセージハンドラー
 *
 * @package socket
 */

class SocketMessageHandler {

    private $logger;

    public function __construct($logger) {
        $this->logger = $logger;
    }

    public function handle($message_type, $data, $connection_info) {
        $this->logger->info("Handling message type: {$message_type}");

        // メッセージをデータベースに記録
        $this->logMessage($connection_info['conn']->connection_id, 'INBOUND', $message_type, $data);

        switch ($message_type) {
            case 'conversion':
                return $this->handleConversion($data, $connection_info);

            case 'click':
                return $this->handleClick($data, $connection_info);

            case 'adware_update':
                return $this->handleAdwareUpdate($data, $connection_info);

            default:
                $this->logger->warning("Unknown message type: {$message_type}");
                return null;
        }
    }

    private function handleConversion($data, $connection_info) {
        // AFADにブロードキャスト
        return [
            'broadcast' => true,
            'message' => [
                'type' => 'conversion',
                'payload' => $data['payload'],
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ];
    }

    private function handleClick($data, $connection_info) {
        return [
            'broadcast' => true,
            'message' => [
                'type' => 'click',
                'payload' => $data['payload'],
                'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
            ]
        ];
    }

    private function handleAdwareUpdate($data, $connection_info) {
        // CATS側クライアントにのみ送信
        return [
            'broadcast' => false,
            'type' => 'adware_update',
            'payload' => $data['payload'],
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
        ];
    }

    private function logMessage($connection_id, $direction, $message_type, $data) {
        // データベースに記録
        require_once __DIR__ . '/../include/base/Initialize.php';
        global $_db;

        if (!isset($_db['socket_messages'])) {
            return;
        }

        $msg_db = &$_db['socket_messages'];

        $msg_rec = [
            'connection_id' => $connection_id,
            'direction' => $direction,
            'message_type' => $message_type,
            'message_data' => json_encode($data),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $msg_db->add($msg_rec);
    }
}
```

### 5.11 SocketAuthenticator.php

```php
<?php
/**
 * Socket認証処理
 *
 * @package socket
 */

class SocketAuthenticator {

    private $db;

    public function __construct() {
        require_once __DIR__ . '/../include/base/Initialize.php';
        global $_db;
        $this->db = &$_db;
    }

    public function validate($token, $client_type) {
        $master_token = getenv('SOCKET_AUTH_TOKEN');

        if (empty($token)) {
            return false;
        }

        if ($token === $master_token) {
            return true;
        }

        // 将来的な拡張: データベースからトークン検証

        return false;
    }

    public function recordConnection($connection_id, $client_type, $client_ip) {
        if (!isset($this->db['socket_connections'])) {
            return;
        }

        $conn_db = &$this->db['socket_connections'];

        $conn_rec = [
            'connection_id' => $connection_id,
            'client_type' => $client_type,
            'client_ip' => $client_ip,
            'connected_at' => date('Y-m-d H:i:s'),
            'last_heartbeat' => date('Y-m-d H:i:s')
        ];

        $conn_db->add($conn_rec);
    }

    public function recordDisconnection($connection_id) {
        if (!isset($this->db['socket_connections'])) {
            return;
        }

        $conn_db = &$this->db['socket_connections'];

        $conn_rec = $conn_db->select([
            'connection_id' => $connection_id
        ], 'first');

        if ($conn_rec) {
            $conn_rec['disconnected_at'] = date('Y-m-d H:i:s');
            $conn_db->edit($conn_rec);
        }
    }
}
```

### 5.12 SocketLogger.php

```php
<?php
/**
 * Socket ロガー
 *
 * @package socket
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;

class SocketLogger {

    private $logger;

    public function __construct($name = 'socket', $log_path = null) {
        $this->logger = new Logger($name);

        $log_path = $log_path ?? __DIR__ . '/logs/socket.log';

        $this->logger->pushHandler(new RotatingFileHandler($log_path, 30, Logger::DEBUG));
    }

    public function info($message, $context = []) {
        $this->logger->info($message, $context);
    }

    public function error($message, $context = []) {
        $this->logger->error($message, $context);
    }

    public function warning($message, $context = []) {
        $this->logger->warning($message, $context);
    }

    public function debug($message, $context = []) {
        $this->logger->debug($message, $context);
    }

    public function logMetrics($metrics) {
        $this->logger->info('Metrics', $metrics);
    }
}
```

### 5.13 SocketRateLimiter.php

```php
<?php
/**
 * Socket レート制限
 *
 * @package socket
 */

class SocketRateLimiter {

    private $limits = [
        'CATS' => 1000,
        'AFAD' => 1000,
        'DASHBOARD' => 100
    ];

    private $counters = [];

    public function isAllowed($connection_id, $client_type) {
        $limit = $this->limits[$client_type] ?? 100;

        if (!isset($this->counters[$connection_id])) {
            $this->counters[$connection_id] = [
                'count' => 0,
                'reset_at' => time() + 60
            ];
        }

        $counter = &$this->counters[$connection_id];

        if (time() > $counter['reset_at']) {
            $counter['count'] = 0;
            $counter['reset_at'] = time() + 60;
        }

        $counter['count']++;

        return $counter['count'] <= $limit;
    }

    public function reset($connection_id) {
        unset($this->counters[$connection_id]);
    }
}
```

---

## 6. データフロー

### 6.1 コンバージョン発生時のフロー

```
ユーザーのコンバージョン
    │
    ▼
add.php
├─ コンバージョン検証
├─ payテーブルに記録
├─ ティア報酬計算（global.php）
│  └─ tierテーブルに記録
│
▼
SocketEventDispatcher::dispatchConversion()
├─ socket_eventsテーブルに記録
├─ SocketClient::send('conversion', payload)
│  └─ WebSocketフレーム送信
│
▼
SocketServer（Gateway）
├─ メッセージ受信
├─ SocketMessageHandler::handle()
├─ socket_messagesテーブルに記録
│
▼
AFADシステムへブロードキャスト
├─ コンバージョンデータ受信
├─ レポート更新
└─ 管理画面リアルタイム反映
```

### 6.2 広告更新時のフロー（AFAD → CATS）

```
AFAD管理画面で広告更新
    │
    ▼
AFADSocketClient::send('adware_update', data)
    │
    ▼
SocketServer（Gateway）
├─ メッセージ受信
├─ SocketMessageHandler::handle()
├─ CATSシステムの対象クライアントへ転送
│
▼
SocketMessageReceiver::handleAdwareUpdate()
├─ adwaresテーブル更新処理
└─ キャッシュクリア
```

---

## 7. エラーハンドリング

### 7.1 エラー分類

| エラーコード | 説明 | 対処 |
|-------------|------|------|
| `AUTH_REQUIRED` | 認証が必要 | 認証メッセージを送信 |
| `AUTH_FAILED` | 認証失敗 | トークンを確認 |
| `INVALID_MESSAGE` | メッセージ形式が不正 | JSON形式を確認 |
| `PROCESSING_ERROR` | メッセージ処理エラー | ログを確認 |
| `CONNECTION_TIMEOUT` | 接続タイムアウト | 再接続を試行 |
| `SEND_FAILED` | 送信失敗 | キューに追加し再試行 |
| `RATE_LIMIT_EXCEEDED` | レート制限超過 | 待機後に再送 |

### 7.2 フォールバック処理

```php
// tools/process_socket_queue.php
<?php
require_once dirname(__FILE__) . '/../include/extends/SocketEventDispatcher.php';

$dispatcher = SocketEventDispatcher::getInstance();
$dispatcher->processQueue();
```

---

## 8. セキュリティ設計

### 8.1 認証・認可

トークンベース認証を使用。将来的にはJWT対応を検討。

### 8.2 暗号化

- TLS/SSL: wss:// プロトコル
- 証明書: Let's Encrypt
- 最小TLSバージョン: TLS 1.2

### 8.3 IPホワイトリスト

```php
// socketConf.php に追加
$SOCKET_CONF['ip_whitelist'] = [
    '203.0.113.0/24',
    '192.0.2.1',
    '198.51.100.0/24'
];
```

---

## 9. デプロイ・運用

### 9.1 マイグレーションスクリプト

```php
<?php
/**
 * Socket テーブルマイグレーション
 *
 * @package migration
 */

require_once dirname(__FILE__) . '/../include/base/Initialize.php';

function createSocketTables() {
    global $_db;

    $sqls = [
        "CREATE TABLE IF NOT EXISTS socket_events (
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
        )",

        "CREATE TABLE IF NOT EXISTS socket_connections (
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
        )",

        "CREATE TABLE IF NOT EXISTS socket_messages (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            connection_id VARCHAR(64) NOT NULL,
            direction VARCHAR(10) NOT NULL,
            message_type VARCHAR(50) NOT NULL,
            message_data TEXT,
            created_at DATETIME NOT NULL,
            INDEX idx_connection_created (connection_id, created_at),
            INDEX idx_message_type (message_type)
        )"
    ];

    foreach ($sqls as $sql) {
        $_db['_database']->query($sql);
    }

    echo "Socket tables created successfully.\n";
}

// CSV定義ファイルの作成
function createSocketCsvDefinitions() {
    $csv_dir = dirname(__FILE__) . '/../lst/';

    $definitions = [
        'socket_events' => "id,event_type,event_data,target_system,status,retry_count,error_message,created_at,sent_at\nINT,VARCHAR(50),TEXT,VARCHAR(20),VARCHAR(20),INT,TEXT,DATETIME,DATETIME",

        'socket_connections' => "id,connection_id,client_type,client_ip,token,connected_at,last_heartbeat,disconnected_at\nINT,VARCHAR(64),VARCHAR(20),VARCHAR(45),VARCHAR(255),DATETIME,DATETIME,DATETIME",

        'socket_messages' => "id,connection_id,direction,message_type,message_data,created_at\nBIGINT,VARCHAR(64),VARCHAR(10),VARCHAR(50),TEXT,DATETIME"
    ];

    foreach ($definitions as $table => $content) {
        file_put_contents($csv_dir . $table . '.csv', $content);
    }

    echo "Socket CSV definitions created successfully.\n";
}

// 実行
createSocketTables();
createSocketCsvDefinitions();
```

### 9.2 サーバー起動スクリプト

```bash
#!/bin/bash
# socket/start_server.sh

cd "$(dirname "$0")"

# 環境変数読み込み
if [ -f ../.env ]; then
    export $(cat ../.env | grep -v '^#' | xargs)
fi

# Composerインストール
if [ ! -d ../vendor ]; then
    composer install
fi

# ログディレクトリ作成
mkdir -p logs

# サーバー起動
php SocketServer.php
```

### 9.3 systemd サービス設定

```ini
# deployment/socket-server.service
[Unit]
Description=CATS WebSocket Server
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/user/ASP-ORKA/socket
ExecStart=/usr/bin/php /home/user/ASP-ORKA/socket/SocketServer.php
Restart=always
RestartSec=10
StandardOutput=append:/var/log/socket-server.log
StandardError=append:/var/log/socket-server-error.log

Environment="SOCKET_AUTH_TOKEN=your-token"
Environment="SOCKET_SSL_ENABLED=true"
Environment="SOCKET_SSL_CERT=/etc/ssl/certs/socket.crt"
Environment="SOCKET_SSL_KEY=/etc/ssl/private/socket.key"

[Install]
WantedBy=multi-user.target
```

### 9.4 cron設定（キュー処理）

```bash
# キュー処理を毎分実行
* * * * * /usr/bin/php /home/user/ASP-ORKA/tools/process_socket_queue.php >> /var/log/socket-queue.log 2>&1
```

### 9.5 nginx リバースプロキシ設定

```nginx
# /etc/nginx/sites-available/socket-gateway

upstream websocket_backend {
    server localhost:8080;
}

server {
    listen 443 ssl;
    server_name socket.example.com;

    ssl_certificate /etc/letsencrypt/live/socket.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/socket.example.com/privkey.pem;

    location /ws {
        proxy_pass http://websocket_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "Upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        proxy_read_timeout 86400;
        proxy_send_timeout 86400;
    }
}
```

### 9.6 フロントエンド実装（JavaScript）

```javascript
// js/socket-client.js

class CatsSocketClient {
    constructor(url, token) {
        this.url = url;
        this.token = token;
        this.ws = null;
        this.connected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.handlers = {};
    }

    connect() {
        this.ws = new WebSocket(this.url);

        this.ws.onopen = () => {
            console.log('WebSocket connected');
            this.authenticate();
        };

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleMessage(data);
        };

        this.ws.onclose = () => {
            console.log('WebSocket disconnected');
            this.connected = false;
            this.reconnect();
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    authenticate() {
        const authMessage = {
            type: 'auth',
            token: this.token,
            client_type: 'DASHBOARD'
        };

        this.ws.send(JSON.stringify(authMessage));
    }

    handleMessage(data) {
        const type = data.type;

        if (type === 'auth_success') {
            this.connected = true;
            this.reconnectAttempts = 0;
            console.log('Authentication successful');
            this.startHeartbeat();
        } else if (type === 'pong') {
            // ハートビート応答
        } else if (this.handlers[type]) {
            this.handlers[type](data);
        }
    }

    on(messageType, callback) {
        this.handlers[messageType] = callback;
    }

    startHeartbeat() {
        setInterval(() => {
            if (this.connected) {
                this.ws.send(JSON.stringify({ type: 'ping' }));
            }
        }, 30000);
    }

    reconnect() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('Max reconnection attempts exceeded');
            return;
        }

        this.reconnectAttempts++;
        const delay = Math.min(2000 * Math.pow(2, this.reconnectAttempts), 60000);

        setTimeout(() => {
            console.log(`Reconnecting... (attempt ${this.reconnectAttempts})`);
            this.connect();
        }, delay);
    }
}

// 使用例
const client = new CatsSocketClient('wss://socket.example.com/ws', 'your-token');

client.on('conversion', (data) => {
    console.log('New conversion:', data);
    updateDashboard(data);
});

client.on('stats_update', (data) => {
    console.log('Stats update:', data);
    updateStats(data);
});

client.connect();
```

---

## 10. 実装計画

### 10.1 フェーズ1: 基盤構築（2週間）

- [ ] 例外クラス群実装
  - [ ] `SocketException.php`
  - [ ] `SocketAuthException.php`
  - [ ] `SocketTemporaryException.php`
- [ ] データベーススキーマ作成
  - [ ] マイグレーションスクリプト実行
  - [ ] CSV定義ファイル作成
- [ ] SocketClient.php 実装
- [ ] SocketEventDispatcher.php 実装
- [ ] SocketRetryStrategy.php 実装
- [ ] socketConf.php 作成
- [ ] .env 設定

### 10.2 フェーズ2: 統合実装（2週間）

- [ ] add.php への統合
- [ ] link.php への統合
- [ ] SocketMessageReceiver.php 実装
- [ ] ユニットテスト作成

### 10.3 フェーズ3: Gatewayサーバー（3週間）

- [ ] Composer環境構築
- [ ] SocketServer.php 実装
- [ ] SocketMessageHandler.php 実装
- [ ] SocketAuthenticator.php 実装
- [ ] SocketLogger.php 実装
- [ ] SocketRateLimiter.php 実装
- [ ] SSL/TLS証明書設定

### 10.4 フェーズ4: デプロイ・運用（2週間）

- [ ] 起動スクリプト作成
- [ ] systemd サービス設定
- [ ] nginx リバースプロキシ設定
- [ ] cron ジョブ設定
- [ ] ログローテーション設定

### 10.5 フェーズ5: AFAD側実装（2週間）

- [ ] AFADSocketClient 実装
- [ ] 広告更新イベント送信
- [ ] コンバージョン受信処理
- [ ] リアルタイムダッシュボード（JavaScript）

### 10.6 フェーズ6: テスト・最適化（2週間）

- [ ] 統合テスト
- [ ] 負荷テスト
- [ ] セキュリティテスト
- [ ] パフォーマンスチューニング

### 10.7 フェーズ7: 本番リリース（1週間）

- [ ] ステージング環境デプロイ
- [ ] 本番環境デプロイ
- [ ] 監視設定
- [ ] ドキュメント整備

---

## 11. テスト計画

### 11.1 ユニットテスト

```php
// tests/SocketClientTest.php

use PHPUnit\Framework\TestCase;

class SocketClientTest extends TestCase {

    public function testConnect() {
        $client = new SocketClient([
            'url' => 'wss://localhost:8080',
            'token' => 'test-token'
        ]);

        $this->assertTrue($client->connect());
        $this->assertTrue($client->isConnected());
    }

    public function testSendMessage() {
        $client = new SocketClient([
            'url' => 'wss://localhost:8080',
            'token' => 'test-token'
        ]);
        $client->connect();

        $result = $client->send('test_event', ['data' => 'test']);
        $this->assertTrue($result);
    }

    public function testAuthFailure() {
        $this->expectException(SocketAuthException::class);

        $client = new SocketClient([
            'url' => 'wss://localhost:8080',
            'token' => 'invalid-token'
        ]);
        $client->connect();
    }
}
```

---

## 12. 実装チェックリスト

### 12.1 CATS側コンポーネント

- [ ] `/include/extends/Exception/SocketException.php`
- [ ] `/include/extends/Exception/SocketAuthException.php`
- [ ] `/include/extends/Exception/SocketTemporaryException.php`
- [ ] `/include/extends/SocketClient.php`
- [ ] `/include/extends/SocketEventDispatcher.php`
- [ ] `/include/extends/SocketMessageReceiver.php`
- [ ] `/include/extends/SocketRetryStrategy.php`
- [ ] `/custom/extends/socketConf.php`

### 12.2 Gateway コンポーネント

- [ ] `/socket/SocketServer.php`
- [ ] `/socket/SocketMessageHandler.php`
- [ ] `/socket/SocketAuthenticator.php`
- [ ] `/socket/SocketLogger.php`
- [ ] `/socket/SocketRateLimiter.php`
- [ ] `/socket/composer.json`
- [ ] `/socket/start_server.sh`

### 12.3 デプロイ・運用

- [ ] `/migration/002_create_socket_tables.php`
- [ ] `/tools/process_socket_queue.php`
- [ ] `/deployment/socket-server.service`
- [ ] `/deployment/nginx-socket.conf`
- [ ] `/.env.example`
- [ ] `/lst/socket_events.csv`
- [ ] `/lst/socket_connections.csv`
- [ ] `/lst/socket_messages.csv`

### 12.4 既存ファイル修正

- [ ] `/add.php` - SocketEventDispatcher統合
- [ ] `/link.php` - SocketEventDispatcher統合
- [ ] `/custom/global.php` - ティア報酬イベント送信（必要に応じて）

### 12.5 フロントエンド

- [ ] `/js/socket-client.js`
- [ ] `/template/admin/dashboard.html` - WebSocket統合

### 12.6 テスト

- [ ] `/tests/SocketClientTest.php`
- [ ] `/tests/SocketEventDispatcherTest.php`
- [ ] `/tests/IntegrationTest.php`

### 12.7 ドキュメント

- [ ] この設計書の最終レビュー
- [ ] デプロイ手順書
- [ ] 運用マニュアル
- [ ] トラブルシューティングガイド

---

## 変更履歴

| バージョン | 日付 | 変更内容 |
|-----------|------|---------|
| 1.0 | 2025-10-28 | 初版作成 |
| 2.0 | 2025-10-29 | 完全版：全実装コード、デプロイスクリプト、チェックリスト追加 |

---

**作成者:** Claude
**承認者:** [承認者名]
**最終更新:** 2025-10-29
