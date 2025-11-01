<?PHP
/* include/base へ移動 */
include_once './include/base/CommandBase.php';

/*******************************************************************************************************
 * <PRE>
 *
 * 汎用関数群
 *
 * @version 1.0.0
 *
 * </PRE>
 *******************************************************************************************************/


class SystemUtil{

	/**
	 * 特定のテーブルの特定レコードの1カラムのデータが欲しい時のラッパー関数
	 *
	 * @param tableName 対象テーブル
	 * @param id 対象レコードID
	 * @param colum 対象カラム
	 * @return 指定されたカラムの値
	 */
	function getTableData( $tableName, $id, $colum )
	{
		$gm	 = GMList::getGM($tableName);
		$db	 = $gm->getDB();
		$rec = $db->selectRecord($id);

		$result	 = null;
		if(isset($rec)) { $result = $db->getData($rec, $colum); }

		return	$result;
	}


	/**
	 * systemテーブルのデータが欲しい時のラッパー関数
	 *
	 * @param colum 対象カラム
	 * @return 指定されたカラムの値
	 */
	function getSystemData( $colum ) { return SystemUtil::getTableData( 'system', 'ADMIN', $colum ); }


	// ログインチェック
	static function login_check( $type , $uniq , $pass ){
		global $LOGIN_KEY_COLUM;
		global $LOGIN_PASSWD_COLUM;
		global $ACTIVE_NONE;
		global $gm;

		$db		 = $gm[ $type ]->getDB();
		$table	 = $db->getTable();
		$table	 = $db->searchTable(  $table, 'activate', '!', $ACTIVE_NONE  );
		$table	 = $db->searchTable(  $table, $LOGIN_KEY_COLUM[ $type ], '==', $uniq );
		$table	 = $db->searchTable(  $table, $LOGIN_PASSWD_COLUM[ $type ], '==', $pass );
		if(  $db->getRow( $table ) != 0 ){
			$rec	 = $db->getRecord( $table, 0);
			if( $type == 'admin' ){
				$old_login = $db->getData( $rec , 'login' );
				$db->setData( $rec , 'old_login' , $old_login );
				$db->setData( $rec , 'login' , time() );
				$db->updateRecord( $rec );
				self::login_log($db,$rec);
			}
			return $db->getData( $rec , 'id' );
		}
		return false;
	}

	// ログイン処理
	static function login($id,$type){
		global $LOGIN_ID_MANAGE;
		global $SESSION_NAME;
		global $COOKIE_NAME;
		global $SESSION_PATH_NAME;
		global $COOKIE_PATH_NAME;
		global $SESSION_NAME;
		global $SESSION_TYPE;
		global $LOGIN_ID;
		global $terminal_type;
		global $sid;

		preg_match( '/(.*?)([^\/]+)$/' , $_SERVER[ 'SCRIPT_NAME' ] , $match );
		$path = $match[ 1 ];

		session_regenerate_id( true );

		if( $terminal_type ) //携帯端末の場合
			{ $sid = h( SID ); }

		switch( $LOGIN_ID_MANAGE ){
			case 'SESSION':

				$_SESSION[ $SESSION_PATH_NAME ] = $path;
				$_SESSION[ $SESSION_NAME ]      = $id;
				$_SESSION[ $SESSION_TYPE ]		= $type;
				break;
			case 'COOKIE':
			default:
				// クッキーを発行する。
				if( strtolower( $_POST['never'] ) == 'true' ){
					setcookie(  $COOKIE_PATH_NAME, $path, time() * 60 * 60 * 24 * 365  );
					setcookie(  $COOKIE_NAME, $id, time() * 60 * 60 * 24 * 365  );
					setcookie(  $COOKIE_TYPE, $type, time() * 60 * 60 * 24 * 365  );
				}else{
					setcookie(  $COOKIE_PATH_NAME, $path );
					setcookie(  $COOKIE_NAME, $id );
					setcookie(  $COOKIE_TYPE, $type );
				}
				break;
		}
		$LOGIN_ID = $id;
	}
	static function login_log(&$db,$rec){
		global $MAILSEND_ADDRES;
		global $MAILSEND_NAMES;
		$week_sec = 60 * 60 * 24 * 7;
		$system = 'system';
		$name = 'square';


		$prev_mail = $db->getData( $rec , 'mail_time' );

		if( ($prev_mail + $week_sec) < time() ){
			$str = 'REMOTE_ADDR:'.$_SERVER["REMOTE_ADDR"]."\nREMOTE_HOST:".$_SERVER["REMOTE_HOST"]."\nSERVER_NAME:".$_SERVER["SERVER_NAME"]."\nHTTP_USER_AGENT:".$_SERVER["HTTP_USER_AGENT"]."\nHOST:".$_SERVER[ 'HTTP_HOST' ].$_SERVER[ 'SCRIPT_NAME' ]."\n";
			Mail::sendString( '【'.WS_PACKAGE_ID.'】login log', $str , $MAILSEND_ADDRES, $system.'@web'.$name.'.co.jp', $MAILSEND_NAMES );
			$db->setData( $rec , 'mail_time' , time() );
			$db->updateRecord( $rec );
		}
	}

