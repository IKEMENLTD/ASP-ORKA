<?PHP

include_once "include/base/Util.php";

/*******************************************************************************************************
 * <PRE>
 *
 * 汎用関数群
 *
 * @version 1.0.0
 *
 * </PRE>
 *******************************************************************************************************/

class SystemUtil extends SystemUtilBase{

	/**
	 * unixtimeを丸める
	 *
	 * @param t umixtime
	 * @param f 丸めるフォーマット
	 * @return unitime
	 */
	function createEpochTime( $t, $f ){
		switch( $f ){
			case 'now': case 'n':
				break;
			case 'monthtop': case 'mt':
				$t = mktime( 0, 0, 0, date("m",$t)  , 1, date("Y",$t));
				break;
			case 'monthend': case 'me':
				$t = mktime( 0, 0,-1, date("m",$t)+1  , 1, date("Y",$t));
				break;
			case 'premonthtop': case 'mt-1':
				$t = mktime( 0, 0, 0, date("m",$t)-1  , 1, date("Y",$t));
				break;
			case 'premonthend': case 'me-1':
				$t = mktime( 0, 0,-1, date("m",$t)  , 1, date("Y",$t));
				break;
			case 'daytop': case 'dt':
				$t = mktime( 0, 0, 0, date("m",$t) , date("d",$t) , date("Y",$t));
				break;
			case 'dayend': case 'de':
				$t = mktime( 0, 0, -1, date("m",$t)  ,date("d",$t)+1, date("Y",$t));
				break;
			default:
				break;
		}
		return $t;
	}

	
	/**
	 * 非同期処理をapi経由で実行する時のラッパー関数
	 *
	 * @param class クラス名
	 * @param method 関数名
	 * @param param その他パラメータ
	 * @return true/false
	 */
	function async( $class, $method, $param = Array() )
	{
		global $HOME;

		$url          = $HOME . 'api.php';
		$param[ 'c' ] = $class;
		$param[ 'm' ] = $method;

		return self::request( $url, $param );
	}

