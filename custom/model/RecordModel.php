<?php

	class RecordModel
	{
		protected $type = null; ///<テーブル名
		protected $db   = null; ///<データベースオブジェクト
		protected $rec  = null; ///<レコードオブジェクト

		//■コンストラクタ・デストラクタ

		/**
			@brief     コンストラクタ。
			@exception InvalidArgumentException 引数 $tyoe_ に存在しないテーブル名を指定した場合。
			@exception RuntimeException         引数 $id_ に指定されたIDを持つレコードが見つからない場合。
			@param     $type_ テーブル名。
			@param     $id_   レコードID。
		*/
		function __construct( $type_ , $id_ )
		{
			global $gm;

			if( !array_key_exists( $type_ , $gm ) ) //GUIManagerが存在しない場合
				throw new InvalidArgumentException( 'テーブル[' . $type_ . ']は存在しません' );

			$this->type = $type_;
			$this->db   = $gm[ $type_ ]->getDB();
			$this->rec  = $this->db->selectRecord( $id_ );

			if( !$this->rec ) //レコードが見つからない場合
				throw new RuntimeException( '[' . $type_ . ']' . ' に ID[ ' . $id_ . ' ]を持つレコードはありません' );
		}

		//■処理

		/**
			@brief レコードを更新する。
		*/
		function update()
		{
			$this->db->updateRecord( $this->rec );
		}

		//■パラメータ

		/**
			@brief     任意のカラムの値を取得する。
			@exception InvalidArgumentException 引数 $name_ のカラムが存在しない場合。
			@param     $name_ カラム名。
			@return    カラムの値。
		*/
		function getData( $name_ )
		{
			if( !in_array( $name_ , $this->db->colName ) ) //カラムが見つからない場合
				throw new InvalidArgumentException( $this->type . ' に [' . $name_ . '] カラムはありません' );

			return $this->db->getData( $this->rec , $name_ );
		}

		/**
			@brief  データベースを取得する。
			@return Databaseオブジェクト。
		*/
		function getDB()
		{
			return $this->db;
		}

		/**
			@brief  IDを取得する。
			@return レコードID。
		*/
		function getID()
		{
			return $this->db->getData( $this->rec , 'id' );
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
			@brief     任意のカラムの値を変更する。
			@exception InvalidArgumentException 引数 $name_ のカラムが存在しない場合。
			@param     $name_  カラム名。
			@param     $value_ カラムの値。
		*/
		function setData( $name_ , $value_ )
		{
			if( !in_array( $name_ , $this->db->colName ) )
				throw new InvalidArgumentException( $this->type . ' に [' . $name_ . '] カラムはありません' );

			return $this->db->setData( $this->rec , $name_ , $value_ );
		}
	}

?>
