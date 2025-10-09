<?php
	include_once 'include/extends/SSLUtil.php';

	$CONFIG_SSL_ENABLE = false; //SSLへのリダイレクトを有効にする場合はtrue
	$CONFIG_SSL_MOBILE = false; //携帯電話でもSSLを仕様する場合はtrue
    $CONFIG_SSL_CHECK_CONTROLLER_NAME = false;


	$CONFIG_SSL_ON_CHECK_USERS = Array( //SSLを常時有効にするユーザー
			'nUser' , 'cUser', 'admin'
	);

    $CONFIG_SSL_ON_CHECK_CONTROLLER_NAME = Array( //SSLを常時有効にするコントロール
    );

    $CONFIG_SSL_OUT_CHECK_CONTROLLER_NAME = Array( //SSLを常時無効にするコントロール
    );

	$CONFIG_SSL_ON_CHECK_FILES = Array(
			'index.php', 'regist.php', 'edit.php', 'cart.php', 'login.php', 'reminder.php', 'link.php', 'add.php',
			'activate.php', 'info.php', 'other.php', 'page.php', 'report.php', 'search.php'
	);
	
	$CONFIG_SSL_OUT_CHECK_FILES = Array(
	);
