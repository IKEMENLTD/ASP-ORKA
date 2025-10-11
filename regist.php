<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * regist.php - 汎用プログラム
	 * 新規登録処理。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	ob_start();

	try
	{
		include_once 'custom/head_main.php';
		error_log("DEBUG: After head_main include");

		//パラメータチェック
		ConceptCheck::IsEssential( $_GET , Array( 'type' ) );
		error_log("DEBUG: After IsEssential check");
		ConceptCheck::IsNotNull( $_GET , Array( 'type' ) );
		error_log("DEBUG: After IsNotNull check");
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'copy' ) );
		ConceptCheck::IsScalar( $_POST , Array( 'post' , 'step' , 'back' ) );
		error_log("DEBUG: After IsScalar checks");

		error_log("DEBUG: Type=" . $_GET['type'] . ", gm_isset=" . (isset($gm[$_GET['type']]) ? 'yes' : 'no') . ", gm_value=" . ($gm[$_GET['type']] ? 'truthy' : 'falsy'));
		error_log("DEBUG: NOHTML=" . ($THIS_TABLE_IS_NOHTML[$_GET['type']] ? 'true' : 'false'));

		if( !$gm[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $_GET[ 'type' ] . 'は定義されていません' );

		error_log("DEBUG: After gm check - passed");

		if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $_GET[ 'type' ] . 'は操作できません' );
		//パラメータチェックここまで

		error_log("DEBUG: After NOHTML check - passed");

		print System::getHead($gm,$loginUserType,$loginUserRank);
		error_log("DEBUG: After System::getHead");
		System::$checkData	 = new CheckData( $gm, false, $loginUserType, $loginUserRank );
		error_log("DEBUG: After CheckData creation");
	
		$sys	 = SystemUtil::getSystem( $_GET["type"] );
	
		if(   $THIS_TABLE_IS_NOHTML[ $_GET['type'] ] || !isset(  $gm[ $_GET['type'] ]  )   )
		{
			$sys->drawRegistFaled( $gm, $loginUserType, $loginUserRank );
		}
		else
		{
			$db		 = $gm[ $_GET['type'] ]->getDB();
			
	        if(isset($_POST['back']))
			{
				$_POST['post'] = "";
	
				if($_POST['step'])
					$_POST['step']--;
			}
	
			// 登録情報入力フォームを描画
			if(  !isset( $_POST['post'] ) || !strlen($_POST['post']) )
			{
				if(!$_POST['step'])
					$_POST['step'] = 1;
				
				if(strlen($_GET['copy']) && $sys->copyCheck( $gm, $loginUserType, $loginUserRank ))
				{
					$rec	 = $db->selectRecord($_GET['copy']);
					$gm[ $_GET['type'] ]->setForm( $rec );
				}
				else
				{
					$gm[ $_GET['type'] ]->setForm( $_GET );
					$rec	 = $db->getNewRecord( $_GET );
				}
	
				$gm[ $_GET['type'] ]->addHiddenForm( 'post', 'check' );
				$gm[ $_GET['type'] ]->addHiddenForm( 'step', $_POST['step'] );
	
				//フォームを全てhiddenで追加
				foreach($gm[ $_GET['type'] ]->colStep as $key => $value)
				{
					if($value && $value < $_POST['step'] )
						$gm[ $_GET['type'] ]->addHiddenForm( $key , ($_POST['back'] ? $_POST[$key] : $_GET[$key]) );
				}
	
				$sys->drawRegistForm( $gm, $rec, $loginUserType, $loginUserRank );
			}
			else
			{
	            // 登録情報確認画面を描画
	            if( $_POST['post'] == 'check' )
				{
				
	                // 入力内容確認
			        $check	 = $sys->registCheck( $gm, false, $loginUserType, $loginUserRank );
	
					if($check)
						$_POST[ 'step' ]++;
	
					if($gm[ $_GET[ 'type' ] ]->maxStep >= 2 && $gm[ $_GET[ 'type' ] ]->maxStep + 1 > $_POST[ 'step' ])
						$check = false;
	
					$rec	 = $db->getNewRecord( $_POST );
					
	              	if( $check )
					{// 新しくPOST内容を利用してレコードを作成する。
	
						$sys->registProc( $gm, $rec, $loginUserType, $loginUserRank ,true);
	
						$gm[ $_GET['type'] ]->setHiddenFormRecord( $rec );
	
						// 登録内容確認ページを出力。
						$gm[ $_GET['type'] ]->addHiddenForm( 'post', 'regist' );
	                    $gm[ $_GET['type'] ]->addHiddenForm( 'step', $_POST['step'] );
						$sys->drawRegistCheck( $gm, $rec, $loginUserType, $loginUserRank );
					}
					else
					{// 入力内容に不備がある場合
						//$gm[ $_GET['type'] ]->setHiddenFormRecord( $rec );
	                    $gm[ $_GET['type'] ]->addHiddenForm( 'post', 'check' );
	                    $gm[ $_GET['type'] ]->addHiddenForm( 'step', $_POST['step'] );
	                    
						$gm[ $_GET['type'] ]->setForm( $rec );
	
						///stepの異なる項目を全てhiddenで追加
						foreach($gm[ $_GET['type'] ]->colStep as $key => $value)
						{
							if($value && $value < $_POST['step'])
								$gm[ $_GET['type'] ]->addHiddenForm( $key , $_POST[$key] );
						}
	
	                    $sys->drawRegistForm( $gm, $rec, $loginUserType, $loginUserRank );
	                }
	            }
				else if( $_POST['post'] == 'regist'  )
				{ // 登録実行処理
	                // 新しくPOST内容を利用してレコードを作成する。
	                $rec	 = $db->getNewRecord( $_POST );
	                
	                $check	 = $sys->registCompCheck( $gm, $rec ,$loginUserType, $loginUserRank);
	                
	                if( $check )
	                {
	                    $sys->registProc( $gm, $rec, $loginUserType, $loginUserRank );
	                    
						if( $THIS_TABLE_IS_USERDATA[ $_GET[ 'type' ] ] )
							{ $db->setData( $rec , 'pass' , SystemUtil::encodePassword( $db->getData( $rec , 'pass' ) , $PASSWORD_MODE ) ); }

	                    // レコードを追加します。
	                    $db->addRecord($rec);
	                    
	                    $sys->registComp( $gm, $rec, $loginUserType, $loginUserRank );
	                    
	                    // 登録完了ページを出力します。
	                    $sys->drawRegistComp( $gm, $rec, $loginUserType, $loginUserRank );
	                }
	                else
	                {
	                    $sys->drawRegistFaled( $gm, $loginUserType, $loginUserRank );
	                }
	            }
	        }
		}
		
		print System::getFoot($gm,$loginUserType,$loginUserRank);
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