<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * add.php - 専用プログラム
	 * 成果のトラッキングを行うプログラム。
	 * 
	 * 以下の形式でアクセスされた場合、成果報酬を追加します。
	 * http://example.com/add.php?check=[check]
	 * check = 認証パス
	 * ※その他のパラメータはreadme等で確認してください。
	 * 
	 * </PRE>
	 *******************************************************************************************************/

	/*******************************************************************************************************
	 * メイン処理
	 *******************************************************************************************************/

	try
	{
		include 'custom/head_main.php';

		if( IsRequestJSTracking() ) //js版トラッキングタグの場合
		{
			header("Content-type: text/javascript");
			OutputJSTrackingSource();
		}
		else
		{
			CheckQuery();
			RequestGUID();

			$access = GetAccess();

			if( !HasPay( $access ) ) //このアクセスの成果が発生していない場合
			{
				$adwares = GetAdwares( $access );

				if( IsPassageWait( $adwares ) ) //最低待ち時間を過ぎている場合
				{
					if( MatchReceptionMode( $adwares , $access ) ) //認証モードが一致する場合
						AddSuccessReward( $adwares , $access );
				}
			}

			header("Content-type: image/gif");
			echo base64_decode('R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
		}

	}
	catch( Exception $e_ )
	{
		WriteErrorLog( $e_ );
	}

	/*******************************************************************************************************
	 * 関数
	 *******************************************************************************************************/

	//■チェック //

	/**
		@brief   クエリを検証する。
		@details クエリに不正な値が含まれる場合、例外をスローします。
	*/
	function CheckQuery()
	{
		global $ADWARES_PASS;

		ConceptCheck::IsEssential( $_GET , Array( 'check' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'check' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'aid' , 'adwares' , 'check' , 'cost' , 'eccube' , 'from' , 'from_sub' , 'guid' , 'ip' , 'sales' , 'uid' ) );
		ConceptCheck::IsScalar( $_COOKIE , Array( 'adwares_cookie' ) );

		if( $ADWARES_PASS != $_GET[ 'check' ] )
				throw new IllegalAccessException( '認証パスが正しくありません' );
	}

	/**
		@brief  アクセスに関連する成果報酬が存在するか確認する。
		@param  $access_ RecordModelオブジェクト。
		@return 成果報酬が見つかった場合はtrue。¥n
		        成果報酬が見つからない場合はfalse。
	*/
	function HasPay( $access_ )
	{
		$pays = new TableModel( 'pay' );
		$pays->search( 'access_id' , '=' , $access_->getID() );
		$row = $pays->getRow();

		return ( $row ? true : false );
	}

	/**
		@brief  広告の予算を検証する。
		@param  $adwares_ RecordModelオブジェクト。
		@return 予算が設定されていない、または余っている場合はtrue。¥n
		        予算が余っていない場合はfalse。
	*/
	function IsEnoughBudget( $adwares_ )
	{
		global $ADWARES_LIMIT_TYPE_YEN;          //成果報酬
		global $ADWARES_LIMIT_TYPE_CNT;          //クリック数
		global $ADWARES_LIMIT_TYPE_CNT_CLICK;    //クリック報酬
		global $ADWARES_LIMIT_TYPE_CNT_CONTINUE; //継続報酬

		//予算の設定を取得する
		$budgetValue = $adwares_->getData( 'limits' );
		$budgetType  = $adwares_->getData( 'limit_type' );

		//設定に応じて比較処理を振り分ける
		switch( $budgetType )
		{
			case $ADWARES_LIMIT_TYPE_YEN : //成果報酬額
				return ( $adwares_->getData( 'money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT : //クリック回数
				return ( $adwares_->getData( 'pay_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CLICK : //クリック報酬額
				return ( $adwares_->getData( 'click_money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CONTINUE : //継続報酬額
				return ( $adwares_->getData( 'continue_money_count' ) < $budgetValue );

			default : //予算上限なし
				return true;
		}
	}

	/**
		@brief  最低待ち時間を検証する。
		@param  $adwares_ RecordModelオブジェクト。
		@return 成果履歴がないか、成果報酬の最低待ち時間を経過している場合はtrue。¥n
		        成果報酬の最低待ち時間を経過していない場合はfalse。
	*/
	function IsPassageWait( $adwares_ )
	{
		//現在の経過時間を取得する
		$passageTime = GetPassageTime( $adwares_ );

		if( 0 > $passageTime ) //アクセスが見つからない場合
			return true;

		//クリック報酬の最低待ち時間を取得する
		$waitNum  = $adwares_->getData( 'pay_span' );
		$waitUnit = $adwares_->getData( 'pay_span_type' );
		$magnify  = Array( 's' => 1 , 'm' => 60 , 'h' => 60 * 60 , 'd' => 60 * 60 * 24 , 'w' => 60 * 60 * 24 * 7, );
		$waitTime = $waitNum * $magnify[ $waitUnit ];

		return ( $waitTime < $passageTime );
	}

	/**
		@brief 広告の成果認証設定が正しいか確認する。
		@param $adwares_ RecordModelオブジェクト。
		@param $access_ RecordModelオブジェクト。
	*/
	function MatchReceptionMode( $adwares_ , $access_ )
	{
		global $terminal_type;

		if( 0 < $terminal_type ) //携帯端末の場合
			return true;

		//認証設定を取得する
		$checkType = $adwares_->getData( 'check_type' );

		if( 'ip' == $checkType ) //ip認証の場合
			return ( getenv( 'REMOTE_ADDR' ) == $access_->getData( 'ipaddress' ) );
		else if( 'aid' == $checkType ) //AID認証の場合
			return ( $_GET[ 'aid' ] == $access_->getData( 'id' ) );
		else //cookie認証の場合
			return ( $_COOKIE[ 'adwares_cookie' ] == $access_->getData( 'cookie' ) );
	}

	/**
		@brief  このユーザーのアクセスを検索する。
		@param  $access_ TableModelオブジェクト。
		@return TableModelオブジェクト。
	*/
	function SearchAccess( $access_ , $adwares_ )
	{
		global $terminal_type; //端末種別

		if( 0 >= $terminal_type ) //PCからのアクセスの場合
		{
			if( 'aid' == $adwares_->getData( 'check_type' ) )
				{ $access_->search( 'ipaddress' , '=' , $_GET[ 'user_ip' ] ); }
			else
				{ $access_->search( 'ipaddress' , '=' , getenv( 'REMOTE_ADDR' ) ); }
		}
		else //携帯からのアクセスの場合
		{
			$utn = MobileUtil::getMobileID();

			if( $utn ) //個体識別番号が取得できる場合
				$access_->search( 'utn' , '=' , $utn );
			else //個体識別番号が取得できない場合
				$access_->search( 'useragent' , '=' , getenv( 'HTTP_USER_AGENT' ) );
		}

		return $access_;
	}

	//■取得 //

	/**
		@brief  アクセスレコードを取得する。
		@return RecordModelオブジェクト。
	*/
	function GetAccess()
	{
		global $terminal_type;
		global $ACCESS_LIMIT;

		//アクセスを検索する
		$access = new TableModel( 'access' );

		$andTerms = Array();
		$orTerms  = Array();

		if( 0 < $ACCESS_LIMIT ) //アクセス有効期限が設定されている場合
			$andTerms[] = Array( 'regist' , '>' , time() - $ACCESS_LIMIT );

		if( $_GET[ 'aid' ] ) //アクセスIDが指定されている場合
			$andTerms[] = Array( 'id' , '=' , $_GET[ 'aid' ] );
		else
		{

            if( $_GET[ 'adwares' ] ) //広告IDが渡されている場合
            {
                $andTerms[] = Array( 'adwares' , '=' , $_GET['adwares'] );
            }
            else if( $_GET[ 's_adwares' ] )
            {
                $andTerms[] = Array( 'adwares' , '=' , $_GET['s_adwares'] );
            }

			if( 0 >= $terminal_type ) //PC端末の場合
			{
				$orTerms[] = Array( 'ipaddress' , '=' , getenv( 'REMOTE_ADDR' ) );
				$orTerms[] = Array( 'cookie' , '=' , $_COOKIE[ 'adwares_cookie' ] );
			}
			else //携帯端末の場合
			{
				$utn = MobileUtil::GetMobileID();

				if( $utn ) //個体識別番号が取得できている場合
					$andTerms[] = Array( 'utn' , '=' , $utn );
				else //個体識別番号が取得できない場合
					$andTerms[] = Array( 'useragent' , '=' , $_SERVER[ 'HTTP_USER_AGENT' ] );
			}
		}

		//アクセスレコードを取得する
		$access->SearchOr( $orTerms );
		$access->SearchAnd( $andTerms );
		$access->sortDesc( 'regist' );
		$access->setLimitOffset( 0 , 1 );
		$row = $access->getRow();

		if( !$row ) //レコードが見つからない場合
			throw new RuntimeException( 'アクセスレコードが見つかりません' );

		$access = $access->getRecordModel( 0 );

		return $access;
	}

	/**
		@brief  広告データを取得する。
		@param  $access_ RecordModelオブジェクト。
		@return RecordModelオブジェクト。
	*/
	function GetAdwares( $access_ )
	{
		$adwType = $access_->getData( 'adwares_type' );
		$adwID   = $access_->getData( 'adwares' );

		if( $_GET[ 'adwares' ] ) //広告IDが渡されている場合
			if( $adwID != $_GET[ 'adwares' ] ) //広告IDが一致しない場合
				throw new IllegalAccessException( '広告IDが一致しません' );

		if( 'secretAdwares' == $adwType ) //クローズド広告の場合
			return new RecordModel( 'secretAdwares' , $adwID );
		else //通常の広告の場合
			return new RecordModel( 'adwares' , $adwID );
	}

	/**
		@brief  このユーザーの最終成果からの経過時間を取得する。
		@param  $adwares_ RecordModelオブジェクト。
		@return アクセスが見つかった場合は経過時間。¥n
		        アクセスが見つからなかった場合は-1。
	*/
	function GetPassageTime( $adwares_ )
	{
		//アクセスを検索する
		$access = new TableModel( 'access' );
		$access = SearchAccess( $access , $adwares_ );

		$access->search( 'adwares'   , '=' , $adwares_->getID() );
		$access->search( 'ipaddress' , '=' , $_SERVER[ 'REMOTE_ADDR' ] );
		$access->sortDesc( 'regist' );

		$aDB    = $access->getDB();
		$aTable = $access->getTable();
		$aTable = $aDB->joinTableSQL( $aTable , 'access' , 'pay' , 'pay.access_id = access.id' );
		$row    = $aDB->getRow( $aTable );

		if( $row ) //レコードが見つかった場合
		{
			$aRec   = $aDB->getRecord( $aTable , 0 );
			$regist = $aDB->getData( $aRec , 'regist' );

			$passageTime = time() - $regist;
		}
		else //レコードが見つからない場合
			{ $passageTime = -1; }

		return $passageTime;
	}

	/**
		@brief     成果報酬額を取得する。
		@exception RuntimeException 広告の報酬設定に不正な値が格納されていた場合。
		@param     $adwares_ RecordModelオブジェクト。
		@param     $nUser_   RecordModelオブジェクト。
		@param     $sales_   売上額。
		@return    報酬額。
	*/
	function GetReward( $adwares_ , $nUser_ , $sales_ )
	{
		global $ADWARES_MONEY_TYPE_YEN;
		global $ADWARES_MONEY_TYPE_PER;
		global $ADWARES_MONEY_TYPE_RANK;
		global $ADWARES_MONEY_TYPE_PERSONAL;

		$rewardPoint   = $adwares_->getData( 'money' );
		$rewardType    = $adwares_->getData( 'ad_type' );
		$autoReception = $adwares_->getData( 'auto' );
		$magni         = $nUser_->getData( 'magni' );

		switch( $rewardType ) //報酬設定に応じて振り分け
		{
			case $ADWARES_MONEY_TYPE_YEN : //固定報酬
				return (int)( round( $rewardPoint * ( $magni / 100.0 ) ) );

			case $ADWARES_MONEY_TYPE_PER : //歩合報酬
				return (int)( round( $sales_ * ( $rewardPoint / 100.0 ) * ( $magni / 100.0 ) ) );

			case $ADWARES_MONEY_TYPE_RANK : //歩合報酬(会員ランク)
				$rank = new RecordModel( 'sales' , $nUser_->getData( 'rank' ) );
				$rate  = $rank->getData( 'rate' );

				return (int)( round( $sales_ * ( $rate / 100.0 ) * ( $magni / 100.0 ) ) );

			case $ADWARES_MONEY_TYPE_PERSONAL : //歩合報酬(パーソナルレート)
				$rate  = $nUser_->getData( 'personal_rate' );

				return (int)( round( $sales_ * ( $rate / 100.0 ) * ( $magni / 100.0 ) ) );

			default : //不明な報酬設定
				throw new RuntimeException( '不明な報酬タイプが指定されています [' . $rewardType . ']' );
		}
	}

	/**
		@brief  売上額を取得する。
		@param $from_     補足情報を受け取るfromパラメータ。
		@param $from_sub_ 補足情報を受け取るfrom_subパラメータ。
		@return 売上額。
	*/
	function GetSales( &$from_ , &$from_sub_ )
	{
		$sales = 0;

		if( $_GET[ 'eccube' ] ) //EC-CUBEパラメータが指定されている場合
		{
			$count = preg_match( '/order_id=(¥d+)/' , $_GET[ 'eccube' ] , $match );

			if( $count ) //注文IDが抽出できた場合
				$from_ .= '(EC-CUBE注文番号:' . $match[ 1 ] . ')';

			$count = preg_match( '/total=([^|]+)/' , $_GET[ 'eccube' ] , $match );

			if( $count ) //合計額が抽出できた場合
			{
				$sales = $match[ 1 ];
				$sales = str_replace( ',' , '' , $sales );
			}
		}

		if( !$sales ) //売り上げが設定されていない場合
		{
			if( 0 < $_GET[ 'sales' ] ) //salesがセットされている場合
				$sales = $_GET[ 'sales' ];
			else if( 0 < $_GET[ 'cost' ] ) //costがセットされている場合
				$sales = $_GET[ 'cost' ];
		}

		return $sales;
	}

	//■処理 //

	/**
		@brief 成果報酬を追加する。
		@param $adwares_ RecordModelオブジェクト。
		@param $access_ RecordModelオブジェクト。
	*/
	function AddSuccessReward( $adwares_ , $access_ )
	{
		global $terminal_type;
		global $ACTIVE_NONE;
		global $ACTIVE_ACTIVATE;
		global $ADWARES_AUTO_ON;

		$nUser  = new RecordModel( 'nUser' , $access_->getData( 'owner' ) );
		$sales  = GetSales( $_GET[ 'from' ] , $_GET[ 'from_sub' ] );
		$reward = GetReward( $adwares_ , $nUser , $sales );

		if( 'secretAdwares' == $adwares_->getType() ) //クローズド広告の場合
		{
			$users = $adwares_->getData( 'open_user' );

			if( FALSE === strpos( $users , $nUser->getID() ) ) //公開ユーザーに含まれない場合
				return;
		}

		$pay = new FactoryModel( 'pay' );
		$pay->setData( 'access_id'    , $access_->getID() );
		$pay->setData( 'ipaddress'    , getenv( 'REMOTE_ADDR' ) );
		$pay->setData( 'cookie'       , $access_->getData( 'cookie' ) );
		$pay->setData( 'owner'        , $nUser->getID() );
		$pay->setData( 'adwares_type' , $adwares_->getType() );
		$pay->setData( 'adwares'      , $adwares_->getID() );
		$pay->setData( 'cost'         , $reward );
		$pay->setData( 'tier1_rate'   , SystemUtil::getSystemData( 'child_per' ) );
		$pay->setData( 'tier2_rate'   , SystemUtil::getSystemData( 'grandchild_per' ) );
		$pay->setData( 'tier3_rate'   , SystemUtil::getSystemData( 'greatgrandchild_per' ) );
		$pay->setData( 'sales'        , $sales );
		$pay->setData( 'froms'        , SafeString( $_GET[ 'from' ] ) );
		$pay->setData( 'froms_sub'    , SafeString( $_GET[ 'from_sub' ] ) );
		$pay->setData( 'state'        , 0 );
		$pay->setData( 'utn'          , SafeString( MobileUtil::GetMobileID() ) );
		$pay->setData( 'useragent'    , SafeString( getenv( 'HTTP_USER_AGENT' ) ) );
		$pay->setData( 'continue_uid' , SafeString( $_GET[ 'uid' ] ) );

		if( $ADWARES_AUTO_ON == $adwares_->getData( 'auto' ) ) //自動認証が有効な場合
		{
			//成果を認証状態にして配当金を追加する
			$pay->setData( 'state' , $ACTIVE_ACTIVATE );
			$pay = $pay->register();

			$payDB = $pay->getDB();
			addPay( $nUser->getID() , $reward , $payDB , $pay->getRecord() , $tier );

			//広告の予算に計上する
			$currentReward = $adwares_->getData( 'money_count' );
			$currentClick  = $adwares_->getData( 'pay_count' );

			$adwares_->setData( 'money_count' , $currentReward + $reward_ );
			$adwares_->setData( 'pay_count' , $currentClick + 1 );
			$adwares_->update();

			sendPayMail( $pay->getRecord() , 'pay' );
		}
		else
		{
			$pay = $pay->register();

			sendDisabledPayMail( $pay->getRecord() , 'pay' );
		}

		if( !IsEnoughBudget( $adwares_ ) ) //予算をオーバーした場合
		{
			$adwares_->setData( 'open' , false );
			$adwares_->update();
		}

		//会員ランク更新チェック
		updateRank( $nUser->getID() );

		// AFAD連携: ポストバック送信
		SendAFADPostback( $adwares_ , $access_ , $pay , $sales );
	}

	/**
		@brief  文字列をエスケープする。
		@param  $str_ 任意の文字列。
		@return エスケープされた文字列。
	*/
	function SafeString( $str_ )
	{
		$str = substr( $str_ , 0 , 4096 );
		$str = h( $str );

		return $str;
	}

	/**
		@brief エラーログを出力する。
		@param $e_ 例外オブジェクト。
	*/
	function WriteErrorLog( $e_ )
	{
		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );
	}

	//■AFAD連携 //

	/**
		@brief  AFADにポストバックを送信する。
		@param  $adwares_ RecordModelオブジェクト。
		@param  $access_  RecordModelオブジェクト。
		@param  $pay_     RecordModelオブジェクト。
		@param  $sales_   売上金額。
	*/
	function SendAFADPostback( $adwares_ , $access_ , $pay_ , $sales_ )
	{
		try {
			// AFAD連携が有効かチェック
			if( !$adwares_->getData( 'afad_enabled' ) ) {
				return;
			}

			// AFADセッションIDを取得
			$afadSessionId = $access_->getData( 'afad_session_id' );
			if( !$afadSessionId ) {
				return; // AFADセッションIDがない場合は送信しない
			}

			// ポストバックURLを取得
			$postbackUrl = $adwares_->getData( 'afad_postback_url' );
			if( !$postbackUrl ) {
				throw new RuntimeException( 'AFAD連携が有効ですがポストバックURLが設定されていません' );
			}

			// パラメータを構築
			$params = array();

			// gidがURLに含まれていない場合は追加
			if( strpos( $postbackUrl , 'gid=' ) === false ) {
				$gid = $adwares_->getData( 'afad_gid' );
				if( $gid ) {
					$params['gid'] = $gid;
				}
			}

			// 必須パラメータ
			$params['af'] = $afadSessionId;

			// 任意パラメータ
			if( isset( $_GET['uid'] ) && $_GET['uid'] ) {
				$params['uid'] = $_GET['uid'];
			}
			if( isset( $_GET['uid2'] ) && $_GET['uid2'] ) {
				$params['uid2'] = $_GET['uid2'];
			}
			if( $sales_ > 0 ) {
				$params['amount'] = $sales_;
			}

			// 承認ステータス（自動承認の場合は「承認待ち」）
			global $ADWARES_AUTO_ON;
			if( $ADWARES_AUTO_ON == $adwares_->getData( 'auto' ) ) {
				$params['Status'] = 1; // 承認待ち
			}

			// URLを構築
			$separator = ( strpos( $postbackUrl , '?' ) === false ) ? '?' : '&';
			$fullUrl = $postbackUrl . $separator . http_build_query( $params );

			// HTTPリクエスト送信
			$result = SendHTTPRequest( $fullUrl );

			// ログに記録
			LogAFADPostback( $pay_->getID() , $access_->getID() , $afadSessionId , $fullUrl , $result );

		} catch( Exception $e ) {
			// エラーログ出力
			$errorManager = new ErrorManager();
			$errorMessage = 'AFAD Postback Error: ' . $errorManager->GetExceptionStr( $e );
			$errorManager->OutputErrorLog( $errorMessage );

			// エラーでも処理は継続（成果記録は成功しているため）
		}
	}

	/**
		@brief  HTTPリクエストを送信する。
		@param  $url_ リクエストURL。
		@return 結果配列 ['status' => HTTPステータスコード, 'body' => レスポンスボディ, 'error' => エラーメッセージ]
	*/
	function SendHTTPRequest( $url_ )
	{
		$result = array(
			'status' => null,
			'body' => null,
			'error' => null
		);

		try {
			$ch = curl_init();

			curl_setopt( $ch , CURLOPT_URL , $url_ );
			curl_setopt( $ch , CURLOPT_RETURNTRANSFER , true );
			curl_setopt( $ch , CURLOPT_TIMEOUT , 10 );
			curl_setopt( $ch , CURLOPT_CONNECTTIMEOUT , 5 );
			curl_setopt( $ch , CURLOPT_FOLLOWLOCATION , true );
			curl_setopt( $ch , CURLOPT_SSL_VERIFYPEER , true );
			curl_setopt( $ch , CURLOPT_SSL_VERIFYHOST , 2 );

			$response = curl_exec( $ch );
			$httpCode = curl_getinfo( $ch , CURLINFO_HTTP_CODE );
			$error = curl_error( $ch );

			curl_close( $ch );

			$result['status'] = $httpCode;
			$result['body'] = $response;

			if( $error ) {
				$result['error'] = $error;
			}

		} catch( Exception $e ) {
			$result['error'] = $e->getMessage();
		}

		return $result;
	}

	/**
		@brief  AFADポストバック送信ログを記録する。
		@param  $payId_          成果ID。
		@param  $accessId_       アクセスID。
		@param  $afadSessionId_  AFADセッションID。
		@param  $postbackUrl_    送信したURL。
		@param  $result_         SendHTTPRequest()の結果。
	*/
	function LogAFADPostback( $payId_ , $accessId_ , $afadSessionId_ , $postbackUrl_ , $result_ )
	{
		try {
			$db = GMlist::getDB( 'afad_postback_log' );
			$rec = $db->getNewRecord();

			$db->setData( $rec , 'id' , md5( time() . $payId_ . rand() ) );
			$db->setData( $rec , 'pay_id' , $payId_ );
			$db->setData( $rec , 'access_id' , $accessId_ );
			$db->setData( $rec , 'afad_session_id' , $afadSessionId_ );
			$db->setData( $rec , 'postback_url' , $postbackUrl_ );
			$db->setData( $rec , 'http_status' , $result_['status'] );
			$db->setData( $rec , 'response_body' , substr( $result_['body'] , 0 , 1000 ) );
			$db->setData( $rec , 'error_message' , $result_['error'] );
			$db->setData( $rec , 'sent_at' , time() );
			$db->setData( $rec , 'retry_count' , 0 );

			$db->addRecord( $rec );

		} catch( Exception $e ) {
			// ログ記録失敗は無視（成果送信は完了しているため）
		}
	}

	//■リダイレクト //

	/**
		@brief   uid付きURLへリダイレクトする。
		@details DoCoMo端末から個体識別番号を取得するため、クエリにuidが存在しない場合はuidを付加してリダイレクトします。
	*/
	function RequestGUID()
	{
		global $terminal_type;

		if( MobileUtil::$TYPE_NUM_DOCOMO != $terminal_type ) //DoCoMo端末でない場合
			return;

		if( 'on' == $_GET[ 'guid' ] ) //既にguidパラメータがセットされている場合
			return;

		//GETパラメータを引き継ぐために文字列化する
		$paramStr = '';

		foreach( Array( 'aid' , 'adwares' , 'check' , 'cost' , 'eccube' , 'from' , 'from_sub' , 'sales' , 'uid' ) as $key )
		{
			if( array_key_exists( $key , $_GET ) )
				$paramStr .= '&' . $key . '=' . $_GET[ $key ];
		}

		//リダイレクト
		header( 'Location: add.php?guid=on' . $paramStr );
		exit();
	}

	//■JS版処理

	/**
		@brief  このリクエストがjsトラッキングタグの要求かどうか確認する。
		@return r=doneがある場合はtrue、それ以外はfalse。
	*/
	function IsRequestJSTracking() //
	{
		if( !isset( $_GET[ 'r' ] ) )
			{ return false; }

		return 'done' == $_GET[ 'r' ];
	}

	/**
		@brief jsトラッキングのソースを出力する。
	*/
	function OutputJSTrackingSource() //
	{
		global $HOME;

		$source = <<<'JS_TRACKING_CODE'

			var $cookie = document.cookie;
			var $params = {};

			$cookie.split( '; ' ).forEach( function( $item ){
				var $set   = $item.split( '=' );
				var $key   = decodeURIComponent( $set[ 0 ] );

				if( 'afl_tracking_aid' == $key )
				{
					var $value = decodeURIComponent( $set[ 1 ] );

					$params[ $key ] = $value;
				}
			} );

			var $image = new Image();

			if( $params[ 'afl_tracking_aid' ] )
				{ $image.src = '<?= $HOME ?>add.php?aid=' + $params[ 'afl_tracking_aid' ] + '&<?= $GETS ?>'; }
			else
				{ sendMessage( '<?= $HOME ?>messaging.html' , 'afl_tracking_aid' , function( $data ){ $image.src = '<?= $HOME ?>add.php?aid=' + $data + '&<?= $GETS ?>'; } ); }

JS_TRACKING_CODE;

		$source = '(function(){' . file_get_contents( 'js/messaging.js' ) . "¥n" . $source . '})();';

		$query = $_GET;
		unset( $query[ 'r' ] );

		$source = str_replace( '<?= $HOME ?>' , $HOME , $source );
		$source = str_replace( '<?= $GETS ?>' , http_build_query( $query ) , $source );

		print $source;
	}
?>
