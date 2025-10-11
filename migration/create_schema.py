#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
LST CSVファイルからPostgreSQL CREATE TABLE文を生成
"""

import csv
import os
import sys

def csv_to_postgres_type(csv_type, size):
    """CSV型をPostgreSQL型に変換"""
    type_map = {
        'char': f'CHAR({size})' if size else 'TEXT',
        'varchar': f'VARCHAR({size})' if size else 'TEXT',
        'string': 'TEXT',
        'int': 'INTEGER',
        'boolean': 'BOOLEAN',
        'timestamp': 'INTEGER',  # Unix timestamp
        'date': 'DATE',
        'text': 'TEXT',
    }
    return type_map.get(csv_type.lower(), 'TEXT')

def parse_lst_csv(csv_file):
    """LST CSVファイルを解析してカラム定義を取得"""
    columns = []

    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for row in reader:
            if len(row) < 2:
                continue

            col_name = row[0].strip()
            col_type = row[1].strip() if len(row) > 1 else 'string'
            col_size = row[2].strip() if len(row) > 2 else ''

            if not col_name:
                continue

            pg_type = csv_to_postgres_type(col_type, col_size)
            columns.append({
                'name': col_name,
                'type': pg_type,
                'original_type': col_type
            })

    return columns

def generate_create_table(table_name, columns):
    """CREATE TABLE文を生成"""
    sql = f"CREATE TABLE IF NOT EXISTS {table_name} (\n"

    col_definitions = []
    for col in columns:
        col_def = f"  {col['name']} {col['type']}"

        # PRIMARY KEYの判定
        if col['name'] == 'id':
            col_def += " PRIMARY KEY"

        col_definitions.append(col_def)

    # 削除フラグを追加（システムの標準）
    col_definitions.append("  delete_key BOOLEAN DEFAULT FALSE")
    col_definitions.append("  shadow_id INTEGER")

    sql += ",\n".join(col_definitions)
    sql += "\n);\n"

    # インデックス作成
    sql += f"\nCREATE INDEX IF NOT EXISTS idx_{table_name}_delete_key ON {table_name}(delete_key);\n"
    sql += f"CREATE INDEX IF NOT EXISTS idx_{table_name}_shadow_id ON {table_name}(shadow_id);\n"

    return sql

def main():
    # systemテーブルを最初に作成
    table_name = 'system'
    csv_file = f'lst/{table_name}.csv'

    if not os.path.exists(csv_file):
        print(f"ERROR: {csv_file} not found")
        return 1

    print(f"=== Generating schema for {table_name} table ===\n")

    columns = parse_lst_csv(csv_file)

    if not columns:
        print(f"ERROR: No columns found in {csv_file}")
        return 1

    sql = generate_create_table(table_name, columns)

    # SQLファイルに保存
    output_file = f'migration/001_create_{table_name}.sql'
    os.makedirs('migration', exist_ok=True)

    with open(output_file, 'w', encoding='utf-8') as f:
        f.write("-- Migration: Create system table\n")
        f.write(f"-- Generated from: {csv_file}\n\n")
        f.write(sql)

    print(f"✓ Generated: {output_file}")
    print(f"✓ Columns: {len(columns)}")
    print(f"\nSQL Preview:\n{sql}")

    return 0

if __name__ == '__main__':
    sys.exit(main())
