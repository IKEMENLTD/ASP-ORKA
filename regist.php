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

		// === AGGRESSIVE DEBUG: Check $gm array ===
		if ($_GET['type'] == 'nUser') {
			ob_end_clean();
			echo "<h1>DEBUG: $gm Array Status for nUser</h1>";
			echo "<p><strong>$_GET['type']:</strong> " . htmlspecialchars($_GET['type']) . "</p>";
			echo "<p><strong>isset(\$gm['nUser']):</strong> " . (isset($gm['nUser']) ? 'YES' : 'NO') . "</p>";
			echo "<p><strong>empty(\$gm['nUser']):</strong> " . (empty($gm['nUser']) ? 'YES' : 'NO') . "</p>";
			echo "<p><strong>\$gm['nUser'] type:</strong> " . (isset($gm['nUser']) ? gettype($gm['nUser']) : 'NOT SET') . "</p>";
			if (isset($gm['nUser'])) {
				echo "<p><strong>\$gm['nUser'] class:</strong> " . get_class($gm['nUser']) . "</p>";
			}
			echo "<p><strong>All \$gm keys:</strong> " . implode(', ', array_keys($gm)) . "</p>";
			echo "<p><strong>\$TABLE_NAME array:</strong> " . (isset($TABLE_NAME) ? implode(', ', $TABLE_NAME) : 'NOT SET') . "</p>";
			echo "<p><a href='index.php'>Back to top</a></p>";
			exit;
		}
		// === END DEBUG ===

		//パラメータチェック
		ConceptCheck::IsEssential( $_GET , Array( 'type' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'type' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'copy' ) );
		ConceptCheck::IsScalar( $_POST , Array( 'post' , 'step' , 'back' ) );

		if( !$gm[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $_GET[ 'type' ] . 'は定義されていません' );

		if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
			throw new IllegalAccessException( $_GET[ 'type' ] . 'は操作できません' );
		//パラメータチェックここまで

		// WORKAROUND MOVED: Template must exist BEFORE System::getHead() is called
	// (Original workaround code moved here from after System::getSystem)
	if ($_GET['type'] == 'nUser' && ($loginUserType == 'nobody' || $loginUserType == $NOT_LOGIN_USER_TYPE)) {
		$tgm = SystemUtil::getGMforType("template");
		$tdb = $tgm->getDB();

		$check_table = $tdb->getTable();
		$check_table = $tdb->searchTable($check_table, 'user_type', '==', '//');
		$check_table = $tdb->searchTable($check_table, 'target_type', '==', 'nUser');
		$check_table = $tdb->searchTable($check_table, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');

		if ($tdb->getRow($check_table) == 0) {
			$new_rec = $tdb->getNewRecord();
			$tdb->setData($new_rec, 'id', '999');
			$tdb->setData($new_rec, 'user_type', '//');
			$tdb->setData($new_rec, 'target_type', 'nUser');
			$tdb->setData($new_rec, 'activate', 15);
			$tdb->setData($new_rec, 'owner', 3);
			$tdb->setData($new_rec, 'label', 'REGIST_FORM_PAGE_DESIGN');
			$tdb->setData($new_rec, 'file', 'nUser/Regist.html');
			$tdb->setData($new_rec, 'sort', 999);
			$tdb->addRecord($new_rec);
		}
	}

	print System::getHead($gm,$loginUserType,$loginUserRank);
		System::$checkData	 = new CheckData( $gm, false, $loginUserType, $loginUserRank );

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

		// TEMPORARY DEBUG: Display exception details
		echo "<h1>DEBUG Exception Details</h1>";
		echo "<p><strong>Exception Class:</strong> " . get_class($e_) . "</p>";
		echo "<p><strong>Message:</strong> " . htmlspecialchars($e_->getMessage()) . "</p>";
		echo "<p><strong>File:</strong> " . $e_->getFile() . ":" . $e_->getLine() . "</p>";
		echo "<pre><strong>Stack Trace:</strong>\n" . htmlspecialchars($e_->getTraceAsString()) . "</pre>";
		echo "<p><a href='index.php'>Back to top</a></p>";
		exit;

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
?>