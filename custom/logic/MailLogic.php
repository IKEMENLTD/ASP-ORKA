<?php

	class MailLogic
	{
		
		/**
		 * 同一デザインで複数のアドレスにメールを送信
		 * 
		 * @param gm GMオブジェクト。
		 * @param rec メールを送信するレコード。
		 * @param desgin メールデザイン。
		 * @param mailList 送信先アドレスの配列。
		 */
		function maltiSend( $gm, $rec, $design, $mailList )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;
			// **************************************************************************************

			if( !is_array($mailList) ) { $mailList = array( $mailList ); }
			
			foreach( $mailList as $mail  )
			{
				if( strlen($mail) )
					Mail::send( $design , $MAILSEND_ADDRES, $mail, $gm, $rec, $MAILSEND_NAMES );
			
			}
		
		}

	
		/**
		 * アクティベート確認メールを送信
		 * 
		 * @param rec ユーザレコード。
		 * @param type ユーザテーブル名。
		 */
		function activateCheck( $rec, $type )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $loginUserType;
			global $loginUserRank;
			global $MAILSEND_ADDRES;
			// **************************************************************************************
			
			$gm = GMList::getGM($type);
			$db = $gm->getDB();
			
			// アクティベート確認メールを登録者/管理者に送信
			$design	 = Template::getTemplate( $loginUserType , $loginUserRank , $type , 'ACTIVATE_MAIL' );

			$mailList[] = $MAILSEND_ADDRES;
			$mailList[] = $db->getData( $rec, 'mail' );

			self::maltiSend( $gm, $rec, $design, $mailList );
		}
		
		
		/**
		 * ユーザ情報登録完了メールを送信
		 * 
		 * @param rec ユーザレコード。
		 * @param type ユーザテーブル名。
		 */
		function userRegistComp( $rec, $type )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $loginUserType;
			global $loginUserRank;
			global $MAILSEND_ADDRES;
			// **************************************************************************************
			
			$gm = GMList::getGM($type);
			$db = $gm->getDB();
			
			// ユーザ情報登録完了メールを登録者/管理者に送信
			$design	 = Template::getTemplate( $loginUserType , $loginUserRank , $type , 'REGIST_COMP_MAIL' );

			$mailList[] = $MAILSEND_ADDRES;
			$mailList[] = $db->getData( $rec, 'mail' );

			self::maltiSend( $gm, $rec, $design, $mailList );
		}

		/**
		 * ユーザ情報削除完了メールを送信
		 * 
		 * @param rec ユーザレコード。
		 * @param type ユーザテーブル名。
		 */
		function userDeleteComp( $rec, $type )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $loginUserType;
			global $loginUserRank;
			global $MAILSEND_ADDRES;
			// **************************************************************************************
			
			$gm = GMList::getGM($type);
			$db = $gm->getDB();
			
			// ユーザ情報登録完了メールを登録者/管理者に送信
			$design	 = Template::getTemplate( $loginUserType , $loginUserRank , $type , 'DELETE_COMP_MAIL' );

			$mailList[] = $MAILSEND_ADDRES;
			$mailList[] = $db->getData( $rec, 'mail' );

			self::maltiSend( $gm, $rec, $design, $mailList );
		}
	
		/**
		 * お問い合わせメールを送信
		 * 
		 * @param rec お問い合わせレコード。
		 */
		function inquiry( $rec )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $loginUserType;
			global $loginUserRank;
			global $MAILSEND_ADDRES;
			global $MAILSEND_NAMES;
			// **************************************************************************************
			
			$gm = GMList::getGM('inquiry');
			$db = $gm->getDB();
			
			// お問い合わせ確認メールをユーザーに送信
			$mail = $db->getData( $rec, 'mail' );
			$design	 = Template::getTemplate( $loginUserType , $loginUserRank , 'inquiry' , 'INQUIRY_MAIL' );
			Mail::send( $design , $MAILSEND_ADDRES, $mail, $gm, $rec, $MAILSEND_NAMES );

			// お問い合わせメールを管理者に送信
			$design	 = Template::getTemplate( 'admin' , $loginUserRank , 'inquiry' , 'INQUIRY_MAIL' );
			Mail::send( $design , $MAILSEND_ADDRES, $MAILSEND_ADDRES, $gm, $rec, $MAILSEND_NAMES );

		}
		

	}

?>