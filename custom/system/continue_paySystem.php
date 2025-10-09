<?php

	include_once 'custom/system/BasePaySystem.php';

	/**
	 * システムコールクラス
	 * 
	 * @author 丹羽一智
	 * @version 1.0.0
	 * 
	 */
	class continue_paySystem extends BasePaySystem
	{
		//■データ取得

		/**
			@brief  テーブル名を取得する。
			@return テーブル名。
		*/
		function GetType()
			{ return 'continue_pay'; }
	}
