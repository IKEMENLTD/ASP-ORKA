-- Migration: Create system table
-- Generated from: lst/system.csv

CREATE TABLE IF NOT EXISTS system (
  id CHAR(5) PRIMARY KEY,
  uuid VARCHAR(64),
  home VARCHAR(255),
  mail_address VARCHAR(255),
  mail_name VARCHAR(128),
  login_id_manage VARCHAR(16),
  site_title VARCHAR(128),
  keywords TEXT,
  description TEXT,
  main_css VARCHAR(32),
  child_per INTEGER,
  grandchild_per INTEGER,
  greatgrandchild_per INTEGER,
  users_returnss BOOLEAN,
  exchange_limit INTEGER,
  nuser_default_activate INTEGER,
  nuser_accept_admin INTEGER,
  adwares_pass VARCHAR(32),
  sales_auto CHAR(1),
  send_mail_admin VARCHAR(32),
  send_mail_nuser VARCHAR(32),
  send_mail_status VARCHAR(32),
  access_limit INTEGER,
  parent_limit INTEGER,
  parent_limit_url VARCHAR(255),
  regist INTEGER,
  delete_key BOOLEAN DEFAULT FALSE,
  shadow_id INTEGER
);

CREATE INDEX IF NOT EXISTS idx_system_delete_key ON system(delete_key);
CREATE INDEX IF NOT EXISTS idx_system_shadow_id ON system(shadow_id);
