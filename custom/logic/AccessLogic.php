<?php

	/**
		@brief   Accessテーブルの処理セット。
		@ingroup SystemAPI
	*/
	class AccessLogic
	{
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
					$endTime   = mktime( 0 , 0 , 0 , $iBeginM_ + 1 , 1 , $iBeginY_ );
				}

				$db    = SystemUtil::getGMforType( self::$Type )->getDB();
				$table = $db->searchTable( $iTable_ , 'regist' , 'b' , $beginTime , $endTime );

				return $table;
			}

			return $iTable_;
		}

		/**
			@brief     ユーザー毎に所有権のあるテーブルを検索する。
			@exception InvalidArgumentException $iTable_ , $iUserType_ のいずれかに無効な値を指定した場合。
			@param[in] $iTable_    検索するテーブルデータ。
			@param[in] $iUserType_ ユーザー種別。
			@param[in] $iUserID_   ユーザーID。
			@remarks   所有権と関わらないユーザーの場合は絞り込まれません。
			@return    検索後のテーブルデータ。
		*/
		static function SearchMine( $iTable_ , $iUserType_ , $iUserID_ )
		{
			if( !$iTable_ ) //テーブルが空の場合
				{ throw new InvalidArgumentException( "引数 $iTable_ は無効です" ); }

			if( !$iUserType_ ) //ユーザー種別が殻の場合
				{ throw new InvalidArgumentException( "引数 $iUserType_ は無効です" ); }

			switch( $iUserType_ ) //ユーザー種別で分岐
			{
				case 'nUser' : //アフィリエイター
				{
					$db    = SystemUtil::getGMforType( self::$Type )->getDB();
					$table = $db->searchTable( $iTable_ , 'owner' , '=' , $iUserID_ );

					return $table;
				}

				case 'cUser' : //広告主
				{
					$db    = SystemUtil::getGMforType( self::$Type )->getDB();
					$table = $db->searchTable( $iTable_ , 'cuser' , '=' , $iUserID_ );

					return $table;
				}

				default : //その他
					{ return $iTable_; }
			}
		}

		//■変数
		private static $Type = 'access'; ///<テーブル名。
	}
