-- invitation テーブル
DROP TABLE IF EXISTS invitation CASCADE;
CREATE TABLE invitation (
  id CHAR(8),
  owner CHAR(8),
  mail VARCHAR(128) NOT NULL UNIQUE,
  message TEXT,
  regist BIGINT,
  PRIMARY KEY (id),
  FOREIGN KEY (owner) REFERENCES nuser(id) ON DELETE CASCADE
);

-- インデックス
CREATE INDEX idx_invitation_owner ON invitation(owner);
CREATE INDEX idx_invitation_mail ON invitation(mail);

