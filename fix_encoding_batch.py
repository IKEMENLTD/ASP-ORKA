#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
文字エンコーディング修正スクリプト
Yen sign (¥ = 0xC2 0xA5) を Backslash (\ = 0x5C) に置き換え
"""

import os
import sys

def fix_encoding(filepath):
    """ファイルの文字エンコーディングを修正"""
    try:
        with open(filepath, 'rb') as f:
            content = f.read()

        original_size = len(content)

        # Yen sign (UTF-8: 0xC2 0xA5) -> Backslash (0x5C)
        content = content.replace(b'\xc2\xa5', b'\\')

        # Wave dash variant (UTF-8: 0xE2 0x80 0xBE) -> Tilde (0x7E)
        content = content.replace(b'\xe2\x80\xbe', b'~')

        fixed_size = len(content)
        replacements = (original_size - fixed_size) // 1  # 大まかな置換数

        if content != original_size:
            with open(filepath, 'wb') as f:
                f.write(content)
            return True, replacements
        return False, 0
    except Exception as e:
        print(f"  ERROR: {e}", file=sys.stderr)
        return False, 0

def main():
    # 修正対象ファイルリスト（優先度順）
    files_to_fix = [
        'custom/checkData.php',
        'include/base/Util.php',
        'include/Util.php',
        'include/base/SQLDatabase.php',
        'include/extends/Exception/ErrorManager.php',
        'include/Mail.php',
        'include/GUIManager.php',
        'include/base/SystemBase.php',
        'include/Command.php',
        'include/base/ccProcBase.php',
    ]

    print("=== Batch 1: Top 10 Critical Files ===\n")

    total_fixed = 0
    total_replacements = 0

    for filepath in files_to_fix:
        if os.path.exists(filepath):
            print(f"Fixing: {filepath}...", end=' ')
            fixed, count = fix_encoding(filepath)
            if fixed:
                print(f"✓ ({count} bytes replaced)")
                total_fixed += 1
                total_replacements += count
            else:
                print("No changes needed")
        else:
            print(f"SKIP: {filepath} (not found)")

    print(f"\n=== Summary ===")
    print(f"Files fixed: {total_fixed}")
    print(f"Total bytes replaced: ~{total_replacements}")
    print(f"\nBatch 1 complete!")

if __name__ == '__main__':
    main()
