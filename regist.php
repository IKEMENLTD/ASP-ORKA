<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * regist.php - 汎用プログラム
	 * 新規登録処理。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	// === LOGGING START ===
	$LOG_FILE = '/var/www/html/regist_debug.log';
	function log_debug($msg) {
		global $LOG_FILE;
		$timestamp = date('Y-m-d H:i:s');
		file_put_contents($LOG_FILE, "[$timestamp] $msg\n", FILE_APPEND);
		error_log("REGIST_DEBUG: $msg");
	}

	log_debug("=== REGIST.PHP EXECUTION START ===");
	log_debug("GET params: " . json_encode($_GET));
	log_debug("POST params: " . json_encode($_POST));

	ob_start();

	try
	{
		log_debug("STEP 1: About to include custom/head_main.php");
		include_once 'custom/head_main.php';
		log_debug("STEP 1: Successfully included custom/head_main.php");

		//パラメータチェック
		log_debug("STEP 2: Starting parameter checks");
		ConceptCheck::IsEssential( $_GET , Array( 'type' ) );
		log_debug("STEP 2.1: IsEssential passed");
		ConceptCheck::IsNotNull( $_GET , Array( 'type' ) );
		log_debug("STEP 2.2: IsNotNull passed");
		ConceptCheck::IsScalar( $_GET , Array( 'type' , 'copy' ) );
		log_debug("STEP 2.3: IsScalar for GET passed");
		ConceptCheck::IsScalar( $_POST , Array( 'post' , 'step' , 'back' ) );
		log_debug("STEP 2.4: All parameter checks passed");

		// Skip access checks for nUser (public registration)
		log_debug("STEP 3: Checking access for type=" . $_GET['type']);
		if ($_GET['type'] != 'nUser') {
			log_debug("STEP 3.1: Not nUser, performing standard access checks");
			if( !$gm[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . ' is not defined' );

			if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . ' cannot be operated' );
		} else {
			log_debug("STEP 3.2: Is nUser, skipping initial access checks");
		}
		//パラメータチェックここまで

		// WORKAROUND: Ensure $gm['nUser'] exists and templates are set up
		log_debug("STEP 4: nUser workaround section");
		if ($_GET['type'] == 'nUser') {
			log_debug("STEP 4.1: Entering nUser workaround");
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

				// Add/Update REGIST_FORM_PAGE_DESIGN template with correct owner value
				$check_table = $tdb->getTable();
				$check_table = $tdb->searchTable($check_table, 'user_type', '==', '//');
				$check_table = $tdb->searchTable($check_table, 'target_type', '==', 'nUser');
				$check_table = $tdb->searchTable($check_table, 'label', '==', 'REGIST_FORM_PAGE_DESIGN');

				// Delete any existing record with wrong owner value
				if ($tdb->getRow($check_table) > 0) {
					$existing_rec = $tdb->getFirstRecord($check_table);
					$existing_owner = $tdb->getData($existing_rec, 'owner');
					if ($existing_owner != 2) {
						// Delete and recreate with correct owner
						$tdb->deleteRecord($tdb->getData($existing_rec, 'id'));
					}
				}

				// Re-check after potential deletion
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
					$tdb->setData($new_rec, 'owner', 2);  // Correct value for NOT_LOGIN_USER_TYPE
					$tdb->setData($new_rec, 'label', 'REGIST_FORM_PAGE_DESIGN');
					$tdb->setData($new_rec, 'file', 'nUser/Regist.html');
					$tdb->setData($new_rec, 'sort', 999);
					$tdb->addRecord($new_rec);
				}
			}  // Close the if ($loginUserType == 'nobody') block
		}  // Close the if ($_GET['type'] == 'nUser') block

		log_debug("STEP 5: Calling System::getHead()");
		print System::getHead($gm,$loginUserType,$loginUserRank);
		log_debug("STEP 5: System::getHead() completed");

		System::$checkData	 = new CheckData( $gm, false, $loginUserType, $loginUserRank );
		log_debug("STEP 6: CheckData created");

		$sys	 = SystemUtil::getSystem( $_GET["type"] );
		log_debug("STEP 7: Got System object for type=" . $_GET["type"]);

		// Skip NOHTML check for nUser (public registration always allowed)
		// Force should_proceed to true for nUser to bypass all access checks
		log_debug("STEP 8: Determining should_proceed");
		if ($_GET['type'] == 'nUser') {
			$should_proceed = true;
			log_debug("STEP 8.1: nUser detected, forcing should_proceed=TRUE");
		} else {
			$should_proceed = !$THIS_TABLE_IS_NOHTML[ $_GET['type'] ] && isset( $gm[ $_GET['type'] ] );
			log_debug("STEP 8.2: Standard should_proceed=" . ($should_proceed ? 'TRUE' : 'FALSE'));
		}

		log_debug("STEP 9: Checking should_proceed value=" . ($should_proceed ? 'TRUE' : 'FALSE'));
		if( !$should_proceed )
		{
			log_debug("STEP 9.1: should_proceed is FALSE, calling drawRegistFailed");
			$sys->drawRegistFaled( $gm, $loginUserType, $loginUserRank );
			log_debug("STEP 9.2: drawRegistFailed completed");
		}
		else
		{
			log_debug("STEP 10: Entering registration form logic (should_proceed=TRUE)");
			$db		 = $gm[ $_GET['type'] ]->getDB();
			log_debug("STEP 10.1: Got DB object");

	        if(isset($_POST['back']))
			{
				log_debug("STEP 10.2: Back button pressed");
				$_POST['post'] = "";

				if($_POST['step'])
					$_POST['step']--;
			}

			// 登録情報入力フォームを描画
			log_debug("STEP 11: Checking POST state - post=" . (isset($_POST['post']) ? $_POST['post'] : 'NOT_SET'));
			if(  !isset( $_POST['post'] ) || !strlen($_POST['post']) )
			{
				log_debug("STEP 11.1: Drawing registration input form");
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
	
				log_debug("STEP 11.5: About to call drawRegistForm");
				log_debug("STEP 11.5.1: loginUserType=" . $loginUserType);
				log_debug("STEP 11.5.2: loginUserRank=" . $loginUserRank);
				log_debug("STEP 11.5.3: rec=" . json_encode($rec));

				$sys->drawRegistForm( $gm, $rec, $loginUserType, $loginUserRank );
				log_debug("STEP 11.6: drawRegistForm completed");
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
		
		log_debug("STEP 999: About to print footer");
		print System::getFoot($gm,$loginUserType,$loginUserRank);
		log_debug("STEP 999: Footer printed, execution completed successfully");
	}
	catch( Exception $e_ )
	{
		log_debug("EXCEPTION CAUGHT: " . get_class($e_) . " - " . $e_->getMessage());
		log_debug("EXCEPTION FILE: " . $e_->getFile() . ":" . $e_->getLine());
		log_debug("EXCEPTION TRACE: " . $e_->getTraceAsString());

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
		echo "<hr>";
		echo "<h2>Debug Log:</h2>";
		echo "<pre>";
		if (file_exists($LOG_FILE)) {
			echo htmlspecialchars(file_get_contents($LOG_FILE));
		} else {
			echo "Log file not found!";
		}
		echo "</pre>";
		exit;

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
?>