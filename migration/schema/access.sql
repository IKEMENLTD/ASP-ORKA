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

