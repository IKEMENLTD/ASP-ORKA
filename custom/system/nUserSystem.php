<?php

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class nUserSystem extends System
	{
		//■処理

		/**
		 * 登録前段階処理。
		 * フォーム入力以外の方法でデータを登録する場合は、ここでレコードに値を代入します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec フォームのからの入力データを反映したレコードデータ。
		 */
		function registProc( &$iGM_ , &$ioRec_ , $iLoginUserType_ , $iLoginUserRank_ ,$iCheck_ = false )
		{
			NUserLogic::SetDefaultParameter( $ioRec_ );
			NUserLogic::SetParentID( $ioRec_ , isset($_SESSION[ 'friend' ]) ? $_SESSION[ 'friend' ] : '' );
			NUserLogic::SetClientTerminalType( $ioRec_ );

			parent::registProc( $iGM_ , $ioRec_ , $iLoginUserType_ , $iLoginUserRank_ , $iCheck_ );
		}

		/**
		 * 登録処理完了処理。
		 * 登録完了時にメールで内容を通知したい場合などに用います。
		 * 
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec レコードデータ。
		 */
		function registComp( &$iGM_ , &$ioRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			if( isset($_SESSION[ 'friend' ]) && $_SESSION[ 'friend' ] ) //親IDが指定されている場合
				{ $_SESSION[ 'friend' ] = ''; }

			NUserLogic::SendActivateMail( $ioRec_ );

			parent::registComp( $iGM_ , $ioRec_ , $iLoginUserType_ , $iLoginUserRank_ );
		}

		/**
		 * 詳細情報が閲覧されたときに表示して良い情報かを返すメソッド。
		 * activateカラムや公開可否フラグ、registやupdate等による表示期間の設定、アクセス権限によるフィルタなどを行います。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec アクセスされたレコードデータ。
		 * @return 表示して良いかどうかを真偽値で渡す。
		 */
		function infoCheck( &$iGM_ , &$ioRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $ACTIVE_NONE;

			if( 'admin' != $iLoginUserType_ ) //管理者以外の場合
			{
				$db       = SystemUtil::getGMforType( self::$Type )->getDB();
				$activate = $db->getData( $ioRec_ , 'activate' );

				if( $ACTIVE_NONE >= $activate ) //未アクティベートの場合
					{ return false; }
			}

			return true;
		}

		/**
		 * 詳細情報前処理。
		 * 簡易情報変更で利用
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec アクセスされたレコードデータ。
		 */
		function infoProc( &$iGM_, &$ioRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			if( isset( $_POST[ 'post' ] ) ) //POSTクエリが存在する場合
			{
				if( 'admin' == $iLoginUserType_ ) //管理者の場合
					{ NUserLogic::QuickUpdate( $ioRec_ , $_POST ); }
			}
		}

		/**
		 * アクティベート処理。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec アクセスされたレコードデータ。
		 */
		function activateAction( &$iGM_ , &$ioRec_ , $iLoginUserType_ , $iLoginUserRank_ )
		{
			global $ACTIVE_NONE;

			$db       = SystemUtil::getGMforType( 'nUser' )->getDB();
			$activate = $db->getData( $ioRec_ , 'activate' );

			if( $ACTIVE_NONE == $activate )
			{
				$db->setData( $ioRec_ , 'activate' , SystemUtil::getSystemData( 'nuser_accept_admin' ) );
				$db->updateRecord( $ioRec_ );

				NUserLogic::SendActivateMail( $ioRec_ );

				return true;
			}

			return false;
		}

		//■変数 //
		private static $Type = 'nUser'; ///<テーブルの名前。
	}
