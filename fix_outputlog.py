#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix OutputLog.php constructor to create log directory and file
"""

import re

# Read the file
with open('include/OutputLog.php', 'r', encoding='utf-8') as f:
    content = f.read()

# Replace the constructor logic
old_constructor = '''	function __construct($file)
	{
		if( !file_exists( $file ) )	{ throw new InternalErrorException('LOGファイルが開けません。->'. $file); }
		$this->file = $file;
	}'''

new_constructor = '''	function __construct($file)
	{
		// PHP 8 compatibility: Create log file and directory if they don't exist
		$dir = dirname($file);
		if (!is_dir($dir)) {
			@mkdir($dir, 0777, true);
		}
		if (!file_exists($file)) {
			@touch($file);
			@chmod($file, 0766);
		}
		if (!is_writable($file)) {
			throw new InternalErrorException('LOGファイルに書き込めません。->'. $file);
		}
		$this->file = $file;
	}'''

content = content.replace(old_constructor, new_constructor)

# Write the file back
with open('include/OutputLog.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✓ Fixed OutputLog.php constructor")
