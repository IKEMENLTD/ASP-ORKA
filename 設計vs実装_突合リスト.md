# CATS システム - 設計vs実装 突合リスト

**作成日:** 2025-11-02
**目的:** 設計書と実装の不整合を特定し、未実装機能を明確化する

---

## 📋 総合評価サマリー

| カテゴリ | 設計済 | 実装済 | 実装率 | 優先度 |
|---------|-------|-------|--------|--------|
| AFAD連携システム | ✅ | ⚠️ 部分実装 | 60% | 🔴 高 |
| REST API | ✅ | ❌ 未実装 | 0% | 🔴 高 |
| データベーススキーマ改善 | ✅ | ❌ 未実装 | 0% | 🟡 中 |
| 新規会員登録 | - | ✅ | 100% | 🟢 完了 |

---

## 1. AFAD連携システム【実装率: 60%】

### ✅ 実装済み機能

#### Phase 2: link.php 拡張
- ✅ `GetAFADSessionId()` 関数実装（link.php:538）
- ✅ `CheckQuery()` に `afad_sid` パラメータ追加

#### Phase 3: add.php 拡張
- ✅ `SendAFADPostback()` 関数実装（add.php:527）
- ✅ `SendHTTPRequest()` 関数実装（add.php:603）
- ✅ `LogAFADPostback()` 関数実装（add.php:663）

### ❌ 未実装機能

#### Phase 1: データベース準備【重要度: 🔴 高】

**access テーブル（lst/access.csv）:**
```diff
- afad_session_id カラムが存在しない
```

**必要な変更:**
```csv
# lst/access.csv に以下を追加:
afad_session_id,varchar,255,Null,Null,
```

**adwares テーブル（lst/adwares.csv）:**
```diff
- afad_enabled カラムが存在しない
- afad_postback_url カラムが存在しない
- afad_gid カラムが存在しない
- afad_param_name カラムが存在しない
```

**必要な変更:**
```csv
# lst/adwares.csv に以下を追加:
afad_enabled,boolean,,,Null,
afad_postback_url,string,,Null,Null,
afad_gid,varchar,100,Null,Null,
afad_param_name,varchar,50,Null,Null,
```

**secretAdwares テーブル（lst/module/secret_adwares.csv）:**
```diff
- 同様にAFAD関連カラムが存在しない
```

**afad_postback_log テーブル:**
```diff
- テーブル自体が存在しない
```

**必要なアクション:**
- `lst/afad_postback_log.csv` を新規作成
- テーブル定義CSVに以下のカラムを定義:
  - id (char, 32)
  - pay_id (char, 32)
  - access_id (char, 32)
  - afad_session_id (varchar, 255)
  - postback_url (text)
  - http_status (int)
  - response_body (text)
  - error_message (text)
  - sent_at (int)
  - retry_count (int)

#### Phase 2: link.php の統合【重要度: 🟡 中】

**現状:**
- 関数は実装されているが、`AddAccess()` での呼び出しが未統合の可能性

**確認が必要:**
```php
// link.php の AddAccess() 内で
$afadSessionId = GetAFADSessionId( $adwares_ );
if( $afadSessionId ) {
    $access->setData( 'afad_session_id' , $afadSessionId );
}
```
↑このコードが実際に存在するか確認が必要

#### Phase 3: add.php の統合【重要度: 🟡 中】

**現状:**
- 関数は実装されているが、`AddSuccessReward()` での呼び出しが未統合の可能性

**確認が必要:**
```php
// add.php の AddSuccessReward() 最後に
SendAFADPostback( $adwares_ , $access_ , $pay_ , $sales_ );
```
↑このコードが実際に存在するか確認が必要

#### Phase 4: 管理画面【重要度: 🔴 高】

**広告編集フォーム（adwares）:**
```diff
- AFAD連携設定フィールドが存在しない
```

