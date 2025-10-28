# CATSシステム - AFADソケット連携システム 設計書

**バージョン:** 1.0
**作成日:** 2025-10-28
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
9. [実装計画](#9-実装計画)
10. [テスト計画](#10-テスト計画)

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
| **SocketConfig** | 設定管理 | `/custom/extends/socketConf.php` |
| **SocketException** | 例外処理 | `/include/extends/Exception/SocketException.php` |

#### 3.2.2 Gateway コンポーネント

| コンポーネント | 責務 | ファイルパス |
|---------------|------|-------------|
| **SocketServer** | WebSocketサーバー | `/socket/SocketServer.php` |
| **SocketMessageHandler** | メッセージ処理 | `/socket/SocketMessageHandler.php` |
| **SocketAuthenticator** | 認証処理 | `/socket/SocketAuthenticator.php` |
| **SocketLogger** | ログ記録 | `/socket/SocketLogger.php` |

### 3.3 データベース拡張

#### 新規テーブル: `socket_events`

```sql
CREATE TABLE socket_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,        -- 'conversion', 'click', 'tier_reward', etc.
    event_data TEXT,                        -- JSON形式のイベントデータ
    target_system VARCHAR(20) NOT NULL,     -- 'AFAD', 'CATS', 'ALL'
    status VARCHAR(20) NOT NULL,            -- 'PENDING', 'SENT', 'FAILED'
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
    client_type VARCHAR(20) NOT NULL,       -- 'AFAD', 'DASHBOARD', 'ADMIN'
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
    direction VARCHAR(10) NOT NULL,         -- 'INBOUND', 'OUTBOUND'
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
    "timestamp": "2025-10-28T12:34:56Z",
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
    "timestamp": "2025-10-28T12:34:56Z",
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
            },
            {
                "tier_level": 2,
                "user_id": 498,
                "user_name": "祖父アフィリエイター",
                "rate": 5,
                "amount": 50
            }
        ],
        "created_at": "2025-10-28T12:34:56Z"
    }
}
```

#### 4.2.2 広告更新通知（AFAD → CATS）

```json
{
    "version": "1.0",
    "type": "adware_update",
    "timestamp": "2025-10-28T12:34:56Z",
    "message_id": "550e8400-e29b-41d4-a716-446655440001",
    "sender": "AFAD",
    "payload": {
        "adware_id": 100,
        "action": "UPDATE",  // CREATE, UPDATE, DELETE
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

#### 4.2.3 リアルタイム統計更新（CATS → Dashboard）

```json
{
    "version": "1.0",
    "type": "stats_update",
    "timestamp": "2025-10-28T12:34:56Z",
    "message_id": "550e8400-e29b-41d4-a716-446655440002",
    "sender": "CATS",
    "payload": {
        "user_id": 500,
        "stats": {
            "today_clicks": 150,
            "today_conversions": 5,
            "today_revenue": 5000,
            "pending_revenue": 3000,
            "active_revenue": 15000,
            "conversion_rate": 3.33
        }
    }
}
```

#### 4.2.4 エラーレスポンス

```json
{
    "version": "1.0",
    "type": "error",
    "timestamp": "2025-10-28T12:34:56Z",
    "message_id": "550e8400-e29b-41d4-a716-446655440003",
    "sender": "GATEWAY",
    "payload": {
        "error_code": "AUTH_FAILED",
        "error_message": "Invalid authentication token",
        "original_message_id": "550e8400-e29b-41d4-a716-446655440000"
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

### 5.1 SocketClient.php（CATS側クライアント）

```php
<?php
/**
 * WebSocketクライアント
 *
 * CATSシステムからAFADシステムへのWebSocket接続を管理
 *
 * @package include/extends
 * @version 1.0
 */

require_once dirname(__FILE__) . '/../Util.php';
require_once dirname(__FILE__) . '/Exception/SocketException.php';

class SocketClient {

    private $config;
    private $socket;
    private $connected = false;
    private $connection_id;
    private $reconnect_attempts = 0;
    private $max_reconnect_attempts = 5;
    private $reconnect_delay = 2; // 秒

    /**
     * コンストラクタ
     *
     * @param array $config 設定配列
     *   - url: WebSocketサーバーURL (wss://example.com:8080)
     *   - token: 認証トークン
     *   - timeout: 接続タイムアウト（秒）
     *   - auto_reconnect: 自動再接続フラグ
     */
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
    }

    /**
     * WebSocketサーバーに接続
     *
     * @return bool 接続成功フラグ
     * @throws SocketException
     */
    public function connect() {
        $url_parts = parse_url($this->config['url']);

        $host = $url_parts['host'];
        $port = isset($url_parts['port']) ? $url_parts['port'] : 443;
        $path = isset($url_parts['path']) ? $url_parts['path'] : '/';
        $scheme = $url_parts['scheme'];

        // SSL/TLS接続の場合
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
            throw new SocketException("Connection failed: {$errstr} ({$errno})");
        }

        // WebSocketハンドシェイク
        $this->performHandshake($host, $path);

        // 認証
        $this->authenticate();

        $this->connected = true;
        $this->reconnect_attempts = 0;

        return true;
    }

    /**
     * WebSocketハンドシェイク
     */
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

        // レスポンス読み取り
        $response = '';
        while (($line = fgets($this->socket)) !== false) {
            $response .= $line;
            if (trim($line) === '') {
                break;
            }
        }

        if (strpos($response, '101 Switching Protocols') === false) {
            throw new SocketException('WebSocket handshake failed');
        }
    }

    /**
     * 認証処理
     */
    private function authenticate() {
        $auth_message = [
            'type' => 'auth',
            'token' => $this->config['token'],
            'client_type' => $this->config['client_type']
        ];

        $this->sendRaw($auth_message);

        // 認証レスポンス待機
        $response = $this->receiveRaw();

        if (!$response || $response['type'] !== 'auth_success') {
            throw new SocketException('Authentication failed');
        }

        $this->connection_id = $response['connection_id'];
    }

    /**
     * メッセージ送信
     *
     * @param string $type イベントタイプ
     * @param array $payload ペイロード
     * @return bool 送信成功フラグ
     */
    public function send($type, $payload) {
        if (!$this->connected) {
            if ($this->config['auto_reconnect']) {
                $this->reconnect();
            } else {
                throw new SocketException('Not connected to WebSocket server');
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

    /**
     * 生のメッセージ送信
     */
    private function sendRaw($message) {
        $json = json_encode($message);
        $frame = $this->encodeFrame($json);

        $written = @fwrite($this->socket, $frame);

        if ($written === false) {
            $this->connected = false;
            throw new SocketException('Failed to send message');
        }

        return true;
    }

    /**
     * メッセージ受信
     *
     * @return array|null 受信メッセージ
     */
    public function receive() {
        if (!$this->connected) {
            return null;
        }

        return $this->receiveRaw();
    }

    /**
     * 生のメッセージ受信
     */
    private function receiveRaw() {
        $frame = $this->decodeFrame();

        if (!$frame) {
            return null;
        }

        return json_decode($frame, true);
    }

    /**
     * WebSocketフレームのエンコード
     */
    private function encodeFrame($data) {
        $length = strlen($data);
        $frame = chr(0x81); // Text frame, FIN=1

        if ($length <= 125) {
            $frame .= chr($length | 0x80); // マスクビット設定
        } elseif ($length <= 65535) {
            $frame .= chr(126 | 0x80);
            $frame .= pack('n', $length);
        } else {
            $frame .= chr(127 | 0x80);
            $frame .= pack('J', $length);
        }

        // マスキングキー生成
        $mask = openssl_random_pseudo_bytes(4);
        $frame .= $mask;

        // データのマスキング
        for ($i = 0; $i < $length; $i++) {
            $frame .= $data[$i] ^ $mask[$i % 4];
        }

        return $frame;
    }

    /**
     * WebSocketフレームのデコード
     */
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

        // 接続クローズ
        if ($opcode === 0x08) {
            $this->connected = false;
            return null;
        }

        // Ping
        if ($opcode === 0x09) {
            $this->sendPong();
            return $this->decodeFrame(); // 次のフレームを読む
        }

        // 長さの拡張読み取り
        if ($length === 126) {
            $extended = fread($this->socket, 2);
            $length = unpack('n', $extended)[1];
        } elseif ($length === 127) {
            $extended = fread($this->socket, 8);
            $length = unpack('J', $extended)[1];
        }

        // マスク読み取り
        if ($masked) {
            $mask = fread($this->socket, 4);
        }

        // データ読み取り
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

        // マスク解除
        if ($masked) {
            for ($i = 0; $i < strlen($data); $i++) {
                $data[$i] = $data[$i] ^ $mask[$i % 4];
            }
        }

        return $data;
    }

    /**
     * Pong送信
     */
    private function sendPong() {
        $frame = chr(0x8A) . chr(0x00); // Pong frame
        fwrite($this->socket, $frame);
    }

    /**
     * 再接続
     */
    private function reconnect() {
        if ($this->reconnect_attempts >= $this->max_reconnect_attempts) {
            throw new SocketException('Max reconnection attempts exceeded');
        }

        $this->reconnect_attempts++;
        sleep($this->reconnect_delay * $this->reconnect_attempts);

        $this->connect();
    }

    /**
     * 接続クローズ
     */
    public function close() {
        if ($this->socket) {
            // クローズフレーム送信
            $frame = chr(0x88) . chr(0x00);
            @fwrite($this->socket, $frame);

            fclose($this->socket);
            $this->connected = false;
        }
    }

    /**
     * UUID v4生成
     */
    private function generateUUID() {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * 接続状態確認
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * デストラクタ
     */
    public function __destruct() {
        $this->close();
    }
}
```

### 5.2 SocketEventDispatcher.php（イベントディスパッチャー）

```php
<?php
/**
 * Socket イベントディスパッチャー
 *
 * CATSシステム内のイベントを検知し、WebSocket経由で送信
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

    /**
     * シングルトンインスタンス取得
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * コンストラクタ
     */
    private function __construct() {
        // 設定読み込み
        $conf_file = dirname(__FILE__) . '/../../custom/extends/socketConf.php';
        if (file_exists($conf_file)) {
            require_once $conf_file;
            if (isset($SOCKET_CONF)) {
                $this->config = $SOCKET_CONF;
                $this->enabled = isset($this->config['enabled']) ? $this->config['enabled'] : false;
            }
        }
    }

    /**
     * ソケットクライアント初期化
     */
    private function initClient() {
        if ($this->client === null && $this->enabled && $this->config) {
            try {
                $this->client = new SocketClient([
                    'url' => $this->config['url'],
                    'token' => $this->config['token'],
                    'timeout' => $this->config['timeout'] ?? 10,
                    'auto_reconnect' => $this->config['auto_reconnect'] ?? true
                ]);
                $this->client->connect();
            } catch (SocketException $e) {
                error_log('SocketClient initialization failed: ' . $e->getMessage());
                $this->client = null;
            }
        }
    }

    /**
     * コンバージョンイベント送信
     *
     * @param array $pay_data payテーブルのレコード
     * @param array $access_data accessテーブルのレコード
     * @param array $adware_data adwaresテーブルのレコード
     * @param array $user_data nuserテーブルのレコード
     * @param array $tier_rewards ティア報酬配列
     */
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

    /**
     * クリックイベント送信
     *
     * @param array $access_data accessテーブルのレコード
     * @param array $click_pay_data click_payテーブルのレコード（クリック報酬がある場合）
     */
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

    /**
     * ティア報酬イベント送信
     *
     * @param array $tier_data tierテーブルのレコード
     */
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

    /**
     * 予算アラートイベント送信
     *
     * @param int $adware_id 広告ID
     * @param int $current_budget 現在の予算
     * @param int $limit 予算上限
     */
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

    /**
     * 不正検知アラートイベント送信
     *
     * @param string $fraud_type 不正タイプ
     * @param array $details 詳細情報
     */
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

    /**
     * イベントディスパッチ（共通処理）
     *
     * @param string $type イベントタイプ
     * @param array $payload ペイロード
     */
    private function dispatch($type, $payload) {
        // データベースに記録
        $this->saveToDatabase($type, $payload);

        // WebSocket送信試行
        try {
            $this->initClient();

            if ($this->client && $this->client->isConnected()) {
                $this->client->send($type, $payload);
            } else {
                // 送信失敗時はキューに追加
                $this->queue[] = ['type' => $type, 'payload' => $payload];
            }
        } catch (Exception $e) {
            error_log('Socket dispatch failed: ' . $e->getMessage());
            // キューに追加
            $this->queue[] = ['type' => $type, 'payload' => $payload];
        }
    }

    /**
     * イベントをデータベースに保存
     *
     * @param string $type イベントタイプ
     * @param array $payload ペイロード
     */
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

    /**
     * キュー処理（定期実行用）
     */
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
                $processed[] = $index;
            } catch (Exception $e) {
                error_log('Queue processing failed: ' . $e->getMessage());
                break; // 失敗したら中断
            }
        }

        // 送信成功したアイテムをキューから削除
        foreach (array_reverse($processed) as $index) {
            array_splice($this->queue, $index, 1);
        }
    }

    /**
     * デストラクタ
     */
    public function __destruct() {
        if ($this->client) {
            $this->client->close();
        }
    }
}
```

### 5.3 socketConf.php（設定ファイル）

```php
<?php
/**
 * ソケット通信設定
 *
 * @package custom/extends
 */

$SOCKET_CONF = [
    // WebSocket機能の有効化
    'enabled' => true,

    // WebSocketサーバーURL
    'url' => 'wss://socket.example.com:8080/ws',

    // 認証トークン（環境変数から取得推奨）
    'token' => getenv('SOCKET_AUTH_TOKEN') ?: 'your-secret-token-here',

    // 接続タイムアウト（秒）
    'timeout' => 10,

    // 自動再接続フラグ
    'auto_reconnect' => true,

    // 最大再接続試行回数
    'max_reconnect_attempts' => 5,

    // ハートビート間隔（秒）
    'heartbeat_interval' => 30,

    // イベントキュー処理間隔（秒）
    'queue_process_interval' => 60,

    // デバッグモード
    'debug' => false,

    // ログファイルパス
    'log_file' => dirname(__FILE__) . '/../../logs/socket.log'
];
```

### 5.4 既存ファイルへの統合

#### 5.4.1 add.php（コンバージョン記録）への統合

```php
// add.php の既存コード内に追加

// ... 既存のコンバージョン記録処理 ...

// SocketEventDispatcher の追加
require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';

// コンバージョン記録後にイベント送信
if ($pay_rec['id']) {
    $dispatcher = SocketEventDispatcher::getInstance();

    // ティア報酬情報の収集
    $tier_rewards = [];
    if (!empty($_tierValue)) {
        foreach ($_tierValue as $tier_level => $tier_data) {
            if (!empty($tier_data)) {
                $tier_rewards[] = [
                    'tier_level' => $tier_level,
                    'user_id' => $tier_data['user_id'],
                    'user_name' => $tier_data['user_name'],
                    'rate' => $tier_data['rate'],
                    'amount' => $tier_data['amount']
                ];
            }
        }
    }

    // コンバージョンイベント送信
    $dispatcher->dispatchConversion(
        $pay_rec,
        $access_rec,
        $adwares_rec,
        $user_rec,
        $tier_rewards
    );
}
```

#### 5.4.2 link.php（クリック追跡）への統合

```php
// link.php の既存コード内に追加

// ... 既存のクリック記録処理 ...

// SocketEventDispatcher の追加
require_once dirname(__FILE__) . '/include/extends/SocketEventDispatcher.php';

// クリック記録後にイベント送信
if ($access_rec['id']) {
    $dispatcher = SocketEventDispatcher::getInstance();

    $dispatcher->dispatchClick(
        $access_rec,
        isset($click_pay_rec) ? $click_pay_rec : null
    );
}
```

### 5.5 SocketServer.php（WebSocketサーバー）

Ratchet（ReactPHP）を使用したWebSocketサーバー実装

#### Composer依存関係

```json
{
    "require": {
        "cboden/ratchet": "^0.4",
        "react/socket": "^1.12",
        "monolog/monolog": "^2.0"
    }
}
```

#### SocketServer.php

```php
<?php
/**
 * WebSocketサーバー
 *
 * RatchetとReactPHPを使用したWebSocketサーバー実装
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

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->connections = [];

        // ロガー初期化
        $this->logger = new Logger('socket_server');
        $this->logger->pushHandler(new StreamHandler(__DIR__ . '/logs/server.log', Logger::DEBUG));

        // 認証・メッセージハンドラー初期化
        require_once __DIR__ . '/SocketAuthenticator.php';
        require_once __DIR__ . '/SocketMessageHandler.php';

        $this->authenticator = new SocketAuthenticator();
        $this->messageHandler = new SocketMessageHandler($this->logger);

        $this->logger->info('SocketServer initialized');
    }

    /**
     * 新規接続時
     */
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

    /**
     * メッセージ受信時
     */
    public function onMessage(ConnectionInterface $from, $msg) {
        $connection_id = $from->connection_id;

        $this->logger->debug("Message received from {$connection_id}", [
            'message' => $msg
        ]);

        try {
            $data = json_decode($msg, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format');
            }

            $message_type = $data['type'] ?? 'unknown';

            // 認証処理
            if ($message_type === 'auth') {
                $this->handleAuth($from, $data);
                return;
            }

            // 認証チェック
            if (!$this->connections[$connection_id]['authenticated']) {
                $this->sendError($from, 'AUTH_REQUIRED', 'Authentication required');
                return;
            }

            // Ping/Pong
            if ($message_type === 'ping') {
                $this->sendPong($from);
                return;
            }

            // メッセージ処理
            $response = $this->messageHandler->handle($message_type, $data, $this->connections[$connection_id]);

            if ($response) {
                // ブロードキャストまたは個別送信
                if (isset($response['broadcast']) && $response['broadcast']) {
                    $this->broadcast($response['message'], $from);
                } else {
                    $from->send(json_encode($response));
                }
            }

        } catch (\Exception $e) {
            $this->logger->error("Message processing error: " . $e->getMessage(), [
                'connection_id' => $connection_id,
                'message' => $msg
            ]);

            $this->sendError($from, 'PROCESSING_ERROR', $e->getMessage());
        }
    }

    /**
     * 認証処理
     */
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

            // データベースに記録
            $this->authenticator->recordConnection($connection_id, $client_type, $conn->remoteAddress);

        } else {
            $this->sendError($conn, 'AUTH_FAILED', 'Invalid authentication token');
            $conn->close();
        }
    }

    /**
     * Pong送信
     */
    protected function sendPong(ConnectionInterface $conn) {
        $response = [
            'type' => 'pong',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z')
        ];

        $conn->send(json_encode($response));
    }

    /**
     * エラー送信
     */
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

    /**
     * ブロードキャスト
     */
    protected function broadcast($message, ConnectionInterface $from = null) {
        $json = json_encode($message);

        foreach ($this->clients as $client) {
            if ($from !== null && $from === $client) {
                continue; // 送信元には送らない
            }

            $connection_id = $client->connection_id;

            if ($this->connections[$connection_id]['authenticated']) {
                $client->send($json);
            }
        }
    }

    /**
     * 接続クローズ時
     */
    public function onClose(ConnectionInterface $conn) {
        $connection_id = $conn->connection_id;

        $this->clients->detach($conn);
        unset($this->connections[$connection_id]);

        $this->logger->info("Connection closed: {$connection_id}");

        // データベース更新
        $this->authenticator->recordDisconnection($connection_id);
    }

    /**
     * エラー発生時
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {
        $connection_id = $conn->connection_id ?? 'unknown';

        $this->logger->error("Connection error: {$connection_id}", [
            'error' => $e->getMessage()
        ]);

        $conn->close();
    }
}

// サーバー起動
$loop = LoopFactory::create();

$webSock = new WsServer(new SocketServer());
$webSock->disableVersion(0); // RFC 6455のみ対応

$webServer = new HttpServer($webSock);

// SSL/TLS設定
$socketServer = new ReactServer('0.0.0.0:8080', $loop);
$secureServer = new SecureServer($socketServer, $loop, [
    'local_cert' => '/path/to/cert.pem',
    'local_pk' => '/path/to/privkey.pem',
    'verify_peer' => false
]);

$server = new IoServer($webServer, $secureServer, $loop);

echo "WebSocket server started on wss://0.0.0.0:8080\n";

$server->run();
```

### 5.6 SocketAuthenticator.php（認証処理）

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
        // データベース接続
        require_once __DIR__ . '/../include/base/Initialize.php';
        global $_db;
        $this->db = &$_db;
    }

    /**
     * トークン検証
     *
     * @param string $token 認証トークン
     * @param string $client_type クライアントタイプ
     * @return bool 検証結果
     */
    public function validate($token, $client_type) {
        // 環境変数からマスタートークン取得
        $master_token = getenv('SOCKET_AUTH_TOKEN');

        if (empty($token)) {
            return false;
        }

        // マスタートークンチェック
        if ($token === $master_token) {
            return true;
        }

        // データベースからトークン検証（将来的な拡張用）
        // TODO: ユーザー/システム別トークン管理

        return false;
    }

    /**
     * 接続記録
     *
     * @param string $connection_id 接続ID
     * @param string $client_type クライアントタイプ
     * @param string $client_ip クライアントIP
     */
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

    /**
     * 切断記録
     *
     * @param string $connection_id 接続ID
     */
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
CATSSocketClient::receive()
├─ メッセージ受信
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

### 7.2 リトライ戦略

```php
class SocketRetryStrategy {

    /**
     * エクスポネンシャルバックオフ
     *
     * @param int $attempt 試行回数
     * @return int 待機時間（秒）
     */
    public static function getDelay($attempt) {
        $base_delay = 2;
        $max_delay = 60;

        $delay = min($base_delay * pow(2, $attempt - 1), $max_delay);

        // ジッター追加（±20%）
        $jitter = $delay * 0.2 * (mt_rand(80, 120) / 100);

        return (int)($delay + $jitter);
    }

    /**
     * リトライ判定
     *
     * @param Exception $e 例外
     * @return bool リトライすべきか
     */
    public static function shouldRetry($e) {
        // 認証エラーはリトライしない
        if ($e instanceof SocketAuthException) {
            return false;
        }

        // 一時的なエラーはリトライ
        if ($e instanceof SocketTemporaryException) {
            return true;
        }

        return true;
    }
}
```

### 7.3 フォールバック処理

接続が確立できない場合、以下のフォールバック処理を実行：

1. **イベントキュー**: `socket_events` テーブルに保存
2. **定期バッチ処理**: cron で定期的にキューを処理
3. **HTTPフォールバック**: WebSocket不可の場合はHTTP POST

```php
// cron で実行するキュー処理スクリプト
// tools/process_socket_queue.php

require_once dirname(__FILE__) . '/../include/extends/SocketEventDispatcher.php';

$dispatcher = SocketEventDispatcher::getInstance();
$dispatcher->processQueue();
```

---

## 8. セキュリティ設計

### 8.1 認証・認可

#### トークンベース認証
```
1. クライアントは事前に共有されたトークンを保持
2. 接続時にトークンを送信
3. サーバーがトークンを検証
4. 接続IDを発行して以降の通信を管理
```

#### 将来的な拡張（JWT）
```json
{
    "alg": "HS256",
    "typ": "JWT"
}
{
    "sub": "CATS_CLIENT",
    "client_type": "CATS",
    "iat": 1730116496,
    "exp": 1730120096
}
```

### 8.2 暗号化

- **TLS/SSL**: wss:// プロトコルを使用
- **証明書**: Let's Encrypt または商用証明書
- **最小TLSバージョン**: TLS 1.2以上

### 8.3 レート制限

```php
class SocketRateLimiter {

    private $limits = [
        'CATS' => 1000,      // 1000 msg/min
        'AFAD' => 1000,      // 1000 msg/min
        'DASHBOARD' => 100   // 100 msg/min
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

        // リセット時刻を過ぎている場合
        if (time() > $counter['reset_at']) {
            $counter['count'] = 0;
            $counter['reset_at'] = time() + 60;
        }

        $counter['count']++;

        return $counter['count'] <= $limit;
    }
}
```

### 8.4 IPホワイトリスト

```php
// socketConf.php に追加

$SOCKET_CONF['ip_whitelist'] = [
    '203.0.113.0/24',     // AFAD サーバー
    '192.0.2.1',          // CATS サーバー
    '198.51.100.0/24'     // 内部ネットワーク
];
```

---

## 9. 実装計画

### 9.1 フェーズ1: 基盤構築（2週間）

- [ ] データベーススキーマ作成
  - [ ] `socket_events` テーブル
  - [ ] `socket_connections` テーブル
  - [ ] `socket_messages` テーブル
- [ ] SocketClient.php 実装
- [ ] SocketEventDispatcher.php 実装
- [ ] socketConf.php 作成
- [ ] 例外クラス実装

### 9.2 フェーズ2: 統合実装（2週間）

- [ ] add.php への統合
- [ ] link.php への統合
- [ ] custom/global.php のティア報酬計算への統合
- [ ] ユニットテスト作成

### 9.3 フェーズ3: Gatewayサーバー（3週間）

- [ ] Composer環境構築
- [ ] SocketServer.php 実装
- [ ] SocketAuthenticator.php 実装
- [ ] SocketMessageHandler.php 実装
- [ ] SocketLogger.php 実装
- [ ] SSL/TLS証明書設定

### 9.4 フェーズ4: AFAD側実装（2週間）

- [ ] AFADSocketClient 実装
- [ ] 広告更新イベント送信
- [ ] コンバージョン受信処理
- [ ] リアルタイムダッシュボード

### 9.5 フェーズ5: テスト・最適化（2週間）

- [ ] 統合テスト
- [ ] 負荷テスト
- [ ] セキュリティテスト
- [ ] パフォーマンスチューニング

### 9.6 フェーズ6: 本番リリース（1週間）

- [ ] ステージング環境デプロイ
- [ ] 本番環境デプロイ
- [ ] 監視設定
- [ ] ドキュメント整備

---

## 10. テスト計画

### 10.1 ユニットテスト

```php
// tests/SocketClientTest.php

class SocketClientTest extends PHPUnit\Framework\TestCase {

    public function testConnect() {
        $client = new SocketClient([
            'url' => 'wss://localhost:8080',
            'token' => 'test-token'
        ]);

        $this->assertTrue($client->connect());
        $this->assertTrue($client->isConnected());
    }

    public function testSendMessage() {
        $client = new SocketClient([...]);
        $client->connect();

        $result = $client->send('test_event', ['data' => 'test']);
        $this->assertTrue($result);
    }

    public function testReconnect() {
        // 再接続テスト
    }
}
```

### 10.2 統合テスト

```php
// tests/IntegrationTest.php

class SocketIntegrationTest extends PHPUnit\Framework\TestCase {

    public function testConversionFlow() {
        // 1. コンバージョン発生
        // 2. SocketEventDispatcher 呼び出し
        // 3. WebSocket送信確認
        // 4. AFAD側受信確認
    }

    public function testAdwareUpdateFlow() {
        // 1. AFAD側で広告更新
        // 2. WebSocket送信
        // 3. CATS側受信
        // 4. データベース更新確認
    }
}
```

### 10.3 負荷テスト

```bash
# Apache Bench を使用した負荷テスト

# 1000 同時接続、10000 メッセージ
ab -n 10000 -c 1000 wss://localhost:8080/ws

# または WebSocket 専用ツール
npm install -g wscat
wscat -c wss://localhost:8080/ws
```

### 10.4 セキュリティテスト

- [ ] 不正トークンでの接続試行
- [ ] SQL インジェクションテスト
- [ ] XSS テスト
- [ ] レート制限テスト
- [ ] SSL/TLS脆弱性スキャン

---

## 11. 運用・監視

### 11.1 ログ管理

```
/logs/
├── socket.log              # 通常ログ
├── socket_error.log        # エラーログ
├── socket_access.log       # アクセスログ
└── socket_performance.log  # パフォーマンスログ
```

### 11.2 メトリクス収集

- **接続数**: 現在の接続数、累計接続数
- **メッセージスループット**: 送受信メッセージ数/秒
- **レイテンシ**: メッセージ配信遅延
- **エラーレート**: エラー発生率
- **再接続回数**: 再接続試行回数

### 11.3 アラート設定

| メトリクス | 閾値 | アクション |
|-----------|------|-----------|
| エラーレート | > 5% | メール通知 |
| 接続数 | > 900 | スケールアウト |
| レイテンシ | > 500ms | パフォーマンス調査 |
| CPU使用率 | > 80% | リソース増強 |

---

## 12. 参考資料

### 12.1 技術仕様

- [RFC 6455: The WebSocket Protocol](https://tools.ietf.org/html/rfc6455)
- [Ratchet Documentation](http://socketo.me/)
- [ReactPHP Documentation](https://reactphp.org/)

### 12.2 関連ファイル

- `/home/user/ASP-ORKA/include/extends/HttpUtil.php` - 既存HTTP通信
- `/home/user/ASP-ORKA/add.php` - コンバージョン記録
- `/home/user/ASP-ORKA/link.php` - クリック追跡
- `/home/user/ASP-ORKA/custom/global.php` - ティア報酬計算

---

## 変更履歴

| バージョン | 日付 | 変更内容 |
|-----------|------|---------|
| 1.0 | 2025-10-28 | 初版作成 |

---

**作成者:** Claude
**承認者:** [承認者名]
**最終更新:** 2025-10-28
