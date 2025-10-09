<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class accessSystem extends System
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
			global $LOGIN_ID;

			$db = SystemUtil::getGMforType( self::$Type )->getDB();

			$db->setData( $iRec_ , 'id' , md5( time() . $LOGIN_ID ) );
			$db->setData( $iRec_ , 'regist' ,time() );

			if( !$iCheck_ )
				{ $this->uplodeComp( $iGM_ , $db , $iRec_ ); }
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

			$ioTable_ = AccessLogic::SearchMine( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );
			$ioTable_ = AccessLogic::SearchDays( $ioTable_ , $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd' ] , $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd2' ] );

			return parent::searchProc( $iGM_ , $ioTable_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		//■変数 //
		private static $Type = 'access'; ///<テーブルの名前。
	}
