<?php

// 環境変数読み込み
require_once __DIR__ . '/../load_env.php';

/*************************
 ** SQL DATABSE 用 定義 **
 *************************/

	$SQL											 = true;				// SQLを用いるかどうかのフラグ

	// 環境変数からデータベース設定を取得（Session Pooler使用）
	$SQL_SERVER										 = getenv('SUPABASE_DB_HOST') ?: 'localhost';
	$SQL_PORT										 = getenv('SUPABASE_DB_PORT') ?: '5432';

	// SQLデーモンのクラス名 (PostgreSQL使用)
	$SQL_MASTER										 = 'PostgreSQLDatabase';

	$DB_NAME										 = getenv('SUPABASE_DB_NAME') ?: 'postgres';
	$SQL_ID	 										 = getenv('SUPABASE_DB_USER') ?: 'postgres';
	$SQL_PASS  										 = getenv('SUPABASE_DB_PASS') ?: '';

	$TABLE_PREFIX									 = '';

	$CONFIG_SQL_FILE_TYPES = Array('image','file');

	$CONFIG_SQL_DATABASE_SESSION = false;

	//the 128 bit key value for crypting
	$CONFIG_SQL_PASSWORD_KEY = getenv('SQL_PASSWORD_KEY') ?: 'derhymqadbrheng';
?>
