#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
文字エンコーディング修正スクリプト Batch 2
Remaining 43 files with Yen sign (¥ = 0xC2 0xA5) -> Backslash (\ = 0x5C)
"""

import os
import sys

def fix_encoding(filepath):
    """ファイルの文字エンコーディングを修正"""
    try:
        with open(filepath, 'rb') as f:
            content = f.read()

        original_content = content

        # Yen sign (UTF-8: 0xC2 0xA5) -> Backslash (0x5C)
        content = content.replace(b'\xc2\xa5', b'\\')

        # Wave dash variant (UTF-8: 0xE2 0x80 0xBE) -> Tilde (0x7E)
        content = content.replace(b'\xe2\x80\xbe', b'~')

        if content != original_content:
            with open(filepath, 'wb') as f:
                f.write(content)
            bytes_replaced = len(original_content) - len(content)
            return True, abs(bytes_replaced) if bytes_replaced < 0 else len(original_content) - len(content)
        return False, 0
    except Exception as e:
        print(f"  ERROR: {e}", file=sys.stderr)
        return False, 0

def main():
    # Batch 2: Remaining 43 files
    files_to_fix = [
        'include/base/SQLDatabase.php',  # Already fixed, but keeping for consistency
        'include/Database.php',
        'include/MailToFile.php',
        'include/Template.php',
        'include/base/SQLTable.php',
        'include/base/Table.php',
        'include/base/ccProc.php',
        'include/extends/DebugUtil.php',
        'include/extends/Exception/ExceptionManager.php',
        'include/extends/Extension.php',
        'include/extends/FileLockBase.php',
        'include/extends/MobileUtil.php',
        'include/extends/PathUtil.php',
        'include/extends/PaymentUtil.php',
        'include/extends/PointMailUtil.php',
        'include/extends/PointPayUtil.php',
        'include/extends/SQLiteDatabase.php',
        'include/extends/SessionUtil.php',
        'include/extends/SupabaseDB.php',
        'include/extends/Util.php',
        'include/extends/mobile/EmojiDocomo.php',
        'include/extends/mobile/EmojiSoftbank.php',
        'include/extends/mobile/MobileStrConv.php',
        'module/clickCountLog.inc',
        'module/fileRead.inc',
        'module/friendDef.php',
        'module/friendProc.php',
        'module/linkCountLog.inc',
        'module/paginationWithTableNumSearch.php',
        'module/renderTemplate.php',
        'custom/checkData.php',  # Already fixed
        'custom/extends/SystemExtends.php',
        'custom/functions.php',
        'custom/head.php',
        'custom/head_admin.php',
        'custom/head_main.php',
        'custom/head_user.php',
        'custom/setting.php',
        'custom/extends/func.php',
        'custom/extends/mobileConf.php',
        'tools/CSVImport.php',
        'tools/FileDownLoad.inc',
        'tools/paginationWithTableNum.php',
        'tools/renderTemplate.php',
    ]

    print("=== Batch 2: Remaining 43 Files ===\n")

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
    print(f"\nBatch 2 complete!")

if __name__ == '__main__':
    main()
