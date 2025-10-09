-- アフィリエイトシステムプロ - 全テーブル作成
-- 自動生成日: 1759991792.796864

-- admin テーブル
DROP TABLE IF EXISTS admin CASCADE;
CREATE TABLE admin (
  id CHAR(5),
  name VARCHAR(128),
  mail VARCHAR(255) NOT NULL,
  pass VARCHAR(128) NOT NULL,
  activate INTEGER DEFAULT 0,
  logout BIGINT,
  login BIGINT,
  old_login BIGINT,
  mail_time BIGINT,
  PRIMARY KEY (id)
);

-- インデックス
CREATE INDEX idx_admin_mail ON admin(mail);

-- nuser テーブル
DROP TABLE IF EXISTS nuser CASCADE;
CREATE TABLE nuser (
  id CHAR(8),
  name VARCHAR(32) NOT NULL,
  zip1 CHAR(3) NOT NULL,
  zip2 CHAR(4),
  adds CHAR(4) NOT NULL,
  add_sub VARCHAR(255) NOT NULL,
  tel VARCHAR(15) NOT NULL,
  fax VARCHAR(15),
  url VARCHAR(255) NOT NULL,
  mail VARCHAR(128) NOT NULL UNIQUE,
  bank_code VARCHAR(4) NOT NULL,
  bank VARCHAR(128) NOT NULL,
  branch_code VARCHAR(3) NOT NULL,
  branch VARCHAR(128) NOT NULL,
  bank_type VARCHAR(2) NOT NULL,
  number VARCHAR(32) NOT NULL,
  bank_name VARCHAR(32) NOT NULL,
  parent CHAR(8),
  grandparent CHAR(8),
  greatgrandparent CHAR(8),
  pass VARCHAR(128) NOT NULL,
  terminal VARCHAR(255),
  activate INTEGER DEFAULT 0,
  pay INTEGER DEFAULT 0,
  tier INTEGER DEFAULT 0,
  rank CHAR(4),
  personal_rate DOUBLE PRECISION NOT NULL,
  magni DOUBLE PRECISION NOT NULL,
  mail_reception VARCHAR(32),
  is_mobile BOOLEAN DEFAULT FALSE,
  limits BIGINT,
  regist BIGINT,
  logout BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (parent) REFERENCES nuser(id) ON DELETE SET NULL,
  FOREIGN KEY (grandparent) REFERENCES nuser(id) ON DELETE SET NULL,
  FOREIGN KEY (greatgrandparent) REFERENCES nuser(id) ON DELETE SET NULL
);

-- インデックス
CREATE INDEX idx_nuser_mail ON nuser(mail);
CREATE INDEX idx_nuser_parent ON nuser(parent);

-- adwares テーブル
DROP TABLE IF EXISTS adwares CASCADE;
CREATE TABLE adwares (
  id CHAR(8),
  comment TEXT NOT NULL,
  ad_text VARCHAR(128) NOT NULL,
  category CHAR(8) NOT NULL,
  banner VARCHAR(255),
  banner2 VARCHAR(255),
  banner3 VARCHAR(255),
  banner_m VARCHAR(255),
  banner_m2 VARCHAR(255),
  banner_m3 VARCHAR(255),
  url VARCHAR(255),
  url_m VARCHAR(255),
  url_over VARCHAR(255),
  url_users BOOLEAN NOT NULL DEFAULT FALSE,
  name VARCHAR(128) NOT NULL,
  money VARCHAR(10) NOT NULL,
  ad_type VARCHAR(10) NOT NULL,
  click_money VARCHAR(10) NOT NULL,
  continue_money VARCHAR(10),
  continue_type VARCHAR(10),
  limits INTEGER NOT NULL,
  limit_type CHAR(1) NOT NULL,
  money_count INTEGER DEFAULT 0,
  pay_count INTEGER DEFAULT 0,
  click_money_count INTEGER DEFAULT 0,
  continue_money_count INTEGER DEFAULT 0,
  span INTEGER NOT NULL,
  span_type CHAR(1) NOT NULL,
  use_cookie_interval BOOLEAN DEFAULT FALSE,
  pay_span INTEGER NOT NULL,
  pay_span_type CHAR(1) NOT NULL,
  auto CHAR(1),
  click_auto CHAR(1),
  continue_auto CHAR(1),
  check_type VARCHAR(10) NOT NULL,
  open BOOLEAN DEFAULT FALSE,
  regist BIGINT,
  PRIMARY KEY (id)
);

