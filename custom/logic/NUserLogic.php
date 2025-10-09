<?php

	/**
		@brief   NUserテーブルの処理セット。
		@ingroup SystemAPI
	*/
	class NUserLogic
	{
		//■処理

		/**
			@brief     ユーザーにアクティベートメールを送信する。
			@exception InvalidArgumentException $iUserRec_ に無効な値を指定した場合。
			@exception FileIOException          メールテンプレートが見つからない場合。
			@param[in] $iUserRec_ ユーザーのレコードデータ。
		*/
		static function SendActivateMail( $iUserRec_ )
		{
			if( !$iUserRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iUserRec_ は無効です' ); }

			global $ACTIVE_NONE;
			global $ACTIVE_ACTIVATE;
			global $ACTIVE_ACCEPT;
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;
			global $template_path;
			global $mobile_path;

			$gm          = SystemUtil::getGMforType( self::$Type );
			$db          = $gm->getDB();
			$activate    = $db->getData( $iUserRec_ , 'activate' );
			$isMobile    = $db->getData( $iUserRec_ , 'is_mobile' );
			$mailAddress = $db->getData( $iUserRec_ , 'mail' );

			switch( $activate ) //アクティベートレベルで分岐
			{
				case $ACTIVE_NONE : //未認証
				{
					$label = 'ACTIVATE_MAIL';

					break;
				}

				case $ACTIVE_ACTIVATE : //仮認証
				{
					$label = 'ACTIVATE_COMP_MAIL';

					break;
				}

				case $ACTIVE_ACCEPT : //認証
				{
					$label = 'REGIST_COMP_MAIL';

					break;
				}

				default : //その他
					{ return; }
			}

			if( $isMobile ) //登録時の端末が携帯の場合
			{
				$currentPath   = $template_path;
				$template_path = $mobile_path;
				$template      = Template::getTemplate( '' , 1 , self::$Type , $label );
				$template_path = $currentPath;
			}
			else //その他の端末の場合
				{ $template = Template::getTemplate( '' , 1 , self::$Type , $label ); }

			if( !$template ) //テンプレートが見つからない場合
				{ throw new FileIOException( 'テンプレートファイルが見つかりません[' . self::$Type . '][' . $label . ']' ); }

			Mail::send( $template , $MAILSEND_ADDRES , $mailAddress , $gm , $iUserRec_ , $MAILSEND_NAMES );

			if( $isMobile ) //登録時の端末が携帯の場合
			{
				$template = Template::getTemplate( '' , 1 , self::$Type , $label );

				if( !$template ) //テンプレートが見つからない場合
					{ throw new FileIOException( 'テンプレートファイルが見つかりません[' . self::$Type . '][' . $label . ']' ); }
			}

			Mail::send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm , $iUserRec_ , $MAILSEND_NAMES );
		}

		/**
			@brief         簡易アップデートを実行する。
			@details       詳細ページや検索結果一覧からの簡易アップデートを処理します。
			@exception     InvalidArgumentException $ioRec_ に無効な値を指定した、または $iQuery_ にスカラを指定した場合。
			@param[in,out] $ioRec_  レコードデータ。
			@param[in]     $iQuery_ 適用するクエリパラメータ配列。
		*/
		static function QuickUpdate( &$ioRec_ , &$iQuery_ )
		{
			global $ACTIVE_ACCEPT;

			if( !$ioRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $ioRec_ は無効です' ); }

			if( !is_array( $iQuery_ ) ) //配列でない場合
				{ throw new InvalidArgumentException( '引数 $iQuery_ は無効です' ); }

			$db       = SystemUtil::getGMforType( self::$Type )->getDB();
			$activate = $db->getData( $ioRec_ , 'activate' );
			$update   = false;

			foreach( $db->colName as $column ) //全てのカラムを処理
			{
				if( isset( $iQuery_[ $column ] ) ) //クエリが存在する場合
				{
					$db->setData( $ioRec_ , $column , $iQuery_[ $column ] );

					$update = true;
				}
			}

			if( $update ) //変更されたカラムがある場合
			{
				$db->updateRecord( $ioRec_ );

				if( $ACTIVE_ACCEPT == $iQuery_[ 'activate' ] && $ACTIVE_ACCEPT > $activate ) //手動で認証にした場合
					{ self::SendActivateMail( $ioRec_ ); }

				//会員ランク更新チェック
				$id = $db->getData( $ioRrec_ , 'id' );

				updateRank( $id );
			}
		}

		//■データ変更

		/**
			@brief         レコードの端末情報を代入する。
			@exception     InvalidArgumentException $ioRec_ に無効な値を指定した場合。
			@param[in,out] $ioRec_ レコードデータ。
		*/
		static function SetClientTerminalType( &$ioRec_ )
		{
			global $terminal_type;

			if( !$ioRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $ioRec_ は無効です' ); }

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			if( 0 < $terminal_type ) //携帯端末の場合
				{ $db->setData( $ioRec_ , 'is_mobile' , true ); }
			else //その他の端末の場合
				{ $db->setData( $ioRec_ , 'is_mobile' , false ); }
		}

		/**
			@brief         レコードの初期値を代入する。
			@exception     InvalidArgumentException $ioRec_ に無効な値を指定した場合。
			@param[in,out] $ioRec_ レコードデータ。
		*/
		static function SetDefaultParameter( &$ioRec_ )
		{
			if( !$ioRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $ioRec_ は無効です' ); }

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$rankID = SalesLogic::GetLowestRankID();

			$db->setData( $ioRec_ , 'rank'          , $rankID );                            //通常会員ランク
			$db->setData( $ioRec_ , 'personal_rate' , 5 );                                  //パーソナルレート
			$db->setData( $ioRec_ , 'magni'         , 100.0 );                              //成果換算レート
			$db->setData( $ioRec_ , 'logout'        , time() );                             //ログアウト時刻
			$db->setData( $ioRec_ , 'limits'        , 0 );                                  //利用期限
			$db->setData( $ioRec_ , 'activate'      , getDefaultActivate( self::$Type ) );  //アクティベートレベル
		}

		/**
			@brief     レコードの親ユーザーIDを設定する。
			@details   親ユーザーにさらに親がいる場合、自動的にリストを構築します。¥n
			           $iParentID_ で指定されたユーザーが存在しない場合は何もせずに返ります。
			@exception InvalidArgumentException $ioRec_ に無効な値を指定した場合。
			@param[in] $ioRec_     レコードデータ。
			@param[in] $iParentID_ 親ユーザーのID。
		*/
		static function SetParentID( &$ioRec_ , $iParentID_ )
		{
			if( !$ioRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $ioRec_ は無効です' ); }

			$userExists = TableLogic::ExistsRecord( self::$Type , $iParentID_ );

			if( !$userExists ) //ユーザーが存在しない場合。
				{ return; }

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$db->setData( $ioRec_ , 'parent' , $iParentID_ );

			$parentID = $iParentID_;

			//先祖のIDを取得する
			foreach( Array( 'grandparent' , 'greatgrandparent' ) as $column ) //カラムを処理
			{
				$parent     = $db->selectRecord( $parentID );
				$parentID   = $db->getData( $parent , 'parent' );
				$userExists = TableLogic::ExistsRecord( self::$Type , $parentID );

				if( !$userExists ) //ユーザーが存在しない場合。
					{ break; }

				$db->setData( $ioRec_ , $column , $parentID );
			}
		}

		//■変数
		private static $Type = 'nUser'; ///<テーブル名。
	}
