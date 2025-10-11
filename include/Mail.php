<?php

	/***************************************************************************************************<pre>
	 *
	 * メール送信クラス
	 *  staticなクラスなので、インスタンスを生成せずに利用してください。
	 *
	 * @author 丹羽一智
	 * @version 3.0.0<br/>
	 *
	 * SendGrid統合対応（環境変数USE_SENDGRID=trueで有効化）
	 *
	 * </pre>
	 ********************************************************************************************************/

	require_once __DIR__ . '/SendGridMailer.php';

	class Mail
	{
        private static $_DEBUG	 = false;
        private static $sendGridMailer = null;

        /**
         * SendGrid使用判定
         */
        private static function useSendGrid()
        {
            $useSendGrid = getenv('USE_SENDGRID');
            return ($useSendGrid === 'true' || $useSendGrid === '1');
        }

        /**
         * SendGridMailerインスタンス取得
         */
        private static function getSendGridMailer()
        {
            if (self::$sendGridMailer === null) {
                self::$sendGridMailer = new SendGridMailer();
            }
            return self::$sendGridMailer;
        }
		/**
		 * メールの送信。
		 * 外部データとなるメールファイルの内容でメールを送信する。
		 * コマンドコメントは$gm に渡ってきたGUIManagerオブジェクトと
		 * $rec に渡ってきたレコードデータで処理をします。
		 * また、メールデータの sub パートは題名として、 main パートは本文として処理されます。
		 *
		 * @param $mail メールファイル
		 * @param $from 送信元メールアドレス
		 * @param $to 送信先メールアドレス
		 * @param $gm=null GUIManagerオブジェクト
		 * @param $rec=null レコードデータ
         * @param $from_name 送信元名 *省略可能
		 */
		static function send($mail, $from, $to, $gm = null, $rec = null, $from_name = null, $ccs = null, $bccs = null )
		{
			global $MAILSEND_ADDRES;
            
            if(is_null($from_name)){
                $from_str = 'From:'. trim($from);
            }else{
                $from_str = 'From:"'.mb_encode_mimeheader($from_name).'" <'. trim($from).'>';
            }
			
			if(  isset( $gm )  ){
				$sub	 = str_replace(  "\n", "", $gm->getString($mail, $rec, 'subject') );
				$main	 = str_replace(  "<br/>", "\n", $gm->getString($mail, $rec, 'main') );
			}else{
				$sub	 = stripslashes( str_replace(  "\n", "", GUIManager::partGetString($mail, 'subject') ) );
				$main	 = str_replace(  "<br/>", "\n", GUIManager::partGetString($mail, 'main') );
			}
			
            if( !is_null($ccs) && is_array($ccs) ){
                foreach( $ccs as $cc ){
                    if(!isset($cc['name'])){
                        $from_str .= "\n".'Cc:'. trim($cc['mail']);
                    }else{
                        $from_str .= "\n".'Cc:"'.mb_encode_mimeheader($cc['name']).'" <'. trim($cc['mail']).'>';
                    }
                }
            }
			
            if( !is_null($bccs) && is_array($bccs) ){
                foreach( $bccs as $bcc ){
                    if(!isset($bcc['name'])){
                        $from_str .= "\n".'Bcc:'. trim($bcc['mail']);
                    }else{
                        $from_str .= "\n".'Bcc:"'.mb_encode_mimeheader($bcc['name']).'" <'. trim($bcc['mail']).'>';
                    }
                }
            }
			
			if($to == $MAILSEND_ADDRES){
				$main .= '-----------------------------------'."\n";
				$main .= 'REMOTE_HOST：'.$_SERVER["REMOTE_ADDR"]."\n";
				$main .= 'HTTP_USER_AGENT：'.$_SERVER["HTTP_USER_AGENT"]."\n";
				$main .= '-----------------------------------'."\n";
			}
			
			mb_language("ja");
			$sub = str_replace("\n", "", $sub);
			$sub = str_replace("\r", "", $sub);

			$main = str_replace("\r", "", $main);
			
			//半角カナを全角カナへ
			$sub = SystemUtil::hankakukana2zenkakukana($sub);
			$main = SystemUtil::hankakukana2zenkakukana($main);
			

            if(self::$_DEBUG){
            	d($to,'to');
				d($sub,'sub');
				d($main,'main');
				d($from_str,'from_str');
				d($from,'from');
            }

            // SendGrid使用判定
            if (self::useSendGrid()) {
                try {
                    $mailer = self::getSendGridMailer();
                    $rcd = $mailer->send($to, $sub, $main, $from, $from_name, $ccs, $bccs);
                } catch (Exception $e) {
                    error_log("SendGrid Error: " . $e->getMessage());
                    $rcd = false;
                }
            } else {
                $rcd = mb_send_mail( $to, $sub, $main, $from_str , '-f ' . trim($from));
            }
		}



		/**
		 * 添付ファイル付きメールの送信。
		 * 外部データとなるメールファイルの内容でメールを送信する。
		 * コマンドコメントは$gm に渡ってきたGUIManagerオブジェクトと
		 * $rec に渡ってきたレコードデータで処理をします。
		 * また、メールデータの sub パートは題名として、 main パートは本文として処理されます。
		 *
		 * @param $mail メールファイル
		 * @param $from 送信元メールアドレス
		 * @param $to 送信先メールアドレス
		 * @param $gm=null GUIManagerオブジェクト
		 * @param $rec=null レコードデータ
         * @param $from_name 送信元名 *省略可能
		 */
		static function sendAttach($mail, $from, $to, $gm = null, $rec = null, $attach = null, $from_name = null )
		{
			global $MAILSEND_ADDRES;
			global $HOME;
			
			if( $attach != '' && is_file( $attach ) )
			{// 添付ファイルがある場合
				if(  isset( $gm ) && isset( $rec )  )
				{
					$sub	 = str_replace(  "\n", "", $gm->getString($mail, $rec, 'subject') );
					$main	 = str_replace(  "<br/>", "\n", $gm->getString($mail, $rec, 'main') );
				}
				else
				{
					$sub	 = stripslashes( str_replace(  "\n", "", GUIManager::partGetString($mail, 'subject') ) );
					$main	 = str_replace(  "<br/>", "\n", GUIManager::partGetString($mail, 'main') );
				}
				
				if($to == $MAILSEND_ADDRES)
				{
					$main .= '-----------------------------------'."\n";
					$main .= 'REMOTE_HOST：'.$_SERVER["REMOTE_ADDR"]."\n";
					$main .= 'HTTP_USER_AGENT：'.$_SERVER["HTTP_USER_AGENT"]."\n";
					$main .= '-----------------------------------'."\n";
				}
	
				mb_language("japanese");
				$sub = str_replace("\n", "", $sub);
				$sub = str_replace("\r", "", $sub);
				
				$main = str_replace("\r", "", $main);

				$sub = SystemUtil::hankakukana2zenkakukana($sub);
				$main = SystemUtil::hankakukana2zenkakukana($main);
				
				//画像の取得と画像のエンコード
				if(  strpos( $attach, ".pdf" ) || strpos( $attach, ".PDF" ) )
				{
					$type		 = 'application/pdf';
					$img_name	 = "data.pdf";
				}
				else
				{
					$img_name			 = "image";
					list($width, $height,$type) = getimagesize($attach);
					switch( $type )
					{
						case '1':
							$type		 = 'image/gif';
							$img_name	.= '.gif';
							break;
						case '2':
							$type		 = 'image/jpeg';
							$img_name	.= '.jpeg';
							break;
						case '3':
							$type		 = 'image/png';
							$img_name	.= '.png';
							break;
					}	
				}
				
				$img				 = file_get_contents($HOME.$attach);
				$img_encode64_000	 = chunk_split(base64_encode($img));
				
				//ヘッダ情報
                if(is_null($from_name))
    				$headers  = "From:" . trim($from). "\r\n";
                else
                    $headers  = 'From:"'.mb_encode_mimeheader($from_name).'" <'. trim($from).'>'. "\r\n";
                
				$headers .= 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-Type: multipart/related;boundary="1000000000"' . "\r\n";
			
//テキストパート
$message =<<<END

--1000000000
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 7bit

$main

--1000000000
Content-Type: $type; name="$img_name"
Content-Disposition: attachment; filename="$img_name"
Content-Transfer-Encoding: base64
Content-Disposition: inline;  filename="$img_name"

$img_encode64_000

--1000000000--

END;

                // SendGrid使用判定
                if (self::useSendGrid()) {
                    try {
                        $mailer = self::getSendGridMailer();
                        $rcd = $mailer->sendWithAttachment($to, $sub, $main, $from, $from_name, $attach);
                    } catch (Exception $e) {
                        error_log("SendGrid Attachment Error: " . $e->getMessage());
                        $rcd = false;
                    }
                } else {
                    $sub	 = mb_encode_mimeheader($sub);// テキストだといける
                    $message = mb_convert_encoding($message, "UTF-8");
                    $rcd 	 = mail( $to, $sub, $message, $headers );
                }

			}
			else {
				self::send( $mail, $from, $to, $gm, $rec, $from_name );
			} // 添付ファイルが無い場合

		}

		/**
		 * メールの送信。
		 * 文字列を直接指定してメールを送信します。
		 *
		 * @param $sub タイトル文字列
		 * @param $main 本文文字列
		 * @param $from 送信元メールアドレス
		 * @param $to 送信先メールアドレス
         * @param $from_name 送信元名 *省略可能
		 */
		static function sendString($sub, $main, $from, $to , $from_name = null)
		{
            if(is_null($from_name)){
                $from_str = 'From:'. trim($from);
            }else{
                $from_str = 'From:"'.mb_encode_mimeheader($from_name).'" <'. trim($from).'>';
            }
			mb_language("ja");

			$main = str_replace("\r", "", $main);

			$sub = SystemUtil::hankakukana2zenkakukana($sub);
			$main = SystemUtil::hankakukana2zenkakukana($main);

            // SendGrid使用判定
            if (self::useSendGrid()) {
                try {
                    $mailer = self::getSendGridMailer();
                    $processedMain = str_replace( "<br/>", "\n", stripslashes($main) );
                    $rcd = $mailer->send($to, stripslashes($sub), $processedMain, $from, $from_name);
                } catch (Exception $e) {
                    error_log("SendGrid String Error: " . $e->getMessage());
                    $rcd = false;
                }
            } else {
                $rcd = mb_send_mail(  $to, stripslashes( $sub ), str_replace( "<br/>", "\n", stripslashes($main) ), $from_str ,'-f ' . trim($from));
            }
		}
        static function onDebug(){ self::$_DEBUG = true; }
        static function offDebug(){ self::$_DEBUG = false; }
	}

	/********************************************************************************************************/
?>