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

