<?PHP
	// P3P header commented out to avoid header warnings
	// header( 'P3P: CP="NOI DSP COR NID CURa OUR IND STA"' );
	$mobile_flag = true;
	$charcode_flag = false;  // UTF-8環境ではfalseに設定（Shift_JIS設定を無効化）
	$euc_garble = false;
	include_once "custom/extends/debugConf.php";
	include_once "custom/conf.php";
	include_once "custom/extends/initConf.php";


	$magic_quotes_gpc = ini_get('magic_quotes_gpc');
	//$magic_quotes_gpc = false;
	date_default_timezone_set( 'Asia/Tokyo' );

	if( $charcode_flag )
	{
		$TRUTH_INTERNAL_ENCODING = mb_internal_encoding();
		ini_set("output_buffering","Off"); // 出力バッファリングを指定します 
		ini_set("default_charset","Shift_JIS"); // デフォルトの文字コードを指定します 
		ini_set("extension","php_mbstring.dll"); // マルチバイト文字列を有効にします。 
		ini_set("mbstring.language","Japanese"); // デフォルトを日本語に設定します。 
		ini_set("mbstring.encoding_translation","Off");//自動エンコーディングを無効に出来る場合は無効にする 
		ini_set("mbstring.detect_order","auto"); // 文字コード検出をautoに設定します。 
		ini_set("mbstring.substitute_character","none"); // 無効な文字を出力しない。 
		if(PHP_VERSION_ID<=50509){
			ini_set("mbstring.internal_encoding","SJIS"); // 内部文字エンコーディングをSJISに設定します。 
			ini_set("mbstring.http_input","auto"); // HTTP入力文字エンコーディング変換をautoに設定します。 
		}
		mb_internal_encoding('SJIS');

		// ケータイの絵文字が表示されない場合は以下の2行をコメントアウト
		if (PHP_VERSION_ID <= 50509) {
			ini_set("mbstring.http_output", "SJIS"); // HTTP出力文字エンコーディング変換をSJISに設定します。
		}
		mb_http_output('SJIS');
	}

	include_once "include/info/SystemInfo.php";
	include_once "include/Weave.php";


/*	include_once "./include/Util.php";*/
	include_once "include/base/Util.php";

	if(!$CRON_SESSION_FLAG){
		session_start();
	}

	if( $terminal_type ) //携帯の場合
		{ $sid = h( SID ); }
	else //携帯以外の端末の場合
	{
		if( $_GET[ ini_get( 'session.name' ) ] == session_id() || $_POST[ ini_get( 'session.name' ) ] == session_id() ) //GET/POSTからの設定は一度強制変更する
			{ session_regenerate_id(); }
	}

	if ($magic_quotes_gpc) {
		$_GET = stripslashes_deep($_GET);
		$_POST = stripslashes_deep($_POST);
		$_COOKIE = stripslashes_deep($_COOKIE);
	}

//euc-jpで$charcode_flag=trueでpost,getが文字化ける場合に有効にする
	if( $euc_garble ){
		mb_convert_variables("SJIS",$TRUTH_INTERNAL_ENCODING, $_POST);
		mb_convert_variables("SJIS",$TRUTH_INTERNAL_ENCODING, $_GET);
	}

	include_once 'include/base/ccProcBase.php';
	include_once "include/ccProc.php";
	include_once "include/IncludeObject.php";
	include_once "include/GUIManager.php";
	include_once "include/Search.php";
	include_once "include/Mail.php";
	include_once "include/Template.php";
	include_once 'include/templateCache.php';
	include_once "include/Command.php";
	include_once "include/GMList.php";
	include_once "custom/checkData.php";
	include_once "custom/extension.php";
	include_once "custom/global.php";
	include_once "module/module.inc";
	include_once $system_path."System.php";

	include_once "custom/extends/modelConf.php";
	include_once "custom/extends/logicConf.php";

	CleanGlobal::action();

	// データベースロード
	$gm		 = SystemUtil::getGM();

	//sytem data set
	$tdb = $gm['system']->getDB();
	$trec = $tdb->getRecord( $tdb->getTable() , 0 );

	//global変数の定義
	$HOME				= $tdb->getData( $trec , 'home' );
	$MAILSEND_ADDRES	= $tdb->getData( $trec , 'mail_address' );
	$MAILSEND_NAMES 	= $tdb->getData( $trec , 'mail_name' );
	$LOGIN_ID_MANAGE	= $tdb->getData( $trec , 'login_id_manage' );
	$css_name			= $tdb->getData( $trec , 'main_css' );
	$users_returnss		= $tdb->getData( $trec , 'users_returnss' );
	$ADWARES_EXCHANGE	= $tdb->getData( $trec , 'exchange_limit' );
	$ADWARES_PASS		= $tdb->getData( $trec , 'adwares_pass' );
	$ACCESS_LIMIT		= $tdb->getData( $trec , 'access_limit' );
	$PARENT_MAX_ROW		= $tdb->getData( $trec , 'parent_limit' );
	$PARENT_LIMIT_URL	= $tdb->getData( $trec , 'parent_limit_url' );

	preg_match( '/(.*?)([^¥/]+)$/' , $_SERVER[ 'SCRIPT_NAME' ] , $match );
	$path   = $match[ 1 ];
	$script = $match[ 2 ];

	// ユーザIDを特定
	switch( $LOGIN_ID_MANAGE )
	{
		case 'SESSION':

			if( $path == $_SESSION[ $SESSION_PATH_NAME ] ){
				$LOGIN_ID						= $_SESSION[ $SESSION_NAME ];
				$LOGIN_TYPE                     = $_SESSION[ $SESSION_TYPE ];
			}
			break;

		case 'COOKIE':
		default:

			if( $path == $_COOKIE[ $COOKIE_PATH_NAME ] ){
				$LOGIN_ID					  = $_COOKIE[ $COOKIE_NAME ];
				$LOGIN_TYPE                   = $_COOKIE[ $COOKIE_TYPE ];
			}
			break;
	}

	//LOGIN_IDが不正な値な場合
	if( preg_match( '/¥W/' , $LOGIN_ID ) ){
		exit('$LOGIN_ID is illegal.');
	}

	// ログインしているユーザのユーザタイプ名とその権限の取得
	$loginUserType = $NOT_LOGIN_USER_TYPE;
	$loginUserRank = $ACTIVE_ACTIVATE;
	if(  isset( $LOGIN_ID ) &&  $LOGIN_ID != '' )
	{
		for($i=0; $i<count($TABLE_NAME); $i++)
		{
			if(  $THIS_TABLE_IS_USERDATA[ $TABLE_NAME[$i] ]  )
			{
				$db		 = $gm[ $TABLE_NAME[$i] ]->getDB();
				$table	 = $db->searchTable( $db->getTable(), 'id', '=', $LOGIN_ID );
				if( $db->getRow($table) != 0 )
				{
					$rec			 = $db->getRecord( $table, 0 );
					$loginUserType	 = $TABLE_NAME[$i];
					$loginUserRank	 = $db->getData( $rec, 'activate' );
					break;
				}
			}
		}
	}

	SSLUtil::ssl_check();
