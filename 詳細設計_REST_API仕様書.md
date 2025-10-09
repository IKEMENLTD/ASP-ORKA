# REST API 詳細仕様書 v1.0

## 📋 目次
1. [API概要](#api概要)
2. [認証・認可](#認証認可)
3. [エンドポイント一覧](#エンドポイント一覧)
4. [エラーハンドリング](#エラーハンドリング)
5. [レート制限](#レート制限)
6. [Webhook](#webhook)

---

## 🌐 API概要

### ベースURL
```
Production:  https://api.affiliate-system.com/v1
Staging:     https://api-staging.affiliate-system.com/v1
Development: http://localhost:8000/api/v1
```

### リクエスト形式
- **Content-Type**: `application/json`
- **文字コード**: UTF-8
- **日時形式**: ISO 8601 (例: `2024-01-15T09:30:00+09:00`)

### レスポンス構造

#### 成功時
```json
{
  "success": true,
  "data": {
    // レスポンスデータ
  },
  "meta": {
    "timestamp": "2024-01-15T09:30:00+09:00",
    "request_id": "req_1234567890abcdef"
  }
}
```

#### エラー時
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力値が不正です",
    "details": [
      {
        "field": "email",
        "message": "有効なメールアドレスを入力してください"
      }
    ]
  },
  "meta": {
    "timestamp": "2024-01-15T09:30:00+09:00",
    "request_id": "req_1234567890abcdef"
  }
}
```

---

## 🔐 認証・認可

### 1. API キー認証

```http
GET /api/v1/campaigns HTTP/1.1
Host: api.affiliate-system.com
Authorization: Bearer YOUR_API_KEY_HERE
```

### 2. OAuth 2.0 フロー

#### ステップ1: 認可コード取得
```http
GET /oauth/authorize?
    client_id=CLIENT_ID&
    redirect_uri=https://example.com/callback&
    response_type=code&
    scope=read:campaigns write:conversions&
    state=random_state_string
```

#### ステップ2: アクセストークン取得
```http
POST /oauth/token HTTP/1.1
Content-Type: application/json

{
  "grant_type": "authorization_code",
  "client_id": "CLIENT_ID",
  "client_secret": "CLIENT_SECRET",
  "code": "AUTHORIZATION_CODE",
  "redirect_uri": "https://example.com/callback"
}
```

**レスポンス:**
```json
{
  "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "refresh_token": "def502001234...",
  "scope": "read:campaigns write:conversions"
}
```

#### ステップ3: リフレッシュトークン使用
```http
POST /oauth/token HTTP/1.1
Content-Type: application/json

{
  "grant_type": "refresh_token",
  "client_id": "CLIENT_ID",
  "client_secret": "CLIENT_SECRET",
  "refresh_token": "REFRESH_TOKEN"
}
```

### 3. スコープ定義

| スコープ | 説明 |
|---------|------|
| `read:campaigns` | キャンペーン情報の読み取り |
| `write:campaigns` | キャンペーンの作成・編集 |
| `read:conversions` | コンバージョンデータの読み取り |
| `write:conversions` | コンバージョンの登録 |
| `read:payments` | 支払い情報の読み取り |
| `read:reports` | レポートデータの読み取り |
| `admin:*` | 管理者権限（全操作） |

---

## 📡 エンドポイント一覧

### 認証 (Authentication)

#### POST /auth/login
ログイン

**リクエスト:**
```json
{
  "email": "user@example.com",
  "password": "SecurePassword123!",
  "remember_me": true
}
```

**レスポンス:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIs...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "user": {
      "id": 12345,
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "email": "user@example.com",
      "user_type": "affiliate",
      "status": "active"
    }
  }
}
```

---

### キャンペーン (Campaigns)

#### GET /campaigns
キャンペーン一覧取得

**クエリパラメータ:**
| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| page | integer | ✗ | ページ番号 (デフォルト: 1) |
| per_page | integer | ✗ | 1ページあたり件数 (デフォルト: 20, 最大: 100) |
| status | string | ✗ | ステータス (active, paused, completed) |
| category_id | integer | ✗ | カテゴリID |
| sort | string | ✗ | ソート (created_at, name, commission_value) |
| order | string | ✗ | 順序 (asc, desc) |

**リクエスト例:**
```http
GET /api/v1/campaigns?page=1&per_page=20&status=active&sort=commission_value&order=desc
Authorization: Bearer YOUR_TOKEN
```

**レスポンス:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1001,
      "uuid": "550e8400-e29b-41d4-a716-446655440001",
      "name": "春のキャンペーン2024",
      "slug": "spring-campaign-2024",
      "description": "新生活応援キャンペーン",
      "commission_type": "percentage",
      "commission_value": 10.00,
      "commission_currency": "JPY",
      "click_commission": 5.00,
      "destination_url": "https://example.com/campaign/spring2024",
      "status": "active",
      "visibility": "public",
      "starts_at": "2024-03-01T00:00:00+09:00",
      "ends_at": "2024-05-31T23:59:59+09:00",
      "statistics": {
        "total_clicks": 15234,
        "total_conversions": 456,
        "conversion_rate": 2.99,
        "total_revenue": 1234567.00
      },
      "created_at": "2024-01-15T10:30:00+09:00",
      "updated_at": "2024-01-20T15:45:00+09:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8,
    "has_next": true,
    "has_prev": false
  }
}
```

---

#### GET /campaigns/{id}
キャンペーン詳細取得

**レスポンス:**
```json
{
  "success": true,
  "data": {
    "id": 1001,
    "uuid": "550e8400-e29b-41d4-a716-446655440001",
    "name": "春のキャンペーン2024",
    "description": "新生活応援キャンペーン",
    "commission_type": "percentage",
    "commission_value": 10.00,
    "tier1_rate": 5.00,
    "tier2_rate": 2.00,
    "tier3_rate": 1.00,
    "budget": {
      "type": "monthly",
      "amount": 1000000.00,
      "spent": 234567.00,
      "remaining": 765433.00
    },
    "targeting": {
      "geo": ["JP", "US"],
      "devices": ["desktop", "mobile"],
      "time_slots": [
        {"day": "monday", "start": "09:00", "end": "18:00"}
      ]
    },
    "tracking": {
      "conversion_window_hours": 720,
      "click_interval_seconds": 3600
    },
    "creatives": [
      {
        "id": 501,
        "type": "banner",
        "size": "300x250",
        "url": "https://cdn.example.com/banners/spring2024_300x250.jpg"
      }
    ]
  }
}
```

---

#### POST /campaigns
キャンペーン作成（広告主のみ）

**リクエスト:**
```json
{
  "name": "夏のセールキャンペーン",
  "description": "最大50%オフの夏セール",
  "category_id": 5,
  "destination_url": "https://example.com/summer-sale",
  "commission_type": "percentage",
  "commission_value": 15.00,
  "click_commission": 10.00,
  "budget": {
    "type": "total",
    "amount": 500000.00
  },
  "tier_rates": {
    "tier1": 5.00,
    "tier2": 3.00,
    "tier3": 1.00
  },
  "targeting": {
    "geo": ["JP"],
    "devices": ["all"]
  },
  "starts_at": "2024-06-01T00:00:00+09:00",
  "ends_at": "2024-08-31T23:59:59+09:00",
  "visibility": "public"
}
```

**レスポンス: 201 Created**
```json
{
  "success": true,
  "data": {
    "id": 1002,
    "uuid": "550e8400-e29b-41d4-a716-446655440002",
    "status": "draft",
    "tracking_url": "https://track.affiliate-system.com/click/1002/{affiliate_id}"
  }
}
```

---

### トラッキング (Tracking)

#### POST /tracking/click
クリック記録（通常はJavaScriptから自動送信）

**リクエスト:**
```json
{
  "campaign_id": 1001,
  "affiliate_id": 12345,
  "tracking_id": "trk_1234567890abcdef",
  "ip_address": "203.0.113.42",
  "user_agent": "Mozilla/5.0...",
  "referer": "https://affiliate-site.com/blog/post",
  "utm_params": {
    "source": "blog",
    "medium": "article",
    "campaign": "spring2024"
  }
}
```

**レスポンス: 201 Created**
```json
{
  "success": true,
  "data": {
    "click_id": 987654321,
    "redirect_url": "https://example.com/campaign/spring2024?aid=12345"
  }
}
```

---

#### POST /tracking/conversion
コンバージョン記録

**リクエスト:**
```json
{
  "tracking_id": "trk_1234567890abcdef",
  "order_id": "ORDER-2024-0001",
  "order_amount": 15000.00,
  "currency": "JPY",
  "products": [
    {
      "product_id": "PROD-001",
      "product_name": "商品A",
      "quantity": 2,
      "price": 7500.00
    }
  ],
  "customer": {
    "email_hash": "5d41402abc4b2a76b9719d911017c592",
    "is_new": true
  },
  "metadata": {
    "coupon_code": "SPRING2024"
  }
}
```

**レスポンス: 201 Created**
```json
{
  "success": true,
  "data": {
    "conversion_id": 456789,
    "uuid": "550e8400-e29b-41d4-a716-446655440003",
    "commission_amount": 1500.00,
    "status": "pending",
    "estimated_approval_date": "2024-02-15T00:00:00+09:00"
  }
}
```

---

### コンバージョン (Conversions)

#### GET /conversions
コンバージョン一覧取得

**クエリパラメータ:**
| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| status | string | ✗ | pending, approved, rejected |
| date_from | date | ✗ | 開始日 (YYYY-MM-DD) |
| date_to | date | ✗ | 終了日 (YYYY-MM-DD) |
| campaign_id | integer | ✗ | キャンペーンID |

**レスポンス:**
```json
{
  "success": true,
  "data": [
    {
      "id": 456789,
      "uuid": "550e8400-e29b-41d4-a716-446655440003",
      "campaign": {
        "id": 1001,
        "name": "春のキャンペーン2024"
      },
      "order_id": "ORDER-2024-0001",
      "order_amount": 15000.00,
      "commission_amount": 1500.00,
      "currency": "JPY",
      "status": "approved",
      "converted_at": "2024-01-15T14:30:00+09:00",
      "approved_at": "2024-01-16T10:00:00+09:00"
    }
  ],
  "meta": {
    "summary": {
      "total_conversions": 456,
      "total_commission": 123456.00,
      "pending_commission": 45678.00,
      "approved_commission": 77778.00
    }
  }
}
```

---

#### PATCH /conversions/{id}
コンバージョンステータス更新（広告主・管理者のみ）

**リクエスト:**
```json
{
  "status": "approved",
  "notes": "正常な購入を確認"
}
```

**レスポンス:**
```json
{
  "success": true,
  "data": {
    "id": 456789,
    "status": "approved",
    "approved_at": "2024-01-16T10:00:00+09:00",
    "approved_by": 99999
  }
}
```

---

### 支払い (Payments)

#### GET /payments
支払い履歴取得

**レスポンス:**
```json
{
  "success": true,
  "data": [
    {
      "id": 78901,
      "amount": 125000.00,
      "currency": "JPY",
      "payment_method": "bank_transfer",
      "period": {
        "start": "2024-01-01",
        "end": "2024-01-31"
      },
      "status": "completed",
      "invoice_number": "INV-2024-0001",
      "invoice_url": "https://cdn.example.com/invoices/INV-2024-0001.pdf",
      "scheduled_at": "2024-02-15",
      "paid_at": "2024-02-15T10:30:00+09:00"
    }
  ],
  "meta": {
    "summary": {
      "total_paid": 500000.00,
      "pending_amount": 75000.00,
      "next_payment_date": "2024-03-15"
    }
  }
}
```

---

#### POST /payments/request
支払いリクエスト（最低支払額以上の残高がある場合）

**リクエスト:**
```json
{
  "payment_method": "bank_transfer",
  "bank_details": {
    "bank_name": "三菱UFJ銀行",
    "branch_name": "渋谷支店",
    "account_type": "普通",
    "account_number": "1234567",
    "account_holder": "ヤマダ タロウ"
  }
}
```

**レスポンス: 201 Created**
```json
{
  "success": true,
  "data": {
    "payment_id": 78902,
    "amount": 75000.00,
    "estimated_payment_date": "2024-03-15",
    "status": "pending"
  }
}
```

---

### レポート (Reports)

#### GET /reports/performance
パフォーマンスレポート

**クエリパラメータ:**
| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| date_from | date | ✓ | 開始日 |
| date_to | date | ✓ | 終了日 |
| group_by | string | ✗ | day, week, month |
| campaign_id | integer | ✗ | キャンペーンID |

**リクエスト例:**
```http
GET /api/v1/reports/performance?date_from=2024-01-01&date_to=2024-01-31&group_by=day
```

**レスポンス:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_clicks": 15234,
      "total_conversions": 456,
      "total_revenue": 1234567.00,
      "total_commission": 123456.00,
      "conversion_rate": 2.99,
      "epc": 81.02,
      "average_order_value": 2706.50
    },
    "timeline": [
      {
        "date": "2024-01-01",
        "clicks": 523,
        "conversions": 15,
        "revenue": 42000.00,
        "commission": 4200.00,
        "cvr": 2.87
      },
      {
        "date": "2024-01-02",
        "clicks": 487,
        "conversions": 18,
        "revenue": 51000.00,
        "commission": 5100.00,
        "cvr": 3.70
      }
    ],
    "top_campaigns": [
      {
        "campaign_id": 1001,
        "campaign_name": "春のキャンペーン2024",
        "clicks": 8234,
        "conversions": 256,
        "commission": 65400.00
      }
    ]
  }
}
```

---

#### GET /reports/export
レポートエクスポート

**クエリパラメータ:**
| パラメータ | 型 | 必須 | 説明 |
|----------|---|------|------|
| format | string | ✓ | csv, excel, json |
| date_from | date | ✓ | 開始日 |
| date_to | date | ✓ | 終了日 |

**レスポンス:**
```json
{
  "success": true,
  "data": {
    "download_url": "https://cdn.example.com/exports/report_20240115_093000.csv",
    "expires_at": "2024-01-15T10:30:00+09:00"
  }
}
```

---

### アカウント (Account)

#### GET /account/me
現在のユーザー情報取得

**レスポンス:**
```json
{
  "success": true,
  "data": {
    "id": 12345,
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "user_type": "affiliate",
    "email": "user@example.com",
    "username": "user123",
    "display_name": "山田太郎",
    "affiliate_code": "AFF-YAMADA-001",
    "rank": {
      "id": 3,
      "name": "ゴールド",
      "commission_boost": 10
    },
    "balance": {
      "available": 125000.00,
      "pending": 45000.00,
      "total_earned": 500000.00
    },
    "statistics": {
      "total_clicks": 25678,
      "total_conversions": 789,
      "conversion_rate": 3.07
    },
    "tier_network": {
      "tier1_count": 15,
      "tier2_count": 78,
      "tier3_count": 234
    }
  }
}
```

---

#### PATCH /account/me
ユーザー情報更新

**リクエスト:**
```json
{
  "display_name": "山田太郎",
  "phone": "090-1234-5678",
  "email_notifications": true,
  "language_code": "ja",
  "timezone": "Asia/Tokyo"
}
```

---

## ❌ エラーハンドリング

### エラーコード一覧

| HTTPステータス | エラーコード | 説明 |
|--------------|------------|------|
| 400 | VALIDATION_ERROR | 入力値エラー |
| 401 | UNAUTHORIZED | 認証エラー |
| 403 | FORBIDDEN | 権限エラー |
| 404 | NOT_FOUND | リソース不存在 |
| 409 | CONFLICT | 競合エラー |
| 422 | UNPROCESSABLE_ENTITY | 処理不可能なリクエスト |
| 429 | RATE_LIMIT_EXCEEDED | レート制限超過 |
| 500 | INTERNAL_SERVER_ERROR | サーバーエラー |
| 503 | SERVICE_UNAVAILABLE | メンテナンス中 |

### エラーレスポンス例

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "入力値が不正です",
    "details": [
      {
        "field": "email",
        "message": "有効なメールアドレスを入力してください",
        "code": "INVALID_EMAIL"
      },
      {
        "field": "commission_value",
        "message": "0以上100以下の値を入力してください",
        "code": "OUT_OF_RANGE"
      }
    ]
  },
  "meta": {
    "timestamp": "2024-01-15T09:30:00+09:00",
    "request_id": "req_1234567890abcdef",
    "documentation_url": "https://docs.affiliate-system.com/api/errors/VALIDATION_ERROR"
  }
}
```

---

## 🚦 レート制限

### 制限値

| プラン | リクエスト数 | 期間 |
|-------|------------|------|
| Free | 100 | 1時間 |
| Basic | 1,000 | 1時間 |
| Pro | 10,000 | 1時間 |
| Enterprise | カスタム | - |

### レスポンスヘッダー

```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 987
X-RateLimit-Reset: 1705288800
```

### 制限超過時のレスポンス

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "APIレート制限を超過しました",
    "retry_after": 3600
  }
}
```

