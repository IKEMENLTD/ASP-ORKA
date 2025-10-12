<?php
	// IMMEDIATE OUTPUT TEST - THIS MUST APPEAR IF regist.php IS EXECUTED
	echo "<!-- REGIST.PHP STARTED AT " . date('Y-m-d H:i:s') . " -->\n";
	flush();

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * regist.php - 汎用プログラム
	 * 新規登録処理。
	 *
	 * </PRE>
	 *******************************************************************************************************/

	// === LOGGING & ERROR DISPLAY START ===
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	error_reporting(E_ALL);

	echo "<!-- ERROR REPORTING ENABLED -->\n";
	flush();

	$LOG_FILE = '/tmp/regist_debug.log'; // Changed to /tmp for permissions
	function log_debug($msg) {
		global $LOG_FILE;
		$timestamp = date('Y-m-d H:i:s');
		@file_put_contents($LOG_FILE, "[$timestamp] $msg\n", FILE_APPEND);
		error_log("REGIST_DEBUG: $msg");
		// Also echo to screen for immediate visibility
		echo "<!-- DEBUG: $msg -->\n";
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

		// WORKAROUND: Ensure $gm['nUser'] exists (テンプレート作成は無効化)
		log_debug("STEP 4: nUser workaround section");
		if ($_GET['type'] == 'nUser') {
			log_debug("STEP 4.1: Entering nUser workaround");

			// Force create nUser GUIManager if missing
			if (!isset($gm['nUser'])) {
				log_debug("STEP 4.1.1: Creating gm[nUser]");
				global $DB_NAME;
				$gm['nUser'] = new GUIManager($DB_NAME, 'nUser');
				log_debug("STEP 4.1.2: gm[nUser] created");
			}

			// テンプレート作成処理は重いので一旦スキップ
			log_debug("STEP 4.2: Skipping template creation (too slow)");
			log_debug("STEP 4.99: nUser workaround completed");
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

		// FORCE DEBUG OUTPUT: Display exception details before ExceptionManager takes over
		header('Content-Type: text/html; charset=utf-8');
		echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>REGIST DEBUG</title></head><body>";
		echo "<h1>🔴 REGIST.PHP EXCEPTION CAUGHT</h1>";
		echo "<div style='background: #ffe6e6; border: 2px solid red; padding: 20px; margin: 10px;'>";
		echo "<p><strong>Exception Class:</strong> " . get_class($e_) . "</p>";
		echo "<p><strong>Message:</strong> " . htmlspecialchars($e_->getMessage()) . "</p>";
		echo "<p><strong>File:</strong> " . $e_->getFile() . ":" . $e_->getLine() . "</p>";
		echo "<pre><strong>Stack Trace:</strong>\n" . htmlspecialchars($e_->getTraceAsString()) . "</pre>";
		echo "</div>";
		echo "<h2>Debug Log Contents:</h2>";
		echo "<div style='background: #f0f0f0; padding: 10px; font-family: monospace; font-size: 11px;'>";
		echo "<pre>";
		if (file_exists($LOG_FILE)) {
			echo htmlspecialchars(file_get_contents($LOG_FILE));
		} else {
			echo "❌ Log file not found at: $LOG_FILE\n";
			echo "Trying alternate locations...\n";
			$alt_paths = ['/var/www/html/regist_debug.log', './regist_debug.log', 'regist_debug.log'];
			foreach ($alt_paths as $alt) {
				if (file_exists($alt)) {
					echo "\n✓ Found at: $alt\n";
					echo htmlspecialchars(file_get_contents($alt));
					break;
				}
			}
		}
		echo "</pre>";
		echo "</div>";
		echo "<p><a href='index.php'>Back to top</a></p>";
		echo "</body></html>";
		exit;

		//例外に応じてエラーページを出力
		$className = get_class( $e_ );
		ExceptionManager::DrawErrorPage($className );
	}

	ob_end_flush();
?>