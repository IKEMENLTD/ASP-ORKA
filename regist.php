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

		//パラメータチェック
		ConceptCheck::IsEssential( $_GET , Array( 'type' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'type' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'copy' ) );
		ConceptCheck::IsScalar( $_POST , Array( 'post' , 'step' , 'back' ) );

		// Skip access checks for nUser (public registration)
		if ($_GET['type'] != 'nUser') {
			if( !$gm[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . ' is not defined' );

			if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . ' cannot be operated' );
		}
		//パラメータチェックここまで

		// WORKAROUND: Ensure $gm['nUser'] exists and templates are set up
		if ($_GET['type'] == 'nUser') {
			// Force create nUser GUIManager if missing
			if (!isset($gm['nUser'])) {
				global $DB_NAME;
				$gm['nUser'] = new GUIManager($DB_NAME, 'nUser');
			}

			// Only set up templates if not logged in
			if ($loginUserType == 'nobody' || $loginUserType == $NOT_LOGIN_USER_TYPE) {
				$tgm = SystemUtil::getGMforType("template");
				$tdb = $tgm->getDB();

				// Add HEAD_DESIGN template for nobody users
				$check_head = $tdb->getTable();
				$check_head = $tdb->searchTable($check_head, 'user_type', '==', '//');
				$check_head = $tdb->searchTable($check_head, 'label', '==', 'HEAD_DESIGN');

				if ($tdb->getRow($check_head) == 0) {
					$new_head = $tdb->getNewRecord();
					$tdb->setData($new_head, 'id', '997');
					$tdb->setData($new_head, 'user_type', '//');
					$tdb->setData($new_head, 'target_type', '');
					$tdb->setData($new_head, 'activate', 15);
					$tdb->setData($new_head, 'owner', 2);
					$tdb->setData($new_head, 'label', 'HEAD_DESIGN');
					$tdb->setData($new_head, 'file', 'pc/include/HeadNobody.html');
					$tdb->setData($new_head, 'sort', 997);
					$tdb->addRecord($new_head);
				}

				// Add FOOT_DESIGN template for nobody users
				$check_foot = $tdb->getTable();
				$check_foot = $tdb->searchTable($check_foot, 'user_type', '==', '//');
				$check_foot = $tdb->searchTable($check_foot, 'label', '==', 'FOOT_DESIGN');

				if ($tdb->getRow($check_foot) == 0) {
					$new_foot = $tdb->getNewRecord();
					$tdb->setData($new_foot, 'id', '998');
					$tdb->setData($new_foot, 'user_type', '//');
					$tdb->setData($new_foot, 'target_type', '');
					$tdb->setData($new_foot, 'activate', 15);
					$tdb->setData($new_foot, 'owner', 2);
					$tdb->setData($new_foot, 'label', 'FOOT_DESIGN');
					$tdb->setData($new_foot, 'file', 'pc/include/Foot.html');
					$tdb->setData($new_foot, 'sort', 998);
					$tdb->addRecord($new_foot);
				}

				// Add REGIST_FORM_PAGE_DESIGN template
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
			}  // Close the if ($loginUserType == 'nobody') block
		}  // Close the if ($_GET['type'] == 'nUser') block

		// DEBUG: Check what we have before rendering
		if ($_GET['type'] == 'nUser') {
			error_log("DEBUG nUser: gm[nUser] exists=" . (isset($gm['nUser']) ? 'YES' : 'NO'));
			error_log("DEBUG nUser: loginUserType=" . $loginUserType);
		}

		print System::getHead($gm,$loginUserType,$loginUserRank);
		System::$checkData	 = new CheckData( $gm, false, $loginUserType, $loginUserRank );

		$sys	 = SystemUtil::getSystem( $_GET["type"] );

		// Skip NOHTML check for nUser (public registration always allowed)
		// Force should_proceed to true for nUser to bypass all access checks
		if ($_GET['type'] == 'nUser') {
			$should_proceed = true;
			error_log("DEBUG nUser: Forcing should_proceed to TRUE for public registration");
		} else {
			$should_proceed = !$THIS_TABLE_IS_NOHTML[ $_GET['type'] ] && isset( $gm[ $_GET['type'] ] );
		}

		if ($_GET['type'] == 'nUser') {
			error_log("DEBUG nUser: Final should_proceed=" . ($should_proceed ? 'YES' : 'NO'));
		}

		if( !$should_proceed )
		{
			error_log("DEBUG nUser: Drawing RegistFailed because should_proceed is FALSE");
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