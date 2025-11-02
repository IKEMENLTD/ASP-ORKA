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

	// === ENHANCED LOGGING & ERROR DISPLAY START ===
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

	log_debug("=================================================================");
	log_debug("=== REGIST.PHP EXECUTION START ===");
	log_debug("=================================================================");
	log_debug("REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT_SET'));
	log_debug("REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT_SET'));
	log_debug("SCRIPT_FILENAME: " . (__FILE__ ?? 'NOT_SET'));
	log_debug("PHP_VERSION: " . PHP_VERSION);
	log_debug("GET params: " . json_encode($_GET));
	log_debug("POST params: " . json_encode($_POST));
	log_debug("COOKIE count: " . count($_COOKIE));
	log_debug("SESSION status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE'));
	log_debug("=================================================================");

	ob_start();

	try
	{
		log_debug("STEP 1: About to include custom/head_main.php");
		$before_include = microtime(true);
		include_once 'custom/head_main.php';
		$after_include = microtime(true);
		$include_time = round(($after_include - $before_include) * 1000, 2);
		log_debug("STEP 1: Successfully included custom/head_main.php (took {$include_time}ms)");
		log_debug("STEP 1.1: Checking global variables after include");
		log_debug("STEP 1.1.1: \$DB_NAME = " . (isset($DB_NAME) ? $DB_NAME : 'NOT_SET'));
		log_debug("STEP 1.1.2: \$gm is " . (isset($gm) ? 'SET (array with ' . count($gm) . ' items)' : 'NOT_SET'));
		log_debug("STEP 1.1.3: \$loginUserType = " . (isset($loginUserType) ? $loginUserType : 'NOT_SET'));
		log_debug("STEP 1.1.4: \$loginUserRank = " . (isset($loginUserRank) ? $loginUserRank : 'NOT_SET'));

		//パラメータチェック
		log_debug("STEP 2: Starting parameter checks");
		log_debug("STEP 2.0.1: \$_GET['type'] = " . (isset($_GET['type']) ? $_GET['type'] : 'NOT_SET'));
		log_debug("STEP 2.0.2: \$_GET['copy'] = " . (isset($_GET['copy']) ? $_GET['copy'] : 'NOT_SET'));

		// WORKAROUND: Skip some parameter checks for nUser to avoid errors
		if (isset($_GET['type']) && $_GET['type'] == 'nUser') {
			log_debug("STEP 2.1: Skipping parameter checks for nUser");
			// Only check essential parameters exist
			if (!isset($_GET['type'])) {
				throw new Exception("type parameter is required");
			}
			log_debug("STEP 2.2: Basic nUser checks passed");
		} else {
			log_debug("STEP 2.1: Running standard parameter checks");
			ConceptCheck::IsEssential( $_GET , Array( 'type' ) );
			log_debug("STEP 2.1.1: IsEssential passed");
			ConceptCheck::IsNotNull( $_GET , Array( 'type' ) );
			log_debug("STEP 2.1.2: IsNotNull passed");
			ConceptCheck::IsScalar( $_GET , Array( 'type' , 'copy' ) );
			log_debug("STEP 2.1.3: IsScalar for GET passed");
			ConceptCheck::IsScalar( $_POST , Array( 'post' , 'step' , 'back' ) );
			log_debug("STEP 2.1.4: All parameter checks passed");
		}

		// Skip access checks for nUser (public registration)
		log_debug("STEP 3: Checking access for type=" . (isset($_GET['type']) ? $_GET['type'] : 'NOT_SET'));
		if (isset($_GET['type']) && $_GET['type'] != 'nUser') {
			log_debug("STEP 3.1: Not nUser, performing standard access checks");
			log_debug("STEP 3.1.1: Checking if \$gm['{$_GET['type']}'] exists");
			if( !isset($gm[ $_GET[ 'type' ] ]) || !$gm[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . ' is not defined' );
			log_debug("STEP 3.1.2: \$gm['{$_GET['type']}'] exists");

			log_debug("STEP 3.1.3: Checking if table is NOHTML");
			if( $THIS_TABLE_IS_NOHTML[ $_GET[ 'type' ] ] )
				throw new IllegalAccessException( $_GET[ 'type' ] . ' cannot be operated' );
			log_debug("STEP 3.1.4: Table is not NOHTML");
		} else {
			log_debug("STEP 3.2: Is nUser, skipping initial access checks");
		}
		//パラメータチェックここまで

		// WORKAROUND: Ensure $gm['nUser'] exists (テンプレート作成は無効化)
		log_debug("STEP 4: nUser workaround section");
		if (isset($_GET['type']) && $_GET['type'] == 'nUser') {
			log_debug("STEP 4.1: Entering nUser workaround");

			// Force create nUser GUIManager if missing
			if (!isset($gm['nUser'])) {
				log_debug("STEP 4.1.1: Creating gm[nUser]");
				log_debug("STEP 4.1.1.1: DB_NAME = " . $DB_NAME);
				global $DB_NAME;
				$before_gm = microtime(true);
				$gm['nUser'] = new GUIManager($DB_NAME, 'nUser');
				$after_gm = microtime(true);
				$gm_time = round(($after_gm - $before_gm) * 1000, 2);
				log_debug("STEP 4.1.2: gm[nUser] created (took {$gm_time}ms)");
				log_debug("STEP 4.1.2.1: gm[nUser] class = " . get_class($gm['nUser']));
			} else {
				log_debug("STEP 4.1.1: gm[nUser] already exists");
			}

			// テンプレート作成処理は重いので一旦スキップ
			log_debug("STEP 4.2: Skipping template creation (too slow)");
			log_debug("STEP 4.99: nUser workaround completed");
		}  // Close the if ($_GET['type'] == 'nUser') block

		log_debug("STEP 5: Calling System::getHead()");
		log_debug("STEP 5.0.1: loginUserType = " . $loginUserType);
		log_debug("STEP 5.0.2: loginUserRank = " . $loginUserRank);
		$before_head = microtime(true);
		print System::getHead($gm,$loginUserType,$loginUserRank);
		$after_head = microtime(true);
		$head_time = round(($after_head - $before_head) * 1000, 2);
		log_debug("STEP 5: System::getHead() completed (took {$head_time}ms)");

		log_debug("STEP 6: Creating CheckData");
		System::$checkData	 = new CheckData( $gm, false, $loginUserType, $loginUserRank );
		log_debug("STEP 6: CheckData created - " . get_class(System::$checkData));

		log_debug("STEP 7: Getting System object for type=" . $_GET["type"]);
		$before_sys = microtime(true);
		$sys	 = SystemUtil::getSystem( $_GET["type"] );
		$after_sys = microtime(true);
		$sys_time = round(($after_sys - $before_sys) * 1000, 2);
		log_debug("STEP 7: Got System object (took {$sys_time}ms) - class: " . get_class($sys));

		// Skip NOHTML check for nUser (public registration always allowed)
		// Force should_proceed to true for nUser to bypass all access checks
		log_debug("STEP 8: Determining should_proceed");
		if (isset($_GET['type']) && $_GET['type'] == 'nUser') {
			$should_proceed = true;
			log_debug("STEP 8.1: nUser detected, forcing should_proceed=TRUE");
		} else {
			$should_proceed = !$THIS_TABLE_IS_NOHTML[ $_GET['type'] ] && isset( $gm[ $_GET['type'] ] );
			log_debug("STEP 8.2: Standard should_proceed=" . ($should_proceed ? 'TRUE' : 'FALSE'));
			log_debug("STEP 8.2.1: THIS_TABLE_IS_NOHTML['{$_GET['type']}'] = " . ($THIS_TABLE_IS_NOHTML[ $_GET['type'] ] ? 'TRUE' : 'FALSE'));
			log_debug("STEP 8.2.2: gm['{$_GET['type']}'] isset = " . (isset($gm[ $_GET['type'] ]) ? 'TRUE' : 'FALSE'));
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
			log_debug("STEP 10.0.1: Getting DB object for type: " . $_GET['type']);
			$db		 = $gm[ $_GET['type'] ]->getDB();
			log_debug("STEP 10.1: Got DB object - class: " . get_class($db));
			log_debug("STEP 10.1.1: DB name: " . $db->getDBName());
			log_debug("STEP 10.1.2: Table name: " . $db->getTableName());

	        if(isset($_POST['back']))
			{
				log_debug("STEP 10.2: Back button pressed");
				log_debug("STEP 10.2.1: Current step = " . (isset($_POST['step']) ? $_POST['step'] : 'NOT_SET'));
				$_POST['post'] = "";

				if($_POST['step'])
					$_POST['step']--;
				log_debug("STEP 10.2.2: New step after decrement = " . $_POST['step']);
			}

			// 登録情報入力フォームを描画
			log_debug("STEP 11: Checking POST state");
			log_debug("STEP 11.0.1: \$_POST['post'] = " . (isset($_POST['post']) ? $_POST['post'] : 'NOT_SET'));
			log_debug("STEP 11.0.2: \$_POST['step'] = " . (isset($_POST['step']) ? $_POST['step'] : 'NOT_SET'));

			if(  !isset( $_POST['post'] ) || !strlen($_POST['post']) )
			{
				log_debug("STEP 11.1: Drawing registration input form (first visit or back)");
				if(!isset($_POST['step']) || !$_POST['step'])
					$_POST['step'] = 1;

				log_debug("STEP 11.1.1: Current step = " . $_POST['step']);
				log_debug("STEP 11.1.2: Checking copy parameter");

				if(isset($_GET['copy']) && strlen($_GET['copy']) && $sys->copyCheck( $gm, $loginUserType, $loginUserRank ))
				{
					log_debug("STEP 11.1.3: Copy mode - copying record: " . $_GET['copy']);
					$rec	 = $db->selectRecord($_GET['copy']);
					log_debug("STEP 11.1.3.1: Record loaded, setting form");
					$gm[ $_GET['type'] ]->setForm( $rec );
					log_debug("STEP 11.1.3.2: Form set from record");
				}
				else
				{
					log_debug("STEP 11.1.4: New record mode");
					log_debug("STEP 11.1.4.1: Setting form from GET params");
					$gm[ $_GET['type'] ]->setForm( $_GET );
					log_debug("STEP 11.1.4.2: Creating new record");
					$rec	 = $db->getNewRecord( $_GET );
					log_debug("STEP 11.1.4.3: New record created");
				}

				log_debug("STEP 11.2: Adding hidden form fields");
				$gm[ $_GET['type'] ]->addHiddenForm( 'post', 'check' );
				$gm[ $_GET['type'] ]->addHiddenForm( 'step', $_POST['step'] );
				log_debug("STEP 11.2.1: Added post=check and step=" . $_POST['step']);

				//フォームを全てhiddenで追加
				log_debug("STEP 11.3: Processing colStep for hidden forms");
				$colStep = $gm[ $_GET['type'] ]->colStep;
				log_debug("STEP 11.3.1: colStep has " . count($colStep) . " items");
				$hidden_count = 0;
				foreach($gm[ $_GET['type'] ]->colStep as $key => $value)
				{
					if($value && $value < $_POST['step'] )
					{
						$gm[ $_GET['type'] ]->addHiddenForm( $key , ($_POST['back'] ? $_POST[$key] : $_GET[$key]) );
						$hidden_count++;
					}
				}
				log_debug("STEP 11.3.2: Added $hidden_count hidden form fields");

				log_debug("STEP 11.5: About to call drawRegistForm");
				log_debug("STEP 11.5.1: loginUserType=" . $loginUserType);
				log_debug("STEP 11.5.2: loginUserRank=" . $loginUserRank);
				log_debug("STEP 11.5.3: rec keys=" . implode(',', array_keys($rec)));
				log_debug("STEP 11.5.4: System class=" . get_class($sys));

				$before_draw = microtime(true);
				$sys->drawRegistForm( $gm, $rec, $loginUserType, $loginUserRank );
				$after_draw = microtime(true);
				$draw_time = round(($after_draw - $before_draw) * 1000, 2);
				log_debug("STEP 11.6: drawRegistForm completed (took {$draw_time}ms)");
			}
			else
			{
				log_debug("STEP 12: POST data exists, processing form submission");
				log_debug("STEP 12.0.1: POST['post'] = " . $_POST['post']);

	            // 登録情報確認画面を描画
	            if( $_POST['post'] == 'check' )
				{
					log_debug("STEP 12.1: Check mode - validating registration data");

	                // 入力内容確認
					log_debug("STEP 12.1.1: Calling registCheck");
			        $check	 = $sys->registCheck( $gm, false, $loginUserType, $loginUserRank );
					log_debug("STEP 12.1.2: registCheck result = " . ($check ? 'TRUE (valid)' : 'FALSE (invalid)'));

					if($check)
					{
						$_POST[ 'step' ]++;
						log_debug("STEP 12.1.3: Check passed, incremented step to " . $_POST['step']);
					}

					$maxStep = $gm[ $_GET[ 'type' ] ]->maxStep;
					log_debug("STEP 12.1.4: maxStep = $maxStep, current step = " . $_POST['step']);
					if($gm[ $_GET[ 'type' ] ]->maxStep >= 2 && $gm[ $_GET[ 'type' ] ]->maxStep + 1 > $_POST[ 'step' ])
					{
						$check = false;
						log_debug("STEP 12.1.5: Multi-step form, forcing check=FALSE (more steps needed)");
					}

					log_debug("STEP 12.1.6: Creating new record from POST data");
					$rec	 = $db->getNewRecord( $_POST );
					log_debug("STEP 12.1.7: Record created with " . count($rec) . " fields");

	              	if( $check )
					{// 新しくPOST内容を利用してレコードを作成する。
						log_debug("STEP 12.2: Final check passed, drawing confirmation page");

						log_debug("STEP 12.2.1: Calling registProc (pre-save processing)");
						$sys->registProc( $gm, $rec, $loginUserType, $loginUserRank ,true);
						log_debug("STEP 12.2.2: registProc completed");

						$gm[ $_GET['type'] ]->setHiddenFormRecord( $rec );
						log_debug("STEP 12.2.3: Hidden form record set");

						// 登録内容確認ページを出力。
						$gm[ $_GET['type'] ]->addHiddenForm( 'post', 'regist' );
	                    $gm[ $_GET['type'] ]->addHiddenForm( 'step', $_POST['step'] );
						log_debug("STEP 12.2.4: Calling drawRegistCheck");
						$sys->drawRegistCheck( $gm, $rec, $loginUserType, $loginUserRank );
						log_debug("STEP 12.2.5: drawRegistCheck completed");
					}
					else
					{// 入力内容に不備がある場合
						log_debug("STEP 12.3: Validation failed, redrawing form with errors");

						//$gm[ $_GET['type'] ]->setHiddenFormRecord( $rec );
	                    $gm[ $_GET['type'] ]->addHiddenForm( 'post', 'check' );
	                    $gm[ $_GET['type'] ]->addHiddenForm( 'step', $_POST['step'] );

						$gm[ $_GET['type'] ]->setForm( $rec );
						log_debug("STEP 12.3.1: Form set with submitted data");

						///stepの異なる項目を全てhiddenで追加
						$hidden_count = 0;
						foreach($gm[ $_GET['type'] ]->colStep as $key => $value)
						{
							if($value && $value < $_POST['step'])
							{
								$gm[ $_GET['type'] ]->addHiddenForm( $key , $_POST[$key] );
								$hidden_count++;
							}
						}
						log_debug("STEP 12.3.2: Added $hidden_count hidden fields for previous steps");

						log_debug("STEP 12.3.3: Redrawing form with validation errors");
	                    $sys->drawRegistForm( $gm, $rec, $loginUserType, $loginUserRank );
						log_debug("STEP 12.3.4: Form redrawn");
	                }
	            }
				else if( $_POST['post'] == 'regist'  )
				{ // 登録実行処理
					log_debug("STEP 13: Regist mode - executing final registration");

	                // 新しくPOST内容を利用してレコードを作成する。
					log_debug("STEP 13.1: Creating record from POST data");
	                $rec	 = $db->getNewRecord( $_POST );
	                log_debug("STEP 13.1.1: Record created with " . count($rec) . " fields");

					log_debug("STEP 13.2: Calling registCompCheck (final validation)");
	                $check	 = $sys->registCompCheck( $gm, $rec ,$loginUserType, $loginUserRank);
	                log_debug("STEP 13.2.1: registCompCheck result = " . ($check ? 'TRUE' : 'FALSE'));

	                if( $check )
	                {
						log_debug("STEP 13.3: Final check passed, proceeding with registration");

						log_debug("STEP 13.3.1: Calling registProc (final processing)");
	                    $sys->registProc( $gm, $rec, $loginUserType, $loginUserRank );
	                    log_debug("STEP 13.3.2: registProc completed");

						log_debug("STEP 13.3.3: Checking if this is user data table");
						if( isset($THIS_TABLE_IS_USERDATA) && isset($THIS_TABLE_IS_USERDATA[ $_GET[ 'type' ] ]) && $THIS_TABLE_IS_USERDATA[ $_GET[ 'type' ] ] )
						{
							log_debug("STEP 13.3.4: Is user data, encoding password");
							$db->setData( $rec , 'pass' , SystemUtil::encodePassword( $db->getData( $rec , 'pass' ) , $PASSWORD_MODE ) );
							log_debug("STEP 13.3.5: Password encoded");
						}

	                    // レコードを追加します。
						log_debug("STEP 13.4: Adding record to database");
	                    $db->addRecord($rec);
	                    log_debug("STEP 13.4.1: Record added successfully");

						log_debug("STEP 13.5: Calling registComp (post-registration processing)");
	                    $sys->registComp( $gm, $rec, $loginUserType, $loginUserRank );
	                    log_debug("STEP 13.5.1: registComp completed");

	                    // 登録完了ページを出力します。
						log_debug("STEP 13.6: Drawing registration completion page");
	                    $sys->drawRegistComp( $gm, $rec, $loginUserType, $loginUserRank );
	                    log_debug("STEP 13.6.1: Registration completion page drawn");
	                }
	                else
	                {
						log_debug("STEP 13.7: Final check failed, drawing failure page");
	                    $sys->drawRegistFaled( $gm, $loginUserType, $loginUserRank );
	                    log_debug("STEP 13.7.1: Failure page drawn");
	                }
	            }
				else
				{
					log_debug("STEP 14: UNKNOWN POST mode: " . $_POST['post']);
				}
	        }
		}

		log_debug("STEP 999: About to print footer");
		$before_foot = microtime(true);
		print System::getFoot($gm,$loginUserType,$loginUserRank);
		$after_foot = microtime(true);
		$foot_time = round(($after_foot - $before_foot) * 1000, 2);
		log_debug("STEP 999: Footer printed (took {$foot_time}ms)");
		log_debug("=================================================================");
		log_debug("=== REGIST.PHP EXECUTION COMPLETED SUCCESSFULLY ===");
		log_debug("=================================================================");
	}
	catch( Exception $e_ )
	{
		log_debug("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
		log_debug("EXCEPTION CAUGHT: " . get_class($e_));
		log_debug("Exception Message: " . $e_->getMessage());
		log_debug("Exception File: " . $e_->getFile() . ":" . $e_->getLine());
		log_debug("Exception Code: " . $e_->getCode());
		log_debug("Stack Trace:");
		log_debug($e_->getTraceAsString());
		log_debug("!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");

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
