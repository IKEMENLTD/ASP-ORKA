<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	abstract class BasePaySystem extends System
	{
		//■データ取得

		abstract function GetType();

		//■処理

		/**
		 * 登録前段階処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function registProc( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ = false )
		{
			global $LOGIN_ID;

			$db = SystemUtil::getGMforType( $this->GetType() )->getDB();

			$db->setData( $iRec_ , 'id' , md5( time() . $LOGIN_ID ) );
			$db->setData( $iRec_ , 'regist' ,time() );

			if( !$iCheck_ )
				{ $this->uplodeComp( $iGM_ , $db , $iRec_ ); }
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
			PayLogic::SetType( $this->GetType() );

			if( PayLogic::IsActivate( $iRec_ ) ) //認証状態の場合
				{ PayLogic::AddPay( $iRec_ ); }

			parent::registComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ );
		}

		/**
		 * 編集前段階処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function editProc( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ = false )
		{
			global $ACTIVE_NONE;

			$db     = GMList::getDB( $_GET[ 'type' ] );
			$id     = $db->getData( $iRec_ , 'id' );
			$oldRec = $db->selectRecord( $id );

			PayLogic::SetType( $this->GetType() );
			PayLogic::MemoryOriginRec( $oldRec );

			$oldState = $db->getData( $oldRec , 'state' );
			$newState = $db->getData( $iRec_  , 'state' );

			if( !( 0 == $oldState && $ACTIVE_NONE == $newState ) )
			{
				if( $dCost = PayLogic::GetNeedUpdateUser( $iRec_ ) )
					{ $iGM_[ $this->GetType() ]->setVariable( 'alert', '該当のアフィリエイターに対し、'.abs($dCost).'円の報酬が'.(($dCost>=0)?'加算':'減算').'されます。' ); }
			}

			parent::editComp( $iGM_ , $iRec_ , $old_rec, $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ );
		}

		/**
		 * 編集完了処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function editComp( &$iGM_ , &$iRec_ , &$old_rec,  $iLoginUserType_ , $iLoginUserRank_ )
		{
			PayLogic::SetType( $this->GetType() );
			PayLogic::UpdateReward( $iRec_ );
			PayLogic::AddPayLog( $iRec_, "edit" );

			parent::editComp( $iGM_ , $iRec_ , $old_rec, $iLoginUserType_ , $iLoginUserRank_ );
		}
		
		/**
		 * 削除内容確認。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 * @return エラーがあるかを真偽値で渡す。
		 */
		function deleteCheck( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ ){
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $TEMPLATE_CLASS_SYSTEM;
			global $ACTIVE_ACTIVATE;
			// **************************************************************************************
		
			$db  = SystemUtil::getGMforType( $this->GetType() )->getDB();
			$state = $db->getData( $iRec_ , 'state' );
			$cost = $db->getData( $iRec_ , 'cost' );
			
			if( $state == $ACTIVE_ACTIVATE && $cost > 0 )
			{
				$iGM_[ $this->GetType() ]->setVariable( 'alert', '該当のアフィリエイターに対し、'.$cost.'円の報酬が減算されます。' );
			}
			
			return self::$checkData->getCheck();
		}
		
		/**
		 * 削除処理。
		 * 削除を実行する前に実行したい処理があれば、ここに記述します。
		 * 例えばユーザデータを削除する際にユーザデータに紐付けられたデータを削除する際などに有効です。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function deleteProc( &$iGM_, &$iRec_, $iLoginUserType_, $iLoginUserRank_ )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $LOGIN_ID;
			// **************************************************************************************
			
			PayLogic::SetType( $this->GetType() );
			PayLogic::MemoryOriginRec( $iRec_ );
			
			parent::deleteProc( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
		}
		
		/**
		 * 削除完了処理。
		 * 登録削除完了時に実行したい処理があればココに記述します。
		 * 削除完了メールを送信したい場合などに利用します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function deleteComp( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			PayLogic::SetType( $this->GetType() );

			if( PayLogic::IsActivate( $iRec_ ) )
				{ 
					PayLogic::SubPay( $iRec_ );
					PayLogic::AddPayLog( $iRec_, "delete" );
				}

			parent::deleteComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
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
			global $ACTIVE_ACTIVATE;
			global $LOGIN_ID;

			PayLogic::SetType( $this->GetType() );

			$ioTable_ = PayLogic::SearchDays( $ioTable_ , $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd' ] , $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd2' ] );
			$ioTable_ = PayLogic::SearchMine( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );

			$db  = SystemUtil::getGMforType( $this->GetType() )->getDB();
			$row = $db->getRow( $ioTable_ );

			if( $row )
			{
				if( $_POST[ 'id' ] && 0 <= $_POST[ 'cost' ] )
				{
					$rec = $db->selectRecord( $_POST[ 'id' ] );

					switch( $iLoginUserType_ )
					{
						case 'admin' :
						{
							$rec = $db->selectRecord( $_POST[ 'id' ] );

							if( $rec )
							{
								PayLogic::MemoryOriginRec( $rec );
								$nowState = $db->getData( $rec , 'state' );

								$db->setData( $rec , 'cost' , $_POST[ 'cost' ] );
								$db->setData( $rec , 'state' , $_POST[ 'state' ] );
								$db->updateRecord( $rec );

								PayLogic::UpdateReward( $rec );
								PayLogic::AddPayLog( $rec, "edit" );

								if( $nowState != $_POST[ 'state' ] ) //認証ステータスを変更した場合
								{
									if( $ACTIVE_ACTIVATE == $_POST[ 'state' ] ) //認証にした場合
										{ sendPayMail( $rec , $this->GetType() ); }
									else //非認証にした場合
										{ sendDisabledPayMail( $rec , $this->GetType() ); }
								}
							}

							break;
						}

						case 'cUser' :
						{
							$rec = $db->selectRecord( $_POST[ 'id' ] );

							if( $rec )
							{
								PayLogic::MemoryOriginRec( $rec );
								$nowState = $db->getData( $rec , 'state' );

								$db->setData( $rec , 'state' , $_POST[ 'state' ] );
								$db->updateRecord( $rec );

								PayLogic::UpdateReward( $rec );
								PayLogic::AddPayLog( $rec, "edit" );

								if( $nowState != $_POST[ 'state' ] ) //認証ステータスを変更した場合
								{
									if( $ACTIVE_ACTIVATE == $_POST[ 'state' ] ) //認証にした場合
										{ sendPayMail( $rec , $this->GetType() ); }
									else //非認証にした場合
										{ sendDisabledPayMail( $rec , $this->GetType() ); }
								}
							}

							break;
						}

						default :
							{ break; }
					}
				}

				$sum = $db->getSum( 'cost' , $ioTable_ );
				$iGM_[ $this->GetType() ]->setVariable( 'SUM' , $sum );
			}

			unset( $_POST[ 'id' ] );
			unset( $_POST[ 'cost' ] );
			unset( $_POST[ 'state' ] );
		}
	}
