<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class multimailSystem extends System
	{
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
			if( !$_POST[ 'receive_id' ] )
			{
				$nDB     = SystemUtil::getGMforType( 'nUser' )->getDB();
				$nTable  = $nDB->getTable();
				$nRow    = $nDB->getRow( $nTable );
				$userIDs = Array();

				for( $i = 0 ; $i < $nRow ; $i++ )
				{
					$nRec      = $nDB->getRecord( $nTable , $i );
					$userIDs[] = $nDB->getData( $nRec , 'id' );
				}

				$nDB->setData( $iRec_ , 'receive_id' , implode( '/' , $userIDs ) );
			}

			parent::registProc( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ );
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
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;

			Mail::sendString( addslashes( $_POST[ 'sub' ] ) , addslashes( $_POST[ 'main' ] ) , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $MAILSEND_NAMES );

			parent::registComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
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
			$nDB    = SystemUtil::getGMforType( 'nUser' )->getDB();
			$nTable = $nDB->getTable();
			$nRow   = $nDB->getRow( $nTable );

			if( !$nRow )
				{ $iLoginUserType_ = 'notFound'; }

			parent::drawRegistForm( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		//■変数 //
		private static $Type = 'multimail'; ///<テーブルの名前。
	}
