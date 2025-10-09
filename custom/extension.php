<?php

	include_once "include/base/ExtensionBase.php";

	/**
	 * 拡張命令クラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class Extension extends ExtensionBase
	{
		/**********************************************************************************************************
		 *　アプリケーション固有メソッド
		 **********************************************************************************************************/

		function loginUserType( &$gm, $rec , $args ){

			global $loginUserType;
			$this->addBuffer($loginUserType);

		}

		/**
		 * 時間に応じた挨拶を表示します。
		 *
		 * @param gm GUIManagerオブジェクトです。このメソッドでは利用しません。
		 * @param rec 登録情報のレコードデータです。このメソッドでは利用しません。
		 * @param args コマンドコメント引数配列です。このメソッドでは利用しません。
		 */
		function hello( &$gm, $rec, $args )
		{
		
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			// **************************************************************************************
			
			$message	 = "";
			switch(  date( "G", time() )  )
			{
				case '0':
				case '1':
				case '2':
				case '3':
					$message = '遅くまでご苦労様です。';
					break;
					
				case '4':
				case '5':
				case '6':
				case '7':
				case '8':
				case '9':
				case '10':
					$message = 'おはようございます。';
					break;
					
				case '11':
				case '12':
				case '13':
				case '14':
				case '15':
				case '16':
					$message = 'こんにちは。';
					break;
					
				case '17':
				case '18':
				case '19':
					$message = 'こんばんは。';
					break;
					
				case '20':
				case '21':
				case '22':
				case '23':
					$message = '遅くまでご苦労様です。';
					break;
			}
			
			$this->addBuffer( $message );
		}


		/**
		 * ログインユーザ固有情報の出力
		 *
		 */
		function getUserProfile( &$gm, $rec, $args )
		{
			// ** conf.php で定義した定数の中で、利用したい定数をココに列挙する。 *******************
			global $LOGIN_ID;
			global $loginUserType;
			// **************************************************************************************
			
			$colum	 = $args[0];
			switch($colum)
			{
			case 'pass':
				break;
			default:
				$this->addBuffer( SystemUtil::getTableData( $loginUserType, $LOGIN_ID, $colum ) );
				break;
			}
		}
	


        /**
         * サイト固有情報の出力
         *
         * args データを示す文字列
         */
		function getSiteProfile( &$gm, $rec, $args ){
        global $HOME;
        global $PARENT_MAX_ROW;
        
            switch($args[0]){
                case 'home':
                    $this->addBuffer( $HOME );
                    break;
				case 'parent_limit':
                    $this->addBuffer( $PARENT_MAX_ROW );
                    break;
				case 'adwares_pass':
                case 'site_title':
                case 'keywords':
                case 'description':
                case 'uuid':
                case 'nuser_default_activate':
                case 'nuser_accept_admin':
                case 'sales_auto':
                    $sgm = SystemUtil::getGMforType('system');
                    $sdb = $sgm->getDB();
                    $rec = $sdb->getRecord( $sdb->getTable() , 0 );
                    $this->addBuffer( $sdb->getData( $rec , $args[0] ) );
                    break;
            }
        }
		
		/**
		 * マージンに関する情報を取得します。
		 *
		 * @param gm GUIManagerオブジェクトです。
		 * @param rec 登録情報のレコードデータです。
		 * @param args コマンドコメント引数配列です。このメソッドでは利用しません。
		 */
		function getCostType( &$gm, $rec, $args ){
		
			global $ADWARES_EXCHANGE;
		
			switch( $args[0] ){
				case 'name':
					$tgm		 = SystemUtil::getGM();
					$adb	 = $tgm['adwares']->getDB();
					$atable	 = $adb->searchTable( $adb->getTable(), 'id', '=', $args[1] );
					if(  $adb->getRow( $atable ) != 0  ){
						$arec	 = $adb->getRecord( $atable, 0 );
						$this->addBuffer(  $adb->getData( $arec, 'name' ) );
					}
					break;
				case 'money':

					if($args[2] == "yen"){
						$this->addBuffer( $gm->getCCResult( $rec , '<!--# code comma ' . $args[1] . ' #-->' )."円" );
					}else if($args[2] == "per"){
						$this->addBuffer( $args[1]."％" );
					}else if( $args[ 2 ] == 'rank' ){
						$this->addBuffer( "会員ランク適用" );
					}else{
						$this->addBuffer( "パーソナルレート適用" );
					}
					
					break;
			
				case 'exchangeLimit':
					$this->addBuffer( $ADWARES_EXCHANGE );
					break;
			}
		
		}
	
	
	
		/**
		 * arg[0]で取得した値を、DATE型に変換します。
		 *
		 * @param gm GUIManagerオブジェクトです。このメソッドでは利用しません。
		 * @param rec 登録情報のレコードデータです。このメソッドでは利用しません。
		 * @param args コマンドコメント引数配列です。第一引数にステータスを渡します。
		 */
		function drawDate( &$gm, $rec, $args )
		{
			$time = $args[0];
			$this->addBuffer( date("Y/m/d/ G:i:s",$time) );
		}
		
		/**
		 * 成果の認証ラジオボタンを描画します。
		 *
		 * @param gm GUIManagerオブジェクトです。このメソッドでは利用しません。
		 * @param rec 登録情報のレコードデータです。このメソッドでは利用しません。
		 * @param args コマンドコメント引数配列です。第一引数にステータスを渡します。
		 */
		function drawStateRadio( &$gm, $rec, $args )
		{
			$currentStatus = $args[ 0 ];

			if( !$currentStatus )
			{
				$tmp = '
					<label><input type="radio" name="state" value="2" />認証</label>
					<label><input type="radio" name="state" value="1" />非認証</label>
				';
			}
			else
			{
				$checked[$args[0]] = ' checked="true"';
				$tmp = '
					<label><input type="radio" name="state" value="2"'.$checked[2].' />認証</label>
					<label><input type="radio" name="state" value="1"'.$checked[1].' />非認証</label>
				';
			}
		
			$this->addBuffer( $tmp );
		}


		/**
		 * 招待状の残送信数を表示します。
		 *
		 * @param gm GUIManagerオブジェクトです。このメソッドでは利用しません。
		 * @param rec 登録情報のレコードデータです。このメソッドでは利用しません。
		 * @param args コマンドコメント引数配列です。このメソッドでは利用しません。
		 */
		function invitationRow( &$gm, $rec, $args )
		{
			global $PARENT_MAX_ROW;
			global $LOGIN_ID;
			
			$tgm	 = SystemUtil::getGM();
			$ndb	 = $tgm['nUser']->getDB();
	
			$table	 = $ndb->getTable();
			
			if(isset($args[0]))
				$table	 = $ndb->searchTable( $table, 'parent', '=', $args[0] );
			else
				$table	 = $ndb->searchTable( $table, 'parent', '=', $LOGIN_ID );
		
			$tmp = $PARENT_MAX_ROW - $ndb->getRow( $table );
			if($tmp < 0) $tmp = 0;
		
			$this->addBuffer( $tmp );
		}


		/**
		 * 招待状制限のチェックを行います。
		 *
		 * @param gm GUIManagerオブジェクトです。このメソッドでは利用しません。
		 * @param rec 登録情報のレコードデータです。このメソッドでは利用しません。
		 * @param args コマンドコメント引数配列です。このメソッドでは利用しません。
		 */
		function invitationCheck( &$gm, $rec, $args )
		{
	        global $PARENT_MAX_ROW;
			global $LOGIN_ID;
			
			$tgm	 = SystemUtil::getGM();
			$ndb	 = $tgm['nUser']->getDB();
	
			$table	 = $ndb->getTable();
			$table	 = $ndb->searchTable( $table, 'parent', '=', $LOGIN_ID );
		
			if( $ndb->getRow( $table ) < $PARENT_MAX_ROW || $PARENT_MAX_ROW == "999"){
				$this->addBuffer( "1" );
			}else{
				$this->addBuffer( "0" );
			}
		}
	
		
		/**
		 * 条件分岐。
		 *
		 * @param gm GUIManagerオブジェクトです。
		 * @param rec 登録情報のレコードデータです。
		 * @param args コマンドコメント引数配列です。第一引数と第二引数の内容が一致した場合は　第三引数を、一致しなかった場合は第四引数を表示します。
		 */
		function ifelse( &$gm, $rec, $args ){
			if( $args[0] == $args[1] ){
				$this->addBuffer( $args[2] );
			}else{
				$this->addBuffer( $args[3] );
			}
		}
	
	
	
	
		
		/**
		 * 配当額を取得。
		 *
		 * @param gm GUIManagerオブジェクトです。
		 * @param rec 登録情報のレコードデータです。
		 * @param args コマンドコメント引数配列です。。
		 */
		function getExchange( &$gm, $rec, $args ){
		
			global $COOKIE_NAME;
			global $loginUserType;
            global $LOGIN_ID;
            global $ACTIVE_ACTIVATE;
		
			$tgm		 = SystemUtil::getGM();
			
            if( $loginUserType != 'admin' ){
                $id = $LOGIN_ID;
            }else if(  isset( $args[1] )  ){
                $id = $args[1];
            }
            
			switch($args[0]){
                case 'past':
                    $db		 = $tgm['returnss']->getDB();
                
                    $table	 = $db->getTable();
                    if( $id ){
                        $table	 = $db->searchTable( $table, 'owner', '=', $id );
                    }
                    $table	 = $db->searchTable( $table, 'state', '=', '入金済み' );
                    $sum = $db->getSum( 'cost' , $table );
                    break;
                case 'proc':
                    $db		 = $tgm['returnss']->getDB();
                
                    $table	 = $db->getTable();
                    if( $id ){
                        $table	 = $db->searchTable( $table, 'owner', '=', $id );
                    }
                    $table	 = $db->searchTable( $table, 'state', '!', '入金済み' );
                    $table	 = $db->searchTable( $table, 'state', '!', '差し戻し' );
                    $sum = $db->getSum( 'cost' , $table );
                    break;
                case 'unattestation':
                    $db		 = $tgm['pay']->getDB();
                
                    $table	 = $db->getTable();
                    if( $id ){
                        $table	 = $db->searchTable( $table, 'owner', '=', $id );
                    }
                    $table	 = $db->searchTable( $table, 'state', '=', 1 );//非認証
                    $sum = $db->getSum( 'cost' , $table );
                    
                    $db		 = $tgm['click_pay']->getDB();
                    
                    $table	 = $db->getTable();
                    if( $id ){
                        $table	 = $db->searchTable( $table, 'owner', '=', $id );
                    }
                    $table	 = $db->searchTable( $table, 'state', '=', 1 );//非認証
                    $sum += $db->getSum( 'cost' , $table );
                    break;
                case 'uncheck':
                    $db		 = $tgm['pay']->getDB();
                
                    $table	 = $db->getTable();
                    if( $id ){
                        $table	 = $db->searchTable( $table, 'owner', '=', $id );
                    }
                    $table	 = $db->searchTable( $table, 'state', '=', 0 );//未確認
                    $sum = $db->getSum( 'cost' , $table );
                    
                    $db		 = $tgm['click_pay']->getDB();
                    
                    $table	 = $db->getTable();
                    if( $id ){
                        $table	 = $db->searchTable( $table, 'owner', '=', $id );
                    }
                    $table	 = $db->searchTable( $table, 'state', '=', 0 );//未確認
                    $sum += $db->getSum( 'cost' , $table );
                    break;
                default:
                    $db		 = $tgm['nUser']->getDB();
                
                    if($id){
                        $rec	 = $db->selectRecord( $id );
                        $sum = $db->getData( $rec , 'pay' );
                    }else{
                        $sum = $db->getSum( 'pay' , $db->getTable() );
                    }
                    break;
			}
			
			$this->addBuffer( $sum );
		}
        
        //ティアを取得
		function getTier( &$gm, $rec, $args ){
            global $LOGIN_ID;
            global $loginUserType;
		
			$tgm		 = SystemUtil::getGM();
			
            $db = SystemUtil::getGMforType('nUser')->getDB();
            $sum = 0;
            
            if( $loginUserType != 'admin' ){
      		    $id = $LOGIN_ID;
            }else{
                if(  isset( $args[0] )  )
                    $id = $args[0];
                else{
                    $id =false;
                }
            }
            
            if($id){
                $rec = $db->selectRecord( $id );
                $sum = $db->getData( $rec , 'tier' );
            }else{
                $sum = $db->getSum( 'tier' , $db->getTable() );
            }
            $this->addBuffer( $sum );
        }
		
         
         function addParentKey( &$gm, $rec, $args ){
             if( isset($_GET['friend']) ){
                 $this->addBuffer( '&parent='.$_GET['friend'] );
             }
         }
		
		function plusNum( &$gm, $rec, $args ){
            $this->addBuffer( $args[0]+$args[1] );
         }
		
		
        function drawMailLink( &$gm, $rec, $args ){
            global $terminal_type;
            global $loginUserType;
            global $loginUserRank;
            
            
            $HTML = Template::getTemplate( $loginUserType , $loginUserRank , 'drawMailLink' , 'EXTENSION_PART_DESIGN' );
            
//            $HTML = "./html/mobile/Extension/friends_mail_link.html";
            switch($terminal_type){
                case 0:
                case 3:
                    //未エンコード
                    $this->addBuffer( $gm->getString( $HTML , $rec , 'nomal' ) );
                    break;
                case 1:
                case 2:
                    //Urlencode済み
                    $this->addBuffer( $gm->getString( $HTML , $rec ,  'encode' ) );
                    break;
            }
        }
        
        /*
          追加用のJS呼び出しを含むカテゴリの選択プルダウンの表示
        */
        function addCategorySelectForm( &$gm, $rec, $args ){
            $tgm = SystemUtil::getGMforType( 'category' );
            $db = $tgm->getDB();
            
            $table = $db->getTable();
            $row = $db->getRow( $table );
            
            $index = '未選択/';
            $value = '/';
            
            for($i=0;$i<$row;$i++){
                $rec = $db->getRecord( $table , $i );
                $index .= SystemUtil::systemArrayEscape($db->getData( $rec , 'name' ))."/";
                $value .= $db->getData( $rec , 'id' )."/";
            }
            $index .= "カテゴリを追加する";
            $value .= "add";
            
            $this->addBuffer( $gm->getCCResult( $rec, '<!--# form option category '.$check.' '.$value.' '.$index.' onchange="changeCategory(this)" #-->' ) );
        }

		function self(&$argGUIManager , $argRecord , $argParams)
		{
			global $loginUserType;
			global $LOGIN_ID;

			if('nobody' == $loginUserType)
				return;

			$guiManager = SystemUtil::getGMforType($loginUserType);
			$database   = $guiManager->getDB();
			$record     = $database->selectRecord($LOGIN_ID);

			$this->addBuffer($database->getData($record , $argParams[0]));
		}
        

		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
	
	
		/**********************************************************************************************************
		 * サイトシステム用メソッド
		 **********************************************************************************************************/
	
	
	

		
		//urlencode と charencodeでを一緒にやっちゃうすごいやつ。
        function ucencode( &$gm, $rec, $args ){
            if(isset($args[1])){
                $atgs[0] = mb_convert_encoding( $atgs[0] , $args[1] );
            }
            $this->addBuffer( urlencode( $args[0] ) );
        }
        
		
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/
		/*******************************************************************************************************/


        
	function tierList4day( &$gm, $rec , $args ){
		global $loginUserType;
		global $LOGIN_ID;

		$HTML = Template::getTemplate(  "" , 15 ,'tier4' , 'EXTENSION_PART_DESIGN' );
		
		$pgm = SystemUtil::getGMforType('tier');
		$db = $pgm->getDB();
		$table = $db->getTable();

		if( isset($_GET['registA']) ){
			$table = $db->searchTable( $table , 'regist' , '>' , $_GET['registA'] );
		}
		if( isset($_GET['registB']) ){
			$table = $db->searchTable( $table , 'regist' , '<' , $_GET['registB'] );
		}
		if( $loginUserType == 'nUser'){
			$table = $db->searchTable( $table , 'owner' , '=' , $LOGIN_ID );
		}else if( isset($_GET['id']) && strlen($_GET['id']) ){
			$table = $db->searchTable( $table , 'owner' , '=' , $_GET['id']  );
		}
		if( isset($_GET['tier']) && strlen($_GET['tier']) ){
			$table = $db->searchTable( $table , 'tier' , '=' , $_GET['tier'] );
		}
		if( isset($_GET['adwares']) && strlen($_GET['adwares']) ){
			$table = $db->searchTable( $table , 'adwares' , '=' , $_GET['adwares'] );
		}
		
		$table = $db->dateGroup( $table, 'regist','d' );
		$table = $db->addSelectColumn($table,'count(*) as cnt',false);
		$table = $db->addSelectColumn($table,'sum(cost) as sum',false);
		$table = $db->addSelectColumn($table,'sum(tier1) as tier1',false);
		$table = $db->addSelectColumn($table,'sum(tier2) as tier2',false);
		$table = $db->addSelectColumn($table,'sum(tier3) as tier3',false);

		$row = $db->getRow( $table );

		$list = Array();
			
		for( $i=0 ; $row>$i ; $i++ ){
			$payrec = $db->getRecord( $table, $i );
			$dategroup = $db->getData( $payrec, 'date_group' );
			$datelist = explode('-',$dategroup);
			$list[ (int)$datelist[2] ] = $payrec;
		}
			
		$return = $pgm->getString( $HTML , null , 'list_day_return' );
			
		$str = Array();
		
		$w = date('w',$_GET['registA']);
			
		if( $w ){
				
			for( $i= 0; $i < $w ; $i++ ){
				$str[0] .= $pgm->getString( $HTML , null , 'blank_head' );
				$str[1] .= $pgm->getString( $HTML , null , 'blank_body' );
			}
		}
		
		$max = (int)date('t',$regist);
		for($i=1; $max>=$i;$i++){
			$pgm->setVariable( 'day', $i );
			if( false ){
				$pgm->setVariable('class',' class="today"');
			}elseif( ($i-1+$w) % 7 == 6 ){
				$pgm->setVariable('class',' class="day_sat"');
			}elseif( ($i-1+$w) % 7 == 0 ){
				$pgm->setVariable('class',' class="day_sun"');
			}else{
				$pgm->setVariable('class','');
			}

			$str[0+(int)(($i-1+$w)/7)*2] .= $pgm->getString( $HTML , $list[$i] , 'list_day_head' );
			$str[1+(int)(($i-1+$w)/7)*2] .= $pgm->getString( $HTML , $list[$i] , 'list_day_body' );
		}
			
		for( $i= ($max+$w)%7; 7>$i && $i ; $i++ ){
			$str[0+(int)((($max+$w)-1)/7)*2] .= $pgm->getString( $HTML , null , 'blank_head' );
			$str[1+(int)((($max+$w)-1)/7)*2] .= $pgm->getString( $HTML , null , 'blank_body' );
		}
			
		$this->addBuffer( implode($return,$str) );
	}
	
	function tierList4month( &$gm, $rec , $args ){
		global $loginUserType;
		global $LOGIN_ID;

		$HTML = Template::getTemplate(  "" , 15 ,'tier4' , 'EXTENSION_PART_DESIGN' );

		$pgm = SystemUtil::getGMforType('tier');
		$db = $pgm->getDB();
		$table = $db->getTable();

		if( isset($_GET['registA']) ){
			$table = $db->searchTable( $table , 'regist' , '>' , $_GET['registA'] );
		}
		if( isset($_GET['registB']) ){
			$table = $db->searchTable( $table , 'regist' , '<' , $_GET['registB'] );
		}
		if( isset($_GET['id']) ){
			$table = $db->searchTable( $table , 'owner' , '=' , $_GET['id']  );
		}

		$table = $db->dateGroup( $table, 'regist','m' );
		$table = $db->addSelectColumn($table,'count(*) as cnt',false);
		$table = $db->addSelectColumn($table,'sum(cost) as sum',false);

		$row = $db->getRow( $table );
		
		if($row){
			$list = Array();
				
			for( $i=0 ; $row>$i ; $i++ ){
				$payrec = $db->getRecord( $table, $i );
				$dategroup = $db->getData( $payrec, 'date_group' );
				
				$datelist = explode('-',$dategroup);
				$list[ (int)$datelist[0] ][ (int)$datelist[1] ] = $payrec;
			}
			$return = $pgm->getString( $HTML , null , 'list_month_return' );
				
			foreach( $list as $year => $months ){
				$pgm->setVariable( 'year', $year );
				$this->addBuffer( $pgm->getString( $HTML , $rec , 'list_month_head' ) );

				$str = Array();
				for($i=1; 12>=$i;$i++){
					$pgm->setVariable( 'month', $i );
					$str[0+(int)(($i-1)/6)*2] .= $pgm->getString( $HTML , $months[$i] , 'list_month_row_head' );
					$str[1+(int)(($i-1)/6)*2] .= $pgm->getString( $HTML , $months[$i] , 'list_month_row_body' );
				}
				$this->addBuffer( implode($return,$str) );

				$this->addBuffer( $pgm->getString( $HTML , $rec , 'list_month_foot' ) );
			}
		}
	}

	function accessList4day( &$gm, $rec , $args ){
		global $loginUserType;
		global $LOGIN_ID;

		$HTML = Template::getTemplate(  "" , 15 ,'access4' , 'EXTENSION_PART_DESIGN' );

		$pgm = SystemUtil::getGMforType('access');
		$db = $pgm->getDB();
		$table = $db->getTable();
		
		if( isset($_GET['registA']) ){
			$table = $db->searchTable( $table , 'regist' , '>' , $_GET['registA'] );
		}
		if( isset($_GET['registB']) ){
			$table = $db->searchTable( $table , 'regist' , '<' , $_GET['registB'] );
		}
		if( $loginUserType == 'nUser'){
			$table = $db->searchTable( $table , 'owner' , '=' , $LOGIN_ID );
		}else if( isset($_GET['owner']) && strlen($_GET['owner']) ){
			$table = $db->searchTable( $table , 'owner' , '=' , $_GET['owner'] );
		}
		if( isset($_GET['adwares']) && strlen($_GET['adwares']) ){
			$table = $db->searchTable( $table , 'adwares' , '=' , $_GET['adwares'] );
		}
		
		$table = $db->dateGroup( $table, 'regist','d' );
		$table = $db->addSelectColumn($table,'count(*) as cnt',false);

		$row = $db->getRow( $table );
		
		if($row){
			$list = Array();
			
			for( $i=0 ; $row>$i ; $i++ ){
				$payrec = $db->getRecord( $table, $i );
				$dategroup = $db->getData( $payrec, 'date_group' );
				$datelist = explode('-',$dategroup);
				$list[ (int)$datelist[2] ] = $payrec;
			}
			$pgm->setVariable( 'year', date('Y',$_GET['registA']) );
			$pgm->setVariable( 'month', date('m',$_GET['registA']) );
			
			$return = $pgm->getString( $HTML , null , 'list_day_return' );
				
			$str = Array();
			$max = (int)date('t',$regist);
			for($i=1; $max>=$i;$i++){
				$pgm->setVariable( 'day', $i );
				$str[0+(int)(($i-1)/6)*2] .= $pgm->getString( $HTML , $list[$i] , 'list_day_head' );
				$str[1+(int)(($i-1)/6)*2] .= $pgm->getString( $HTML , $list[$i] , 'list_day_body' );
			}
				
			for( $i= $max%6; 6>$i && $i ; $i++ ){
				$str[0+(int)(($max-1)/6)*2] .= $pgm->getString( $HTML , null , 'blank_head' );
				$str[1+(int)(($max-1)/6)*2] .= $pgm->getString( $HTML , null , 'blank_body' );
			}
			$this->addBuffer( implode($return,$str) );
		}
	}
	function accessList4month( &$gm, $rec , $args ){
		global $loginUserType;
		global $LOGIN_ID;

		$HTML = Template::getTemplate(  "" , 15 ,'access4' , 'EXTENSION_PART_DESIGN' );

		$pgm = SystemUtil::getGMforType('access');
		$db = $pgm->getDB();
		$table = $db->getTable();

		if( isset($_GET['registA']) ){
			$table = $db->searchTable( $table , 'regist' , '>' , $_GET['registA'] );
		}
		if( isset($_GET['registB']) ){
			$table = $db->searchTable( $table , 'regist' , '<' , $_GET['registB'] );
		}
		if( $loginUserType == 'nUser'){
			$table = $db->searchTable( $table , 'owner' , '=' , $LOGIN_ID );
		}else if( isset($_GET['owner']) && strlen($_GET['owner']) ){
			$table = $db->searchTable( $table , 'owner' , '=' , $_GET['owner'] );
		}
		if( isset($_GET['adwares']) && strlen($_GET['adwares']) ){
			$table = $db->searchTable( $table , 'adwares' , '=' , $_GET['adwares'] );
		}

		$table = $db->dateGroup( $table, 'regist','m');
		$table = $db->addSelectColumn($table,'count(*) as cnt',false);

		$row = $db->getRow( $table );

		if($row){
			$list = Array();
				
			for( $i=0 ; $row>$i ; $i++ ){
				$payrec = $db->getRecord( $table, $i );
				$dategroup = $db->getData( $payrec, 'date_group' );
				$datelist = explode('-',$dategroup);
				$list[ (int)$datelist[0] ][ (int)$datelist[1] ] = $payrec;
			}
			$return = $pgm->getString( $HTML , null , 'list_month_return' );
				
			foreach( $list as $year => $months ){
				$pgm->setVariable( 'year', $year );
				$this->addBuffer( $pgm->getString( $HTML , $rec , 'list_month_head' ) );

				$str = Array();
				for($i=1; 12>=$i;$i++){
					$pgm->setVariable( 'month', $i );
					$str[0+(int)(($i-1)/6)*2] .= $pgm->getString( $HTML , $months[$i] , 'list_month_row_head' );
					$str[1+(int)(($i-1)/6)*2] .= $pgm->getString( $HTML , $months[$i] , 'list_month_row_body' );
				}
				$this->addBuffer( implode($return,$str) );

				$this->addBuffer( $pgm->getString( $HTML , $rec , 'list_month_foot' ) );
			}
		}
	}
        

	
	function createEpochTime( &$gm, $rec , $args ){
		if(!strlen($args[0])){
			$t = time( );
		}else if(is_numeric($args[0])){
			$t = $args[0];
		}else{
			$t = $gm->getDB()->getData( $rec, $args[0] );
		}
		$this->addBuffer( createEpochTime($t,$args[1]) );
	}

	function createEpochTime4ymd( &$gm, $rec , $args ){
		if( isset( $args[1] ) && strlen( $args[1] ) ){	$y = $args[1];	}
		else{	$y = date("Y");	}
		
		if( isset( $args[2] ) && strlen( $args[2] ) ){	$m = $args[2];	}
		else{	$m = 1;	}
		
		if( isset( $args[3] ) && strlen( $args[3] ) ){	$d = $args[3];	}
		else{	$d = 1;	}
		
		switch( $args[0] ){
			case 'now': case 'n':
				break;
			case 'monthtop': case 'mt':
				$t = mktime(0, 0, 0, $m   , 1, $y);
				break;
			case 'monthend': case 'me':
				$t = mktime(0, 0,-1, $m+1 , 1, $y);
				break;
			case 'premonthtop': case 'mt-1':
				$t = mktime(0, 0, 0, $m-1  , 1, $y);
				break;
			case 'premonthend': case 'me-1':
				$t = mktime(0, 0,-1, $m , 1, $y);
				break;
			case 'daytop': case 'dt':
				$t = mktime(0, 0, 0, $m , $d , $y);
				break;
			case 'dayend': case 'de':
				$t = mktime(0, 0, -1, $m  ,$d+1, $y);
				break;
			default:
				break;
		}
		$this->addBuffer( $t );
	}
	
	/**
	 * 指定したget引数を継続する。
	 * @param $gm
	 * @param $rec
	 * @param $args 第一引数は最初に付ける接続(?/&)、第二引数移行に継続するGETパラメータの名前
	 * @return String
	 */
	function getParam( &$gm, $rec , $args ){
		$sep = array_shift($args);
		$param = Array();
		foreach( $args as $get){
			if( isset($_GET[ $get ])){
				$param[] = $get.'='.$_GET[ $get ];
			}
		}
		if(count($param)){ $ret = $sep.join('&',$param); }
		$this->addBuffer( $ret );
	}
	
	/**
	 * 各ユーザーのメール受信設定の設定項目をシステム設定に合わせて出力
	 */
	function drawMailReceptionEdit( &$gm, $rec , $args ){
		global $loginUserType;
		global $LOGIN_ID;
		
		$HTML = Template::getTemplate(  "" , 15 ,'mail_reception' , 'EXTENSION_PART_DESIGN' );
		
		$set = Array( 1 => 'クリック広告', 2 => '成果認証', 4 => '継続課金' );
		
		switch( $_GET['type'] ){
			case 'nUser':
				$lists = SystemUtil::getSystemData('send_mail_nuser');
				break;
		}
		if( !strlen($lists) ){ return; }
		
		if( isset($args[0]) && $args[0] == 'check'){
			$this->addBuffer( $gm->getString( $HTML , $rec , 'check' ) );
			return;
		}

		$names = Array();
		foreach( explode('/',$lists ) as $key ){
			$names[] = $set[$key].'の通知をキャンセル';
		}
		$this->addBuffer( $gm->getString( $HTML , $rec , 'head' ) );
		$this->addBuffer( $gm->getCCResult( $rec , '<!--# form checkbox mail_reception  <br/><br/> '.$lists.' '.join('/',$names).' #-->' ) );
		$this->addBuffer( $gm->getString( $HTML , $rec , 'foot' ) );
	}

	/**
		@brief   引数をエスケープして出力する。
		@remarks CC引数は半角空白で結合されます。
	*/
	function htmlEscape( &$a_gm , $a_rec , $a_args )
	{
		$this->addBuffer( h( implode( ' ' , $a_args )) );
	}

	function getActiveHome( $iGM_ , $iRec_ , $iArgs_ )
	{
		global $HOME;

		$result = $HOME;

		if( 'on' == $_SERVER[ 'HTTPS' ] )
			{ $result = str_replace( 'http://' , 'https://' , $result ); }

		$this->addBuffer( $result );
	}

	function query( $iGM_ , $iRec_ , $iArgs_ )
	{
		$this->addBuffer( h( $_SERVER[ 'QUERY_STRING' ]) );
	}

	function decryptPassword( $iGM , $iRec , $iArgs )
	{
		if( 'SHA' == SystemUtil::getPasswordEncode( $iArgs[ 0 ] ) )
			{ $this->addBuffer( '********' ); }
		else
			{ $this->addBuffer( SystemUtil::decryptPassword( $iArgs[ 0 ] ) ); }
	}
}


?>