#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Apply database migration to Supabase PostgreSQL
"""

import sys
import os

try:
    import psycopg2
    from psycopg2 import sql
except ImportError:
    print("ERROR: psycopg2 not installed. Installing...")
    os.system("pip3 install psycopg2-binary")
    import psycopg2
    from psycopg2 import sql

# Supabase connection (Direct Connection)
DB_CONFIG = {
    'host': 'db.ezucbzqzvxgcyikkrznj.supabase.co',
    'port': 5432,
    'database': 'postgres',
    'user': 'postgres',
    'password': 'akutu4256'
}

def connect_db():
    """Connect to PostgreSQL database"""
    try:
        conn = psycopg2.connect(**DB_CONFIG)
        print(f"✓ Connected to PostgreSQL at {DB_CONFIG['host']}")
        return conn
    except Exception as e:
        print(f"✗ Connection failed: {e}")
        return None

def apply_migration(conn, sql_file):
    """Apply SQL migration file"""
    try:
        with open(sql_file, 'r', encoding='utf-8') as f:
            sql_content = f.read()

        cursor = conn.cursor()
        cursor.execute(sql_content)
        conn.commit()
        cursor.close()

        print(f"✓ Applied migration: {sql_file}")
        return True
    except Exception as e:
        print(f"✗ Migration failed: {e}")
        conn.rollback()
        return False

def check_table_exists(conn, table_name):
    """Check if table exists"""
    try:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_name = %s
            )
        """, (table_name,))
        exists = cursor.fetchone()[0]
        cursor.close()
        return exists
    except Exception as e:
        print(f"✗ Check failed: {e}")
        return False

def get_table_columns(conn, table_name):
    """Get table column names"""
    try:
        cursor = conn.cursor()
        cursor.execute("""
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = %s
            ORDER BY ordinal_position
        """, (table_name,))
        columns = cursor.fetchall()
        cursor.close()
        return columns
    except Exception as e:
        print(f"✗ Get columns failed: {e}")
        return []

def main():
    print("=== Database Migration ===\n")

    # Connect to database
    conn = connect_db()
    if not conn:
        return 1

    # Apply migration
    migration_file = 'migration/001_create_system.sql'
    if not os.path.exists(migration_file):
        print(f"✗ Migration file not found: {migration_file}")
        conn.close()
        return 1

    success = apply_migration(conn, migration_file)

    if success:
        # Verify table exists
        if check_table_exists(conn, 'system'):
            print("\n✓ Table 'system' created successfully")

            # Show columns
            columns = get_table_columns(conn, 'system')
            print(f"\nColumns ({len(columns)}):")
            for col_name, col_type in columns:
                print(f"  - {col_name}: {col_type}")
        else:
            print("\n✗ Table 'system' was not created")
            success = False

    conn.close()
    return 0 if success else 1

if __name__ == '__main__':
    sys.exit(main())
