<?php

include "include/base/SystemBase.php";

/**
 * システムコールクラス
 * 
 * @author 丹羽一智
 * @version 1.0.0
 * 
 */
class System extends SystemBase
{
	/**********************************************************************************************************
	 * 汎用システム用メソッド
	 **********************************************************************************************************/
	
	
	
	
	
	
	
	
	
	

	/*
	 * 例外を自動の例外出力に回す前にキャッチして、内容によって別の処理に長し込む為のもの。
	 */
	static function manageExceptionView( $className ){
		global $gm;
		global $loginUserType;
		global $loginUserRank;
		global $NOT_LOGIN_USER_TYPE;
		global $THIS_TABLE_REGIST_USER;
        global $controllerName;

		if(is_null($gm)){
			// GUIManagerが生成される前のエラーなので、諦めて例外用のエラーを出している。
			return false;
		}
		
		switch($className){
			case "IllegalAccessException":
				//非ログインかどうか
				if( $loginUserType != $NOT_LOGIN_USER_TYPE ){
					return false;
				}
				
				//特定のユーザータイプでログインすれば見れるコンテンツかどうかをチェックする実装のサンプル。
				// regist.php?type=items に非ログインでアクセスした場合にメッセージを追加してログイン画面を表示しています。
				$type = $_GET['type'];
				//$db = $gm[$type]->getDB();
				if( $controllerName == "Register" && isset($THIS_TABLE_REGIST_USER[$type]) && array_search("cUser",$THIS_TABLE_REGIST_USER[$type]) !== false ){
				
					//ログインを促す場合。
					$gm[$type]->setVariable( 'message', "当該のページはログイン時にのみ表示可能なページです。" );
					
					Template::drawTemplate( $gm[$type] , $rec , $loginUserType , $loginUserRank , '' , 'LOGIN_PAGE_DESIGN' );
					return true;
				}
				
				//特にログイン後のページがない場合は、通常のエラー画面を表示する。
				
				break;
		}
		
		return false;
	}

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 登録関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * 複製登録条件確認。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param edit 編集なのか、新規追加なのかを真偽値で渡す。
		 * @return 複製登録が可能かを真偽値で返す。
		 */
		function duplicateCheck( &$gm, $loginUserType, $loginUserRank )
		{
			return parent::copyCheck($gm, $loginUserType, $loginUserRank);
		}
		

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 編集関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 削除関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * 削除内容確認。
	 *
	 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
	 * @param rec フォームのからの入力データを反映したレコードデータ。
	 * @return エラーがあるかを真偽値で渡す。
	 */
	function deleteCheck(&$gm, &$rec, $loginUserType, $loginUserRank )
	{

		return self::$checkData->getCheck();
	}

		/**
		 * 削除確認ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */

		function drawDeleteCheck( &$gm, &$rec, $loginUserType, $loginUserRank )
		{
		
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $DELETE_CHECK_PAGE_DESIGN;
			// **************************************************************************************
			global $TABLE_NAME;
			global $THIS_TABLE_IS_USERDATA;
			global $LOGIN_ID;
			global $NOT_LOGIN_USER_TYPE;
			// **************************************************************************************
			
			$db = $gm[ $_GET[ 'type' ] ]->getDB();

			switch(  $_GET['type']  )
			{
				default:
					// 汎用処理
                    Template::drawTemplate( $gm[ $_GET['type'] ] , $rec , $loginUserType , $loginUserRank , $_GET['type'] , 'DELETE_CHECK_PAGE_DESIGN' , 'edit.php?type='. $_GET['type'] .'&id='. $_GET['id'] );
			}
			
		
		}

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 検索関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * 検索処理。
		 * フォーム入力以外の方法で検索条件を設定したい場合に利用します。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param table フォームのからの入力内容に一致するレコードを格納したテーブルデータ。
		 */
		function searchProc( &$gm, &$table, $loginUserType, $loginUserRank )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $THIS_TABLE_IS_USERDATA;
			global $ACTIVE_ACTIVATE;
            global $ACTIVE_ACCEPT;
			// **************************************************************************************
			global $LOGIN_ID;
            global $HOME;
			// **************************************************************************************
			
			$db		 = $gm[ $_GET['type'] ]->getDB();
					