---

## 🪝 Webhook

### Webhook登録

#### POST /webhooks

**リクエスト:**
```json
{
  "name": "コンバージョン通知",
  "url": "https://your-app.com/webhooks/conversions",
  "events": [
    "conversion.created",
    "conversion.approved",
    "conversion.rejected"
  ],
  "secret": "whsec_1234567890abcdefghijklmnopqrstuvwxyz"
}
```

### Webhook送信フォーマット

**ヘッダー:**
```http
POST /webhooks/conversions HTTP/1.1
Host: your-app.com
Content-Type: application/json
X-Webhook-Signature: sha256=5d41402abc4b2a76b9719d911017c592
X-Webhook-Event: conversion.approved
X-Webhook-Id: whk_1234567890
X-Webhook-Timestamp: 1705288800
```

**ペイロード:**
```json
{
  "event": "conversion.approved",
  "timestamp": "2024-01-15T10:00:00+09:00",
  "data": {
    "id": 456789,
    "campaign_id": 1001,
    "affiliate_id": 12345,
    "order_id": "ORDER-2024-0001",
    "order_amount": 15000.00,
    "commission_amount": 1500.00,
    "status": "approved",
    "approved_at": "2024-01-15T10:00:00+09:00"
  }
}
```

### 署名検証

```php
function verifyWebhookSignature($payload, $signature, $secret) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}
```

---

## 📚 SDKサンプル

### PHP SDK

```php
use AffiliateSystem\Client;

$client = new Client('YOUR_API_KEY');

// キャンペーン一覧取得
$campaigns = $client->campaigns()->list([
    'status' => 'active',
    'per_page' => 20
]);

// コンバージョン記録
$conversion = $client->tracking()->createConversion([
    'tracking_id' => 'trk_123',
    'order_id' => 'ORDER-001',
    'order_amount' => 15000.00
]);
```

### JavaScript SDK

```javascript
import AffiliateSystem from '@affiliate-system/sdk';

const client = new AffiliateSystem('YOUR_API_KEY');

// キャンペーン詳細取得
const campaign = await client.campaigns.get(1001);

// クリック記録
await client.tracking.recordClick({
  campaign_id: 1001,
  affiliate_id: 12345
});
```

---

このAPI仕様により、外部システムとのシームレスな連携が可能になります。