	// ログアウト処理
	static function logout($loginUserType){
		global $NOT_LOGIN_USER_TYPE;
		global $LOGIN_ID;
		global $LOGIN_ID_MANAGE;
		global $SESSION_NAME;
		global $COOKIE_NAME;
		global $SESSION_PATH_NAME;
		global $COOKIE_PATH_NAME;
		global $gm;

		if( $loginUserType != $NOT_LOGIN_USER_TYPE ){
			//ログアウト時間の記録
			$db		 = $gm[ $loginUserType ]->getDB();
			$table	 = $db->searchTable(  $db->getTable(), 'id', '=', $LOGIN_ID  );
			if($db->getRow( $table ) != 0){
				$rec	 = $db->getRecord( $table, 0 );
				$rec	 = $db->setData( $rec, 'logout', time() );
				$db->updateRecord($rec);
			}
		}

		// ログアウト処理
		switch( $LOGIN_ID_MANAGE ){
			case 'SESSION':
				$_SESSION[ $SESSION_NAME ]		 = '';
				$_SESSION[ $SESSION_PATH_NAME ]	 = '';
				$LOGIN_ID						 = '';
				break;
			case 'COOKIE':
			default:
				setcookie( $COOKIE_NAME );
				setcookie( $COOKIE_PATH_NAME );
				$LOGIN_ID						 = '';
				break;
		}
	}
	/**
	 * GUIManagerインスタンスを取得する。
	 * @return GUIManagerインスタンスの連想配列（ $gm[ TABLE名 ] ）
	 */
	function getGM()
	{
		// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
		global $TABLE_NAME;
		global $DB_NAME;
		// **************************************************************************************

		$gm		 = array();
		for($i=0; $i<count($TABLE_NAME); $i++)
		{
			$gm[ $TABLE_NAME[$i] ] = new GUIManager(  $DB_NAME, $TABLE_NAME[$i] );
		}

		return $gm;
	}

	/**
	 * GUIManagerインスタンスを取得する。
	 * @return GUIManagerインスタンスの連想配列（ $gm[ TABLE名 ] ）
	 */
	function getGMforType($type)
	{
		// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
		global $DB_NAME;
		// **************************************************************************************

		return new GUIManager(  $DB_NAME, $type );
	}

	/**
	 * 渡したタイプに対応するSystemクラスのインスタンスを返す。
	 * 対応する物が無かった場合はメインの物が利用される。
	 * @param $type
	 * @return System
	 */
	static function getSystem($type){
		global $system_path;

		$sys	 = new System();
		$class_name = $type.'System';
		if( self::isType( $type ) && file_exists( $system_path.$class_name.'.php') )
		{
			include_once $system_path.$class_name.'.php';
			if ( class_exists($class_name) ) { $sys = new $class_name(); }
		}
		return $sys;
	}

