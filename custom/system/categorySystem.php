<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class categorySystem extends System
	{
		//■処理

		/**
		 * 登録フォームを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawRegistForm( &$iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			$this->setErrorMessage( $iGM_[ self::$Type ] );

			if( 'true' == $_GET[ 'hfnull' ] )
				{ Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'REGIST_FORM_PAGE_DESIGN_POPUP' , 'regist.php?hfnull=true&type=' . self::$Type , null , null , 'v' ); }
			else
				{ Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'REGIST_FORM_PAGE_DESIGN' , 'regist.php?type=' . self::$Type ); }
		}

		/**
		 * 登録内容確認ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 登録情報を格納したレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawRegistCheck( &$iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			if( 'true' == $_GET[ 'hfnull' ] )
				{ Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'REGIST_CHECK_PAGE_DESIGN_POPUP' , 'regist.php?hfnull=true&type=' . self::$Type , null , null , 'v' ); }
			else
				{ Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'REGIST_CHECK_PAGE_DESIGN' , 'regist.php?type=' . self::$Type ); }
		}

		/**
		 * 登録完了ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 登録情報を格納したレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawRegistComp( &$iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			if( 'true' == $_GET[ 'hfnull' ] )
				{ Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'REGIST_COMP_PAGE_DESIGN_POPUP' ); }
			else
				{ Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'REGIST_COMP_PAGE_DESIGN' ); }
		}

		//■変数
		private static $Type = 'category'; ///<テーブルの名前。
	}
