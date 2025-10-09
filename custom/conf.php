<?php
/*****************************************************************************
 *
 * 定数宣言
 *
 ****************************************************************************/

	define( "WS_PACKAGE_ID", "affiliate_pro2" );				//セッションキーprefixなどに使う、パッケージを識別する為のID

	$NOT_LOGIN_USER_TYPE							 = 'nobody';							// ログインしていない状態のユーザ種別名
	$NOT_HEADER_FOOTER_USER_TYPE					 = 'nothf';								// ヘッダー・フッターを表示していない状態のユーザ種別名

	$LOGIN_KEY_FORM_NAME							 = 'mail';								// ログインフォームのキーを入力するフォーム名
	$LOGIN_PASSWD_FORM_NAME							 = 'passwd';							// ログインフォームのパスワードを入力するフォーム名

	$ADD_LOG										 = true;								// DBの新規追加情報を記録するか
	$UPDATE_LOG										 = true;								// DBの更新情報を記録するか
	$DELETE_LOG										 = true;								// DBの削除情報を記録するか

	$SESSION_NAME									 = WS_PACKAGE_ID.'loginid';							// ログイン情報を管理するSESSION の名前
	$COOKIE_NAME									 = WS_PACKAGE_ID.'loginid';							// ログイン情報を管理するCOOKIE の名前

	$SESSION_TYPE									 = WS_PACKAGE_ID.'logintype';			// ログイン情報(TYPE)を管理するSESSION の名前
	$COOKIE_TYPE									 = WS_PACKAGE_ID.'logintype';			// ログイン情報(TYPE)を管理するCOOKIE の名前
	
	$SESSION_PATH_NAME								 = WS_PACKAGE_ID.'__system_path__';						// システムの設置パス情報を管理するSESSION の名前
	$COOKIE_PATH_NAME								 = WS_PACKAGE_ID.'__system_path__';						// システムの設置パス情報を管理するCOOKIE の名前

	$ACTIVE_NONE									 = 1;									// アクティベートされていない状態を表す定数
	$ACTIVE_ACTIVATE	 							 = 2;									// アクティベートされている状態を表す定数
	$ACTIVE_ACCEPT		 							 = 4;									// 許可されている状態を表す定数
	$ACTIVE_DENY		 							 = 8;									// 拒否されている状態を表す定数
	$ACTIVE_ALL	 									 = 15;

    $template_path                                   = "template/pc/";
	$system_path                          	         = "custom/system/";
    $model_path 	                                 = "custom/model/";
    $logic_path 	                                 = "custom/logic/";
	$view_path                          	         = "custom/view/";
	$page_path										 = "file/page/";
	$lst_path										 = "lst/";
	$tdb_path										 = "tdb/";
	$index_path										 = "lst/indexs/";
//	$template_tdb_path								 = "db/template/";
	$sqlite_db_path									 = "tdb/";
	
	$FORM_TAG_DRAW_FLAG	 							 = 'variable';					//  buffer/variable
	
	$COOKIE_PATH 									 = '/';

	$IMAGE_NOT_FOUND								= '<span>No Image</span>';
	$IMAGE_NOT_FOUND_SRC							= 'img/noimage.gif';

	$CSS_PATH										= 'common/css/';

	$terminal_type = isset($terminal_type)?$terminal_type:0;
	$sp_mode = false;
	$sid = "";

	$cron_pass										 = "10NU294Q";

	$FORM_TAG_DRAW_FLAG	 							 = 'buffer';					//  buffer/variable

	$DB_LOG_FILE									 = "logs/dbaccess.log";					// データベースアクセスログファイル
	$COOKIE_PATH 									 = '/';

	$MAX_FILE_SIZE = 1024*1024*2;	// 2MB

	$DELETE_FILE_AUTO = false;
	$DELETE_FILE_TYPES = Array('image','file');

	$ADWARES_LIMIT_TYPE_NONE             = 0;
	$ADWARES_LIMIT_TYPE_YEN              = 1;
	$ADWARES_LIMIT_TYPE_CNT              = 2;
	$ADWARES_LIMIT_TYPE_CNT_CLICK        = 3;
	$ADWARES_LIMIT_TYPE_CNT_CONTINUE     = 4;
	$ADWARES_MONEY_TYPE_YEN              = "yen";
	$ADWARES_MONEY_TYPE_PER              = "per";
	$ADWARES_MONEY_TYPE_RANK             = "rank";
	$ADWARES_MONEY_TYPE_PERSONAL         = "personal";
	$ADWARES_AUTO_ON                     = 1;
	$ADWARES_AUTO_OFF                    = 0;
	$RANK_AUTO_ON                        = 1;
	$RANK_AUTO_OFF                       = 0;
	$i_mode_id                           = true;
	$multimail_send_user['admin']        = true;
	$PAY_TYPE_CLICK                      = 1;//クリック広告
	$PAY_TYPE_NOMAL                      = 2;//成果認証
	$PAY_TYPE_CONTINUE                   = 4;//継続課金

    //文字コード
    $SYSTEM_CHARACODE = "UTF-8";
    $OUTPUT_CHARACODE = $SYSTEM_CHARACODE;
    $LONG_OUTPUT_CHARACODE = "UTF-8";

/***************************
 ** 設定ファイルの読み込み**
 ***************************/

	include_once "custom/extends/sqlConf.php";
	include_once "custom/extends/mobileConf.php";
	include_once "custom/extends/tableConf.php";
	include_once "custom/extends/exceptionConf.php";
	include_once "custom/extends/exConf.php";
	include_once "custom/extends/sslConf.php";
	include_once "custom/extends/systemConf.php";
	include_once "custom/extends/formConf.php";
	include_once "custom/extends/filebaseConf.php";

/*************************
 *  拡張クラスの読み込み *
 *************************/

	//include_once "./include/extends/";

/***************************
 ** LINK&JS IMPORT関連 **
 ****************************/

    $js_file_paths['all']['jquery'] 		= 'js/jquery.js';
	$js_file_paths['all']['selectboxes']	= 'js/jquery.selectboxes.js';
	$js_file_paths['all']['lightbox']   	= 'js/jquery.lightbox.js';
	
	$js_file_paths['admin']['pay']   	= 'js/pay.js';

		/* 管理用 */
	$css_file_paths['admin']['import'] = 'template/pc/css/base.css';
	$css_file_paths['cUser']['import'] = 'template/pc/css/base.css';

	/* フロント用 */
	$css_file_paths['nUser']['import'] = 'template/pc/css/base.css';
    $css_file_paths['nobody']['import'] = 'template/pc/css/base.css';

	$css_file_paths['all']['import']   = 'template/pc/css/base.css';
	$css_file_paths['all']['lightbox']   = 'template/pc/css/jquery.lightbox.css';

?>