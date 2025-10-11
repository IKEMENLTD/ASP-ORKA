#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix implode() argument order for PHP 8 compatibility
"""

import re

# Read the file
with open('include/base/SQLDatabase.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Fix line 2916: implode($array," $conjunction ")
# Should be: implode(" $conjunction ", $array)
old_line = 'return "(".implode($array," $conjunction ").")"'
new_line = '// PHP 8 compatibility: implode(separator, array) order is required\n\treturn "(".implode(" $conjunction ", $array).")"'

if old_line in content:
    content = content.replace(old_line, new_line)
    print("✓ Fixed implode() on line 2916")
else:
    print("✗ Pattern not found on line 2916")

# Write back
with open('include/base/SQLDatabase.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✓ File updated")