			switch( $_GET['type'] )
			{
				default:
					if(  $THIS_TABLE_IS_USERDATA[ $_GET['type'] ]  )
					{
						if( $loginUserType != 'admin' )	 { $table	 = $db->searchTable( $table, 'activate', 'in', array($ACTIVE_ACTIVATE ,$ACTIVE_ACCEPT) ); }
					}
					break;
			}
		}



		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 詳細情報関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		//   アクティベート関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

        //activate判定及びアクティベート完了処理
        function activateAction( &$gm, &$rec, $loginUserType, $loginUserRank ){
        global $ACTIVE_NONE;
        global $ACTIVE_ACTIVATE;
        global $MAILSEND_ADDRES;
        global $MAILSEND_NAMES;
		global $template_path;
		global $mobile_path;
        
            $db = $gm[ $_GET['type'] ]->getDB();
            
			if(  $db->getData( $rec, 'activate' ) == $ACTIVE_NONE  )
			{
				$db->updateRecord( $rec );

                $mail_template = Template::getTemplate('',1,$_GET['type'], "ACTIVATE_COMP_MAIL" );
				Mail::send( $mail_template , $MAILSEND_ADDRES, $db->getData( $rec, 'mail' ), $gm[ $_GET['type'] ], $rec , $MAILSEND_NAMES );
				Mail::send( $mail_template , $MAILSEND_ADDRES, $MAILSEND_ADDRES, $gm[ $_GET['type'] ], $rec , $MAILSEND_NAMES );
			}
            return true;
        }


		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		//   ログイン関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/



		/**********************************************************************************************************
		 * 汎用システム描画系用メソッド
		 **********************************************************************************************************/



		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 登録関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * 登録内容確認ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 登録情報を格納したレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawRegistCheck( &$gm, $rec, $loginUserType, $loginUserRank )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $REGIST_CHECK_PAGE_DESIGN;
			// **************************************************************************************
			global $TABLE_NAME;
			global $THIS_TABLE_IS_USERDATA;
			global $MAIL_SEND_FALED_DESIGN;
			global $LOGIN_PASSWD_COLUM;
			// **************************************************************************************
			
			// 確認入力系の補完
			if(  $THIS_TABLE_IS_USERDATA[ $_GET['type'] ]  )
			{
				foreach( $gm[ $_GET['type'] ]->colName as $name ){
					if( $LOGIN_PASSWD_COLUM[ $_GET['type'] ] != $name ){//マージしたらtypeがpasswordかを見る
						if( ($pos = strpos( $gm[ $_GET['type'] ]->colRegist[$name], 'ConfirmInput' ) ) !== FALSE ){
							$after = substr( $gm[ $_GET['type'] ]->colRegist[$name], $pos+13);
							if( ( $end = strpos( $after, '/') ) !== FALSE ){ $after = substr( $after, 0, $end ); }
							$db = $gm[$_GET['type']]->getDB();
							$gm[ $_GET['type'] ]->addHiddenForm( $after, $db->getData( $rec, $name ) );
						}
					}
				}
			}
			
			switch(  $_GET['type']  )
			{
				default:
					// 汎用処理
                    Template::drawTemplate( $gm[ $_GET['type'] ] , $rec , $loginUserType , $loginUserRank , $_GET['type'] , 'REGIST_CHECK_PAGE_DESIGN' , 'regist.php?type='. $_GET['type'] );
			}
		
		}

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 編集関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/**
		 * 編集内容確認ページを描画する。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param rec 編集対象のレコードデータ
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawEditCheck( &$gm, &$rec, $loginUserType, $loginUserRank )
		{
		
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $EDIT_CHECK_PAGE_DESIGN;
			global $LOGIN_ID;
            global $NOT_LOGIN_USER_TYPE;
			// **************************************************************************************
			
			$db = $gm[ $_GET[ 'type' ] ]->getDB();
			
			// 確認入力系の補完
			if(  $THIS_TABLE_IS_USERDATA[ $_GET['type'] ]  )
			{
				foreach( $gm[ $_GET['type'] ]->colName as $name ){
					if( $LOGIN_PASSWD_COLUM[ $_GET['type'] ] != $name ){//マージしたらtypeがpasswordかを見る
						if( ($pos = strpos( $gm[ $_GET['type'] ]->colRegist[$name], 'ConfirmInput' ) ) !== FALSE ){
							$after = substr( $gm[ $_GET['type'] ]->colRegist[$name], $pos+13);
							if( ( $end = strpos( $after, '/') ) !== FALSE ){ $after = substr( $after, 0, $end ); }
							$gm[ $_GET['type'] ]->addHiddenForm( $after, $db->getData( $rec, $name ) );
						}
					}
				}
			}
			
			switch(  $_GET['type']  )
			{
				default:
					// 汎用処理
                    Template::drawTemplate( $gm[ $_GET['type'] ] , $rec , $loginUserType , $loginUserRank , $_GET['type'] , 'EDIT_CHECK_PAGE_DESIGN' , 'edit.php?type='. $_GET['type'] .'&id='. $_GET['id'] );
			}
		
		}

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 削除関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 検索関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////


        function drawSearch( &$gm, &$sr, $table, $loginUserType, $loginUserRank ){
            SearchTableStack::pushStack($table);

            if(  isset( $_GET['multimail'] )  ){
                $db = $gm[ $_GET['type'] ]->getDB();
                $row	 = $db->getRow( $table );
                for($i=0; $i<$row; $i++){
                    $rec	 = $db->getRecord( $table, $i );
                    $_GET['receive_id'][] = $db->getData( $rec, 'id' );
                }
                $_GET['type'] = 'multimail';
                include_once "regist.php";
            }else{

			$label = 'SEARCH_RESULT_DESIGN';

			if( $_GET[ 'exstyle' ] )
				$label .= '_' . strtoupper( $_GET[ 'exstyle' ] );

            Template::drawTemplate( $gm[ $_GET['type'] ] , null , $loginUserType , $loginUserRank , $_GET['type'] , $label );
            }
        }

		/**
		 * 検索結果、該当なしを描画。
		 *
		 * @param gm GUIManagerオブジェクト配列　連想配列で gm[ TABLE名 ] でアクセスが可能です。
		 * @param loginUserType ログインしているユーザの種別
		 * @param loginUserRank ログインしているユーザの権限
		 */
		function drawSearchNotFound( &$gm, $loginUserType, $loginUserRank )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $SEARCH_NOT_FOUND_DESIGN;
			// **************************************************************************************
			global $NOT_LOGIN_USER_TYPE;
			// **************************************************************************************
			
			$label = 'SEARCH_NOT_FOUND_DESIGN';

			if( $_GET[ 'exstyle' ] )
				$label .= '_' . strtoupper( $_GET[ 'exstyle' ] );

			Template::drawTemplate( $gm[ $_GET['type'] ] , null , $loginUserType , $loginUserRank , $_GET['type'] , $label );
