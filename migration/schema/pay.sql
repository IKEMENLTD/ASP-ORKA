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

