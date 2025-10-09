<?php

	/**
		@brief   Adwaresテーブルの処理セット。
		@ingroup SystemAPI
	*/
	class AdwaresLogic
	{
		//■処理

		/**
			@brief 未使用項目に対する入力エラーチェックを無効化する。
		*/
		static function KillCheck()
		{
			if( !$_POST[ 'money' ] ) //報酬額が入力されていない場合
			{
				switch( $_POST[ 'ad_type' ] ) //報酬タイプで分岐
				{
					case 'rank' :     //ユーザーランク
					case 'personal' : //パーソナルレート
					{
						$_POST[ 'money' ] = '0';

						break;
					}

					default : //その他
						{ break; }
				}
			}

			if( !$_POST[ 'limits' ] ) //予算上限が入力されていない場合
			{
				if( !$_POST[ 'limit_type' ] ) //予算上限を使用しない場合
					{ $_POST[ 'limits' ] = '0'; }
			}
		}

		//■データ取得

		/**
			@brief     レコードの所有権があるか確認する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_      レコードデータ。
			@param[in] $iUserType_ ユーザー種別。
			@param[in] $iUserID_   ユーザーID。
			@retval    true  所有権がある場合。
			@retval    false 所有権がない場合。
		*/
		static function IsMine( $iRec_ , $iUserType_ , $iUserID_ )
		{
			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( "引数 $iRec_ は無効です" ); }

			if( 'cUser' == $iUserType_ ) //広告主の場合
			{
				$db      = SystemUtil::getGMforType( self::$Type )->getDB();
				$ownerID = $db->getData( $iRec_ , 'cuser' );

				if( $ownerID == $iUserID_ ) //広告の所有者の場合
					{ return true; }
			}

			return false;
		}

		/**
			@brief     ユーザー毎に所有権のあるテーブルを検索する。
			@exception InvalidArgumentException $iTable_ に無効な値を指定した場合。
			@param[in] $iTable_    テーブルデータ。
			@param[in] $iUserType_ ユーザー種別。
			@param[in] $iUserID_   ユーザーID。
			@remarks   所有権と関わらないユーザーの場合は絞り込まれません。
			@return    検索後のテーブルデータ。
		*/
		static function SearchMine( $iTable_ , $iUserType_ , $iUserID_ )
		{
			if( !$iTable_ ) //テーブルが空の場合
				{ throw new InvalidArgumentException( "引数 $iTable_ は無効です" ); }

			switch( $iUserType_ ) //ユーザー種別で分岐
			{
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

		/**
			@brief     ユーザー毎に参照権のあるテーブルを検索する。
			@exception InvalidArgumentException $iTable_ に無効な値を指定した場合。
			@param[in] $iTable_    テーブルデータ。
			@param[in] $iUserType_ ユーザー種別。
			@param[in] $iUserID_   ユーザーID。
			@remarks   参照権と関わらないユーザーの場合は絞り込まれません。
			@return    検索後のテーブルデータ。
		*/
		static function SearchOpen( $iTable_ , $iUserType_ , $iUserID_ )
		{
			if( !$iTable_ ) //テーブルが空の場合
				{ throw new InvalidArgumentException( "引数 $iTable_ は無効です" ); }

			switch( $iUserType_ ) //ユーザー種別で分岐
			{
				case 'nobody' : //一般ユーザー
				case 'nUser' :  //アフィリエイター
				{
					$db    = SystemUtil::getGMforType( self::$Type )->getDB();
					$table = $db->searchTable( $iTable_ , 'open' , '=' , TRUE );

					return $table;
				}

				default : //その他
					{ return $iTable_; }
			}
		}

		//■変数
		private static $Type = 'adwares'; ///<テーブル名。
	}
