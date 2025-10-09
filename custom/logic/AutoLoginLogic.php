<?php

	//★クラス //

	class AutoLoginLogic //
	{
		//■処理 //

		/**
			@brief 自動ログイン情報を削除する。
		*/
		function DeleteLoginInfo() //
		{

			$path   = SystemInfo::GetRealBaseURL();;

			$cookieID = SystemUtil::getCookieUtil( $path . '_LIID' );
			$tokenID  = SystemUtil::getCookieUtil( $path . '_LIID_T' );
			$fileID   = md5( $cookieID . self::$Seed );

			if( is_file( 'file/login/' . $fileID ) ) //ログイン情報がある場合
			{
				$fp   = fopen( 'file/login/' . $fileID , 'rb' );
				$data = fgets( $fp );

				fclose( $fp );

				List( $userType , $userID ) = explode( ',' , $data );

				if( $tokenID == md5( $userType . '/' . $userID ) ) //ログインターゲットが一致する場合
					{ unlink( 'file/login/' . $fileID ); }
			}

			SystemUtil::deleteCookieUtil( $path . '_LIID' );
			SystemUtil::deleteCookieUtil( $path . '_LIID_T' );
		}

		/**
			@brief      自動ログイン情報を取得する。
			@param[out] $oUserType ユーザー種別。
			@param[out] $oUserRank ユーザー認証レベル。
			@param[out] $oUserID   ユーザーID。
		*/
		function LoadLoginInfo( &$oUserType , &$oUserRank , &$oUserID ) //
		{
			$path   = SystemInfo::GetRealBaseURL();

			$cookieID = SystemUtil::getCookieUtil( $path . '_LIID' );
			$tokenID  = SystemUtil::getCookieUtil( $path . '_LIID_T' );
			$fileID   = md5( $cookieID . self::$Seed );

			if( !$fileID || !is_file( 'file/login/' . $fileID ) ) //ログイン情報が見つからない場合
				{ return false; }

			$fp   = fopen( 'file/login/' . $fileID , 'rb' );
			$data = fgets( $fp );

			fclose( $fp );

			List( $userType , $userID ) = explode( ',' , $data );

			if( $tokenID != md5( $userType . '/' . $userID ) ) //ログインターゲットが一致しない場合
				{ return false; }

			$oUserType = $userType;
			$oUserID   = $userID;

			SystemUtil::login( $oUserID , $oUserType );
			self::UpdateLoginInfo();

			$db    = GMList::getDB( $userType );
			$table = $db->searchTable( $db->getTable() , 'id' , '=' , $userID );
			$table = $db->limitOffset( $table , 0 , 1 );
			$rec   = $db->getRecord( $table , 0 );
			$rank  = $db->getData( $rec , 'activate' );

			$oUserRank = $rank;

			return true;
		}

		/**
			@brief     自動ログイン情報を記録する。
			@param[in] $iUserType ユーザー種別。
			@param[in] $iUserID   ユーザーID。
		*/
		function SaveLoginInfo( $iUserType , $iUserID ) //
		{
			self::DeleteLoginInfo();

			$cookieID = md5( rand() );
			$fileID   = md5( $cookieID . self::$Seed );

			while( is_file( 'file/login/' . $fileID ) ) //記録名が重複する限り繰り返し
			{
				$cookieID = md5( rand() );
				$fileID   = md5( $cookieID . self::$Seed );
			}

			$fp = fopen( 'file/login/' . $fileID , 'wb' );

			fputs( $fp , $iUserType  . ',' . $iUserID );
			fclose( $fp );

			$path   = SystemInfo::GetRealBaseURL();

			SystemUtil::setCookieUtil( $path . '_LIID' , $cookieID );
			SystemUtil::setCookieUtil( $path . '_LIID_T' , md5( $iUserType . '/' . $iUserID ) );
		}

		/**
			@brief 自動ログイン情報の認証を更新する。
		*/
		function UpdateLoginInfo() //
		{
            $path   = SystemInfo::GetRealBaseURL();

			$cookieID = SystemUtil::getCookieUtil( $path . '_LIID' );
			$fileID   = md5( $cookieID . self::$Seed );

			if( is_file( 'file/login/' . $fileID ) ) //ログイン情報がある場合
			{
				$newCookieID = md5( rand() );
				$newFileID   = md5( $newCookieID . self::$Seed );

				rename( 'file/login/' . $fileID , 'file/login/' . $newFileID );
				touch( 'file/login/' . $newFileID );

				SystemUtil::setCookieUtil( $path . '_LIID' , $newCookieID );
			}
		}

		//■変数 //

		private static $Seed = 'seed'; ///<自動ログイン情報のランダムシード
	}
