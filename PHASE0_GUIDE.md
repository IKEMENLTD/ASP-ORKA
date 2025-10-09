# アフィリエイトシステムプロ - 環境変数テンプレート
# コピーして .env.local として使用してください: cp .env.example .env.local

# ========================================
# アプリケーション設定
# ========================================
APP_ENV=production
APP_DEBUG=false

# ========================================
# Supabase 設定
# ========================================
# Supabase Dashboard > Settings > API で取得
SUPABASE_URL=https://xxxxxxxxxxxxx.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Supabase Dashboard > Settings > Database で取得
SUPABASE_DB_HOST=db.xxxxxxxxxxxxx.supabase.co
SUPABASE_DB_PORT=5432
SUPABASE_DB_NAME=postgres
SUPABASE_DB_USER=postgres
SUPABASE_DB_PASS=your-database-password

# ========================================
# SendGrid 設定
# ========================================
# SendGrid Dashboard > Settings > API Keys で作成
SENDGRID_API_KEY=SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
USE_SENDGRID=true

# ========================================
# Supabase Storage 設定
# ========================================
USE_SUPABASE_STORAGE=true

# ========================================
# セキュリティ設定
# ========================================
# ランダムな32文字の文字列を生成
# コマンド: openssl rand -hex 16
SQL_PASSWORD_KEY=

# ランダムな64文字の文字列を生成
# コマンド: openssl rand -hex 32
SESSION_SECRET=

# ========================================
# メール設定
# ========================================
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=アフィリエイトシステム

# ========================================
# システム設定
# ========================================
SYSTEM_URL=https://affiliate-pro.onrender.com
