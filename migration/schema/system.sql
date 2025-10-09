-- system テーブル
DROP TABLE IF EXISTS system CASCADE;
CREATE TABLE system (
  id CHAR(5) NOT NULL,
  uuid VARCHAR(64) NOT NULL,
  home VARCHAR(255) NOT NULL,
  mail_address VARCHAR(255) NOT NULL,
  mail_name VARCHAR(128) NOT NULL,
  login_id_manage VARCHAR(16) NOT NULL,
  site_title VARCHAR(128) NOT NULL,
  keywords TEXT NOT NULL,
  description TEXT NOT NULL,
  main_css VARCHAR(32) NOT NULL,
  child_per INTEGER NOT NULL,
  grandchild_per INTEGER NOT NULL,
  greatgrandchild_per INTEGER NOT NULL,
  users_returnss BOOLEAN NOT NULL DEFAULT FALSE,
  exchange_limit INTEGER NOT NULL,
  nuser_default_activate INTEGER NOT NULL,
  nuser_accept_admin INTEGER NOT NULL,
  adwares_pass VARCHAR(32) NOT NULL,
  sales_auto CHAR(1) NOT NULL,
  send_mail_admin VARCHAR(32),
  send_mail_nuser VARCHAR(32),
  send_mail_status VARCHAR(32),
  access_limit INTEGER NOT NULL,
  parent_limit INTEGER NOT NULL,
  parent_limit_url VARCHAR(255) NOT NULL,
  regist BIGINT,
  PRIMARY KEY (id)
);

