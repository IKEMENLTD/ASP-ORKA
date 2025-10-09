<?php

	/**
		@brief   Salesテーブルの処理セット。
		@ingroup SystemAPI
	*/
	class SalesLogic
	{
		//■データ取得

		/**
			@brief     最も低いユーザーランクのレコードIDを取得する。
			@return    レコードID。
			@attention ユーザーランクテーブルが空の場合は、空文字列を返します。
		*/
		static function GetLowestRankID()
		{
			$db    = SystemUtil::getGMforType( self::$Type )->getDB();
			$table = $db->getTable();
			$row   = $db->getRow( $table );

			if( !$row ) //ユーザーランクがない場合
				{ return ''; }

			$table = $db->sortTable( $table , 'shadow_id' , 'asc' );
			$rec   = $db->getRecord( $table , 0 );
			$id    = $db->getData( $rec , 'id' );

			return $id;
		}

		//■変数
		private static $Type = 'sales'; ///<テーブル名。
	}
