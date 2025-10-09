<?php

	/**
		@brief   Payテーブルの処理セット。
		@ingroup SystemAPI
	*/
	class PayLogic
	{
		//■処理

		/**
			@brief     ユーザーの報酬を追加する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_    成果情報のレコードデータ。
			@param[in] $iReward_ 報酬額。省略時は $iRec_ の報酬額を使用。
		*/
		static function AddPay( $iRec_ , $iReward_ = null )
		{
			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$ownerID = $db->getData( $iRec_ , 'owner' );
			$reward  = $db->getData( $iRec_ , 'cost' );

			if( !is_null( $iReward_ ) ) //報酬が空の場合
				{ $reward = $iReward_; }

			addPay( $ownerID , $reward , $db , $iRec_ , $tier );

			$adDB  = GMList::getDB( $db->getData( $iRec_ , 'adwares_type' ) );
			$adRec = $adDB->selectRecord( $db->getData( $iRec_ , 'adwares' ) );
			$name  = Array( 'pay' => 'pay_count' , 'click_pay' => 'click_money_count' , 'continue_pay' => 'continue_money_count' );

			$adDB->setData( $adRec , 'money_count' , $adDB->getData( $adRec , 'money_count' ) + $reward + $tier );
			$adDB->setData( $adRec , $name[ $_GET[ 'type' ] ] , $adDB->getData( $adRec , $name[ $_GET[ 'type' ] ] ) + 1 );
			$adDB->updateRecord( $adRec );

			return $tier;
		}

		/**
			@brief     ユーザーの報酬を取り除くする。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_    成果情報のレコードデータ。
			@param[in] $iReward_ 報酬額。省略時は $iRec_ の報酬額を使用。
		*/
		static function SubPay( $iRec_ , $iReward_ = null )
		{
			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$ownerID = $db->getData( $iRec_ , 'owner' );
			$reward  = $db->getData( $iRec_ , 'cost' );

			if( !is_null( $iReward_ ) ) //報酬が空の場合
				{ $reward = $iReward_; }

			subPay( $ownerID , $reward , $db , $iRec_ , $tier );

			$adDB  = GMList::getDB( $db->getData( $iRec_ , 'adwares_type' ) );
			$adRec = $adDB->selectRecord( $db->getData( $iRec_ , 'adwares' ) );
			$name  = Array( 'pay' => 'pay_count' , 'click_pay' => 'click_money_count' , 'continue_pay' => 'continue_money_count' );

			$adDB->setData( $adRec , 'money_count' , $adDB->getData( $adRec , 'money_count' ) - $reward );
			$adDB->setData( $adRec , $name[ $_GET[ 'type' ] ] , $adDB->getData( $adRec , $name[ $_GET[ 'type' ] ] ) - 1 );
			$adDB->updateRecord( $adRec );

			return $tier;
		}
	
		
		/**
			@brief     ユーザーの報酬の変化に関するログを記録する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_      レコードデータ。
			@param[in] $action_      編集種別。
			@param[in] $iNewCost_  新しい成果額。
			@param[in] $iNewState_ 新しい認証状態。
		*/
		static function AddPayLog( $iRec_, $action_ )
		{
			global $LOGIN_ID;
			global $ACTIVE_NONE;
			
			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }
			
			$cost = self::GetNeedUpdateUser( $iRec_ );
			
			if( $cost != 0 ){
				$payDB     = GMList::getDB( self::$Type );
				$logDB     = GMList::getDB( 'log_pay' );

				$oldState = $payDB->getData( self::$OriginRec , 'state' );
				$newState = $payDB->getData( $iRec_           , 'state' );

				if( 0 == $oldState && $ACTIVE_NONE == $newState ) //未確認から非認証への変更の場合
					{ return; }
				
				$logRec = $logDB->getNewRecord();
				
				$logDB->setData( $logRec, 'id', $logDB->getTimeID());
				$logDB->setData( $logRec, 'pay_type', self::$Type );
				$logDB->setData( $logRec, 'pay_id', $payDB->getData( $iRec_,'id') );
				$logDB->setData( $logRec, 'nuser_id', $payDB->getData( $iRec_,'owner') );
				$logDB->setData( $logRec, 'operator', $LOGIN_ID );
				$logDB->setData( $logRec, 'cost', $cost );
				$logDB->setData( $logRec, 'action', $action_ );
				$logDB->setData( $logRec, 'state', $payDB->getData( $iRec_,'state') );
				$logDB->setData( $logRec, 'regist', time() );
				
				$logDB->addRecord( $logRec );
			}
		}
	
		/**
			@brief     ユーザーの報酬を追加する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@exception LogicException           オリジナルデータが記録されていない場合。
			@param[in] $iRec_    成果情報のレコードデータ。
		*/
		static function UpdateReward( $iRec_ )
		{
			global $ACTIVE_ACTIVATE;
			global $ACTIVE_NONE;

			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }

			if( !self::$OriginRec ) //オリジナルデータの記録がない場合
				{ throw new LogicException( "呼び出しが正しくありません" ); }

			$db       = SystemUtil::getGMforType( self::$Type )->getDB();
			$nowState = $db->getData( $iRec_ , 'state' );
			$oldState = $db->getData( self::$OriginRec , 'state' );

			if( $oldState != $nowState ) //ステータスが変更されている場合
			{
				switch( $nowState ) //現在のステータスで分岐
				{
					case $ACTIVE_ACTIVATE: //認証
					{
						PayLogic::AddPay( $iRec_ );

						break;
					}

					case $ACTIVE_NONE: //未認証
					{
						if( $oldState )
						{
							$oldCost = $db->getData( self::$OriginRec , 'cost' );

							PayLogic::SubPay( $iRec_ , $oldCost );
						}

						break;
					}
				}
			}
			else //ステータスが変更されていない場合
			{
				if( $ACTIVE_ACTIVATE == $oldState ) //ステータスが認証の場合
				{
					$nowCost = $db->getData( $iRec_ , 'cost' );
					$oldCost = $db->getData( self::$OriginRec , 'cost' );

					if( $oldCost < $nowCost ) //報酬を増やした場合
						{ PayLogic::AddPay( $iRec_ , $nowCost - $oldCost ); }
					else if( $oldCost > $_POST[ 'cost' ] ) //報酬を減らした場合
						{ PayLogic::SubPay( $iRec_ , $oldCost - $nowCost ); }
				}
			}
		}
		
		/**
			@brief     ユーザーの報酬に影響する変更かを調べ、影響がある場合はその金額を返す。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@exception LogicException           オリジナルデータが記録されていない場合。
			@param[in] $iRec_    成果情報のレコードデータ。
		*/
		static function GetNeedUpdateUser( $iRec_ )
		{
			global $ACTIVE_ACTIVATE;
			global $ACTIVE_NONE;

			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }

			if( !self::$OriginRec ) //オリジナルデータの記録がない場合
				{ throw new LogicException( "呼び出しが正しくありません" ); }

			$db       = SystemUtil::getGMforType( self::$Type )->getDB();
			
			$nowDelete = $db->to_boolean( $db->getData( $iRec_ , 'delete_key' ) );
			$oldDelete = $db->to_boolean( $db->getData( self::$OriginRec , 'delete_key' ) );
			
			if( $nowDelete != $oldDelete )
			{
				//削除されている。
				$nowCost = $db->getData( $iRec_ , 'cost' );
				return $nowCost*-1;
			}
			
			$nowState = $db->getData( $iRec_ , 'state' );
			$oldState = $db->getData( self::$OriginRec , 'state' );

			if( $oldState != $nowState ) //ステータスが変更されている場合
			{
				switch( $nowState ) //現在のステータスで分岐
				{
					case $ACTIVE_ACTIVATE: //認証
					{
						$nowCost = $db->getData( $iRec_ , 'cost' );
						return $nowCost;
					}

					case $ACTIVE_NONE: //未認証
					{
						$oldCost = $db->getData( self::$OriginRec , 'cost' );
						return $oldCost*-1;
					}
				}
			}
			else //ステータスが変更されていない場合
			{
				if( $ACTIVE_ACTIVATE == $oldState ) //ステータスが認証の場合
				{
					$nowCost = $db->getData( $iRec_ , 'cost' );
					$oldCost = $db->getData( self::$OriginRec , 'cost' );
					if( $oldCost != $nowCost ) //報酬に変化があった場合。
						return $nowCost - $oldCost;
				}
			}
			return 0;
		}
		

		//■データ変更

		/**
			@brief     オリジナルのレコードデータを記録する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_    成果情報のレコードデータ。
		*/
		static function MemoryOriginRec( $iRec_ )
		{
			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }

			$db              = SystemUtil::getGMforType( self::$Type )->getDB();
			$id              = $db->getData( $iRec_ , 'id' );
			self::$OriginRec = $db->selectRecord( $id );
		}

		/**
			@brief     テーブル名をセットする。
			@param[in] $iType_ テーブル名。
		*/
		static function SetType( $iType_ )
			{ self::$Type = $iType_; }

		//■データ取得

		/**
			@brief     レコードの編集を正常に行えるか確認する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_      レコードデータ。
			@param[in] $iNewCost_  新しい成果額。
			@param[in] $iNewState_ 新しい認証状態。
			@retval    true  正常に編集できる場合。
			@retval    false 正常に編集できない場合。
		*/
		static function Editable( $iRec_ , $iNewCost_ , $iNewState_ )
		{
			global $ACTIVE_NONE;
			global $ACTIVE_ACTIVATE;

			$db     = GMList::getDB( self::$Type );
			$userID = $db->getData( $iRec_ , 'owner' );
			$cost   = $db->getData( $iRec_ , 'cost' );
			$state  = $db->getData( $iRec_ , 'state' );

			if( 'admin' == $loginUserType )
			{
				if( $cost > $iNewCost_ ) //成果額が減少する場合
					{ $diff = $cost - $iNewCost_; }
			}

			if( $ACTIVE_ACTIVATE == $state && $ACTIVE_NONE == $iNewState_ ) //未認証になる場合
				{ $diff = $cost; }

			$nDB  = GMList::getDB( 'nUser' );
			$nRec = $nDB->selectRecord( $userID );
			$pay  = $nDB->getData( $nRec , 'pay' );

			if( 0 > ( $pay - $diff ) ) //変更後の成果がマイナスになる場合
				{ return false; }

			$nTable = $nDB->getTable();
			$pay    = $nDB->getSum( 'pay' , $nTable );

			if( 0 > ( $pay - $diff ) ) //変更後の成果がマイナスになる場合
				{ return false; }

			return true;
		}
		
		
		/**
			@brief     レコードが認証されているか確認する。
			@exception InvalidArgumentException $iRec_ に無効な値を指定した場合。
			@param[in] $iRec_      レコードデータ。
			@retval    true  認証されている場合。
			@retval    false 認証されていない場合。
		*/
		static function IsActivate( $iRec_ )
		{
			global $ACTIVE_ACTIVATE;

			if( !$iRec_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iRec_ は無効です' ); }

			$db    = SystemUtil::getGMforType( self::$Type )->getDB();
			$state = $db->getData( $iRec_ , 'state' );

			if( $ACTIVE_ACTIVATE == $state )
				{ return true; }
			else
				{ return false; }
		}

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
			if( !$iTable_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iTable_ は無効です' ); }

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
			else //どちらかが指定されていない場合
			{
				if( $_GET[ 'registA' ] ) //タイムスタンプ形式で指定されている場合
				{
					$date = date( 'Y/n/j' , $_GET[ 'registA' ] );

					List( $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd' ] ) = explode( '/', $date );

					unset( $_GET[ 'registA' ] );
				}

				if( $_GET[ 'registB' ] && 'top' != $_GET[ 'registB' ] ) //タイムスタンプ形式で指定されている場合
				{
					$_GET[ 'd2' ] = date( 'j' , $_GET[ 'registB' ] );

					unset( $_GET[ 'registB' ] );
				}

				return $iTable_;
			}
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
			if( !$iTable_ ) //レコードが空の場合
				{ throw new InvalidArgumentException( '引数 $iTable_ は無効です' ); }

			switch( $iUserType_ )
			{
				case 'nUser' :
				{
					$db = GMList::getDB( self::$Type );

					return $db->searchTable( $iTable_ , 'owner' , '=' , $iUserID_ );
				}

				case 'cUser' :
				{
					$db = GMList::getDB( self::$Type );

					return $db->searchTable( $iTable_ , 'cuser' , '=' , $iUserID_ );
				}

				default :
					{ return $iTable_; }
			}
		}

		//■変数
		private static $Type      = 'pay'; ///<テーブル名。
		private static $OriginRec = null;  ///<元のレコードデータ。。
	}
