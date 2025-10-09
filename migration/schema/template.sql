-- template テーブル
DROP TABLE IF EXISTS template CASCADE;
CREATE TABLE template (
  id CHAR(5),
  user_type VARCHAR(64),
  target_type VARCHAR(32),
  activate INTEGER DEFAULT 0,
  owner INTEGER,
  label VARCHAR(128),
  file VARCHAR(255),
  regist INTEGER,
  PRIMARY KEY (id)
);

-- インデックス
CREATE INDEX idx_template_owner ON template(owner);

