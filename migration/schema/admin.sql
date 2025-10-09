-- admin テーブル
DROP TABLE IF EXISTS admin CASCADE;
CREATE TABLE admin (
  id CHAR(5),
  name VARCHAR(128),
  mail VARCHAR(255) NOT NULL,
  pass VARCHAR(128) NOT NULL,
  activate INTEGER DEFAULT 0,
  logout BIGINT,
  login BIGINT,
  old_login BIGINT,
  mail_time BIGINT,
  PRIMARY KEY (id)
);

-- インデックス
CREATE INDEX idx_admin_mail ON admin(mail);

