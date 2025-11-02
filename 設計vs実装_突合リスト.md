# CATS システム - 設計vs実装 突合リスト（Supabase版）

**作成日:** 2025-11-02
**更新日:** 2025-11-02（Supabase対応版）
**目的:** 設計書と実装の不整合を特定し、未実装機能を明確化する

**重要:** このシステムは **Supabase (PostgreSQL)** を使用しています

---

## 🏗 システム構成

```
データ層:
- Supabase (PostgreSQL) ← 実際のデータベース
- lst/*.csv ← スキーマ定義（GUIManagerが読み込む）
- migration/*.sql ← Supabaseマイグレーションファイル

接続:
- include/extends/SupabaseStorageClient.php ← Supabase Storage連携
- .env ← Supabase接続設定
```

---

## 📋 総合評価サマリー

| カテゴリ | 設計済 | 実装済 | 実装率 | 優先度 |
|---------|-------|-------|--------|--------|
| AFAD連携システム | ✅ | ⚠️ 部分実装 | 70% | 🔴 高 |
| REST API | ✅ | ❌ 未実装 | 0% | 🔴 高 |
| Supabase連携 | - | ✅ | 100% | 🟢 完了 |
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

**Supabaseマイグレーション:**
```diff
✅ migration/afad_integration_supabase.sql は作成済み
❓ Supabaseに実行されているか未確認
```

**必要な確認:**
```sql
-- Supabaseで以下を実行して確認:
SELECT column_name
FROM information_schema.columns
WHERE table_name = 'access' AND column_name = 'afad_session_id';
```

**スキーマ定義ファイル（GUIManager用）:**

**lst/access.csv:**
```diff
- afad_session_id カラムが存在しない
```

**必要な変更:**
```csv
# lst/access.csv の最後に追加:
afad_session_id,varchar,255,Null,Null,
```

**lst/adwares.csv:**
```diff
- afad_enabled カラムが存在しない
- afad_postback_url カラムが存在しない
- afad_gid カラムが存在しない
- afad_param_name カラムが存在しない
```

**必要な変更:**
```csv
# lst/adwares.csv の url_over 行の後に追加:
afad_enabled,boolean,,Null,Null,
afad_postback_url,string,,Null,Null,
afad_gid,varchar,100,Null,Null,
afad_param_name,varchar,50,Null,Null,
```

**lst/module/secret_adwares.csv:**
```diff
- 同様にAFAD関連カラムが存在しない
```

**lst/afad_postback_log.csv:**
```diff
- ファイル自体が存在しない
```

**必要なアクション:**
1. ✅ Supabaseマイグレーション作成済み（`migration/afad_integration_supabase.sql`）
2. ❌ Supabaseでマイグレーション実行（実行済みか確認が必要）
3. ❌ `lst/access.csv` にカラム追加
4. ❌ `lst/adwares.csv` にカラム追加
5. ❌ `lst/module/secret_adwares.csv` にカラム追加
6. ❌ `lst/afad_postback_log.csv` 新規作成

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

## 3. Supabase (PostgreSQL) 連携状況【実装率: 100%】

### ✅ 実装済み

