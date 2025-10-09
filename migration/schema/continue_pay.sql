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

