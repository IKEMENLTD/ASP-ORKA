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

