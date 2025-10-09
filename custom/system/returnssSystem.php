<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class returnssSystem extends System
	{
		//■処理

		/**
		 * 登録内容確認。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param edit 編集なのか、新規追加なのかを真偽値で渡す。
		 * @return エラーがあるかを真偽値で渡す。
		 */
		function registCheck( &$iGM_ , $iEdit_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $ADWARES_EXCHANGE;
			global $LOGIN_ID;

			parent::registCheck( $iGM_ , $iEdit_ , $iLoginUserType_ , $iLoginUserRank_ );

			if( self::$checkData->getCheck() )
			{
				if( $ADWARES_EXCHANGE > $_POST[ 'cost' ] )
					{ self::$checkData->addError( 'limit' ); }
				else
				{
					$nDB     = SystemUtil::getGMforType( 'nUser' )->getDB();
					$nRec    = $nDB->selectRecord( $LOGIN_ID );
					$nReward = $nDB->getData( $nRec , 'pay' );

					if( $nReward < $_POST[ 'cost' ] )
						{ self::$checkData->addError( 'outof' ); }
				}
			}

			return self::$checkData->getCheck();
		}

		function registCompCheck( &$iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $ADWARES_EXCHANGE;
			global $LOGIN_ID;

			if( $ADWARES_EXCHANGE > $_POST[ 'cost' ] )
				{ return false; }
			else
			{
				$nDB     = SystemUtil::getGMforType( 'nUser' )->getDB();
				$nRec    = $nDB->selectRecord( $LOGIN_ID );
				$nReward = $nDB->getData( $nRec , 'pay' );

				if( $nReward < $_POST[ 'cost' ] )
					{ return false; }
			}

			return true;
		}

		/**
		 * 登録前段階処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function registProc( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ = false )
		{
			ReturnssLogic::SetDefaultParameter( $iRec_ );

			if( !$iCheck_ )
			{
				$db = SystemUtil::getGMforType( self::$Type )->getDB();

				$this->uplodeComp( $iGM_ , $db , $iRec_ );
			}
		}

		/**
		 * 登録処理完了処理。
		 * 登録完了時にメールで内容を通知したい場合などに用います。
		 * 
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec レコードデータ。
		 */
		function registComp( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			ReturnssLogic::CutNUserReward( $iRec_ );
			ReturnssLogic::NoticeRegister( $iRec_ );

			parent::registComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		/**
		 * 検索処理。
		 * フォーム入力以外の方法で検索条件を設定したい場合に利用します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param table フォームのからの入力内容に一致するレコードを格納したテーブルデータ。
		 */
		function searchProc( &$iGM_ , &$ioTable_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $LOGIN_ID;

			$ioTable_ = ReturnssLogic::SearchMine( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );
			$ioTable_ = ReturnssLogic::SearchDays( $ioTable_ , $_GET[ 'y' ] , $_GET[ 'm' ] , null , $_GET[ 'y2' ] , $_GET[ 'm2' ] , null );

			if( $_POST[ 'id' ] )
			{
				$db  = SystemUtil::getGMforType( self::$Type )->getDB();
				$rec = $db->selectRecord( $_POST[ 'id' ] );

				if( $rec )
				{
					$oldState = $db->getData( $rec , 'state' );
					$db->setData( $rec , 'state' , $_POST[ 'state' ] );

					if( '差し戻し' == $_POST[ 'state' ] )
					{
						if( '差し戻し' != $oldState )
						{
							ReturnssLogic::ReturnNUserReward( $rec );
							ReturnssLogic::NoticeDeny( $rec );
						}
					}
					else if( '入金済み' == $_POST[ 'state' ] )
						{ ReturnssLogic::NoticeAccept( $rec ); }

					$db->updateRecord( $rec );
				}

				unset( $_POST[ 'state' ] );
			}

			$db        = SystemUtil::getGMforType( self::$Type )->getDB();
			$tempTable = $db->searchTable( $ioTable_ , 'state' , '!=' , '差し戻し' );
			$sum       = $db->getSum( 'cost' , $tempTable );

			$iGM_[ self::$Type ]->setVariable( 'SUM' , $sum );

			parent::searchProc( $iGM_ , $ioTable_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		/**
		 * 登録フォームを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawRegistForm( &$iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $ADWARES_EXCHANGE;
			global $LOGIN_ID;
			global $users_returnss;

			$this->setErrorMessage( $iGM_[ self::$Type ] );

			if( !$users_returnss )
			{
				Template::drawErrorTemplate();

				return;
			}

			$nDB  = SystemUtil::getGMforType( 'nUser' )->getDB();
			$nRec = $nDB->selectRecord( $LOGIN_ID );

			if( !$nRec )
			{
				Template::drawTemplate( $iGM_[ self::$Type ] , $nRec , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'ADWARES_EXCHANGE' );

				return;
			}

			$pay = $nDB->getData( $nRec , 'pay' );

			if( $ADWARES_EXCHANGE > $pay )
			{
				Template::drawTemplate( $iGM_[ self::$Type ] , $nRec , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'ADWARES_EXCHANGE' );

				return;
			}

			return parent::drawRegistForm( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		/**
		 * 編集完了ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawEditComp( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			print '
				<script language="javascript">
				<!--
						window.location.replace(¥'search.php?type=returnss&run=true¥');
				// #-->
				</script>
				<a href="index.php">クリックしてください</a><br>
			';
		}

		//■変数 //
		private static $Type = 'returnss'; ///<テーブルの名前。
	}
