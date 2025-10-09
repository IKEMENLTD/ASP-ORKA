-- multimail テーブル
DROP TABLE IF EXISTS multimail CASCADE;
CREATE TABLE multimail (
  id CHAR(8),
  sub VARCHAR(128) NOT NULL,
  main TEXT NOT NULL,
  receive_id TEXT,
  PRIMARY KEY (id)
);

