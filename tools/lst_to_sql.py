#!/usr/bin/env python3
"""
LST定義ファイルをPostgreSQL DDL(CREATE TABLE)に変換

使用方法:
    python3 tools/lst_to_sql.py
"""

import os
import csv
from pathlib import Path

# 出力ディレクトリ
OUTPUT_DIR = Path(__file__).parent.parent / 'migration' / 'schema'
OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

# LST定義ディレクトリ
LST_DIR = Path(__file__).parent.parent / 'lst'

# 変換対象テーブル
TABLES = [
    'admin', 'nuser', 'adwares', 'access', 'pay',
    'click_pay', 'continue_pay', 'tier', 'sales',
    'log_pay', 'returnss', 'category', 'area',
    'prefectures', 'zenginkyo', 'blacklist',
    'invitation', 'multimail', 'system', 'template', 'page'
]

def map_type_to_postgresql(php_type, size=''):
    """LST型をPostgreSQL型にマッピング"""
    type_map = {
        'char': f'CHAR({size})' if size else 'VARCHAR(255)',
        'varchar': f'VARCHAR({size})' if size else 'VARCHAR(255)',
        'string': 'TEXT',
        'text': 'TEXT',
        'int': 'INTEGER',
        'double': 'DOUBLE PRECISION',
        'boolean': 'BOOLEAN',
        'timestamp': 'BIGINT',  # Unixタイムスタンプ
        'image': 'VARCHAR(255)',
        'file': 'VARCHAR(255)',
    }
    return type_map.get(php_type, 'TEXT')

def convert_lst_to_sql(lst_file, table_name):
    """LST定義をPostgreSQL DDLに変換"""
    if not lst_file.exists():
        return None, 0

    sql = f"-- {table_name} テーブル\n"
    sql += f"DROP TABLE IF EXISTS {table_name} CASCADE;\n"
    sql += f"CREATE TABLE {table_name} (\n"

    columns = []
    primary_key = ''
    indexes = []
    foreign_keys = []
    column_count = 0

    with open(lst_file, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for row in reader:
            if len(row) < 2:
                continue

            col_name = row[0].strip()
            if not col_name:
                continue

            col_type = row[1].strip()
            col_size = row[2].strip() if len(row) > 2 else ''
            input_rule = row[3].strip() if len(row) > 3 else ''
            update_rule = row[4].strip() if len(row) > 4 else ''

            # PostgreSQL型にマッピング
            pg_type = map_type_to_postgresql(col_type, col_size)

            # 制約
            constraints = []

            # NOT NULL制約
            if 'Null' in input_rule or 'Null' in update_rule:
                if 'Null/' in input_rule or 'Null/' in update_rule:
                    constraints.append('NOT NULL')
                elif input_rule == 'Null' or update_rule == 'Null':
                    constraints.append('NOT NULL')

            # PRIMARY KEY
            if col_name == 'id':
                primary_key = col_name

            # UNIQUE制約
            if 'MailDup' in input_rule or 'MailDup' in update_rule:
                if col_name == 'mail':
                    constraints.append('UNIQUE')

            # DEFAULT値
            if 'BOOLEAN' in pg_type:
                constraints.append('DEFAULT FALSE')
            elif 'INTEGER' in pg_type and col_name in ['activate', 'pay', 'tier', 'money_count', 'pay_count', 'click_money_count', 'continue_money_count']:
                constraints.append('DEFAULT 0')

            column_def = f"  {col_name} {pg_type}"
            if constraints:
                column_def += ' ' + ' '.join(constraints)

            columns.append(column_def)
            column_count += 1

            # 外部キー検出
            if col_name in ['parent', 'grandparent', 'greatgrandparent'] and table_name == 'nuser':
                foreign_keys.append(f"  FOREIGN KEY ({col_name}) REFERENCES nuser(id) ON DELETE SET NULL")

            if col_name == 'access_id' and table_name in ['pay', 'click_pay']:
                foreign_keys.append(f"  FOREIGN KEY ({col_name}) REFERENCES access(id) ON DELETE SET NULL")

            if col_name == 'owner' and table_name in ['pay', 'click_pay', 'continue_pay', 'returnss', 'invitation']:
                foreign_keys.append(f"  FOREIGN KEY ({col_name}) REFERENCES nuser(id) ON DELETE CASCADE")

            # インデックス検出
            if col_name in ['cookie', 'owner', 'adwares', 'parent', 'mail']:
                indexes.append(f"CREATE INDEX idx_{table_name}_{col_name} ON {table_name}({col_name});")

            if col_name == 'state' and table_name in ['pay', 'click_pay', 'continue_pay']:
                indexes.append(f"CREATE INDEX idx_{table_name}_{col_name} ON {table_name}({col_name});")

    # カラム定義を結合
    sql += ',\n'.join(columns)

    # PRIMARY KEY追加
    if primary_key:
        sql += f",\n  PRIMARY KEY ({primary_key})"

    # 外部キー追加
    if foreign_keys:
        sql += ',\n' + ',\n'.join(foreign_keys)

    sql += "\n);\n\n"

    # インデックス追加
    if indexes:
        sql += "-- インデックス\n"
        sql += '\n'.join(indexes) + '\n\n'

    return sql, column_count

def main():
    print("========================================")
    print("  LST → PostgreSQL DDL 変換")
    print("========================================")
    print()

    total_tables = 0
    total_columns = 0
    all_sql = ""

    for table in TABLES:
        lst_file = LST_DIR / f"{table}.csv"
        sql_file = OUTPUT_DIR / f"{table}.sql"

        if not lst_file.exists():
            print(f"⚠ スキップ: {table}.csv が見つかりません")
            continue

        print(f"変換中: {table}...")

        sql, column_count = convert_lst_to_sql(lst_file, table)

        if sql:
            # 個別SQLファイル出力
            with open(sql_file, 'w', encoding='utf-8') as f:
                f.write(sql)

            all_sql += sql
            total_tables += 1
            total_columns += column_count

            print(f"  ✓ {sql_file}")

    # 統合SQLファイル出力
    combined_file = OUTPUT_DIR.parent / '001_create_all_tables.sql'
    with open(combined_file, 'w', encoding='utf-8') as f:
        f.write("-- アフィリエイトシステムプロ - 全テーブル作成\n")
        f.write("-- 自動生成日: " + Path(__file__).stat().st_mtime.__str__() + "\n\n")
        f.write(all_sql)

    print()
    print("========================================")
    print("  変換完了")
    print("========================================")
    print(f"テーブル数: {total_tables}")
    print(f"カラム総数: {total_columns}")
    print()
    print("生成されたファイル:")
    print(f"  - migration/schema/*.sql (個別テーブル)")
    print(f"  - migration/001_create_all_tables.sql (統合)")
    print()
    print("次のステップ:")
    print("  1. Supabase SQL Editorで 001_create_all_tables.sql を実行")
    print("  2. またはpsqlコマンドで実行")
    print()

if __name__ == '__main__':
    main()