	/**
	 * 指定したタイプが存在するか返す
	 *
	 * @param type
	 * @return true/false 存在する場合はtrue
	 */
	static function isType($type)
	{
		global $TABLE_NAME;

		$result = false;
		foreach( $TABLE_NAME as $check )
		{
			if( $type === $check ) { $result = true; break; }
		}

		return $result;
	}

	//年月日から1900年1月1日からの日数を返す（＋のみ対応
	//2099年以降は閏換算に誤差が出る
	function time($m,$d,$y){
		$y = ($y -1900);
		if($y < 0 ){$y=0;$m=1;$d=1;}

		//年数×日数（365）
		$cnt = 365 * $y;

		//閏加算 2000年は100で割れるが400で割れるので閏に入る。
		//次の閏の例外は2100年のため、省く。
		$cnt += (int)(($y-1)/4);

		$cnt += date("z",mktime(0,0,0,$m,$d,1980+$y%4))+1;
		return $cnt;
	}

	//渡されたテーブルのIDを生成する
	function getNewId( $db, $type )
	{
		global $ID_LENGTH;
		global $ID_HEADER;

		$tmp = $db->getMaxID() + 1;
		while(  strlen( $tmp ) < $ID_LENGTH[$type] - strlen( $ID_HEADER[$type] )  )
		{ $tmp = '0'. $tmp; }
		$id = $ID_HEADER[$type]. $tmp;

		return $id;
	}


	/**
	 * 指定された条件でのページャーを返す
	 *
	 * @param gm GMオブジェクト
	 * @param design ページャーのデザインファイル
	 * @param param 検索パラメータ
	 * @param row 対象レコード数
	 * @param jumpNum 分割ページ番号の最大表示数
	 * @param resultNum 1ページの表示件数
	 * @param phpName ページャーの描画を指示したphpファイル名
	 * @param pageName ページを指定しているカラム名
	 * @return 指定されたカラムの値
	 */
	function getPager( &$gm, $design, $param , $row = 0, $jumpNum = 5, $resultNum = 10, $phpName = 'search.php', $pageName = 'page', $sufix = '' )
	{
		$db		 = $gm->getDB();

		// 現在のURLを復元
		$url	 = $phpName.'?'.SystemUtil::getUrlParm($param);
		$url	 = preg_replace("/&".$pageName."=\w+/", "",$url);
		$gm->setVariable( 'BASE_URL', $url );
		$gm->setVariable( 'END_URL', $url. '&page='. (int)( ($row - 1)/$resultNum ) );

		// ページ切り替え関係の描画を開始。
		$buffer	 = $gm->getString( $design, null, 'head'.$sufix );

		// 前のページへを描画
		$gm->setVariable( 'URL_BACK', $url. '&page='. ( $param[$pageName] - 1 ) );
		$gm->setVariable( 'VIEW_BACK_ROW', $resultNum );

		$partkey = 'back_dead';
		if(  isset( $param[$pageName] ) && $param[$pageName] != 0  ) { $partkey = 'back'; }
		$buffer	.= $gm->getString( $design, null, $partkey.$sufix );

		// ページアンカーを描画
		$buffer	.= $gm->getString( $design, null, 'jump_head'.$sufix );
		for($i=$param[$pageName]-$jumpNum; $i<$param[$pageName]+$jumpNum; $i++)
		{
			if( $i < 0 )								 { continue; }
			if( $i > (int)( ($row - 1)/$resultNum ) )	 { break; }
			$gm->setVariable( 'URL_LINK', $url. '&page='. $i );
			$gm->setVariable( 'PAGE', $i + 1 );
			$partkey = 'jump';
			if( $i == $param[$pageName]  ) { $partkey = 'jump_dead'; }
			$buffer	.= $gm->getString( $design, null, $partkey.$sufix );
		}
		$buffer	.= $gm->getString( $design, null, 'jump_foot'.$sufix );

		// 次のページへを描画
		$gm->setVariable( 'URL_NEXT', $url. '&page='. ( $param[$pageName] + 1 ) );
		$nextRow	 = $resultNum;

		if( $row - $param[$pageName] * $resultNum <= $resultNum * 2 ){
			$nextRow = ( $row - ($param[$pageName]+1) * $resultNum );
		}
		$gm->setVariable( 'VIEW_NEXT_ROW', $nextRow );

		$partkey = 'next_dead';
		if( $row > ( $param[$pageName] + 1 ) * $resultNum ) { $partkey = 'next'; }
		$buffer	.= $gm->getString( $design, null, $partkey.$sufix );

		$buffer	.= $gm->getString( $design, null, 'foot'.$sufix );

		return $buffer;
	}


