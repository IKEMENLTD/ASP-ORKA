<?php

	class TableModel
	{
		protected $type  = null; //テーブル名
		protected $db    = null; //Databaseオブジェクト
		protected $table = null; //Tableオブジェクト

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

			$this->type  = $type_;
			$this->db    = $gm[ $type_ ]->getDB();
			$this->table = $this->db->getTable();
		}

		//■処理

		/**
			@brief     テーブルを検索する。
			@exception InvalidArgumentException 引数 $column_ に存在しないカラムを指定した場合。
			@param     $column_ カラム名。
			@param     $or_     演算子。
			@param     $value_  値。
		*/
		function search( $column_ , $op_ , $value_ )
		{
			if( !in_array( $column_ , $this->db->colName ) ) //カラムが見つからない場合
				throw new InvalidArgumentException( '[' . $this->type . '] に [' . $column_ . '] カラムはありません' );

			$this->table = $this->db->searchTable( $this->table , $column_ , $op_ , $value_ );
		}

		/**
			@brief     テーブルをand検索する。
			@exception InvalidArgumentException 引数に存在しないカラムを指定した場合。
			@param     $terms_ 条件配列。以下の構造の配列を任意の数だけ格納する
				@li 0 カラム名。
				@li 1 演算子。
				@li 2 値。
		*/
		function searchAnd( $terms_ )
		{
			foreach( $terms_ as $term )
			{
				List( $column , $op , $value ) = $term;

				if( !in_array( $column , $this->db->colName ) ) //カラムが見つからない場合
					throw new InvalidArgumentException( '[' . $this->type . '] に [' . $column . '] カラムはありません' );

				$this->table = $this->db->searchTable( $this->table , $column , $op , $value );
			}
		}

		/**
			@brief     テーブルをor検索する。
			@exception InvalidArgumentException 引数に存在しないカラムを指定した場合。
			@param     $terms_ 条件配列。以下の構造の配列を任意の数だけ格納する
				@li 0 カラム名。
				@li 1 演算子。
				@li 2 値。
		*/
		function searchOr( $terms_ )
		{
			if( !count( $terms_ ) ) ///パラメータが空の場合
				return;

			//or結合時の無駄なクエリ増加を防ぐため、空のテーブルをベースに検索する
			$baseTable = $this->db->getTable();
			$results   = Array();

			foreach( $terms_ as $term )
			{
				List( $column , $op , $value ) = $term;

				if( !in_array( $column , $this->db->colName ) ) //カラムが見つからない場合
					throw new InvalidArgumentException( '[' . $this->type . '] に [' . $column . '] カラムはありません' );

				$results[] = $this->db->searchTable( $baseTable , $column , $op , $value );
			}

			$baseTable = array_shift( $results );

			foreach( $results as $result )
				$baseTable = $this->db->orTable( $baseTable , $result );

			$this->table = $this->db->andTable( $this->table , $baseTable );
		}

		/**
			@brief レコードの取得範囲を設定する。
			@param $ofset_ 開始位置。
			@param $limit_ 最大行数。
		*/
		function setLimitOffset( $ofset_ , $limit_ )
		{
			$this->db->limitOffset( $this->table , $ofset_ , $limit_ );
		}

		/**
			@brief     テーブルを昇順ソートする。
			@exception InvalidArgumentException 引数 $column_ に存在しないカラムを指定した場合。
			@param     $column_ カラム名。
		*/
		function sortAsc( $column_ )
		{
			if( !in_array( $column_ , $this->db->colName ) ) //カラムが見つからない場合
				throw new InvalidArgumentException( '[' . $this->type . '] に [' . $column_ . '] カラムはありません' );

			$this->table = $this->db->sortTable( $this->table , $column_ , 'asc' );
		}

		/**
			@brief     テーブルを降順ソートする。
			@exception InvalidArgumentException 引数 $column_ に存在しないカラムを指定した場合。
			@param     $column_ カラム名。
		*/
		function sortDesc( $column_ )
		{
			if( !in_array( $column_ , $this->db->colName ) ) //カラムが見つからない場合
				throw new InvalidArgumentException( '[' . $this->type . '] に [' . $column_ . '] カラムはありません' );

			$this->table = $this->db->sortTable( $this->table , $column_ , 'desc' );
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
			@brief  テーブルの行数を取得する。
			@return 行数。
		*/
		function getRow()
		{
			return $this->db->getRow( $this->table );
		}

		/**
			@brief  テーブルを取得する。
			@return Tableオブジェクト。
		*/
		function getTable()
		{
			return $this->table;
		}

		/**
			@brief  テーブル名を取得する。
			@return テーブル名。
		*/
		function getType()
		{
			return $this->type;
		}

		//■取得

		/**
			@brief     RecordModelオブジェクトを取得する。
			@exception OutOfRangeException 引数 $row_ に有効範囲外の行を指定した場合。
			@return    RecordModelオブジェクト。
		*/
		function getRecordModel( $row_ )
		{
			$row = $this->getRow();

			if( $row <= $row_ ) //行数を超えている場合
				throw new InvalidArgumentException( "行数指定を超えています" );

			$rec = $this->db->getRecord( $this->table , $row_ );
			$id  = $this->db->getData( $rec , 'id' );

			return new RecordModel( $this->type , $id );
		}
	}

?>
