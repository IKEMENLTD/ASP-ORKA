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

