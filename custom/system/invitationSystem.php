<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class invitationSystem extends System
	{
		//■処理

		/**
		 * 登録前段階処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function registProc( &$iGM_ , &$ioRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ = false )
		{
			global $LOGIN_ID;

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$db->setData( $ioRec_ , 'owner' , $LOGIN_ID );

			parent::registProc( $iGM_ , $ioRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ );
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

			$db       = SystemUtil::getGMforType( self::$Type )->getDB();
			$mail     = $db->getData( $iRec_ , 'mail' );
			$template = Template::getLabelFile( 'INVITATION_MAIL' );

			Mail::send( $template , $MAILSEND_ADDRES , $mail , $iGM_[ self::$Type ] , $iRec_ , $MAILSEND_NAMES );
		}

		//■変数
		private static $Type = 'invitation'; ///<テーブルの名前。
	}
