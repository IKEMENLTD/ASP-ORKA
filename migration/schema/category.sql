-- category テーブル
DROP TABLE IF EXISTS category CASCADE;
CREATE TABLE category (
  id CHAR(8),
  name VARCHAR(128) NOT NULL,
  regist BIGINT,
  PRIMARY KEY (id)
);

