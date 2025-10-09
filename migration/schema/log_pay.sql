-- log_pay テーブル
DROP TABLE IF EXISTS log_pay CASCADE;
CREATE TABLE log_pay (
  id CHAR(16),
  pay_type VARCHAR(32),
  pay_id CHAR(32),
  nuser_id CHAR(8),
  operator VARCHAR(8),
  cost INTEGER NOT NULL,
  state INTEGER NOT NULL,
  action VARCHAR(16),
  regist BIGINT,
  PRIMARY KEY (id)
);

