-- ============================================
-- CATS - AFAD連携システム データベースマイグレーション
-- 作成日: 2025-10-29
-- 説明: AFADポストバック連携に必要なテーブル変更とテーブル作成
-- ============================================

-- --------------------------------------------
-- 1. accessテーブル拡張
-- --------------------------------------------

-- AFADセッションIDカラムを追加
ALTER TABLE `access`
ADD COLUMN `afad_session_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'AFADセッションID' AFTER `cookie`,
ADD INDEX `idx_afad_session_id` (`afad_session_id`);

-- --------------------------------------------
-- 2. adwaresテーブル拡張
-- --------------------------------------------

-- AFAD連携設定カラムを追加
ALTER TABLE `adwares`
ADD COLUMN `afad_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'AFAD連携有効フラグ (0:無効, 1:有効)' AFTER `url_over`,
ADD COLUMN `afad_postback_url` TEXT NULL DEFAULT NULL COMMENT 'AFADポストバックURL' AFTER `afad_enabled`,
ADD COLUMN `afad_gid` VARCHAR(100) NULL DEFAULT NULL COMMENT 'AFAD広告グループID' AFTER `afad_postback_url`,
ADD COLUMN `afad_param_name` VARCHAR(50) NOT NULL DEFAULT 'afad_sid' COMMENT 'AFADセッションIDパラメータ名' AFTER `afad_gid`;

-- --------------------------------------------
-- 3. secretAdwaresテーブル拡張（クローズド広告）
-- --------------------------------------------

-- AFAD連携設定カラムを追加（adwaresと同じ構成）
ALTER TABLE `secretAdwares`
ADD COLUMN `afad_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'AFAD連携有効フラグ (0:無効, 1:有効)' AFTER `url_over`,
ADD COLUMN `afad_postback_url` TEXT NULL DEFAULT NULL COMMENT 'AFADポストバックURL' AFTER `afad_enabled`,
ADD COLUMN `afad_gid` VARCHAR(100) NULL DEFAULT NULL COMMENT 'AFAD広告グループID' AFTER `afad_postback_url`,
ADD COLUMN `afad_param_name` VARCHAR(50) NOT NULL DEFAULT 'afad_sid' COMMENT 'AFADセッションIDパラメータ名' AFTER `afad_gid`;

-- --------------------------------------------
-- 4. afad_postback_logテーブル作成（新規）
-- --------------------------------------------

CREATE TABLE IF NOT EXISTS `afad_postback_log` (
  `id` VARCHAR(32) NOT NULL COMMENT 'ログID',
  `pay_id` VARCHAR(32) NOT NULL COMMENT '成果ID (pay.id)',
  `access_id` VARCHAR(32) NOT NULL COMMENT 'アクセスID (access.id)',
  `afad_session_id` VARCHAR(255) NOT NULL COMMENT 'AFADセッションID',
  `postback_url` TEXT NOT NULL COMMENT '送信したポストバックURL',
  `http_status` INT(11) NULL DEFAULT NULL COMMENT 'HTTPステータスコード',
  `response_body` TEXT NULL DEFAULT NULL COMMENT 'レスポンスボディ',
  `error_message` TEXT NULL DEFAULT NULL COMMENT 'エラーメッセージ',
  `sent_at` INT(11) NOT NULL COMMENT '送信日時（UNIXタイムスタンプ）',
  `retry_count` INT(11) NOT NULL DEFAULT 0 COMMENT 'リトライ回数',
  PRIMARY KEY (`id`),
  INDEX `idx_pay_id` (`pay_id`),
  INDEX `idx_access_id` (`access_id`),
  INDEX `idx_afad_session_id` (`afad_session_id`),
  INDEX `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AFADポストバック送信ログ';

-- --------------------------------------------
-- 完了メッセージ
-- --------------------------------------------

SELECT 'AFAD連携システム マイグレーション完了' AS status;
