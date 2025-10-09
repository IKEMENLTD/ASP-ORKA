<?php

	// 環境変数読み込み
	require_once __DIR__ . '/../load_env.php';

	include_once "include/base/interface/iFileBase.php";
	include_once 'include/extends/FileBaseControl.php';

	//アップロードファイルの保存先変更用の設定
    //$CONF_FILEDIR_ENGINEの設定ファイルを読み込み、アップロードファイルの保存先を変更する

	// Supabase Storage使用フラグ
	$useSupabaseStorage = getenv('USE_SUPABASE_STORAGE');

	if ($useSupabaseStorage === 'true' || $useSupabaseStorage === '1') {
		// Supabase Storage使用
		include_once 'include/extends/SupabaseFileBase.php';
		$FileBase = new \Websquare\FileBase\SupabaseFileBase();
		$FileBase->init([]);
	} else {
		// ローカルファイルシステム使用（デフォルト）
		$CONF_FILEBASE_FLAG = false;
		$CONF_FILEBASE_ENGINE = 'Null';
		$FileBase = \Websquare\FileBase\FileBaseControl::getControl();
	}

