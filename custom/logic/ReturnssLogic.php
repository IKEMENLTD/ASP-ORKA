<?php

	/**
		@brief   Returnssテーブルの処理セット。
		@ingroup SystemAPI
	*/
	class ReturnssLogic
	{
		//■処理

		/**
			@brief     アフィリエイターの報酬から申請額を引く。
			@exception InvalidArgumentException $iReturnssRec_ に無効な値を指定した場合。
			@param[in] $iReturnssRec_ 申請情報のレコードデータ。
		*/
		static function CutNUserReward( $iReturnssRec_ )
		{
			if( !$iReturnssRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( "引数 $iReturnssRec_ は無効です" ); }

			$db      = SystemUtil::getGMforType( self::$Type )->getDB();
			$ownerID = $db->getData( $iReturnssRec_ , 'owner' );
			$cost    = $db->getData( $iReturnssRec_ , 'cost' );

			$nDB  = SystemUtil::getGMforType( 'nUser' )->getDB();
			$nRec = $nDB->selectRecord( $ownerID );

			$nDB->setCalc( $nRec , 'pay' , '-' , $cost );
			$nDB->updateRecord( $nRec );
		}

		/**
			@brief     アフィリエイターの報酬に申請額を戻す。
			@exception InvalidArgumentException $iReturnssRec_ に無効な値を指定した場合。
			@param[in] $iReturnssRec_ 申請情報のレコードデータ。
		*/
		static function ReturnNUserReward( $iReturnssRec_ )
		{
			if( !$iReturnssRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( "引数 $iReturnssRec_ は無効です" ); }

			$db      = SystemUtil::getGMforType( self::$Type )->getDB();
			$ownerID = $db->getData( $iReturnssRec_ , 'owner' );
			$cost    = $db->getData( $iReturnssRec_ , 'cost' );

			$nDB  = SystemUtil::getGMforType( 'nUser' )->getDB();
			$nRec = $nDB->selectRecord( $ownerID );

			$nDB->setCalc( $nRec , 'pay' , '+' , $cost );
			$nDB->updateRecord( $nRec );
		}

		//■データ変更

		/**
			@brief         レコードの初期値を代入する。
			@exception     InvalidArgumentException $ioRec_ に無効な値を指定した場合。
			@param[in,out] $ioRec_ レコードデータ。
		*/
		static function SetDefaultParameter( &$ioRec_ )
		{
			global $LOGIN_ID;

			if( !$ioRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( "引数 $ioRec_ は無効です" ); }

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$db->setData( $ioRec_ , 'id'     , md5( time() . $LOGIN_ID ) ); //レコードID
			$db->setData( $ioRec_ , 'regist' , time() );                    //登録時刻
			$db->setData( $ioRec_ , 'owner'  , $LOGIN_ID  );                //アフィリエイターID
			$db->setData( $ioRec_ , 'state'  , '管理者確認待ち' );          //認証状態
		}

		//■データ取得

		/**
			@brief     日付からテーブルを検索する。
			@exception InvalidArgumentException $iTable_ に無効な値を指定した場合。
			@param[in] $iTable_  検索するテーブルデータ。
			@param[in] $iBeginY_ 開始年。
			@param[in] $iBeginM_ 開始月。
			@param[in] $iBeginD_ 開始日。
			@param[in] $iEndY_   終了年。
			@param[in] $iEndM_   終了月。
			@param[in] $iEndD_   終了日。
			@return    検索後のテーブルデータ。
		*/
		static function SearchDays( $iTable_ , $iBeginY_ , $iBeginM_ , $iBeginD_ , $iEndY_ , $iEndM_ , $iEndD_ )
		{
			if( !$iTable_ ) //テーブルが空の場合
				{ throw new InvalidArgumentException( "引数 $iTable_ は無効です" ); }

			if( $iBeginY_ && $iBeginM_ ) //開始年月が指定されている場合
			{
				if( $iBeginD_ && $iEndD_ ) //開始日と終了に値が指定されている場合
				{
					$beginTime = mktime( 0 , 0 , 0 , $iBeginM_ , $iBeginD_ , $iBeginY_ );
					$endTime   = mktime( 0 , 0 , 0 , $iEndM_ , $iEndD_ + 1 , $iEndY_ );
				}
				else //日が指定されていない場合
				{
					$beginTime = mktime( 0 , 0 , 0 , $iBeginM_ , 1 , $iBeginY_ );
					$endTime   = mktime( 0 , 0 , 0 , $iEndM_ + 1 , 1 , $iEndY_ );
				}

				$db    = SystemUtil::getGMforType( self::$Type )->getDB();
				$table = $db->searchTable( $iTable_ , 'regist' , 'b' , $beginTime , $endTime );

				return $table;
			}

			return $iTable_;
		}

		/**
			@brief     ユーザー毎に所有権のあるテーブルを検索する。
			@exception InvalidArgumentException $iTable_ に無効な値を指定した場合。
			@param[in] $iTable_    テーブルデータ。
			@param[in] $iUserType_ ユーザー種別。
			@param[in] $iUserID_   ユーザーID。
			@return    検索後のテーブルデータ。
		*/
		static function SearchMine( $iTable_ , $iUserType_ , $iUserID_ )
		{
			if( !$iTable_ ) //テーブルが空の場合
				{ throw new InvalidArgumentException( "引数 $iTable_ は無効です" ); }

			switch( $iUserType_ ) //ユーザー種別で分岐
			{
				case 'nUser' : //アフィリエイター
				{
					$db    = SystemUtil::getGMforType( self::$Type )->getDB();
					$table = $db->searchTable( $iTable_ , 'owner' , '=' , $iUserID_ );

					return $table;
				}

				default : //その他
					{ return $iTable_; }
			}
		}

		//■通知 //

		/**
			@brief     換金申請の登録を通知する。
			@param[in] $iRec 換金申請レコード。
		*/
		static function NoticeRegister( $iRec ) //
		{
			global $gm;
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;

			$template = Template::getTemplate( 'admin' , 1 , '', 'RETURNSS_REGIST_MAIL' );
			Mail::send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm[ self::$Type ] , $iRec , $MAILSEND_NAMES );
		}

		/**
			@brief     換金申請の入金完了を通知する。
			@param[in] $iRec 換金申請レコード。
		*/
		static function NoticeAccept( $iRec ) //
		{
			global $gm;
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;

			$db       = GMList::getDB( self::$Type );
			$ownerID  = $db->getData( $iRec , 'owner' );
			$userDB   = GMList::getDB( 'nUser' );
			$userRec  = $userDB->selectRecord( $ownerID );
			$userMail = $userDB->getData( $userRec , 'mail' );

			$template = Template::getTemplate( 'admin' , 1 , '', 'RETURNSS_ACCEPT_MAIL' );
			Mail::send( $template , $MAILSEND_ADDRES , $userMail , $gm[ self::$Type ] , $iRec , $MAILSEND_NAMES );
			Mail::send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm[ self::$Type ] , $iRec , $MAILSEND_NAMES );
		}

		/**
			@brief     換金申請の差し戻しを通知する。
			@param[in] $iRec 換金申請レコード。
		*/
		static function NoticeDeny( $iRec ) //
		{
			global $gm;
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;

			$db       = GMList::getDB( self::$Type );
			$ownerID  = $db->getData( $iRec , 'owner' );
			$userDB   = GMList::getDB( 'nUser' );
			$userRec  = $userDB->selectRecord( $ownerID );
			$userMail = $userDB->getData( $userRec , 'mail' );

			$template = Template::getTemplate( 'admin' , 1 , '', 'RETURNSS_DENY_MAIL' );
			Mail::send( $template , $MAILSEND_ADDRES , $userMail , $gm[ self::$Type ] , $iRec , $MAILSEND_NAMES );
			Mail::send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm[ self::$Type ] , $iRec , $MAILSEND_NAMES );
		}

		//■変数
		private static $Type = 'returnss'; ///<テーブル名。
	}