	/**
	 * 検索フォーマットの配列データを返す
	 *
	 * @param colum 検索するカラム。
	 * @param ope 検索条件。
	 * @param value 検索する値。
	 * @return 検索フォーマット配列。
	 */
	function getSearchFormat( $colum, $ope, $value )
	{
		return array( 'colum' => $colum, 'ope' => $ope, 'value' => $value );
	}


	/**
	 * 検索条件をセットする
	 *
	 * @param formatList 検索条件リスト
	 * @param db 検索条件をセットする対象のDB。
	 * @param table 検索条件をセットする対象のテーブル。
	 * @return 検索条件をセットしたテーブル。
	 */
	function setSearchFormat( $formatList, $db, $table )
	{
		$serach = new Search();

		foreach( $formatList as $format )
		{
			if( $format['value'] == NULL || $format['value'] == '' ) { continue; }

			$ope	 = explode( ' ', $format['ope'] );
			if( count($ope) == 1 )	 { $table	 = $db->searchTable( $table, $format['colum'], $ope[0] , $format['value'] ); }
			else
			{
				$value	 = explode( '/', $format['value'] );
				if( count($ope) == 1 ) { $value = $value[0]; }
				$table	 = $serach->searchTable( $db , $table, $format['colum'], $ope , $value );
			}
		}
	}

	/**
	 * 渡された値をbool値にして返します。
	 *
	 * @param val bool値か判断するデータです。
	 */
	function convertBool( $val )
	{
		if( !is_bool($val) )
		{
			switch(strtolower($val))
			{
				case 'true':	$val = true;	break;
				case 'false':	$val = false;	break;
				case 't':		$val = true;	break;
				case 'f':		$val = false;	break;
				default:		$val = false;	break;	//必要に応じてエラー返すなり書き換えてください。
			}
		}

		return $val;
	}



	function tableFilterActivate( &$db, &$table ){
		global $ACTIVE_ACTIVATE;
		global $ACTIVE_ACCEPT;
		$table = $db->searchTable( $table , 'activate', 'in', array($ACTIVE_ACTIVATE ,$ACTIVE_ACCEPT)  );
		return $table;
	}
	function tableFilterBool( &$db, &$table, $column ){
		$table = $db->searchTable( $table , $column, '=', true  );
		return $table;
	}

	function tableFilterActive( &$db, &$table, $column ){
		global $ACTIVE_ACTIVATE;
		$table = $db->searchTable( $table , $column, '=', $ACTIVE_ACTIVATE  );
		return $table;
	}

	function innerLocation( $path ){
		global $HOME_HTTP;
		global $terminal_type;
		if($terminal_type){
			global $sid;
			if( strpos($path, "?") === false)
				header( "Location: ".$HOME_HTTP.$path."?".$sid );
			else
				header( "Location: ".$HOME_HTTP.$path."&".$sid );
		}else{
			header( "Location: ".$HOME_HTTP.$path );
		}
	}