/*
			switch( $_GET['type'] )
			{					
				default:
					Template::drawTemplate( $gm[ $_GET['type'] ] , null , $loginUserType , $loginUserRank , $_GET['type'] , 'SEARCH_NOT_FOUND_DESIGN' );
					break;
			}
*/
		
		}


		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		// 詳細ページ関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

		/////////////////////////////////////////////////////////////////////////////////////////////////////////
		//   アクティベート関係
		/////////////////////////////////////////////////////////////////////////////////////////////////////////

        function drawActivateComp( &$gm, &$rec, $loginUserType, $loginUserRank ){
                $gm[ $_GET['type'] ]->draw( Template::getLabelFile('ACTIVATE_DESIGN_HTML'), $rec );
        }
        function drawActivateFaled( &$gm, &$rec, $loginUserType, $loginUserRank ){
                $gm[ $_GET['type'] ]->draw( Template::getLabelFile('ACTIVATE_FALED_DESIGN_HTML'), $rec );
        }

		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/


		/*
		 * ページ全体で共通のheadを返する。
		 * 各種表示ページの最初に呼び出される関数
		 * 
		 * 出力に制限をかけたい場合や分岐したい場合はここで分岐処理を記載する。
		 */
		static function getHead($gm,$loginUserType,$loginUserRank){
			global $NOT_LOGIN_USER_TYPE;

			if( self::$head || isset( $_GET['hfnull'] ) ){ return "";}

			self::$head = true;

			$html = "";

			if( $loginUserType == $NOT_LOGIN_USER_TYPE )	{ $html = Template::getTemplateString( null , null , $loginUserType , $loginUserRank , '' , 'HEAD_DESIGN' ); }
			else											{ $html = Template::getTemplateString( $gm[ $loginUserType ] , null , $loginUserType , $loginUserRank , '' , 'HEAD_DESIGN' ); }

			if(isset($_SESSION['ADMIN_MODE']) && $_SESSION['ADMIN_MODE']){
				$html .= Template::getTemplateString( $gm[ $loginUserType ] , null , $loginUserType , $loginUserRank , '' , 'HEAD_DESIGN_ADMIN_MODE' );
			}
			return $html;
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
		function getSearchResult( &$_gm, $table, $loginUserType, $loginUserRank )
		{
		
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $gm;
			// **************************************************************************************
			
			$type = SearchTableStack::getType();
			
			$label = 'SEARCH_LIST_PAGE_DESIGN';

			if( $_GET[ 'exstyle' ] )
				$label .= '_' . strtoupper( $_GET[ 'exstyle' ] );
			else
				$label = self::ModifyTemplateLabel( $label );

			switch( $type )
			{
				default:
				if(SearchTableStack::getPartsName('list'))
				{
					$file = Template::getTemplate( $loginUserType , $loginUserRank , $type , $label , false );

					if( is_file( $file ) )
						{ $html = Template::getListTemplateString( $gm[ $type ] , $table , $loginUserType , $loginUserRank , $type , $label , false , SearchTableStack::getPartsName('list') ); }
					else
						{ $html = Template::getListTemplateString( $gm[ $type ] , $table , $loginUserType , $loginUserRank , $type , 'SEARCH_LIST_PAGE_DESIGN' , false , SearchTableStack::getPartsName('list') ); }
				}
				else
				{
					$file = Template::getTemplate( $loginUserType , $loginUserRank , $type , $label , false );

					if( is_file( $file ) )
						{ $html = Template::getListTemplateString( $gm[ $type ] , $table , $loginUserType , $loginUserRank , $type , $label ); }
					else
						{ $html = Template::getListTemplateString( $gm[ $type ] , $table , $loginUserType , $loginUserRank , $type , 'SEARCH_LIST_PAGE_DESIGN' ); }
				}
			}
            return $html;
		}

		/**
		 * 検索結果描画。
		 *
		 * @param gm GUIManagerオブジェクトです。
		 * @param rec 登録情報のレコードデータです。
		 * @param args コマンドコメント引数配列です。　第一引数にIDを渡します。 第二引数にリンクするかを真偽値で渡します。
		 */
		function searchResult( &$gm, $rec, $args )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			
			global $loginUserType;
			global $loginUserRank;
			
			global $resultNum;
			global $pagejumpNum;
			global $phpName;
			// **************************************************************************************
			
			$db		 = $gm->getDB();
			
			$table   = SearchTableStack::getCurrent();
			$row	 = $db->getRow( $table );
		
			// 変数の初期化。
			if(  !isset( $_GET['page'] )  ){ $_GET['page']	 = 0; }
			
			if( 0 < $_GET[ 'page' ] ) //ページが指定されている場合
			{
				$beginRow = $_GET[ 'page' ] * $resultNum; //ページ内の最初のレコードの行数
				$tableRow = $db->getRow( $table );        //テーブルの行数

				if( $tableRow <= $beginRow ) //テーブルの行数を超えている場合
				{
					$maxPage = ( int )( ( $tableRow - 1 ) / $resultNum ); //表示可能な最大ページ

					$_GET[ 'page' ] = $maxPage;
				}
			}

			if(  $_GET['page'] < 0 || $_GET['page'] * $resultNum + 1 > $db->getRow( $table )  )
			{
				// 検索結果を表示するページがおかしい場合

                $tgm	 = SystemUtil::getGM();
                for($i=0; $i<count((array)$TABLE_NAME); $i++)
                {
                    $tgm[ $_GET['type'] ]->addAlias(  $TABLE_NAME[$i], $tgm[ $TABLE_NAME[$i] ]  );	
                }
				$this->drawSearchError( $tgm , $loginUserType, $loginUserRank );
			}
			else
			{
				// 検索結果情報を出力。
				$viewTable	 = $db->limitOffset(  $table, $_GET['page'] * $resultNum, $resultNum  );
				
				switch( $args[0] )
				{
					case 'info':
						// 検索結果情報データ生成
						$gm->setVariable( 'RES_ROW', $row );
						
						$gm->setVariable( 'VIEW_BEGIN', $_GET['page'] * $resultNum + 1 );
						if( $row >= $_GET['page'] * $resultNum + $resultNum )
						{
							$gm->setVariable( 'VIEW_END', $_GET['page'] * $resultNum + $resultNum );
							$gm->setVariable( 'VIEW_ROW', $resultNum );
						}
						else
						{
							$gm->setVariable( 'VIEW_END', $row );
							$gm->setVariable( 'VIEW_ROW', $row % $resultNum );
						}
						$this->addBuffer( $this->getSearchInfo( $gm, $viewTable, $loginUserType, $loginUserRank ) );
						
						break;
						
					case 'result':
						// 検索結果をリスト表示
						for($i=0; $i<count((array)$TABLE_NAME); $i++)
						{
							$tgm[ $_GET['type'] ]->addAlias(  $TABLE_NAME[$i], $tgm[ $TABLE_NAME[$i] ]  );	
						}
						$this->addBuffer( $this->getSearchResult( $gm, $viewTable, $loginUserType, $loginUserRank ) );
						break;
					case 'pageChange':
						$this->addBuffer( $this->getSearchPageChange( $gm, $viewTable, $loginUserType, $loginUserRank, $row, $pagejumpNum, $resultNum, $phpName, 'page' )  );
						break;
					case 'setResultNum':
						$resultNum				 = $args[1];
						break;
						
					case 'setPagejumpNum':
						$pagejumpNum			 = $args[1];
						break;
						
					case 'setPhpName': // ページャーのリンクphpファイルを指定(未設定時はsearch.php)
						$phpName				 = $args[1];
						break;
					case 'row':
						$this->addBuffer( $row );
						break;
				}
			}
		}

	}

?>
