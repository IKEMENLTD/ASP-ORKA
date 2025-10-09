# Phase 5: Renderデプロイガイド

**所要時間**: 1-2時間
**前提条件**: Phase 1-4完了

---

## 📋 Phase 5 概要

ローカル開発環境からRender本番環境へのデプロイを実施します。

### 実施内容

- ✅ **準備完了**: デプロイ設定ファイル作成
- ✅ **準備完了**: GitHubリポジトリ準備スクリプト
- 🔄 **実施待ち**: GitHubリポジトリ作成・プッシュ
- 🔄 **実施待ち**: Render Web Service作成
- 🔄 **実施待ち**: 環境変数設定
- 🔄 **実施待ち**: デプロイ実行

---

## 🎯 Step 1: GitHubリポジトリ作成

### 1.1 GitHubで新規リポジトリ作成

```
1. https://github.com にアクセス
2. 右上の「+」→「New repository」をクリック
3. 以下を入力:
   - Repository name: affiliate-system-pro
   - Description: Affiliate System Pro - Render × Supabase
   - Visibility: Private (推奨) または Public
   - Initialize: ❌ チェックしない（既存プロジェクト）
4. 「Create repository」をクリック
```

### 1.2 ローカルリポジトリ準備

```bash
cd "/mnt/c/Users/ooxmi/Downloads/アフィリエイトシステムプロ＿システム本体003CSS未タッチ"

# Git初期化スクリプト実行
./setup_git.sh
```

このスクリプトは以下を実行します：
- Gitリポジトリ初期化
- .gitkeepファイル作成
- ファイルをステージング

### 1.3 リモートリポジトリ追加・プッシュ

```bash
# リモートリポジトリ追加（YOUR_USERNAMEを実際のユーザー名に変更）
git remote add origin https://github.com/YOUR_USERNAME/affiliate-system-pro.git

# 初回コミット
git commit -m "Initial commit: Render × Supabase migration complete

- Phase 1: UTF-8 character encoding conversion
- Phase 2: PostgreSQL database migration (Supabase)
- Phase 3: Supabase Storage integration
- Phase 4: SendGrid email integration
- Phase 5: Render deployment preparation"

# メインブランチに切り替え
git branch -M main

# プッシュ
git push -u origin main
```

**認証が必要な場合**:
- Personal Access Token (PAT) を使用
- Settings → Developer settings → Personal access tokens → Generate new token
- Scope: `repo` をチェック

---

## 🚀 Step 2: Render Web Service作成

### 2.1 Renderアカウント作成

```
1. https://render.com にアクセス
2. 「Get Started」をクリック
3. GitHubアカウントでサインアップ（推奨）
4. アカウント作成完了
```

**無料プラン**:
- 750時間/月の稼働時間
- 自動スリープ（15分間アクセスなし）
- スリープ解除に数秒かかる

### 2.2 Web Service作成

```
1. Renderダッシュボードで「New +」をクリック
2. 「Web Service」を選択
3. GitHubリポジトリ選択:
   - 「Connect a repository」をクリック
   - 「affiliate-system-pro」を選択
   - 「Connect」をクリック
```

### 2.3 Web Service設定

以下の情報を入力：

```
Name: affiliate-system-pro

Region: Singapore (Southeast Asia) または Oregon (US West)

Branch: main

Runtime: Native (PHP自動検出)

Build Command:
./render-build.sh

Start Command:
apache2-foreground
```

**⚠️ 注意**: Start Commandは後で環境変数設定後に変更する可能性があります

---

## ⚙️ Step 3: 環境変数設定

### 3.1 Renderで環境変数設定

Render Web Service設定画面で「Environment」タブをクリックし、以下をすべて追加：

#### アプリケーション設定
```
APP_ENV=production
APP_DEBUG=false
```

#### Supabase設定
```
SUPABASE_URL=https://ezucbzqzvxgcyikkrznj.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImV6dWNienF6dnhnY3lpa2tyem5qIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTk4Mjk5MzUsImV4cCI6MjA3NTQwNTkzNX0.VlNYTfPalE05PqYhR9TP0F64mvZS9BWEoW9YJcUwo0k

SUPABASE_DB_HOST=aws-1-ap-northeast-1.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres.ezucbzqzvxgcyikkrznj
SUPABASE_DB_PASS=akutu4256
```

#### SendGrid設定
```
SENDGRID_API_KEY=（SendGridで取得したAPI Key）
USE_SENDGRID=true

MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=アフィリエイトシステム
```

#### Supabase Storage設定
```
USE_SUPABASE_STORAGE=true
SUPABASE_STORAGE_BUCKET=affiliate-images
```

#### セキュリティ設定
```
SQL_PASSWORD_KEY=（ランダムな32文字の文字列）
SESSION_SECRET=（ランダムな64文字の文字列）
```

**セキュリティキー生成方法**:
```bash
# SQL_PASSWORD_KEY (32文字)
openssl rand -hex 16

# SESSION_SECRET (64文字)
openssl rand -hex 32
```

---

## 🔨 Step 4: デプロイ実行

### 4.1 手動デプロイ

Render Web Service設定が完了したら：

```
1. 「Create Web Service」をクリック
2. デプロイが自動で開始される
3. ビルドログを確認:
   - PHP拡張のインストール
   - ディレクトリ作成
   - パーミッション設定
4. デプロイ完了を待つ（5-10分）
```

### 4.2 デプロイステータス確認

