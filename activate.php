<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * activate.php - 専用プログラム
	 * ユーザのアクティベーション処理を行うプログラム。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';

		//パラメータチェック
		ConceptCheck::IsEssential( $_GET , Array( 'type' , 'id' , 'md5' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'type' , 'id' , 'md5' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'id' , 'md5' ) );

		if( !$THIS_TABLE_IS_USERDATA[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . 'はユーザーテーブルではありません' );

		if( !$gm[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . 'は定義されていません' );

		if( $_GET[ 'type' ] == 'admin' )
				throw new IllegalAccessException( $_GET[ 'type' ] . 'は操作できません' );
		//パラメータチェックここまで

		print System::getHead( $gm , $loginUserType , $loginUserRank );

		//認証するユーザーを検索
		$db    = $gm[ $_GET[ 'type' ] ]->getDB();
		$table = $db->getTable();
		$table = $db->searchTable( $table , 'id' , '=' , $_GET[ 'id' ] );

		if( $db->getRow( $table ) <= 0 ) //ユーザーが見つからない
			throw new RuntimeException( $_GET[ 'type' ] . 'の' . $_GET[ 'id' ] . 'は存在しないレコードです' );

		//認証用MD5を計算する
		$rec = $db->getRecord( $table , 0 );
		$id  = $db->getData( $rec , 'id' );
		$mail = $db->getData( $rec , 'mail' );
		$md5 = md5( $id . $mail );

		if( $md5 != $_GET[ 'md5' ] ) //認証用MD5が一致しない
			throw new RuntimeException( 'MD5が一致しません' );

		//ユーザーを認証する
		$sys     = SystemUtil::getSystem( $_GET[ 'type' ] );
		$success = $sys->activateAction( $gm , $rec , $loginUserType , $loginUserRank );

		//画面出力
		if( $success ) //認証に成功した場合
			$sys->drawActivateComp( $gm , $rec , $loginUserType , $loginUserRank );
		else //認証に失敗した場合
			$sys->drawActivateFaled( $gm , $rec , $loginUserType , $loginUserRank );

		print System::getFoot( $gm , $loginUserType , $loginUserRank );
	}
	catch( Exception $e_ )
	{
		ob_end_clean();

		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}
	ob_end_flush();
?>