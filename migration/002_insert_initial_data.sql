-- 初期データ挿入スクリプト
-- システムテーブルにデフォルト設定を追加

-- systemテーブルの初期レコード（ID=0）
INSERT INTO system (
  id,
  uuid,
  home,
  mail_address,
  mail_name,
  login_id_manage,
  site_title,
  keywords,
  description,
  main_css,
  child_per,
  grandchild_per,
  greatgrandchild_per,
  users_returnss,
  exchange_limit,
  nuser_default_activate,
  nuser_accept_admin,
  adwares_pass,
  sales_auto,
  send_mail_admin,
  send_mail_nuser,
  send_mail_status,
  access_limit,
  parent_limit,
  parent_limit_url,
  regist
) VALUES (
  '0',                                          -- id
  'default-system-uuid',                        -- uuid
  'https://asp-orka-1.onrender.com',           -- home
  'admin@orkaasp.com',                          -- mail_address
  'ASP-ORKA System',                            -- mail_name
  'SESSION',                                    -- login_id_manage (SESSION or COOKIE)
  'ASP-ORKA アフィリエイトシステム',           -- site_title
  'アフィリエイト,ASP,広告',                   -- keywords
  'ASP-ORKA アフィリエイトシステム',           -- description
  'default.css',                                -- main_css
  10,                                           -- child_per (子報酬率 10%)
  5,                                            -- grandchild_per (孫報酬率 5%)
  2,                                            -- greatgrandchild_per (ひ孫報酬率 2%)
  TRUE,                                         -- users_returnss
  1000,                                         -- exchange_limit (最低換金額)
  2,                                            -- nuser_default_activate (デフォルト: アクティベート済み)
  1,                                            -- nuser_accept_admin
  'admin123',                                   -- adwares_pass (広告パスワード)
  '0',                                          -- sales_auto
  NULL,                                         -- send_mail_admin
  NULL,                                         -- send_mail_nuser
  NULL,                                         -- send_mail_status
  100,                                          -- access_limit
  999,                                          -- parent_limit
  '',                                           -- parent_limit_url
  EXTRACT(EPOCH FROM NOW())::BIGINT             -- regist (現在のUNIXタイムスタンプ)
)
ON CONFLICT (id) DO NOTHING;

-- 管理者用テンプレートの初期レコード（必要に応じて）
-- templateテーブルへの初期データ挿入は、実際のテンプレートファイルが存在する場合に実行

-- 完了メッセージ
DO $$
BEGIN
  RAISE NOTICE 'Initial data insertion completed successfully';
END $$;
