-- prefectures テーブル
DROP TABLE IF EXISTS prefectures CASCADE;
CREATE TABLE prefectures (
  id CHAR(4),
  area_id CHAR(6),
  name VARCHAR(16),
  name_kana VARCHAR(16),
  PRIMARY KEY (id)
);

