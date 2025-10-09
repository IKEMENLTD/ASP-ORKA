# Phase 0: 準備フェーズ - 完全セットアップガイド

**所要時間**: 1-2時間
**前提条件**: メールアドレス、GitHubアカウント (推奨)

---

## ✓ ステップ1: バックアップ完了

バックアップが完了しました:

```
✓ バックアップ先: /mnt/c/Users/ooxmi/Downloads/affiliate-pro-backup-20251009-152154
✓ サイズ: 3.2M
✓ ファイル数: 615
```

---

## ステップ2: Supabase プロジェクト作成

### 2.1 アカウント作成

1. ブラウザで https://supabase.com にアクセス
2. **Start your project** をクリック
3. 以下のいずれかでサインアップ:
   - GitHub アカウント (推奨)
   - Google アカウント
   - メールアドレス

### 2.2 プロジェクト作成

1. ダッシュボードで **New project** をクリック

2. **プロジェクト情報を入力**:

   | 項目 | 入力内容 |
   |-----|---------|
   | Organization | (既存または新規作成) |
   | Name | `affiliate-pro` |
   | Database Password | **強力なパスワードを生成** |
   | Region | `Northeast Asia (Tokyo)` |
   | Pricing Plan | `Free` (開発用) |

   **重要**: Database Passwordは安全な場所に保存してください

3. **Create new project** をクリック

4. プロジェクト作成を待つ (2-3分)

### 2.3 接続情報を取得

プロジェクト作成完了後:

#### データベース接続情報

1. **Settings** (左メニュー) > **Database** をクリック

2. **Connection string** セクションで以下を確認:

   ```
   Pooler (Transaction mode):
   postgresql://postgres.xxxxxxxxxxxxx:[YOUR-PASSWORD]@aws-0-ap-northeast-1.pooler.supabase.com:6543/postgres
   ```

3. **以下の情報をメモ**:
   - `SUPABASE_DB_HOST`: `db.xxxxxxxxxxxxx.supabase.co`
   - `SUPABASE_DB_PORT`: `5432`
   - `SUPABASE_DB_NAME`: `postgres`
   - `SUPABASE_DB_USER`: `postgres`
   - `SUPABASE_DB_PASS`: (先ほど設定したパスワード)

#### API接続情報

1. **Settings** > **API** をクリック

2. **Project URL** をコピー:
   ```
   https://xxxxxxxxxxxxx.supabase.co
   ```

3. **Project API keys** セクションで以下をコピー:
   - `anon` `public` key (公開用)
   - `service_role` `secret` key (サーバー用)

4. **以下の情報をメモ**:
   - `SUPABASE_URL`: `https://xxxxxxxxxxxxx.supabase.co`
   - `SUPABASE_ANON_KEY`: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`
   - `SUPABASE_SERVICE_ROLE_KEY`: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`

### 2.4 Storage バケット作成

1. **Storage** (左メニュー) をクリック

2. **New bucket** をクリック

3. **画像用バケット作成**:
   - Name: `affiliate-images`
   - Public bucket: **ON** (チェック)
   - **Create bucket** をクリック

4. **ファイル用バケット作成** (同様に):
   - Name: `affiliate-files`
   - Public bucket: **OFF**
   - **Create bucket** をクリック

### 2.5 接続テスト (オプション)

PostgreSQLクライアントで接続確認:

```bash
# psql インストール済みの場合
psql "postgresql://postgres:[YOUR-PASSWORD]@db.xxxxxxxxxxxxx.supabase.co:5432/postgres"

# 接続成功時の表示例:
# psql (14.x, server 15.x)
# SSL connection (protocol: TLSv1.3, cipher: TLS_AES_256_GCM_SHA384)
# Type "help" for help.
# postgres=>

# 切断
\q
```

---

## ステップ3: SendGrid セットアップ

### 3.1 アカウント作成

1. ブラウザで https://sendgrid.com にアクセス

2. **Try for Free** をクリック

3. アカウント情報を入力して登録

4. メール認証を完了

### 3.2 Sender Identity 設定

1. **Settings** > **Sender Authentication** をクリック

2. **Domain Authentication** または **Single Sender Verification** を選択

   **推奨**: 独自ドメインがある場合は Domain Authentication

3. 手順に従って設定完了

### 3.3 API Key 作成

1. **Settings** > **API Keys** をクリック

2. **Create API Key** をクリック

3. API Key 情報を入力:
   - Name: `Affiliate Pro Production`
   - API Key Permissions: **Full Access** (または **Restricted Access** で Mail Send のみ)

4. **Create & View** をクリック

5. **API Key をコピーして安全な場所に保存**:
   ```
   SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

   **重要**: このAPIキーは一度しか表示されません

6. `SENDGRID_API_KEY` としてメモ

### 3.4 無料プラン制限

SendGrid 無料プラン:
- **月100通まで無料**
- 有料プラン: 月40,000通で $19.95/月

---

## ステップ4: Render アカウント作成

### 4.1 サインアップ

1. ブラウザで https://render.com にアクセス

2. **Get Started** をクリック

3. サインアップ方法を選択:
   - **GitHub** (推奨)
   - **GitLab**
   - **Email**

4. 必要に応じて権限を許可

### 4.2 支払い情報登録 (オプション)

無料プランで開始する場合は不要ですが、以下の制限があります:

**Render 無料プラン**:
- Web Service: $0/月 (750時間/月)
- 制限:
  - 15分間アクセスがないと自動スリープ
  - 起動に数秒かかる
  - 月750時間まで (約31日)

**Starter プラン** ($7/月):
- 常時起動
- スリープなし
- カスタムドメイン
- 環境変数保護

本番運用時はStarter以上を推奨

---

## ステップ5: 環境変数ファイル作成

### 5.1 .env ファイル作成

プロジェクトディレクトリで:

```bash
cd /mnt/c/Users/ooxmi/Downloads/アフィリエイトシステムプロ＿システム本体003CSS未タッチ