**必要な追加項目:**
1. AFAD連携を有効にする（チェックボックス）
2. AFADポストバックURL（テキストフィールド）
3. AFAD広告グループID（テキストフィールド）
4. AFADセッションIDパラメータ名（テキストフィールド、デフォルト: afad_sid）

**対象ファイル:**
- `template/pc/adwares/RegistAdmin.html`
- `template/pc/adwares/Edit.html`
- `template/pc/module/secretAdwares/RegistAdmin.html`
- `template/pc/module/secretAdwares/Edit.html`

#### Phase 5: テスト【重要度: 🟡 中】

**未実施:**
- ✗ クリック時のAFADセッションID保存テスト
- ✗ 成果発生時のポストバック送信テスト
- ✗ ログ記録確認テスト
- ✗ エラーハンドリングテスト

---

## 2. REST API【実装率: 0%】

### ❌ 完全未実装

**設計書:** `詳細設計_REST_API仕様書.md`

#### 未実装エンドポイント【全て】

**認証:**
- ❌ `POST /api/v1/auth/login`
- ❌ `POST /api/v1/oauth/token`

**キャンペーン:**
- ❌ `GET /api/v1/campaigns`
- ❌ `GET /api/v1/campaigns/{id}`
- ❌ `POST /api/v1/campaigns`
- ❌ `PATCH /api/v1/campaigns/{id}`

**トラッキング:**
- ❌ `POST /api/v1/tracking/click`
- ❌ `POST /api/v1/tracking/conversion`

**コンバージョン:**
- ❌ `GET /api/v1/conversions`
- ❌ `PATCH /api/v1/conversions/{id}`

**支払い:**
- ❌ `GET /api/v1/payments`
- ❌ `POST /api/v1/payments/request`

**レポート:**
- ❌ `GET /api/v1/reports/performance`
- ❌ `GET /api/v1/reports/export`

**アカウント:**
- ❌ `GET /api/v1/account/me`
- ❌ `PATCH /api/v1/account/me`

#### 未実装機能

**認証・認可:**
- ❌ APIキー認証
- ❌ OAuth 2.0フロー
- ❌ JWT トークン発行
- ❌ スコープベースのアクセス制御

**共通機能:**
- ❌ レート制限（Rate Limiting）
- ❌ ページネーション
- ❌ エラーハンドリング（統一フォーマット）
- ❌ CORS設定
- ❌ Webhook送信

**必要なディレクトリ構造:**
```
/api
  /v1
    /auth
    /campaigns
    /tracking
    /conversions
    /payments
    /reports
    /account
```

**必要なファイル:**
- `api/index.php` - エントリーポイント
- `api/Router.php` - ルーティング
- `api/Auth.php` - 認証処理
- `api/RateLimiter.php` - レート制限
- `api/Response.php` - レスポンス整形

---

## 3. データベーススキーマ改善【実装率: 0%】

### ❌ 完全未実装

**設計書:** `詳細設計_データベーススキーマ改善版.md`

#### 現状のテーブル構造