**現状:**
- ✅ Supabase (PostgreSQL) に移行済み
- ✅ migration/*.sql でスキーマ定義
- ✅ include/extends/SupabaseStorageClient.php でStorage連携
- ✅ .env でSupabase接続設定

**マイグレーションファイル:**
- ✅ `migration/001_create_all_tables.sql` - 全テーブル作成
- ✅ `migration/002_insert_initial_data.sql` - 初期データ
- ✅ `migration/afad_integration_supabase.sql` - AFAD連携（未実行の可能性）

**スキーマファイル:**
- ✅ `migration/schema/*.sql` - 各テーブル定義

**注意点:**
```
⚠️ lst/*.csv と migration/schema/*.sql の二重管理
- lst/*.csv = GUIManagerが読むスキーマ定義
- migration/schema/*.sql = Supabaseの実際のスキーマ
- 両方を同期する必要がある
```

---

## 4. データベーススキーマ改善提案【実装率: N/A】

### 📝 設計書との比較

**設計書:** `詳細設計_データベーススキーマ改善版.md`

この設計書は **理想的なスキーマ** を提案していますが、現状のSupabaseスキーマとは異なります。

#### 設計書で提案されている改善（未採用）

**1. users テーブル統合:**
```diff
- 現状: admin, nuser テーブルが個別
+ 提案: users テーブルに統合、user_type カラムで区別（未採用）
```

**2. campaigns テーブル:**
```diff
- 現状: adwares, secretAdwares テーブルが個別
+ 提案: campaigns テーブルに統合（未採用）
```

**3. UUID、created_at、updated_at:**
```diff
- 現状: id (CHAR), regist (BIGINT)
+ 提案: uuid, created_at, updated_at, deleted_at（未採用）
```

**評価:**
- 🟡 設計書は理想的だが、既存システムとの互換性で採用困難
- 🟡 段階的な移行が必要（長期プロジェクト）

---

## 5. その他の設計書

### 詳細設計_不正検知システム.md【未確認】
- 実装状況の確認が必要

### 詳細設計_セキュリティ実装ガイド.md【未確認】
- 実装状況の確認が必要

### 詳細設計_パフォーマンスチューニングガイド.md【未確認】
- 実装状況の確認が必要

### 詳細設計_デプロイ・運用手順書.md【未確認】
- 実装状況の確認が必要

---

## 6. 最近修正された機能

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
1. **Supabaseマイグレーション実行確認**
   - Supabase Dashboardで `access` テーブルに `afad_session_id` カラムが存在するか確認
   - 存在しない場合: `migration/afad_integration_supabase.sql` を実行

2. **スキーマ定義ファイル（GUIManager用）更新**
   - `lst/access.csv` に `afad_session_id` カラム追加
   - `lst/adwares.csv` に AFAD関連4カラム追加
   - `lst/module/secret_adwares.csv` に同様のカラム追加
   - `lst/afad_postback_log.csv` 新規作成

3. **統合確認**
   - link.php の `AddAccess()` で実際にAFADセッションID保存を呼び出し
   - add.php の `AddSuccessReward()` で実際にポストバック送信を呼び出し

4. **管理画面拡張**
   - 広告編集フォームにAFAD設定項目追加

5. **統合テスト実施**
   - クリック → Supabaseに保存 → 成果発生 → ポストバック送信

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

1. **Supabaseマイグレーション確認・実行**
   - Supabase Dashboardで access.afad_session_id の存在確認
   - 未実行なら `migration/afad_integration_supabase.sql` を実行

2. **スキーマ定義ファイル（lst/*.csv）の更新**
   - lst/access.csv 編集
   - lst/adwares.csv 編集
   - lst/module/secret_adwares.csv 編集
   - lst/afad_postback_log.csv 新規作成

3. **link.php と add.php の統合確認**
   - 実際の呼び出しコードが存在するか確認
   - 存在しない場合は追加

4. **広告編集フォームの拡張**
   - AFAD設定項目の追加

5. **動作テスト（エンドツーエンド）**
   - クリック → Supabaseに保存 → 成果発生 → ポストバック送信
   - afad_postback_log テーブルへの記録確認

### 質問事項

1. **REST API の優先度は？**
   - 外部システムとの連携が必要か？
   - 必要な場合、どのエンドポイントが最優先か？

2. **データベーススキーマ改善は必要か？**
   - 現状: Supabase (PostgreSQL) 使用中
   - 設計書の理想的なスキーマ（users統合、uuid追加等）への移行は必要か？
   - 現状のスキーマで問題は発生しているか？

3. **その他の設計書の実装状況は？**
   - 不正検知システム
   - セキュリティ実装
   - パフォーマンスチューニング

---

**以上**