**ビルド成功の例**:
```
=========================================
  アフィリエイトシステムプロ - ビルド
=========================================

1. PHP拡張確認...
pgsql
mbstring
curl
gd
exif
json

2. ディレクトリ作成...
  ✓ ディレクトリ作成完了

3. パーミッション設定...
  ✓ パーミッション設定完了

4. 環境変数確認...
  ✓ SUPABASE_DB_HOST: aws-1-ap-northeast-1.pooler.supabase.com
  ✓ SENDGRID_API_KEY: 設定済み

=========================================
  ビルド完了
=========================================

==> Your service is live 🎉
```

### 4.3 デプロイURL確認

デプロイ完了後、以下のURLでアクセス可能：

```
https://affiliate-system-pro.onrender.com
```

---

## ✅ Step 5: 動作確認

### 5.1 基本動作確認

```
1. デプロイURLにアクセス
   https://affiliate-system-pro.onrender.com

2. トップページが表示されることを確認

3. 管理画面にログイン（既存の管理者アカウント）

4. 以下を確認:
   - ✓ ページが正常に表示される
   - ✓ データベース接続が正常
   - ✓ 画像表示が正常（Supabase Storage）
   - ✓ メール送信が正常（SendGrid）
```

### 5.2 データベース接続確認

管理画面で以下を確認：

```
1. ユーザー一覧が表示される
2. 都道府県マスタが表示される（46件）
3. テンプレート一覧が表示される（279件）
```

### 5.3 メール送信確認

```
1. 管理画面でテストメール送信
2. SendGridダッシュボードで配信ログ確認:
   https://app.sendgrid.com/email_activity
3. メールが正常に届くことを確認
```

### 5.4 ファイルアップロード確認

```
1. 管理画面で画像ファイルアップロード
2. Supabase Storageに保存されることを確認:
   Supabase Dashboard → Storage → affiliate-images
3. アップロードした画像が表示されることを確認
```

---

## 🔧 トラブルシューティング

### エラー1: ビルド失敗

```
Error: Failed to install PHP extensions
→ composer.jsonのPHP拡張を確認
→ RenderのPHPバージョンを確認（PHP 8.2推奨）
```

### エラー2: データベース接続エラー

```
Error: Connection refused
→ 環境変数SUPABASE_DB_HOSTを確認
→ Session Pooler使用を確認（IPv4対応）
→ Supabaseプロジェクトがアクティブか確認
```

### エラー3: 500 Internal Server Error

```
1. Renderログを確認:
   Dashboard → Logs タブ

2. PHPエラーログを確認:
   logs/error.log

3. 環境変数が正しく設定されているか確認
```

### エラー4: メール送信失敗

```
1. SendGrid API Keyが正しいか確認
2. 送信元メールアドレスが認証済みか確認
3. USE_SENDGRID=true になっているか確認
4. SendGridダッシュボードでエラーログ確認
```

### エラー5: ファイルアップロード失敗

```
1. USE_SUPABASE_STORAGE=true になっているか確認
2. SUPABASE_ANON_KEY が正しいか確認
3. Supabase Storageバケットが作成されているか確認
4. バケットがPublicになっているか確認
```

---

## 📊 デプロイ後の設定

### カスタムドメイン設定（オプション）

独自ドメインを使用する場合：

```
1. Render Dashboard → Settings → Custom Domain
2. 「Add Custom Domain」をクリック
3. ドメイン名を入力（例: affiliate.yourdomain.com）
4. DNS設定:
   - Type: CNAME
   - Name: affiliate
   - Value: affiliate-system-pro.onrender.com
5. SSL証明書は自動で発行される（Let's Encrypt）
```

### 自動デプロイ設定

GitHubへのプッシュで自動デプロイ：

```
1. Render Dashboard → Settings
2. 「Auto-Deploy」をOnに設定
3. Branch: main を選択
4. 以降、git pushで自動デプロイ
```

### スケーリング設定

アクセス増加時：

```
1. Render Dashboard → Settings
2. Instance Type を変更:
   - Free: 512MB RAM
   - Starter: $7/月、512MB RAM
   - Standard: $25/月、2GB RAM
```

---

## 📝 本番環境チェックリスト

デプロイ前の最終確認：

- [ ] **GitHubリポジトリ作成・プッシュ完了**
  - [ ] プライベートリポジトリ
  - [ ] .envファイルが除外されている

- [ ] **Render Web Service作成完了**
  - [ ] ビルドコマンド設定
  - [ ] スタートコマンド設定
  - [ ] リージョン選択

- [ ] **環境変数設定完了**
  - [ ] Supabase接続情報
  - [ ] SendGrid API Key
  - [ ] セキュリティキー生成

- [ ] **デプロイ実行・確認完了**
  - [ ] ビルド成功
  - [ ] デプロイURL確認
  - [ ] トップページ表示確認

- [ ] **動作確認完了**
  - [ ] データベース接続確認
  - [ ] メール送信確認
  - [ ] ファイルアップロード確認
  - [ ] 管理画面ログイン確認

---

## 🎯 Phase 5完了後

Phase 5完了後、Phase 6に進みます：

### Phase 6: 最終テスト

- 全機能の統合テスト
- 負荷テスト
- セキュリティテスト
- パフォーマンステスト

### Phase 7: 本番移行

- DNS切り替え
- データ移行（本番データ）
- ユーザー通知
- 監視設定

---

## 📞 サポート

問題が発生した場合：

1. **Renderログ確認**: Dashboard → Logs
2. **Supabaseログ確認**: Supabase Dashboard → Logs
3. **SendGridログ確認**: SendGrid Dashboard → Email Activity
4. **環境変数確認**: Render Dashboard → Environment

---

**Phase 5 完了**: Render本番環境デプロイ完了後、このファイルを `PHASE5_COMPLETE.md` にリネームしてください。

**次のステップ**: Phase 6（最終テスト）