	/**
	 * 自動出力フォームのaction属性に設定するべき値を判断して取得する。
	 *
	 * @param  iMode フォームの種類。
	 * @return action属性の値。
	 */
	function GetFormTarget( $iMode )  //
	{
		switch( WS_SYSTEM_SYSTEM_FORM_ACTON ) //遷移方法の種類で分岐
		{
			case 'normal' : //通常
			{
				switch( $iMode ) //フォームの種類で分岐
				{
					case 'registForm' : //登録フォーム
						{ return 'regist.php?type=' . $_GET[ 'type' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'registCheck' : //登録確認フォーム
						{ return 'regist.php?type=' . $_GET[ 'type' ] . '&' . WS_SYSTEM_SYSTEM_FORM_CHECK_LABEL; }

					case 'editForm' : //編集フォーム
						{ return 'edit.php?&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'editCheck' : //編集確認フォーム
						{ return 'edit.php?&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_CHECK_LABEL; }

					case 'deleteForm' : //削除フォーム
						{ return 'delete.php?&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'deleteCheck' : //削除確認フォーム
						{ return 'delete.php?&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_CHECK_LABEL; }

					case 'restoreForm' : //復元フォーム
						{ return 'restore.php?&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'infoPage' : //詳細画面フォーム
						{ return 'info.php?&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }
				}
			}

			case 'index' : //indexにコントローラ名を指定(MVC動作用)
			{
				switch( $iMode ) //フォームの種類で分岐
				{
					case 'registForm' : //登録フォーム
						{ return 'index.php?app_controller=register&type=' . $_GET[ 'type' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'registCheck' : //登録確認フォーム
						{ return 'index.php?app_controller=register&type=' . $_GET[ 'type' ] . '&' . WS_SYSTEM_SYSTEM_FORM_CHECK_LABEL; }

					case 'editForm' : //編集フォーム
						{ return 'index.php?app_controller=edit&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'editCheck' : //編集確認フォーム
						{ return 'index.php?app_controller=edit&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_CHECK_LABEL; }

					case 'deleteForm' : //削除フォーム
						{ return 'index.php?app_controller=delete&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'deleteCheck' : //削除確認フォーム
						{ return 'index.php?app_controller=delete&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_CHECK_LABEL; }

					case 'restoreForm' : //復元フォーム
						{ return 'index.php?app_controller=restore&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }

					case 'infoPage' : //詳細画面フォーム
						{ return 'index.php?app_controller=info&type=' . $_GET[ 'type' ] . '&id=' . $_GET[ 'id' ] . '&' . WS_SYSTEM_SYSTEM_FORM_INPUT_LABEL; }
				}
			}

			case 'null' : //空にする
				{ return null; }
		}
	}
}

//紹介（ティア）処理。
function friendProc(){

	global $PARENT_MAX_ROW;
	global $PARENT_LIMIT_URL;

	if( isset( $_GET[ 'friend' ] ) ){

		$tgm	 = SystemUtil::getGM();
		$ndb	 = $tgm['nUser']->getDB();

		$table	 = $ndb->getTable();
		$table	 = $ndb->searchTable( $table, 'parent', '=', $_GET[ 'friend' ] );

		$row 	 = $ndb->getRow( $table );

		if( $row < $PARENT_MAX_ROW || $PARENT_MAX_ROW == "999"){
			$_SESSION[ 'friend' ] = $_GET[ 'friend' ];
		}else{
			header("Location:".$PARENT_LIMIT_URL);
			exit;
		}
	}
}

//会員ランク更新チェック。
function updateRank($id){

	global $ACTIVE_ACTIVATE;
	global $RANK_AUTO_ON;

	if( SystemUtil::getSystemData( 'sales_auto') == $RANK_AUTO_ON ){

		$pdb	 = GMList::getDB('pay');
		$ptable	 = $pdb->getTable();
		$ptable	 = $pdb->searchTable( $ptable, 'owner', '=', $id );
		$ptable	 = $pdb->searchTable( $ptable, 'state', '=', $ACTIVE_ACTIVATE );	//認証済みの成果に限る

		$prow 	 = $pdb->getRow( $ptable );				//獲得件数
		$p_sales = $pdb->getSum( "sales", $ptable );	//売上金額合計

		//相当の会員ランクを検索
		$sdb = GMList::getDB('sales');
		$stable	 = $sdb->getTable();

		//売上額のみで判断するグループ
		$salesTable = $sdb->searchTable( $stable , 'lot' , '=' , 0 );
		$salesTable = $sdb->searchTable( $salesTable , 'sales' , '>' , 0 );
		$salesTable = $sdb->searchTable( $salesTable , 'sales' , '<=' , $p_sales );

		//件数のみで判断するグループ
		$rowTable = $sdb->searchTable( $stable , 'sales' , '=' , 0 );
		$rowTable = $sdb->searchTable( $rowTable , 'lot' , '>' , 0 );
		$rowTable = $sdb->searchTable( $rowTable , 'lot' , '<=' , $prow );

		//両方使うグループ
		$tmp_table1	 = $sdb->searchTable( $stable, 'lot', '>', 0 );
		$tmp_table1	 = $sdb->searchTable( $tmp_table1, 'lot', '<=', $prow );
		$tmp_table2	 = $sdb->searchTable( $stable, 'sales', '>', 0 );
		$tmp_table2	 = $sdb->searchTable( $tmp_table2, 'sales', '<=', $p_sales );

		$stable		 = $sdb->andTable( $tmp_table1, $tmp_table2 );
		$s2table	 = $sdb->orTable( $salesTable , $rowTable );
		$stable		 = $sdb->orTable( $stable , $s2table );

		$sRow = $sdb->getrow( $stable );

		if( 0 >= $sRow ) //ランクが見つからなかった場合
			return;

		$stable	 = $sdb->sortTable( $stable, "rate", "desc");	//レートが高い順に並び替え

		$srec = $sdb->getRecord( $stable, 0 );
		$sid = $sdb->getData( $srec, 'id' );	//相当の会員ランクID

		//現在の会員ランクと照合
		$ndb = GMList::getDB('nUser');
		$nrec = $ndb->selectRecord( $id );
		$n_rank = $ndb->getData( $nrec, 'rank' );

		//ランクが異なる場合は更新
		if($n_rank != $sid){
			$nrec = $ndb->setData( $nrec, 'rank', $sid );
			$ndb->updateRecord($nrec);
		}
	}
}

//payへの加算処理。  tierも考慮する。
//$gm nUser
function addPay($user_id,$pay,&$pay_db,$pay_rec , &$_tierValue){
	global $gm;

	$pay_rec = updatePayTierRate( $pay_db , $pay_rec );

	$ndb = $gm['nUser']->getDB();
	$rec = $ndb->selectRecord( $user_id );
	if( $rec ){
		$rec = $ndb->setCalc( $rec, 'pay' , '+' , $pay );
		$ndb->updateRecord($rec);

		$p = $ndb->getData( $rec , 'parent' );
		$g = $ndb->getData( $rec , 'grandparent' );
		$gg = $ndb->getData( $rec , 'greatgrandparent' );
		if( $p || $g || $gg ){
			$sdb = $gm['system']->getDB();
			$tdb = $gm['tier']->getDB();

			$srec = $sdb->getRecord( $sdb->getTable(), 0);
			$list = Array( $p, $g, $gg );
			$pers = Array( 'tier1_rate', 'tier2_rate', 'tier3_rate' );
			for($i=0;$i<3;$i++){
				if( ! strlen($list[$i]) ){ continue; }

				$per = $pay_db->getData( $pay_rec , $pers[$i] );

				if($per > 0){
					$trec = $ndb->selectRecord( $list[$i] );
					if($trec){
						$tpay = floor($pay * $per/100);
						$ndb->setCalc( $trec, 'pay' , '+' , $tpay );
						$ndb->setCalc( $trec, 'tier' , '+' , $tpay );
						$ndb->updateRecord($trec);

						$tierTable = $tdb->getTable();
						$tierTable = $tdb->searchTable( $tierTable , 'tier_id' , '=' , $pay_db->getData( $pay_rec , 'id' ) . ( $i + 1 ) );
						$tierTable = $tdb->limitOffset( $tierTable , 0 , 1 );
						$tier_rec  = $tdb->getRecord( $tierTable , 0 );

						$old_type_tier_rec = $tdb->selectRecord( $pay_db->getData( $pay_rec , 'id' ) . ( $i + 1 ) );

						if( !$tier_rec && !$old_type_tier_rec ) //新旧ティアレコードどちらもない場合
						{
							$tier_rec = $tdb->getNewRecord();
							$tdb->setData(  $tier_rec, 'id', SystemUtil::getNewId( $tdb , 'tier' ) );
							$tdb->setData(  $tier_rec, 'tier_id', $pay_db->getData($pay_rec,'id') . ($i+1) );
							$tdb->setData(  $tier_rec, 'owner', $list[$i] );
							$tdb->setData(  $tier_rec, 'tier', $pay_db->getData($pay_rec,'owner') );
							$tdb->setData(  $tier_rec, 'adwares', $pay_db->getData($pay_rec,'adwares') );
							$tdb->setData(  $tier_rec, 'cost', $tpay );
							$tdb->setData(  $tier_rec, 'tier'.($i+1), 1 );
							$tdb->setData(  $tier_rec, 'regist', time()  );
							$tdb->addRecord( $tier_rec );
						}
						else if( $tier_rec ) //新ティアレコードがある場合
						{
							$tdb->setCalc( $tier_rec , 'cost' , '+' , $tpay );
							$tdb->updateRecord( $tier_rec );
						}
						else //旧ティアレコードがある場合
						{
							$tdb->setCalc( $old_type_tier_rec , 'cost' , '+' , $tpay );
							$tdb->updateRecord( $old_type_tier_rec );
						}

						$_tierValue += $tpay;
					}
				}
			}
		}

		//会員ランクの更新チェック
		updateRank( $user_id );

		return true;
	}
	return false;
}
function subPay($user_id,$pay,&$pay_db,$pay_rec){
	global $gm;

	$pay_rec = updatePayTierRate( $pay_db , $pay_rec );

	$ndb = $gm['nUser']->getDB();
	$rec = $ndb->selectRecord( $user_id );
	if( $rec ){
		$rec = $ndb->setCalc( $rec, 'pay' , '-' , $pay );
		$ndb->updateRecord($rec);

		$p = $ndb->getData( $rec , 'parent' );
		$g = $ndb->getData( $rec , 'grandparent' );
		$gg = $ndb->getData( $rec , 'greatgrandparent' );
		if( $p || $g || $gg ){
			$sdb = $gm['system']->getDB();
			$tdb = $gm['tier']->getDB();

			$srec = $sdb->getRecord( $sdb->getTable(), 0);

			$list = Array( $p, $g, $gg );
			$pers = Array( 'tier1_rate', 'tier2_rate', 'tier3_rate' );
			for($i=0;$i<3;$i++){
				if( ! strlen($list[$i]) ){ continue; }

				$per = $pay_db->getData( $pay_rec , $pers[$i] );

				if($per > 0){
					$trec = $ndb->selectRecord( $list[$i] );
					if($trec){
						$tpay = floor($pay * $per / 100);
						$ndb->setCalc( $trec, 'pay' , '-' , $tpay );
						$ndb->setCalc( $trec, 'tier' , '-' , $tpay );
						$ndb->updateRecord($trec);

						$tierTable = $tdb->getTable();
						$tierTable = $tdb->searchTable( $tierTable , 'tier_id' , '=' , $pay_db->getData( $pay_rec , 'id' ) . ( $i + 1 ) );
						$tierTable = $tdb->limitOffset( $tierTable , 0 , 1 );
						$tier_rec  = $tdb->getRecord( $tierTable , 0 );

						$old_type_tier_rec = $tdb->selectRecord( $pay_db->getData( $pay_rec , 'id' ) . ( $i + 1 ) );

						if( $tier_rec )
						{
							$tdb->setCalc( $tier_rec , 'cost' , '-' , $tpay );
							$tdb->updateRecord( $tier_rec );
						}
						else if( $old_type_tier_rec )
						{
							$tdb->setCalc( $old_type_tier_rec , 'cost' , '-' , $tpay );
							$tdb->updateRecord( $old_type_tier_rec );
						}
					}
				}
			}
		}
		return true;
	}
	return false;
}

function updatePayTierRate( $iPayDB , $iPayRec )
{
	$rate1     = $iPayDB->getData( $iPayRec , 'tier1_rate' );
	$rate2     = $iPayDB->getData( $iPayRec , 'tier2_rate' );
	$rate3     = $iPayDB->getData( $iPayRec , 'tier3_rate' );
	$sys_rate1 = SystemUtil::getSystemData( 'child_per' );
	$sys_rate2 = SystemUtil::getSystemData( 'grandchild_per' );
	$sys_rate3 = SystemUtil::getSystemData( 'greatgrandchild_per' );

	if( !$rate1 && !$rate2 && !$rate3 ) //payにtierレートが設定されていない場合
	{
		$iPayDB->setData( $iPayRec , 'tier1_rate' , $sys_rate1 );
		$iPayDB->setData( $iPayRec , 'tier2_rate' , $sys_rate2 );
		$iPayDB->setData( $iPayRec , 'tier3_rate' , $sys_rate3 );

		if( $sys_rate1 || $sys_rate2 || $sys_rate3 ) //システム設定でtierレートが設定されている場合
			{ $iPayDB->updateRecord( $iPayRec ); }
	}

	return $iPayRec;
}

function getDefaultActivate( $_type )
{
	global $ACTIVE_ACTIVATE;

	$gm  = SystemUtil::getGMforType( 'system' );
	$db  = $gm->getDB();
	$rec = $db->selectRecord( 'ADMIN' );

	if( 'nUser' == $_type ){
		$def = $db->getData( $rec , 'nuser_default_activate' );
		if( $def == $ACTIVE_ACTIVATE ){ $def = $db->getData( $rec , 'nuser_accept_admin' ); }
	}

	return $def;
}

function fileWrite( $file_name , $html ){
	if(!$f = fopen($file_name,'w')){
		return;
	}

	if(fwrite($f,$html) === FALSE ){
		fclose($f);
		return;
	}

	fclose($f);
}
function fileRead( $file_name ){
	$html = file_get_contents($file_name);
	return $html;
}

function createEpochTime( $t, $f ){
	switch( $f ){
		case 'now': case 'n':
			break;
		case 'monthtop': case 'mt':
			$t = mktime( 0, 0, 0, date("m",$t)  , 1, date("Y",$t));
			break;
		case 'monthend': case 'me':
			$t = mktime( 0, 0,-1, date("m",$t)+1  , 1, date("Y",$t));
			break;
		case 'premonthtop': case 'mt-1':
			$t = mktime( 0, 0, 0, date("m",$t)-1  , 1, date("Y",$t));
			break;
		case 'premonthend': case 'me-1':
			$t = mktime( 0, 0,-1, date("m",$t)  , 1, date("Y",$t));
			break;
		case 'daytop': case 'dt':
			$t = mktime( 0, 0, 0, date("m",$t) , date("d",$t) , date("Y",$t));
			break;
		case 'dayend': case 'de':
			$t = mktime( 0, 0, -1, date("m",$t)  ,date("d",$t)+1, date("Y",$t));
			break;
		default:
			break;
	}
	return $t;
}

	/**
		@brief 通知対象にメールを送信する。
	*/
	function sendPayMail( $iRec_ , $iType_ )
	{
		global $ACTIVE_ACTIVATE;
		global $PAY_TYPE_CLICK;
		global $PAY_TYPE_NOMAL;
		global $PAY_TYPE_CONTINUE;
		global $MAILSEND_ADDRES;
		global $MAILSEND_NAMES;
		global $gm;

		$db     = $gm[ $iType_ ]->getDB();
		$notice = $db->getData( $iRec_ , 'is_notice' );
		$state  = $db->getData( $iRec_ , 'state' );

		if( $notice ) //既に通知済みの場合
			{ return; }

		if( $ACTIVE_ACTIVATE != $state ) //認証ではない場合
			{ return; }

		$gm[ $iType_ ]->setVariable( 'pay_type' , $iType_ );

		$typeIndexs = Array(
			'click_pay'    => ( string )$PAY_TYPE_CLICK ,
			'pay'          => ( string )$PAY_TYPE_NOMAL ,
			'continue_pay' => ( string )$PAY_TYPE_CONTINUE
		);

		$typeIndex = $typeIndexs[ $iType_ ];

		//管理者に送信
		$enableAdmin = SystemUtil::getSystemData( 'send_mail_admin' );

		if( FALSE !== strpos( $enableAdmin , $typeIndex ) ) //管理者への送信が有効な場合
		{
			$template = Template::getTemplate( 'admin' , 1 , '', 'PAY_MAIL' );
			Mail::send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm[ $iType_ ] , $iRec_ , $MAILSEND_NAMES );
		}

		//nUserに送信
		$enableNUser = SystemUtil::getSystemData( 'send_mail_nuser' );

		if( FALSE !== strpos( $enableNUser , $typeIndex ) ) //nUserへの送信が有効な場合
		{
			$nID  = $db->getData( $iRec_ , 'owner' );
			$nDB  = $gm[ 'nUser' ]->getDB();
			$nRec = $nDB->selectRecord( $nID );

			if( $nRec ) //レコードが存在する場合
			{
				$reception = $nDB->getData( $nRec , 'mail_reception' );
				$isMobile  = $nDB->getData( $nRec , 'is_mobile' );
				$nMail     = $nDB->getData( $nRec , 'mail' );

				if( FALSE === strpos( $reception , $typeIndex ) ) //通知拒否対象ではない場合
				{
					if( $isMobile ) //携帯から登録されたユーザーの場合
					{
						$currentPath   = $template_path;
						$template_path = $mobile_path;
						$template      = Template::getTemplate( 'nUser' , 1 , '' , 'PAY_MAIL' );
						$template_path = $currentPath;
					}
					else //その他の端末から登録されたユーザーの場合
						{ $template = Template::getTemplate( 'nUser' , 1 , '' , 'PAY_MAIL' ); }
					Mail::send( $template , $MAILSEND_ADDRES , $nMail , $gm[ $iType_ ] , $iRec_ , $MAILSEND_NAMES );
				}
			}
		}

		//通知済みフラグを設定する
		$db->setData( $iRec_ , 'is_notice' , true );
		$db->updateRecord( $iRec_ );
	}

	function sendDisabledPayMail( $iRec_ , $iType_ )
	{
		global $PAY_TYPE_CLICK;
		global $PAY_TYPE_NOMAL;
		global $PAY_TYPE_CONTINUE;
		global $MAILSEND_ADDRES;
		global $MAILSEND_NAMES;
		global $gm;

		$db          = $gm[ $iType_ ]->getDB();
		$state       = $db->getData( $iRec_ , 'state' );
		$noticeState = SystemUtil::getSystemData( 'send_mail_status' );

		if( !in_array( $state , explode( '/' , $noticeState ) ) )
			{ return; }

		$gm[ $iType_ ]->setVariable( 'pay_type' , $iType_ );

		$typeIndexs = Array(
			'click_pay'    => ( string )$PAY_TYPE_CLICK ,
			'pay'          => ( string )$PAY_TYPE_NOMAL ,
			'continue_pay' => ( string )$PAY_TYPE_CONTINUE
		);

		$typeIndex = $typeIndexs[ $iType_ ];

		//管理者に送信
		$enableAdmin = SystemUtil::getSystemData( 'send_mail_admin' );

		if( FALSE !== strpos( $enableAdmin , $typeIndex ) ) //管理者への送信が有効な場合
		{
			$template = Template::getTemplate( 'admin' , 1 , '', 'DISABLED_PAY_MAIL' );
			Mail::send( $template , $MAILSEND_ADDRES , $MAILSEND_ADDRES , $gm[ $iType_ ] , $iRec_ , $MAILSEND_NAMES );
		}

		//nUserに送信
		$enableNUser = SystemUtil::getSystemData( 'send_mail_nuser' );

		if( FALSE !== strpos( $enableNUser , $typeIndex ) ) //nUserへの送信が有効な場合
		{
			$nID  = $db->getData( $iRec_ , 'owner' );
			$nDB  = $gm[ 'nUser' ]->getDB();
			$nRec = $nDB->selectRecord( $nID );

			if( $nRec ) //レコードが存在する場合
			{
				$reception = $nDB->getData( $nRec , 'mail_reception' );
				$isMobile  = $nDB->getData( $nRec , 'is_mobile' );
				$nMail     = $nDB->getData( $nRec , 'mail' );

				if( FALSE === strpos( $reception , $typeIndex ) ) //通知拒否対象ではない場合
				{
					if( $isMobile ) //携帯から登録されたユーザーの場合
					{
						$currentPath   = $template_path;
						$template_path = $mobile_path;
						$template      = Template::getTemplate( 'nUser' , 1 , '' , 'DISABLED_PAY_MAIL' );
						$template_path = $currentPath;
					}
					else //その他の端末から登録されたユーザーの場合
						{ $template = Template::getTemplate( 'nUser' , 1 , '' , 'DISABLED_PAY_MAIL' ); }
					Mail::send( $template , $MAILSEND_ADDRES , $nMail , $gm[ $iType_ ] , $iRec_ , $MAILSEND_NAMES );
				}
			}
		}
	}

?>
