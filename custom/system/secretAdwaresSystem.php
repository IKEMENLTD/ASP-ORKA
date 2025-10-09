<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class secretAdwaresSystem extends System
	{
		//■処理

		/**
		 * 登録前段階処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function registProc( &$iGM_ , &$iRec_ , $iLoginUserType_ , $iLoginUserRank_ ,$iCheck_ = false )
		{
			global $LOGIN_ID;

			switch( $iLoginUserType_ ) //ユーザー種別で分岐
			{
				case 'cUer' : //広告主
				{
					$db = SystemUtil::getGMforType( self::$Type );

					$db->setData( $ioRec_ , 'cuser' , $LOGIN_ID );

					break;
				}

				default :
					{ break; }
			}

			return parent::registProc( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ,$iCheck_ );
		}

		/**
		 * 登録内容確認。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param edit 編集なのか、新規追加なのかを真偽値で渡す。
		 * @return エラーがあるかを真偽値で渡す。
		 */
		function registCheck( &$iGM_, $iEdit_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			SecretAdwaresLogic::KillCheck();

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

			$ioTable_ = SecretAdwaresLogic::SearchMine( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );
			$ioTable_ = SecretAdwaresLogic::SearchOpen( $ioTable_ , $iLoginUserType_ , $LOGIN_ID );

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

			if( SecretAdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
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

			if( SecretAdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
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

			if( SecretAdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
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

			if( SecretAdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
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

			if( SecretAdwaresLogic::IsMine( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ parent::drawDeleteComp( $iGM_ , $iRec_ , 'owner' , $iLoginUserRank_ ); }
			else
				{ parent::drawDeleteComp( $iGM_ , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ ); }
		}

		/**
		 * 検索結果を描画。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawSearch( &$iGM_ , &$iSR_ , $iTable_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			SearchTableStack::pushStack( $iTable_ );

			if( $_GET[ 'exstyle' ] )
				{ $label = 'SEARCH_RESULT_DESIGN_' . strtoupper( $_GET[ 'exstyle' ] ); }
			else
				{ $label = 'SEARCH_RESULT_DESIGN'; }

			Template::drawTemplate( $iGM_[ self::$Type ] , null , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , $label );
		}

		/**
		 * 検索結果、該当なしを描画。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawSearchNotFound( &$iGM_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			if( $_GET[ 'exstyle' ] )
				{ $label = 'SEARCH_NOT_FOUND_DESIGN_' . strtoupper( $_GET[ 'exstyle' ] ); }
			else
				{ $label = 'SEARCH_NOT_FOUND_DESIGN'; }

			Template::drawTemplate( $iGM_[ self::$Type ] , null , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , $label );
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
			global $LOGIN_ID;
			global $HOME;

			global $LOGIN_ID;

			if( !SecretAdwaresLogic::IsOpen( $iRec_ , $iLoginUserType_ , $LOGIN_ID ) )
				{ $loginUserType = 'nobody'; }

			$iGM_[ self::$Type ]->setVariable( 'host' , $HOME );
			$iGM_[ self::$Type ]->setVariable( 'loginID' , $LOGIN_ID );

			Template::drawTemplate( $iGM_[ self::$Type ] , $iRec_ , $iLoginUserType_ , $iLoginUserRank_ , self::$Type , 'INFO_PAGE_DESIGN' , 'info.php?type=' . self::$Type . '&id=' . $_GET[ 'id' ] );
		}

		/**
		 * 検索結果をリスト描画する。
		 * ページ切り替えはこの領域で描画する必要はありません。
		 *
		 * @param gm GUIManagerオブジェクト
		 * @param table 検索結果のテーブルデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function getSearchResult( &$iGM_, $iTable_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $gm;

			$type = SearchTableStack::getType();

			if( $_GET[ 'exstyle' ] )
				{ $label = 'SEARCH_LIST_PAGE_DESIGN_' . strtoupper( $_GET[ 'exstyle' ] ); }
			else
				{ $label = 'SEARCH_LIST_PAGE_DESIGN'; }

			$partsName = SearchTableStack::getPartsName( 'list' );

			if( $partsName )
				{ return Template::getListTemplateString( $gm[ $type ] , $iTable_ , $iLoginUserType_ , $iLoginUserRank_ , $type , $label , false , $partsName ); }
			else
				{ return Template::getListTemplateString( $gm[ $type ] , $iTable_ , $iLoginUserType_ , $iLoginUserRank_ , $type , $label ); }
		}

		//■変数 //
		private static $Type = 'secretAdwares'; ///<テーブルの名前。
	}