**既存:**
- CSV ベースのデータベース（lst/*.csv、tdb/*.csv）
- 非正規化されたスキーマ
- インデックス戦略なし
- パーティショニングなし

#### 設計書で提案されている改善

**1. users テーブル統合:**
```diff
- 現状: admin.csv、nuser.csv が個別に存在
+ 提案: users テーブルに統合、user_type カラムで区別
```

**2. campaigns テーブル:**
```diff
- 現状: adwares.csv、secretAdwares.csv が個別
+ 提案: campaigns テーブルに統合、visibility カラムで区別
```

**3. 追加カラム（UUID、タイムスタンプなど）:**
```diff
- 現状: id (MD5ハッシュ)、regist (timestamp) のみ
+ 提案: uuid, created_at, updated_at, deleted_at 追加
```

**4. インデックス戦略:**
```diff
- 現状: インデックスなし（CSV検索はシーケンシャルスキャン）
+ 提案: 複合インデックス、外部キー制約
```

**5. MySQL移行:**
```diff
- 現状: CSV ファイル
+ 提案: MySQL 8.0 + InnoDB
```

**移行の複雑性:**
- 🔴 高リスク: 既存システムの根幹に関わる変更
- 💰 高コスト: データ移行、テスト、ダウンタイムが必要
- ⏱️ 長期プロジェクト: 数週間〜数ヶ月規模

---

## 4. その他の設計書

### 詳細設計_不正検知システム.md【未確認】
- 実装状況の確認が必要

### 詳細設計_セキュリティ実装ガイド.md【未確認】
- 実装状況の確認が必要

### 詳細設計_パフォーマンスチューニングガイド.md【未確認】
- 実装状況の確認が必要

### 詳細設計_デプロイ・運用手順書.md【未確認】
- 実装状況の確認が必要

---

## 5. 最近修正された機能

### ✅ 新規会員登録【2025-11-02 修正】

**問題:**
- 新規会員登録ボタンで「アクセス権限がありません」エラー

**修正内容:**
- `custom/head_main.php`: nUser GUIManager の確実な作成
- `include/Template.php`: 過剰なログ出力の削減

**状態:** ✅ 完了

---

## 📊 優先度別アクションプラン

### 🔴 最優先（即座に対応が必要）

#### 1. AFAD連携の完全動作化

**手順:**
1. `lst/access.csv` に `afad_session_id` カラム追加
2. `lst/adwares.csv` に AFAD関連4カラム追加
3. `lst/module/secret_adwares.csv` に同様のカラム追加
4. `lst/afad_postback_log.csv` 新規作成
5. link.php の `AddAccess()` で実際にAFADセッションID保存を呼び出し
6. add.php の `AddSuccessReward()` で実際にポストバック送信を呼び出し
7. 広告編集フォームにAFAD設定項目追加
8. 統合テスト実施

**工数見積もり:** 2〜3日

#### 2. REST API 基本実装

**最小限の実装（MVP）:**
- 認証エンドポイント (`/api/v1/auth/login`)
- キャンペーン一覧・詳細 (`/api/v1/campaigns`)
- トラッキング (`/api/v1/tracking/click`, `/api/v1/tracking/conversion`)

**工数見積もり:** 1週間

### 🟡 中優先（計画的に対応）

#### 3. テストカバレッジ向上

- AFAD連携のエンドツーエンドテスト
- REST APIの統合テスト
- 負荷テスト

**工数見積もり:** 3〜5日

#### 4. ドキュメント整備

- API仕様書の実装版
- 管理者マニュアル
- トラブルシューティングガイド

**工数見積もり:** 2〜3日

### 🟢 低優先（長期的な改善）

#### 5. データベーススキーマ改善

- MySQL移行計画の策定
- 段階的マイグレーション
- パフォーマンステスト

**工数見積もり:** 4〜6週間

#### 6. 不正検知システム

- 設計書の確認と実装状況の評価
- 段階的な実装

**工数見積もり:** 2〜3週間

---

## 📝 次のステップ

### 即座に実行すべきアクション

1. **AFAD連携のデータベーススキーマ追加**
   - lst/access.csv 編集
   - lst/adwares.csv 編集
   - lst/afad_postback_log.csv 作成

2. **link.php と add.php の統合確認**
   - 実際の呼び出しコードが存在するか確認
   - 存在しない場合は追加

3. **広告編集フォームの拡張**
   - AFAD設定項目の追加

4. **動作テスト**
   - クリック → セッションID保存 → 成果発生 → ポストバック送信
   - 全フロー確認

### 質問事項

1. **REST API の優先度は？**
   - 外部システムとの連携が必要か？
   - 必要な場合、どのエンドポイントが最優先か？

2. **データベース移行は検討すべきか？**
   - CSV からMySQL への移行は長期目標か？
   - 現状のCSVシステムで問題は発生しているか？

3. **その他の設計書の実装状況は？**
   - 不正検知システム
   - セキュリティ実装
   - パフォーマンスチューニング

---

**以上**