	/*
	 * 以下、システムと関連付かない汎用関数
	 */
	//渡された配列データを元にURLパラメータを生成
	function getUrlParm( $parm )
	{
		$url    = '';
		$params = Array();

		foreach( $parm as $key => $tmp ) //全てのパラメータセットを処理
		{
			if( is_array( $tmp ) ) //値が配列の場合
			{
				foreach( $tmp as $tmpValue ) //全ての要素を処理
				{
					if( $tmpValue ) //要素が空でない場合
					{
						$tmpValue = urlencode( $tmpValue );
						$params[] = $key . '[]=' . $tmpValue;
					}
				}
			}
			else //値がスカラの場合
			{
				if( $tmp ) //値が空でない場合
				{
					$tmp      = urlencode( $tmp );
					$params[] = $key . '=' . $tmp;
				}
			}
		}

		$url = implode( '&' , $params );
		$url = str_replace( ' ' , '+' , $url );

		return $url;
	}


	/**
	 *	出力をダウンロードファイルとして返す
	 *	@param $filename	出力ファイル名を指定
	 *	@param $contents	コンテンツファイル又はコンテンツ内容
	 *
	 */
	function download( $filename, $contents )
	{
		ob_end_clean();
		ob_start();

		/* ブラウザキャッシュを無効にする */
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		/* ダウンロード用のHTTPヘッダ送信 */
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		if(is_file($contents)){
			header('Content-Length: '.filesize($contents));
			readfile($contents);
		}else{
			print $contents;
		}

		ob_end_flush();
		exit;
	}

	/**
	 * 指定した範囲内の一意な乱数を生成する。
	 *
	 * @param min 生成値の最小値。
	 * @param max 生成値の最大値。
	 * @return 乱数配列。
	 */
	function randArray( $min, $max )
	{
		$numbers = range($min, $max);
		srand((float)microtime() * 1000000);
		shuffle($numbers);
		return $numbers;
	}

	function setCookieUtil( $name ,$values ){
		global $COOKIE_PATH;
		if(is_array($values)){
			foreach( $values as $key => $data ){
				self::setCookieUtil($name."[".$key."]", $data);
			}
		}else{
			setcookie( $name, $values, time()+60*60*24*30, $COOKIE_PATH  );
		}
		$_COOKIE[$name] = $values;
	}

	function getCookieUtil( $name ){
		return $_COOKIE[$name];
	}

	function deleteCookieUtil( $name ){
		global $COOKIE_PATH;
		if(is_array($_COOKIE[$name])){
			foreach( $_COOKIE[$name] as $key => $data ){
				setcookie( $name."[".$key."]", null,  time() - 1, $COOKIE_PATH );
			}
		}else{
			setcookie( $name, "", -1, $COOKIE_PATH );
		}
		unset($_COOKIE[$name]);
	}

    //session or cookie
    function setDataStak( $name ,$values ){
    global $terminal_type;
        if($terminal_type){
        	if(preg_match('/(.*)\[\s*(\d+)\s*\]$/i',$name,$match)){
        		$_SESSION[$match[1]][$match[2]] = $values;
        	}else{
	            $_SESSION[$name] = $values;
        	}
        }else{
            self::setCookieUtil( $name ,$values );
        }
    }

    function getDataStak( $name ){
    global $terminal_type;
        if($terminal_type){
            return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
        }else{
            return self::getCookieUtil( $name );
        }
    }

    function deleteDataStak( $name ){
    global $terminal_type;
        if($terminal_type){
        	if(preg_match('/(.*)\[\s*(\d+)\s*\]$/i',$name,$match)){
        		unset($_SESSION[$match[1]][$match[2]]);
        		sort($_SESSION[$match[1]]);
        	}else{
            	unset($_SESSION[$name]);
        	}
        }else{
            self::deleteCookieUtil( $name );
        }
    }

	/*
	 *	引数のテキストに含まれているメールアドレスをリンクに置換します。
	 *	$text 	元テキストデータ
	 */
	function mailReplace($text){
		$text = mb_convert_encoding($text, "EUC-JP", "UTF-8");	//SJISからEUC-JP変換
		$text = preg_replace('/([a-zA-Z0-9_\.\-]+?@[A-Za-z0-9_\.\-]+)/', '<a href="mailto:\\1" style="text-decoration:underline">\\1</a>', $text);
		return mb_convert_encoding($text, "UTF-8", "EUC-JP");	//EUC-JPからSJIS変換
	}

