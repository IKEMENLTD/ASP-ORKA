<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class adwaresSystem extends System
	{
		//■処理

		/**
		 * 登録内容確認。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param edit 編集なのか、新規追加なのかを真偽値で渡す。
		 * @return エラーがあるかを真偽値で渡す。
		 */
		function registCheck( &$iGM_, $iEdit_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			AdwaresLogic::KillCheck();

			return parent::registCheck( $iGM_, $iEdit_ , $iLoginUserType_ , $iLoginUserRank_ );
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

			$ioTable_ = AdwaresLogic::SearchMine( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );
			$ioTable_ = AdwaresLogic::SearchOpen( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );

			return parent::searchProc( $iGM_ , $ioTable_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		/**
		 * 編集フォームを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawEditForm( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $LOGIN_ID;

			if( AdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ parent::drawEditForm( $iGM_ , $iRec_ , 'owner' , $iLoginUserRank_ ); }
			else
				{ parent::drawEditForm( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ); }
		}

		/**
		 * 編集内容確認ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawEditCheck( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $LOGIN_ID;

			if( AdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ parent::drawEditCheck( $iGM_ , $iRec_ , 'owner' , $iLoginUserRank_ ); }
			else
				{ parent::drawEditCheck( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ); }
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
			global $LOGIN_ID;

			if( AdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ parent::drawEditComp( $iGM_ , $iRec_ , 'owner' , $iLoginUserRank_ ); }
			else
				{ parent::drawEditComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ); }
		}

		/**
		 * 削除確認ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawDeleteCheck( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $LOGIN_ID;

			if( AdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ parent::drawDeleteCheck( $iGM_ , $iRec_ , 'owner' , $iLoginUserRank_ ); }
			else
				{ parent::drawDeleteCheck( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ); }
		}

		/**
		 * 削除完了ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawDeleteComp( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $LOGIN_ID;

			if( AdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ parent::drawDeleteComp( $iGM_ , $iRec_ , 'owner' , $iLoginUserRank_ ); }
			else
				{ parent::drawDeleteComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ); }
		}

		/**
		 * 詳細情報ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawInfo( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $HOME;
			global $LOGIN_ID;

			$iGM_[ self::$Type ]->setVariable( 'host' , $HOME );
			$iGM_[ self::$Type ]->setVariable( 'loginID' , $LOGIN_ID );

			parent::drawInfo( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		//■変数 //
		private static $Type = 'adwares'; ///<テーブルの名前。
	}
