<?php

	/**
		@brief   テーブルの処理セット。
		@ingroup SystemAPI
	*/
	class TableLogic
	{
		//■データ取得

		/**
			@brief     テーブルに指定されたIDのレコードが存在するか確認する。
			@exception InvalidArgument $iTablename_ , $iRecordID_ のいずれかに無効な値を指定した場合。
			@param[in] $iTableName_ テーブル名。
			@param[in] $iRecordID_  レコードID。
			@retval    true  レコードが存在する場合。
			@retval    false レコードが存在しない場合。
		*/
		static function ExistsRecord( $iTableName_ , $iRecordID_ )
		{
			if( !$iRecordID_ )
				{ return false; }

			$db    = SystemUtil::getGMforType( $iTableName_ )->getDB();
			$table = $db->getTable();
			$table = $db->searchTable( $table , 'id' , '=' , $iRecordID_ );
			$row   = $db->getRow( $table );

			if( $row ) //レコードが見つかった場合
				{ return true; }
			else //レコードが見つからなかった場合
				{ return false; }
		}
	}