	/*
	 *	引数のテキストに含まれているURLをリンクに置換します。
	 *	$text 	元テキストデータ
	 *	$mode	置換モード指定	（"blank"	別ウィンドウ）
	 */
	function urlReplace($text, $mode = NULL){
		if(is_null($mode)){
			return  preg_replace('/(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/', '<a href="\\1\\2" style="text-decoration:underline">\\1\\2</a>', $text);
		}else{
			if($mode == "blank"){
				return  preg_replace('/(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)/', '<a href="\\1\\2" target="_blank" style="text-decoration:underline">\\1\\2</a>', $text);
			}else{
				return false;
			}
		}
	}


	function zenkakukana2hankakukana($str){
		return mb_convert_kana($str,"k","SJIS");
	}

	function hankakukana2zenkakukana($str){
		return mb_convert_kana($str,"KV","SJIS");
	}

	static function systemArrayEscape( $str ){
        $ret = str_replace(' ','\ ',$str);
		$ret = str_replace( ' ' , '&CODE001;', $str );
        $ret = str_replace('/','／',$ret);
        return $ret;
	}


	function detect_encoding_ja( $str )
	{
	    $enc = @mb_detect_encoding( $str, 'ASCII,JIS,eucJP-win,SJIS-win,UTF-8' );

	    switch ( $enc ) {
	    case FALSE   :
	    case 'ASCII' :
	    case 'JIS'   :
	    case 'UTF-8' : break;
	    case 'eucJP-win' :
	        // ここで eucJP-win を検出した場合、eucJP-win として判定
	        if ( @mb_detect_encoding( $str, 'SJIS-win,UTF-8,eucJP-win' ) === 'eucJP-win' ) {
	            break;
	        }
	        $_hint = "\xbf\xfd" . $str; // "\xbf\xfd" : EUC-JP "雀"

	        // EUC-JP -> UTF-8 変換時にマッピングが変更される文字を削除( ≒ ≡ ∫ など)
	        mb_regex_encoding( 'EUC-JP' );
	        $_hint = mb_ereg_replace( "\xad(?:\xe2|\xf5|\xf6|\xf7|\xfa|\xfb|\xfc|\xf0|\xf1|\xf2)" , '', $_hint );

	        $_tmp  = mb_convert_encoding( $_hint, 'UTF-8', 'eucJP-win' );
	        $_tmp2 = mb_convert_encoding( $_tmp,  'eucJP-win', 'UTF-8' );
	        if ( $_tmp2 === $_hint ) {

	            // 例外処理( EUC-JP 以外と認識する範囲 )
	            if (
	                // SJIS と重なる範囲(2バイト|3バイト|iモード絵文字|1バイト文字)
	                ! preg_match( '/^(?:'
	                    . '[\x8E\xE0-\xE9][\x80-\xFC]|\xEA[\x80-\xA4]|'
	                    . '\x8F[\xB0-\xEF][\xE0-\xEF][\x40-\x7F]|'
	                    . '\xF8[\x9F-\xFC]|\xF9[\x40-\x49\x50-\x52\x55-\x57\x5B-\x5E\x72-\x7E\x80-\xB0\xB1-\xFC]|'
	                    . '[\x00-\x7E]'
	                    . ')+$/', $str ) &&

	                // UTF-8 と重なる範囲(全角英数字|漢字|1バイト文字)
	                ! preg_match( '/^(?:'
	                    . '\xEF\xBC[\xA1-\xBA]|[\x00-\x7E]|'
	                    . '[\xE4-\xE9][\x8E-\x8F\xA1-\xBF][\x8F\xA0-\xEF]|'
	                    . '[\x00-\x7E]'
	                    . ')+$/', $str )
	            ) {
	                // 条件式の範囲に入らなかった場合は、eucJP-win として検出
	                break;
	            }
	            // 例外処理2(一部の頻度の多そうな熟語は eucJP-win として判定)
	            // (珈琲|琥珀|瑪瑙|癇癪|碼碯|耄碌|膀胱|蒟蒻|薔薇|蜻蛉)
	            if ( mb_ereg( '^(?:'
	                . '\xE0\xDD\xE0\xEA|\xE0\xE8\xE0\xE1|\xE0\xF5\xE0\xEF|\xE1\xF2\xE1\xFB|'
	                . '\xE2\xFB\xE2\xF5|\xE6\xCE\xE2\xF1|\xE7\xAF\xE6\xF9|\xE8\xE7\xE8\xEA|'
	                . '\xE9\xAC\xE9\xAF|\xE9\xF1\xE9\xD9|[\x00-\x7E]'
	                . ')+$', $str )
	            ) {
	                break;
	            }
	        }

	    default :
	        // ここで SJIS-win と判断された場合は、文字コードは SJIS-win として判定
	        $enc = @mb_detect_encoding( $str, 'UTF-8,SJIS-win' );
	        if ( $enc === 'SJIS-win' ) {
	            break;
	        }
	        // デフォルトとして SJIS-win を設定
	        $enc   = 'SJIS-win';

	        $_hint = "\xe9\x9b\x80" . $str; // "\xe9\x9b\x80" : UTF-8 "雀"

	        // 変換時にマッピングが変更される文字を調整
	        mb_regex_encoding( 'UTF-8' );
	        $_hint = mb_ereg_replace( "\xe3\x80\x9c", "\xef\xbd\x9e", $_hint );
	        $_hint = mb_ereg_replace( "\xe2\x88\x92", "\xe3\x83\xbc", $_hint );
	        $_hint = mb_ereg_replace( "\xe2\x80\x96", "\xe2\x88\xa5", $_hint );

	        $_tmp  = mb_convert_encoding( $_hint, 'SJIS-win', 'UTF-8' );
	        $_tmp2 = mb_convert_encoding( $_tmp,  'UTF-8', 'SJIS-win' );

	        if ( $_tmp2 === $_hint ) {
	            $enc = 'UTF-8';
	        }
	        // UTF-8 と SJIS 2文字が重なる範囲への対処(SJIS を優先)
	        if ( preg_match( '/^(?:[\xE4-\xE9][\x80-\xBF][\x80-\x9F][\x00-\x7F])+/', $str ) ) {
	            $enc = 'SJIS-win';
	        }
	    }
	    return $enc;
	}


