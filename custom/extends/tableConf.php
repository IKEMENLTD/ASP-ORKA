<?php

/*****************************************************************
 ** 汎用プログラム（regist.php / search.php / info.php）用 定義 **
 *****************************************************************/
/**********          テーブル定義          **********/


/**********          adminの定義          **********/

	$EDIT_TYPE                            = 'admin';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = true;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = 'mail';
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = 'pass';
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = 'pass_confirm';
	$LST[ $EDIT_TYPE ]                    = 'admin.csv';
	$TDB[ $EDIT_TYPE ]                    = 'admin.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'ADMIN';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;


/**********          nUserの定義          **********/

	$EDIT_TYPE                            = 'nUser';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = true;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = 'mail';
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = 'pass';
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = 'pass_confirm';
	$LST[ $EDIT_TYPE ]                    = 'nuser.csv';
	$TDB[ $EDIT_TYPE ]                    = 'nuser.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'N';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;
	$THIS_TABLE_IS_QUICK[ $EDIT_TYPE ] 	  = true;

/**********          adwaresの定義          **********/

	$EDIT_TYPE                            = 'adwares';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'adwares.csv';
	$TDB[ $EDIT_TYPE ]                    = 'adwares.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'A';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;


/**********          categoryの定義          **********/

	$EDIT_TYPE                            = 'category';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'category.csv';
	$TDB[ $EDIT_TYPE ]                    = 'category.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'CT';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;


/**********          accessの定義          **********/

	$EDIT_TYPE                            = 'access';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'access.csv';
	$TDB[ $EDIT_TYPE ]                    = 'access.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'AC';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;


/**********          payの定義          **********/

	$EDIT_TYPE                            = 'pay';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'pay.csv';
	$TDB[ $EDIT_TYPE ]                    = 'pay.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 0;


/**********          click_payの定義          **********/

	$EDIT_TYPE                            = 'click_pay';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'click_pay.csv';
	$TDB[ $EDIT_TYPE ]                    = 'click_pay.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 32;
	

/**********          click_payの定義          **********/

	$EDIT_TYPE                            = 'continue_pay';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'continue_pay.csv';
	$TDB[ $EDIT_TYPE ]                    = 'continue_pay.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 32;
	

/**********          returnssの定義          **********/

	$EDIT_TYPE                            = 'returnss';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'returnss.csv';
	$TDB[ $EDIT_TYPE ]                    = 'returnss.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'R';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;


/**********          prefecturesの定義          **********/

	$EDIT_TYPE                            = 'prefectures';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'prefectures.csv';
	$TDB[ $EDIT_TYPE ]                    = 'prefectures.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'PF';
	$ID_LENGTH[ $EDIT_TYPE ]              = 4;


/**********          areaの定義          **********/

	$EDIT_TYPE                            = 'area';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'area.csv';
	$TDB[ $EDIT_TYPE ]                    = 'area.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'AREA';
	$ID_LENGTH[ $EDIT_TYPE ]              = 6;


/**********          salesの定義          **********/

	$EDIT_TYPE                            = 'sales';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'sales.csv';
	$TDB[ $EDIT_TYPE ]                    = 'sales.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'SA';
	$ID_LENGTH[ $EDIT_TYPE ]              = 4;


/**********          multimailの定義          **********/

	$EDIT_TYPE                            = 'multimail';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'multimail.csv';
	$TDB[ $EDIT_TYPE ]                    = 'multimail.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'MM';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;


/**********          templateの定義          **********/

	$EDIT_TYPE                            = 'template';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'template.csv';
	$TDB[ $EDIT_TYPE ]                    = 'template.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'T';
	$ID_LENGTH[ $EDIT_TYPE ]              = 5;


/**********          systemの定義          **********/

	$EDIT_TYPE                            = 'system';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'system.csv';
	$TDB[ $EDIT_TYPE ]                    = 'system.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 0;


/**********       invitationの定義       **********/

	$EDIT_TYPE                            = 'invitation';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'invitation.csv';
	$TDB[ $EDIT_TYPE ]                    = 'invitation.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'I';
	$ID_LENGTH[ $EDIT_TYPE ]              = 8;

/**********          pageの定義          **********/

	$EDIT_TYPE                            = 'page';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'page.csv';
	$TDB[ $EDIT_TYPE ]                    = 'page.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'P';
	$ID_LENGTH[ $EDIT_TYPE ]              = 6;


/**********          tierの定義          **********/

	$EDIT_TYPE                            = 'tier';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'tier.csv';
	$TDB[ $EDIT_TYPE ]                    = 'tier.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = 'T';
	$ID_LENGTH[ $EDIT_TYPE ]              = 33;


/**********          zenginkyoの定義          **********/

	$EDIT_TYPE                            = 'zenginkyo';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'zenginkyo.csv';
	$TDB[ $EDIT_TYPE ]                    = 'zenginkyo.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 5;


/**********          zenginkyoの定義          **********/

	$EDIT_TYPE                            = 'log_pay';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'log_pay.csv';
	$TDB[ $EDIT_TYPE ]                    = 'log_pay.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 10;

/**********          ブラックリストの定義          **********/

	$EDIT_TYPE                            = 'blacklist';
	$TABLE_NAME[]                         = $EDIT_TYPE;
	$THIS_TABLE_IS_USERDATA[ $EDIT_TYPE ] = false;
	$THIS_TABLE_IS_NOHTML[ $EDIT_TYPE ]   = false;
	$LOGIN_KEY_COLUM[ $EDIT_TYPE ]        = null;
	$LOGIN_PASSWD_COLUM[ $EDIT_TYPE ]     = null;
	$LOGIN_PASSWD_COLUM2[ $EDIT_TYPE ]    = null;
	$LST[ $EDIT_TYPE ]                    = 'blacklist.csv';
	$TDB[ $EDIT_TYPE ]                    = 'blacklist.csv';
	$ID_HEADER[ $EDIT_TYPE ]              = '';
	$ID_LENGTH[ $EDIT_TYPE ]              = 10;
