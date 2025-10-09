-- blacklist テーブル
DROP TABLE IF EXISTS blacklist CASCADE;
CREATE TABLE blacklist (
  id CHAR(32),
  blacklist_mode VARCHAR(32),
  blacklist_value VARCHAR(255) NOT NULL,
  memo VARCHAR(255),
  PRIMARY KEY (id)
);

