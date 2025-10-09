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