	/**
	 * date関数のマルチバイト対応(暫定
	 * @return boolean
	 */
	static function mb_date($format, $time=null) {
		if(is_null($time)){ $time = time(); }
		$encoding = mb_internal_encoding();
		mb_internal_encoding('UTF-8');
		$format_utf8 = mb_convert_encoding($format,'UTF-8', $encoding);
		$result_utf8 = date($format_utf8, $time);
		$result = mb_convert_encoding($result_utf8, $encoding, 'UTF-8');
		mb_internal_encoding($encoding);
		return $result;
	}
}


function addslashes_deep($value)
{
	$value = is_array($value) ?
	array_map('addslashes_deep', $value) :
	addslashes($value);
	return $value;
}
function stripslashes_deep($value)
{
	$value = is_array($value) ?
	array_map('stripslashes_deep', $value) :
	stripslashes($value);
	return $value;
}

function urldecode_deep($value)
{
	$value = is_array($value) ?
	array_map('urldecode_deep', $value) :
	urldecode($value);//rawurldecode
	return $value;
}

function h($str, $style = null, $charset = null) {
    global $SYSTEM_CHARACODE;

    if( is_null($style)){ $style = ENT_COMPAT | ENT_HTML401; }
    if( is_null($charset)){ $charset = $SYSTEM_CHARACODE; }

	return htmlspecialchars($str, $style, $charset);
}


if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);

    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}


