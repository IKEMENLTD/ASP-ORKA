#!/usr/bin/env python3
"""
CSVデータをPostgreSQLに移行

使用方法:
    # 環境変数設定
    export SUPABASE_DB_HOST="db.xxxxx.supabase.co"
    export SUPABASE_DB_PORT="5432"
    export SUPABASE_DB_NAME="postgres"
    export SUPABASE_DB_USER="postgres"
    export SUPABASE_DB_PASS="your-password"

    # 実行
    python3 tools/migrate_data.py
"""

import os
import csv
import sys
from pathlib import Path

try:
    import psycopg2
    from psycopg2 import sql
except ImportError:
    print("エラー: psycopg2がインストールされていません")
    print("実行: pip3 install psycopg2-binary")
    sys.exit(1)

# ディレクトリ
TDB_DIR = Path(__file__).parent.parent / 'tdb'
LST_DIR = Path(__file__).parent.parent / 'lst'

# 移行順序 (外部キー制約を考慮)
MIGRATION_ORDER = [
    # マスタデータ（依存なし）
    'area', 'prefectures', 'zenginkyo', 'category', 'sales',
    'blacklist', 'template', 'page', 'system',

    # ユーザーデータ（自己参照あり）
    'admin', 'nuser',

    # 広告データ
    'adwares',

    # トランザクションデータ
    'access', 'pay', 'click_pay', 'continue_pay',
    'tier', 'log_pay', 'returnss',

    # その他
    'invitation', 'multimail'
]

def get_columns_from_lst(lst_file):
    """LST定義からカラム名リストを取得"""
    columns = []
    with open(lst_file, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        for row in reader:
            if row and row[0].strip():
                columns.append(row[0].strip())
    return columns

def convert_value(value, column_type):
    """値を適切な型に変換"""
    if not value or value == '':
        return None

    # 数値型
    if column_type in ['INTEGER', 'BIGINT']:
        try:
            return int(value)
        except ValueError:
            return None

    # 浮動小数点
    if column_type == 'DOUBLE PRECISION':
        try:
            return float(value)
        except ValueError:
            return None

    # ブール型
    if column_type == 'BOOLEAN':
        if value in ['1', 'true', 'TRUE', 't']:
            return True
        elif value in ['0', 'false', 'FALSE', 'f', '']:
            return False
        return None

    # 文字列型
    return value

def get_column_types(cursor, table_name):
    """テーブルのカラム型情報を取得"""
    cursor.execute("""
        SELECT column_name, data_type, udt_name
        FROM information_schema.columns
        WHERE table_name = %s
        ORDER BY ordinal_position
    """, (table_name,))

    types = {}
    for row in cursor.fetchall():
        col_name = row[0]
        data_type = row[1].upper()
        types[col_name] = data_type

    return types

def migrate_table(cursor, table_name):
    """1テーブルのデータを移行"""
    csv_file = TDB_DIR / f"{table_name}.csv"
    lst_file = LST_DIR / f"{table_name}.csv"

    if not csv_file.exists():
        print(f"  ⚠ スキップ: {csv_file} が見つかりません")
        return 0, 0

    if not lst_file.exists():
        print(f"  ⚠ スキップ: {lst_file} が見つかりません")
        return 0, 0

    # カラム名取得
    columns = get_columns_from_lst(lst_file)
    if not columns:
        print(f"  ⚠ スキップ: カラム定義が見つかりません")
        return 0, 0

    # カラム型情報取得
    column_types = get_column_types(cursor, table_name)

    # CSVデータ読み込み
    with open(csv_file, 'r', encoding='utf-8') as f:
        reader = csv.reader(f)
        rows = list(reader)

    if not rows:
        print(f"  ⚠ データなし")
        return 0, 0

    success_count = 0
    error_count = 0

    for row in rows:
        if not row or not row[0] or row[0] == '0':
            continue

        # 値を型に合わせて変換
        values = []
        for i, col_name in enumerate(columns):
            if i < len(row):
                col_type = column_types.get(col_name, 'TEXT')
                value = convert_value(row[i], col_type)
                values.append(value)
            else:
                values.append(None)

        # INSERT文構築
        placeholders = ','.join(['%s'] * len(columns))
        query = f"INSERT INTO {table_name} ({','.join(columns)}) VALUES ({placeholders})"

        try:
            cursor.execute(query, values)
            success_count += 1
        except Exception as e:
            error_count += 1
            if error_count <= 5:  # 最初の5件のみエラー表示
                print(f"    エラー: {e}")
                print(f"    データ: {values[:3]}...")  # 最初の3カラムのみ表示

    return success_count, error_count

def main():
    print("========================================")
    print("  CSV → PostgreSQL データ移行")
    print("========================================")
    print()

    # 環境変数確認
    db_config = {
        'host': os.getenv('SUPABASE_DB_HOST'),
        'port': os.getenv('SUPABASE_DB_PORT', '5432'),
        'database': os.getenv('SUPABASE_DB_NAME', 'postgres'),
        'user': os.getenv('SUPABASE_DB_USER', 'postgres'),
        'password': os.getenv('SUPABASE_DB_PASS'),
    }

    if not db_config['host'] or not db_config['password']:
        print("エラー: 環境変数が設定されていません")
        print()
        print("以下の環境変数を設定してください:")
        print("  export SUPABASE_DB_HOST=\"db.xxxxx.supabase.co\"")
        print("  export SUPABASE_DB_PASS=\"your-password\"")
        print()
        sys.exit(1)

    print(f"接続先: {db_config['host']}:{db_config['port']}")
    print()

    # PostgreSQL接続
    try:
        conn = psycopg2.connect(**db_config)
        conn.set_session(autocommit=False)
        cursor = conn.cursor()
        print("✓ 接続成功")
        print()
    except Exception as e:
        print(f"✗ 接続エラー: {e}")
        sys.exit(1)

    total_success = 0
    total_error = 0

    try:
        for table_name in MIGRATION_ORDER:
            print(f"[{MIGRATION_ORDER.index(table_name)+1}/{len(MIGRATION_ORDER)}] {table_name}...")

            success, error = migrate_table(cursor, table_name)
            total_success += success
            total_error += error

            if success > 0:
                print(f"  ✓ {success}件")
            if error > 0:
                print(f"  ⚠ エラー: {error}件")

        # コミット
        conn.commit()
        print()
        print("✓ コミット完了")

    except Exception as e:
        conn.rollback()
        print()
        print(f"✗ エラー: {e}")
        print("ロールバックしました")
        sys.exit(1)

    finally:
        cursor.close()
        conn.close()

    print()
    print("========================================")
    print("  移行完了")
    print("========================================")
    print(f"成功: {total_success}件")
    print(f"エラー: {total_error}件")
    print()

    if total_error > 0:
        print("⚠ エラーがあります。ログを確認してください。")
        sys.exit(1)

if __name__ == '__main__':
    main()
