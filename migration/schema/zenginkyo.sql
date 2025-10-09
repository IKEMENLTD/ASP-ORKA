-- zenginkyo テーブル
DROP TABLE IF EXISTS zenginkyo CASCADE;
CREATE TABLE zenginkyo (
  id CHAR(5) NOT NULL,
  name_kana VARCHAR(128),
  bank_code VARCHAR(4) NOT NULL,
  bank_name_kana VARCHAR(32),
  branch_code VARCHAR(3) NOT NULL,
  branch_name_kana VARCHAR(32),
  bank_type VARCHAR(1) NOT NULL,
  number VARCHAR(7) NOT NULL,
  PRIMARY KEY (id)
);

