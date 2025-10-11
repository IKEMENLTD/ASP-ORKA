#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix ccProc methods to be static for PHP 8 compatibility
"""

import re

# Read the file
with open('include/ccProc.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Process each line
output_lines = []
modified_count = 0

for line in lines:
    # Match lines like "		function methodName(" but NOT "		static function"
    # Need to handle both tabs and spaces at the beginning
    if re.match(r'^(\s+)function\s+', line) and 'static function' not in line:
        # Add static before function
        modified_line = re.sub(r'^(\s+)function\s+', r'\1static function ', line)
        output_lines.append(modified_line)
        modified_count += 1
        # Print what we're changing (extract method name for clarity)
        match = re.search(r'function\s+(\w+)', line)
        if match:
            print(f"✓ Made {match.group(1)}() static")
    else:
        output_lines.append(line)

# Write back
with open('include/ccProc.php', 'w', encoding='utf-8') as f:
    f.writelines(output_lines)

print(f"\n✓ Modified {modified_count} methods to be static")
print("✓ File updated")
