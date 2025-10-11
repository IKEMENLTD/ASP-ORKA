<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * htmlファイル読み込みクラス
	 *  このクラスはstaticなクラスです。インスタンスを生成せずに利用してください。
	 *
	 * @author 丹羽一智
	 * @version 3.0.0
	 *
	 * </PRE>
	 *******************************************************************************************************/

	class IncludeObject
	{
		/**
		 * 外部ファイルを読み込みます。
		 * コマンドコメントが存在する場合はGUIManagerオブジェクトとレコードデータを用いて処理をします。
		 * @param $file ファイル名
		 * @param $gm=null GUIManagerオブジェクト
		 * @param $rec=null レコードデータ
		 */
		// PHP 8 compatibility: Changed to static method as documented
		static function run($file, $gm = null, $rec = null)
		{
			if( !file_exists( $file ) )	{ throw new InternalErrorException('INCLUDEファイルが開けません。->'. $file); }

			$fp		 = fopen ($file, 'r');

		    $state = GUIManager::getDefState( true );
		    $c_part = null; // PHP 8: Cannot pass by-reference argument to static method inline
			while(!feof($fp))
			{
				$buffer	 = fgets($fp, 20480);
				$str	 = GUIManager::commandComment($buffer, $gm, $rec, $state , $c_part);

				$str	 = str_replace( Array("!CODE000;","!CODE001;"), Array("/"," "), $str );

				print DebugUtil::addFilePathComment( $str, $file );
			}
			fclose($fp);

		}

		/**
		 * 外部ファイルを読み込み、文字列データを返します。
		 * コマンドコメントが存在する場合はGUIManagerオブジェクトとレコードデータを用いて処理をします。
		 * @param $file ファイル名
		 * @param $gm=null GUIManagerオブジェクト
		 * @param $rec=null レコードデータ
		 */
		// PHP 8 compatibility: Changed to static method as documented
		static function get($file, $gm = null, $rec = null)
		{
			if( !file_exists( $file ) )	{ throw new InternalErrorException('INCLUDEファイルが開けません。->'. $file); }

			$fp		 = fopen ($file, 'r');
			$ret	 = "";
		    $state = GUIManager::getDefState( true );
		    $c_part = null; // PHP 8: Cannot pass by-reference argument to static method inline
			while(!feof($fp))
			{
				$buffer	 = fgets($fp, 20480);
				$ret	 .= GUIManager::commandComment($buffer, $gm, $rec, $state , $c_part);
			}
			fclose($fp);

			$ret = str_replace( Array("!CODE000;","!CODE001;"), Array("/"," "), $ret );

			return DebugUtil::addFilePathComment( $ret, $file );
		}

	}

	/********************************************************************************************************/
?>
