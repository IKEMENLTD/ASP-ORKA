-- ============================================
-- CATS - AFAD連携システム データベースマイグレーション
-- Supabase (PostgreSQL) 版
-- 作成日: 2025-10-30
-- 説明: AFADポストバック連携に必要なテーブル変更とテーブル作成
-- ============================================

-- --------------------------------------------
-- 1. accessテーブル拡張
-- --------------------------------------------

-- AFADセッションIDカラムを追加
ALTER TABLE access
ADD COLUMN IF NOT EXISTS afad_session_id VARCHAR(255) NULL DEFAULT NULL;

-- インデックスを追加
CREATE INDEX IF NOT EXISTS idx_afad_session_id ON access(afad_session_id);

-- カラムコメントを追加
COMMENT ON COLUMN access.afad_session_id IS 'AFADセッションID';

-- --------------------------------------------
-- 2. adwaresテーブル拡張
-- --------------------------------------------

-- AFAD連携設定カラムを追加
ALTER TABLE adwares
ADD COLUMN IF NOT EXISTS afad_enabled BOOLEAN NOT NULL DEFAULT false,
ADD COLUMN IF NOT EXISTS afad_postback_url TEXT NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS afad_gid VARCHAR(100) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS afad_param_name VARCHAR(50) NOT NULL DEFAULT 'afad_sid';

-- カラムコメントを追加
COMMENT ON COLUMN adwares.afad_enabled IS 'AFAD連携有効フラグ (false:無効, true:有効)';
COMMENT ON COLUMN adwares.afad_postback_url IS 'AFADポストバックURL';
COMMENT ON COLUMN adwares.afad_gid IS 'AFAD広告グループID';
COMMENT ON COLUMN adwares.afad_param_name IS 'AFADセッションIDパラメータ名';

-- --------------------------------------------
-- 3. secretAdwaresテーブル拡張（クローズド広告）
-- --------------------------------------------

-- AFAD連携設定カラムを追加（adwaresと同じ構成）
ALTER TABLE "secretAdwares"
ADD COLUMN IF NOT EXISTS afad_enabled BOOLEAN NOT NULL DEFAULT false,
ADD COLUMN IF NOT EXISTS afad_postback_url TEXT NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS afad_gid VARCHAR(100) NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS afad_param_name VARCHAR(50) NOT NULL DEFAULT 'afad_sid';

-- カラムコメントを追加
COMMENT ON COLUMN "secretAdwares".afad_enabled IS 'AFAD連携有効フラグ (false:無効, true:有効)';
COMMENT ON COLUMN "secretAdwares".afad_postback_url IS 'AFADポストバックURL';
COMMENT ON COLUMN "secretAdwares".afad_gid IS 'AFAD広告グループID';
COMMENT ON COLUMN "secretAdwares".afad_param_name IS 'AFADセッションIDパラメータ名';

-- --------------------------------------------
-- 4. afad_postback_logテーブル作成（新規）
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS afad_postback_log (
  id VARCHAR(32) NOT NULL,
  pay_id VARCHAR(32) NOT NULL,
  access_id VARCHAR(32) NOT NULL,
  afad_session_id VARCHAR(255) NOT NULL,
  postback_url TEXT NOT NULL,
  http_status INTEGER NULL DEFAULT NULL,
  response_body TEXT NULL DEFAULT NULL,
  error_message TEXT NULL DEFAULT NULL,
  sent_at INTEGER NOT NULL,
  retry_count INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
);

-- インデックスを追加
CREATE INDEX IF NOT EXISTS idx_pay_id ON afad_postback_log(pay_id);
CREATE INDEX IF NOT EXISTS idx_access_id ON afad_postback_log(access_id);
CREATE INDEX IF NOT EXISTS idx_afad_session_id_log ON afad_postback_log(afad_session_id);
CREATE INDEX IF NOT EXISTS idx_sent_at ON afad_postback_log(sent_at);

-- テーブルコメントを追加
COMMENT ON TABLE afad_postback_log IS 'AFADポストバック送信ログ';

-- カラムコメントを追加
COMMENT ON COLUMN afad_postback_log.id IS 'ログID';
COMMENT ON COLUMN afad_postback_log.pay_id IS '成果ID (pay.id)';
COMMENT ON COLUMN afad_postback_log.access_id IS 'アクセスID (access.id)';
COMMENT ON COLUMN afad_postback_log.afad_session_id IS 'AFADセッションID';
COMMENT ON COLUMN afad_postback_log.postback_url IS '送信したポストバックURL';
COMMENT ON COLUMN afad_postback_log.http_status IS 'HTTPステータスコード';
COMMENT ON COLUMN afad_postback_log.response_body IS 'レスポンスボディ';
COMMENT ON COLUMN afad_postback_log.error_message IS 'エラーメッセージ';
COMMENT ON COLUMN afad_postback_log.sent_at IS '送信日時（UNIXタイムスタンプ）';
COMMENT ON COLUMN afad_postback_log.retry_count IS 'リトライ回数';

-- --------------------------------------------
-- 完了メッセージ
-- --------------------------------------------

SELECT 'AFAD連携システム マイグレーション完了 (Supabase版)' AS status;