-- access テーブル
DROP TABLE IF EXISTS access CASCADE;
CREATE TABLE access (
  id CHAR(32),
  ipaddress VARCHAR(16),
  cookie VARCHAR(32),
  adwares_type CHAR(32),
  adwares CHAR(8),
  owner CHAR(8),
  useragent TEXT,
  referer TEXT,
  state INTEGER,
  utn VARCHAR(128),
  regist BIGINT,
  PRIMARY KEY (id)
);

-- インデックス
CREATE INDEX idx_access_cookie ON access(cookie);
CREATE INDEX idx_access_adwares ON access(adwares);
CREATE INDEX idx_access_owner ON access(owner);

-- pay テーブル
DROP TABLE IF EXISTS pay CASCADE;
CREATE TABLE pay (
  id CHAR(32),
  access_id CHAR(32),
  ipaddress VARCHAR(16),
  cookie VARCHAR(32),
  owner CHAR(8) NOT NULL,
  adwares_type VARCHAR(32) NOT NULL,
  adwares CHAR(8) NOT NULL,
  cost INTEGER NOT NULL,
  tier1_rate INTEGER,
  tier2_rate INTEGER,
  tier3_rate INTEGER,
  sales INTEGER,
  froms TEXT,
  froms_sub TEXT,
  state INTEGER NOT NULL,
  is_notice BOOLEAN DEFAULT FALSE,
  utn VARCHAR(128),
  useragent TEXT,
  continue_uid VARCHAR(128),
  regist BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (access_id) REFERENCES access(id) ON DELETE SET NULL,
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE
);

-- インデックス
CREATE INDEX idx_pay_cookie ON pay(cookie);
CREATE INDEX idx_pay_owner ON pay(owner);
CREATE INDEX idx_pay_adwares ON pay(adwares);
CREATE INDEX idx_pay_state ON pay(state);

-- click_pay テーブル
DROP TABLE IF EXISTS click_pay CASCADE;
CREATE TABLE click_pay (
  id CHAR(32),
  access_id CHAR(32),
  owner CHAR(8) NOT NULL,
  adwares_type VARCHAR(32) NOT NULL,
  adwares CHAR(8) NOT NULL,
  cost INTEGER,
  tier1_rate INTEGER,
  tier2_rate INTEGER,
  tier3_rate INTEGER,
  state VARCHAR(10),
  is_notice BOOLEAN DEFAULT FALSE,
  regist BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (access_id) REFERENCES access(id) ON DELETE SET NULL,
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE
);

-- インデックス
CREATE INDEX idx_click_pay_owner ON click_pay(owner);
CREATE INDEX idx_click_pay_adwares ON click_pay(adwares);
CREATE INDEX idx_click_pay_state ON click_pay(state);

-- continue_pay テーブル
DROP TABLE IF EXISTS continue_pay CASCADE;
CREATE TABLE continue_pay (
  id CHAR(32),
  pay_id CHAR(32),
  owner CHAR(8) NOT NULL,
  adwares_type VARCHAR(32) NOT NULL,
  adwares CHAR(8) NOT NULL,
  cost INTEGER,
  tier1_rate INTEGER,
  tier2_rate INTEGER,
  tier3_rate INTEGER,
  sales INTEGER,
  state VARCHAR(10),
  is_notice BOOLEAN DEFAULT FALSE,
  regist BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE
);

-- インデックス
CREATE INDEX idx_continue_pay_owner ON continue_pay(owner);
CREATE INDEX idx_continue_pay_adwares ON continue_pay(adwares);
CREATE INDEX idx_continue_pay_state ON continue_pay(state);

-- tier テーブル
DROP TABLE IF EXISTS tier CASCADE;
CREATE TABLE tier (
  id CHAR(33),
  tier_id CHAR(33),
  owner CHAR(8),
  tier CHAR(8),
  adwares CHAR(8),
  cost INTEGER,
  tier1 INTEGER,
  tier2 INTEGER,
  tier3 INTEGER,
  regist BIGINT,
  PRIMARY KEY (id)
);

-- インデックス
CREATE INDEX idx_tier_owner ON tier(owner);
CREATE INDEX idx_tier_adwares ON tier(adwares);

