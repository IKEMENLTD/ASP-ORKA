#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix SQL error message in SQLDatabase.php to include actual error details
"""

# Read the file
with open('include/base/SQLDatabase.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Old error message (simple)
old_error = 'throw new InternalErrorException("getRecord() : SQL MESSAGE ERROR. \\n");'

# New error message (detailed)
new_error = '''// PHP 8 compatibility: Enhanced error message with SQL query and actual error
			$sql_query = isset($table) ? $table->getString() : 'unknown';
			$last_error = '';
			if (function_exists('pg_last_error') && isset($this->connect)) {
				$last_error = pg_last_error($this->connect);
			}
			throw new InternalErrorException(
				"getRecord() : SQL MESSAGE ERROR\\n" .
				"Table: {$this->tableName}\\n" .
				"SQL: {$sql_query}\\n" .
				"Error: {$last_error}\\n"
			);'''

# Replace
if old_error in content:
    content = content.replace(old_error, new_error)
    print("✓ Fixed getRecord() error message")
else:
    print("✗ Pattern not found")

# Write back
with open('include/base/SQLDatabase.php', 'w', encoding='utf-8') as f:
    f.write(content)
