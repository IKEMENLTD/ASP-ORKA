<?php

	/*******************************************************************************************************
	 * <PRE>
	 *
	 * continue.php - 専用プログラム
	 * 継続報酬を発生させるプログラム。
	 * 
	 * 以下の形式でアクセスされた場合、継続報酬を追加します。
	 * continue.php?adwares=[foo]&uid=[var]
	 * foo = 広告ID
	 * var = 継続報酬ID
	 * ※その他のパラメータはreadme等で確認してください。
	 * 
	 * </PRE>
	 *******************************************************************************************************/

	/*******************************************************************************************************
	 * メイン処理
	 *******************************************************************************************************/

	try
	{
		include 'custom/head_main.php';

		CheckQuery();

		$pays = new TableModel( 'pay' );
		$pays = SearchContinuePay( $pays );
		$row  = $pays->getRow();

		for( $i = 0 ; $i < $row ; $i++ )
		{
			$pay     = $pays->getRecordModel( $i );
			$adType  = $pay->getData( 'adwares_type' );
			$adwares = new RecordModel( $adType , $pay->getData( 'adwares' ) );
			$nUser   = new RecordModel( 'nUser' , $pay->getData( 'owner' ) );

			if( !IsEnoughBudget( $adwares ) ) //予算をオーバーしている場合
				continue;

			$sales  = GetSales();
			$reward = GetReward( $adwares , $nUser , $sales );

			if( 0 >= $reward ) //報酬額が0円以下になるの場合
			{
				continue;
			}

			$cPay = new FactoryModel( 'continue_pay' );
			$cPay->setID( md5( time() . getenv( 'REMOTE_ADDR' ) ) );

			$cPay->setData( 'adwares_type' , $adwares->getType() );
			$cPay->setData( 'adwares' , $adwares->getID() );
			$cPay->setData( 'pay_id' , $pay->getID() );
			$cPay->setData( 'owner' , $nUser->getID() );
			$cPay->setData( 'sales' , $sales );
			$cPay->setData( 'cost' , $reward );
			$cPay->setData( 'tier1_rate'   , SystemUtil::getSystemData( 'child_per' ) );
			$cPay->setData( 'tier2_rate'   , SystemUtil::getSystemData( 'grandchild_per' ) );
			$cPay->setData( 'tier3_rate'   , SystemUtil::getSystemData( 'greatgrandchild_per' ) );
			$cPay->setData( 'state' , $ACTIVE_NONE );

			//広告の自動認証設定を確認
			$adDB        = SystemUtil::getGMforType( 'adwares' )->getDB();
			$adRec       = $adDB->selectRecord( $pay->getData( 'adwares' ) );
			$openAdwares = $adDB->getData( $adRec , 'open' );
			$acceptType  = $adDB->getData( $adRec , 'auto' );

			if( $ADWARES_AUTO_ON == $adwares->getData( 'continue_auto' ) )
			{
				$cPay->setData( 'state' , $ACTIVE_ACTIVATE );
				$cPay = $cPay->register();
				$tier = 0;

				$payDB = $pay->getDB();
				addPay( $nUser->getID() , $reward , $payDB , $pay->getRecord() , $tier );

				//広告の予算に計上する
				$money         = $adwares->getData( 'money_count' );
				$continueCount = $adwares->getData( 'continue_money_count' );

				$adwares->setData( 'money_count' , $money + $reward + $tier );
				$adwares->setData( 'continue_money_count' , $continueCount + 1 );
				$adwares->update();
			}
			else
				$cPay = $cPay->register();

			sendPayMail( $cPay->getRecord() , 'continue_pay' );
			updateRank( $ownerID );

			if( $adwares->getData( 'open' ) ) //広告が公開されている場合
			{
				if( !IsEnoughBudget( $adwares ) ) //予算をオーバーしている場合
				{
					$adwares->setData( 'open' , false );
					$adwares->update();

					//取り下げ通知メールを送信
					$template = Template::GetLabelFile( 'HIDDEN_NOTICE_MAIL' );
					Mail::Send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm[ 'adwares' ] , $adwares->getRecord() , $MAILSEND_NAMES );
				}
			}
		}
	}
	catch( Exception $e_ )
	{
		WriteErrorLog( $e_ );
	}

	/*******************************************************************************************************
	 * 関数
	 *******************************************************************************************************/

	//■チェック

	/**
		@brief   クエリを検証する。
		@details クエリに不正な値が含まれる場合、例外をスローします。
	*/
	function CheckQuery()
	{
		global $ADWARES_PASS;

		ConceptCheck::IsEssential( $_GET , Array( 'check' ) );
		ConceptCheck::IsNotNull( $_GET , Array( 'check' ) );
		ConceptCheck::IsScalar( $_GET , Array( 'adwares' , 'check' , 'cost' , 'sales' , 'uid' ) );

		if( $_GET[ 'check' ] != $ADWARES_PASS )
				throw new IllegalAccessException( '認証パスが正しくありません' );
	}

	/**
		@brief  広告の予算を検証する。
		@param  $adwares_ RecordModelオブジェクト。
		@return 予算が設定されていない、または余っている場合はtrue。¥n
		        予算が余っていない場合はfalse。
	*/
	function IsEnoughBudget( $adwares_ )
	{
		global $ADWARES_LIMIT_TYPE_YEN;          //成果報酬
		global $ADWARES_LIMIT_TYPE_CNT;          //クリック数
		global $ADWARES_LIMIT_TYPE_CNT_CLICK;    //クリック報酬
		global $ADWARES_LIMIT_TYPE_CNT_CONTINUE; //継続報酬

		//予算の設定を取得する
		$budgetValue = $adwares_->getData( 'limits' );
		$budgetType  = $adwares_->getData( 'limit_type' );

		//設定に応じて比較処理を振り分ける
		switch( $budgetType )
		{
			case $ADWARES_LIMIT_TYPE_YEN : //成果報酬額
				return ( $adwares_->getData( 'money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT : //クリック回数
				return ( $adwares_->getData( 'pay_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CLICK : //クリック報酬額
				return ( $adwares_->getData( 'click_money_count' ) < $budgetValue );

			case $ADWARES_LIMIT_TYPE_CNT_CONTINUE : //継続報酬額
				return ( $adwares_->getData( 'continue_money_count' ) < $budgetValue );

			default : //予算上限なし
				return true;
		}
	}

	//■処理 //

	/**
		@brief エラーログを出力する。
		@param $e_ 例外オブジェクト。
	*/
	function WriteErrorLog( $e_ )
	{
		//エラーメッセージをログに出力
		$errorManager = new ErrorManager();
		$errorMessage = $errorManager->GetExceptionStr( $e_ );

		$errorManager->OutputErrorLog( $errorMessage );
	}

	//■取得

	/**
		@brief 報酬額を計算する。
		@param $adwares_ RecordModelオブジェクト。
		@param $nUser_   RecordModelオブジェクト。
		@param $sales_   指定報酬額。
	*/
	function GetReward( $adwares_ , $nUser_ , $sales_ )
	{
		global $ADWARES_MONEY_TYPE_PER;
		global $ADWARES_MONEY_TYPE_BANK;
		global $ADWARES_MONEY_TYPE_PERSONAL;

		//広告に設定された報酬計算レートを参照する
		$rewardType = $adwares_->getData( 'continue_type' );

		switch( $rewardType )
		{
			//歩合
			case $ADWARES_MONEY_TYPE_PER :
				$rate = $adwares_->getData( 'continue_money' );
				break;

			//会員ランク
			case $ADWARES_MONEY_TYPE_BANK :
				$rank = new RecordModel( $nUser_->getData( 'rank' ) );
				$rate = $rank->getData( 'rate' );
				break;

			//パーソナルレート
			case $ADWARES_MONEY_TYPE_PERSONAL :
				$rate = $nUser_->getData( 'personal_rate' );
				break;

			//円
			default :
				return $adwares_->getData( 'continue_money' );
		}

		$magni = $nUser_->getData( 'magni' );

		return $sales_ * ( $rate / 100 ) * ( $magni / 100 );
	}

	/**
		@brief  売上額を取得する。
		@return 売上額。
	*/
	function GetSales()
	{
		if( 0 <= $_GET[ 'sales' ] )
		{
			return $_GET[ 'sales' ];
		}
		else if( 0 <= $_GET[ 'cost' ] )
		{
			return $_GET[ 'cost' ];
		}
		else
		{
			return 0;
		}
	}

	/**
		@brief  継続課金の対象レコードを検索する。
		@param  $payTable_ payのテーブルモデル。
		@return 検索後のテーブルモデル。
	*/
	function SearchContinuePay( $payTable_ )
	{
		$payTable_->search( 'continue_uid' , '=' , $_GET[ 'uid' ] );
		$payTable_->search( 'adwares' , '=' , $_GET[ 'adwares' ] );
		$payTable_->search( 'state' , '=' , 2 );

		return $payTable_;
	}
?>
