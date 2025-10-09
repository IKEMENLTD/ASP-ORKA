<?php

	class FactoryModel
	{
		protected $type     = null; ///<テーブル名
		protected $db       = null; ///<データベースオブジェクト
		protected $rec      = null; ///<レコードオブジェクト
		protected $originID = null; ///<独自ID

		//■コンストラクタ・デストラクタ

		/**
			@brief     コンストラクタ。
			@exception InvalidArgumentException 引数 $tyoe_ に存在しないテーブル名を指定した場合。
			@param     $type_ テーブル名。
		*/
		function __construct( $type_ )
		{
			global $gm;

			if( !array_key_exists( $type_ , $gm ) ) //GUIManagerが存在しない場合
				throw new InvalidArgumentException( 'テーブル[' . $type_ . ']は存在しません' );

			$this->type = $type_;
			$this->db   = $gm[ $type_ ]->getDB();
			$this->rec  = $this->db->getNewRecord();
		}

		//■処理

		function register()
		{
			if( $this->originID ) //独自IDが設定されている場合
				$this->db->setData( $this->rec , 'id' , $this->originID );
			else //独自IDが設定されていない場合
				$this->db->setData( $this->rec , 'id' , SystemUtil::GetNewID( $this->db , $this->type ) );

			if( in_array( 'regist' , $this->db->colName ) ) //registカラムが存在する場合
				$this->db->setData( $this->rec , 'regist' , time() );

			$this->db->addRecord( $this->rec );

			return new RecordModel( $this->type , $this->db->getData( $this->rec , 'id' ) );
		}

		//■パラメータ

		/**
			@brief  データベースを取得する。
			@return Databaseオブジェクト。
		*/
		function getDB()
		{
			return $this->db;
		}

		/**
			@brief  レコードデータを取得する。
			@return レコードデータ。
		*/
		function getRecord()
		{
			return $this->rec;
		}

		/**
			@brief  テーブル名を取得する。
			@return テーブル名。
		*/
		function getType()
		{
			return $this->type;
		}

		/**
			@brief IDを変更する。
			@param $id_ 独自ID。
		*/
		function setID( $id_ )
		{
			$this->originID = $id_;
		}

		/**
			@brief     任意のカラムの値を変更する。
			@exception InvalidArgumentException 引数 $name_ のカラムが存在しない場合。
			@param     $name_  カラム名。
			@param     $value_ カラムの値。
		*/
		function setData( $column_ , $value_ )
		{
			if( !in_array( $column_ , $this->db->colName ) ) //カラムが見つからない場合
				throw new InvalidArgumentException( $this->type . ' に [' . $name_ . '] カラムはありません' );

			return $this->db->setData( $this->rec , $column_ , $value_ );
		}
	}

?>