-- sales テーブル
DROP TABLE IF EXISTS sales CASCADE;
CREATE TABLE sales (
  id CHAR(4),
  name VARCHAR(8) NOT NULL,
  rate INTEGER NOT NULL,
  lot INTEGER NOT NULL,
  sales INTEGER NOT NULL,
  PRIMARY KEY (id)
);

-- log_pay テーブル
DROP TABLE IF EXISTS log_pay CASCADE;
CREATE TABLE log_pay (
  id CHAR(16),
  pay_type VARCHAR(32),
  pay_id CHAR(32),
  nuser_id CHAR(8),
  operator VARCHAR(8),
  cost INTEGER NOT NULL,
  state INTEGER NOT NULL,
  action VARCHAR(16),
  regist BIGINT,
  PRIMARY KEY (id)
);

-- returnss テーブル
DROP TABLE IF EXISTS returnss CASCADE;
CREATE TABLE returnss (
  id TEXT,
  owner CHAR(8),
  cost INTEGER NOT NULL,
  state VARCHAR(16),
  regist BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE
);

-- インデックス
CREATE INDEX idx_returnss_owner ON returnss(owner);

-- category テーブル
DROP TABLE IF EXISTS category CASCADE;
CREATE TABLE category (
  id CHAR(8),
  name VARCHAR(128) NOT NULL,
  regist BIGINT,
  PRIMARY KEY (id)
);

-- area テーブル
DROP TABLE IF EXISTS area CASCADE;
CREATE TABLE area (
  id CHAR(6),
  name VARCHAR(32),
  PRIMARY KEY (id)
);

-- prefectures テーブル
DROP TABLE IF EXISTS prefectures CASCADE;
CREATE TABLE prefectures (
  id CHAR(4),
  area_id CHAR(6),
  name VARCHAR(16),
  name_kana VARCHAR(16),
  PRIMARY KEY (id)
);

-- zenginkyo テーブル
DROP TABLE IF EXISTS zenginkyo CASCADE;
CREATE TABLE zenginkyo (
  id CHAR(5) NOT NULL,
  name_kana VARCHAR(128),
  bank_code VARCHAR(4) NOT NULL,
  bank_name_kana VARCHAR(32),
  branch_code VARCHAR(3) NOT NULL,
  branch_name_kana VARCHAR(32),
  bank_type VARCHAR(1) NOT NULL,
  number VARCHAR(7) NOT NULL,
  PRIMARY KEY (id)
);

-- blacklist テーブル
DROP TABLE IF EXISTS blacklist CASCADE;
CREATE TABLE blacklist (
  id CHAR(32),
  blacklist_mode VARCHAR(32),
  blacklist_value VARCHAR(255) NOT NULL,
  memo VARCHAR(255),
  PRIMARY KEY (id)
);

-- invitation テーブル
DROP TABLE IF EXISTS invitation CASCADE;
CREATE TABLE invitation (
  id CHAR(8),
  owner CHAR(8),
  mail VARCHAR(128) NOT NULL UNIQUE,
  message TEXT,
  regist BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE
);

-- インデックス
CREATE INDEX idx_invitation_owner ON invitation(owner);
CREATE INDEX idx_invitation_mail ON invitation(mail);

-- multimail テーブル
DROP TABLE IF EXISTS multimail CASCADE;
CREATE TABLE multimail (
  id CHAR(8),
  sub VARCHAR(128) NOT NULL,
  main TEXT NOT NULL,
  receive_id TEXT,
  PRIMARY KEY (id)
);

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

-- template テーブル
DROP TABLE IF EXISTS template CASCADE;
CREATE TABLE template (
  id CHAR(5),
  user_type VARCHAR(64),
  target_type VARCHAR(32),
  activate INTEGER DEFAULT 0,
  owner INTEGER,
  label VARCHAR(128),
  file VARCHAR(255),
  regist INTEGER,
  PRIMARY KEY (id)
);

-- インデックス
CREATE INDEX idx_template_owner ON template(owner);

-- page テーブル
DROP TABLE IF EXISTS page CASCADE;
CREATE TABLE page (
  id CHAR(6),
  name VARCHAR(128) NOT NULL,
  authority VARCHAR(128) NOT NULL,
  open BOOLEAN NOT NULL DEFAULT FALSE,
  regist BIGINT,
  PRIMARY KEY (id)
);

