<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class log_paySystem extends System
	{

		//■処理

		/**
		 * 検索処理。
		 * フォーム入力以外の方法で検索条件を設定したい場合に利用します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param table フォームのからの入力内容に一致するレコードを格納したテーブルデータ。
		 */
		function searchProc( &$iGM_ , &$ioTable_ , $iLoginUserType_ , $iLoginUserRank_ )
		{

			PayLogic::SetType( self::$Type );

			$ioTable_ = PayLogic::SearchDays( $ioTable_ , $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd' ] , $_GET[ 'y' ] , $_GET[ 'm' ] , $_GET[ 'd2' ] );
			$ioTable_ = PayLogic::SearchMine( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );

			$db  = SystemUtil::getGMforType( self::$Type )->getDB();
			$row = $db->getRow( $ioTable_ );

			unset( $_POST[ 'id' ] );
			unset( $_POST[ 'cost' ] );
			unset( $_POST[ 'state' ] );
		}

		//■変数 //
		private static $Type = 'log_pay'; ///<テーブルの名前。
	}