# テンプレートから .env をコピー
cp .env.example .env
```

### 5.2 環境変数を入力

`.env` ファイルを編集:

```bash
# Windowsの場合
notepad .env

# WSL/Linuxの場合
nano .env
```

**以下の値を入力**:

```bash
# Supabase (ステップ2.3でメモした値)
SUPABASE_URL=https://xxxxxxxxxxxxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

SUPABASE_DB_HOST=db.xxxxxxxxxxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=(ステップ2.2で設定したパスワード)

# SendGrid (ステップ3.3でメモした値)
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
USE_SENDGRID=true

# Storage
USE_SUPABASE_STORAGE=true

# セキュリティ (ランダム文字列を生成)
SQL_PASSWORD_KEY=
SESSION_SECRET=

# メール設定
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=アフィリエイトシステムプロ

# システムURL (後で Render URL に変更)
SYSTEM_URL=https://affiliate-pro.onrender.com
```

### 5.3 セキュリティキー生成

```bash
# SQL_PASSWORD_KEY 生成 (32文字)
openssl rand -hex 16

# 出力例: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
# これを .env の SQL_PASSWORD_KEY= にコピー

# SESSION_SECRET 生成 (64文字)
openssl rand -hex 32

# 出力例: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1f2
# これを .env の SESSION_SECRET= にコピー
```

### 5.4 .gitignore 作成

機密情報をGitに含めないために:

```bash
# .gitignore ファイル作成
cat > .gitignore << 'EOF'
# 環境変数
.env
.env.local
.env.production

# データベースファイル
tdb/*.csv
!tdb/.gitkeep

# ログファイル
logs/*.log
!logs/.gitkeep

# 一時ファイル
file/tmp/*
!file/tmp/.gitkeep

# アップロードファイル (Supabase Storage使用のため)
file/image/*
!file/image/.gitkeep

# Composer
vendor/
composer.lock

# バックアップ
backup.sh
EOF
```

---

## ステップ6: チェックリスト

### 完了確認

- [ ] ✓ システムバックアップ作成完了
- [ ] Supabase プロジェクト作成
- [ ] Supabase データベース接続情報取得
- [ ] Supabase API キー取得
- [ ] Supabase Storage バケット作成 (affiliate-images, affiliate-files)
- [ ] SendGrid アカウント作成
- [ ] SendGrid API キー取得
- [ ] Render アカウント作成
- [ ] .env ファイル作成
- [ ] セキュリティキー生成
- [ ] .gitignore 作成

### 情報確認リスト

以下の情報が揃っているか確認:

```
✓ SUPABASE_URL
✓ SUPABASE_ANON_KEY
✓ SUPABASE_SERVICE_ROLE_KEY
✓ SUPABASE_DB_HOST
✓ SUPABASE_DB_PORT
✓ SUPABASE_DB_NAME
✓ SUPABASE_DB_USER
✓ SUPABASE_DB_PASS
✓ SENDGRID_API_KEY
✓ SQL_PASSWORD_KEY
✓ SESSION_SECRET
```

---

## 次のステップ

Phase 0 完了後:

### すぐに実行可能

**Phase 1: 文字コード変換** に進む:

```bash
# Phase 1 ガイドを確認
cat MIGRATION_PLAN.md | grep -A 50 "Phase 1: 文字コード変換"
```

### 推奨: 接続テスト

Phase 1 の前に、Supabase 接続をテスト:

```bash
# PostgreSQL接続テスト
psql "postgresql://postgres:[PASSWORD]@db.xxxxxxxxxxxxx.supabase.co:5432/postgres"

# SQL実行テスト
\l  # データベース一覧
\q  # 終了
```

---

## トラブルシューティング

### 問題1: Supabase プロジェクト作成エラー

**症状**: "Project creation failed"

**解決策**:
- ブラウザのキャッシュをクリア
- 別のブラウザで試す
- 数分待ってから再試行

### 問題2: SendGrid アカウント承認待ち

**症状**: "Account under review"

**解決策**:
- SendGrid の審査完了を待つ (通常24時間以内)
- 代替: Resend (https://resend.com) を使用
  - 無料枠: 月3,000通
  - 即座に使用可能

### 問題3: openssl コマンドがない

**症状**: `openssl: command not found`

**解決策**:

```bash
# Windows WSL の場合
sudo apt-get install openssl

# または、オンラインツールを使用
# https://www.random.org/strings/
# - Numeric digits (0-9)
# - Length: 32 (SQL_PASSWORD_KEY) または 64 (SESSION_SECRET)
```

---

## Phase 0 完了

おめでとうございます！Phase 0 (準備) が完了しました。

次は **Phase 1: 文字コード変換** に進みます。

実行準備ができたら:

```bash
# Phase 1 の実行支援が必要な場合は、Claudeに伝えてください
# "Phase 1を開始" または "C" と入力
```

---

**作成日**: 2025-01-XX
**所要時間**: 1-2時間
**次のフェーズ**: Phase 1 - 文字コード変換
