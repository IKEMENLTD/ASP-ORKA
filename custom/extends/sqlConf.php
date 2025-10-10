<?php

// 環境変数読み込み
require_once __DIR__ . '/../load_env.php';

/*************************
 ** SQL DATABSE 用 定義 **
 *************************/

	$SQL											 = true;				// SQLを用いるかどうかのフラグ

	// 環境変数からデータベース設定を取得（Session Pooler使用）
	// フォールバック値なし - 環境変数が設定されていない場合はエラー
	$SQL_SERVER										 = getenv('SUPABASE_DB_HOST');
	$SQL_PORT										 = getenv('SUPABASE_DB_PORT') ?: '5432';

	// SQLデーモンのクラス名 (PostgreSQL使用)
	$SQL_MASTER										 = 'PostgreSQLDatabase';

	$DB_NAME										 = getenv('SUPABASE_DB_NAME') ?: 'postgres';
	$SQL_ID	 										 = getenv('SUPABASE_DB_USER');
	$SQL_PASS  										 = getenv('SUPABASE_DB_PASS');

	// 必須環境変数のチェック（警告のみ、起動は継続）
	if (!$SQL_SERVER || !$SQL_ID || !$SQL_PASS) {
		error_log('WARNING: Some database environment variables not set');
		error_log('SUPABASE_DB_HOST: ' . ($SQL_SERVER ? 'set' : 'NOT SET'));
		error_log('SUPABASE_DB_USER: ' . ($SQL_ID ? 'set' : 'NOT SET'));
		error_log('SUPABASE_DB_PASS: ' . ($SQL_PASS ? 'set' : 'NOT SET'));
		// 起動は継続（データベース接続時にエラーになる）
	}

	$TABLE_PREFIX									 = '';

	$CONFIG_SQL_FILE_TYPES = Array('image','file');

	$CONFIG_SQL_DATABASE_SESSION = false;

	//the 128 bit key value for crypting
	$CONFIG_SQL_PASSWORD_KEY = getenv('SQL_PASSWORD_KEY');
	if (!$CONFIG_SQL_PASSWORD_KEY) {
		error_log('WARNING: SQL_PASSWORD_KEY not set, using default');
		$CONFIG_SQL_PASSWORD_KEY = 'derhymqadbrheng';
	}
?>
