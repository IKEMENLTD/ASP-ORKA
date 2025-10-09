<?php

	/**
		@brief   コンセプトクラス。
		@details スクリプトのコンセプトを満たしていない場合に例外をスローします。
	*/
	class ConceptCheck
	{
		private static $ExceptionName = 'InvalidQueryException'; ///<スローする例外クラス名

		/**
			@brief     スローする例外を変更する。
			@exception InvalidArgumentException $name_ に空文字列を指定した、または $name_ クラスが見つからない場合。
			@param     $name_ 例外クラス名。
		*/
		private static function SetExceptionName( $name_ )
		{
			if( !$name_ )
				throw new InvalidArgumentException( '$name_ は必須のパラメータです' );

			if( !class_exists( $name_ ) )
				throw new InvalidArgumentException( 'クラス ' . $name_ . ' は定義されていません' );

			self::$ExceptionName = $name_;
		}

		/**
			@brief     必須パラメータをチェックする。
			@exception InvalidQueryException パラメータがセットされていない場合。
			@param     $method_    チェックするパラメータ配列。
			@param     $keys_      チェックするキー配列。
			@param     $option_    チェック方式。
			@param     $exception_ 例外クラス名。
		*/
		static function IsEssential( &$method_ , $keys_ , $option_ = 'and' , $exception_ = 'InvalidQueryException' )
		{
			self::SetExceptionName( $exception_ );

			switch( $option_ )
			{
				case 'and' :
					foreach( $keys_ as $key )
					{
						if( !array_key_exists( $key , $method_ ) )
							throw new self::$ExceptionName( $key . 'は必須のパラメータです' );
					}
					break;

				case 'or' :
					foreach( $keys_ as $key )
					{
						if( array_key_exists( $key , $method_ ) )
							return;
					}
					throw new self::$ExceptionName( '次のパラメータのいずれかを指定しなければいけません:' . implode( ',' , $keys_ ) );

				default :
					throw new InvalidArgumentException( '不明なオプションです' );
			}
		}

		/**
			@brief     nullパラメータをチェックする。
			@exception InvalidQueryException パラメータが空の場合。
			@param     $method_    チェックするパラメータ配列。
			@param     $keys_      チェックするキー配列。
			@param     $option_    チェック方式。
			@param     $exception_ 例外クラス名。
		*/
		static function IsNotNull( &$method_ , $keys_ , $option_ = 'and' , $exception_ = 'InvalidQueryException' )
		{
			self::SetExceptionName( $exception_ );

			switch( $option_ )
			{
				case 'and' :
					foreach( $keys_ as $key )
					{
						if( !$method_[ $key ] )
							throw new self::$ExceptionName( $key . 'が空です' );
					}
					break;

				case 'or' :
					foreach( $keys_ as $key )
					{
						if( $method_[ $key ] )
							return;
					}
					throw new self::$ExceptionName( '次のパラメータのいずれかに有効な値を指定しなければいけません:' . implode( ',' , $keys_ ) );

				default :
					throw new InvalidArgument( '不明なオプションです' );
			}
		}

		/**
			@brief     パラメータの型をチェックする。
			@exception InvalidQueryException パラメータが配列の場合。
			@param     $method_    チェックするパラメータ配列。
			@param     $keys_      チェックするキー配列。
			@param     $exception_ 例外クラス名。
		*/
		static function IsScalar( &$method_ , $keys_ , $exception_ = 'InvalidQueryException' )
		{
			self::SetExceptionName( $exception_ );

			foreach( $keys_ as $key )
			{
				if( is_array( $method_[ $key ] ) )
					throw new self::$ExceptionName( $key . 'に配列を指定することはできません' );
			}
		}

		/**
			@brief     パラメータの型をチェックする。
			@exception InvalidQueryException パラメータがスカラの場合。
			@param     $method_    チェックするパラメータ配列。
			@param     $keys_      チェックするキー配列。
			@param     $exception_ 例外クラス名。
		*/
		static function IsArray( &$method_ , $keys_ , $exception_ = 'InvalidQueryException' )
		{
			self::SetExceptionName( $exception_ );

			foreach( $keys_ as $key )
			{
				if( !is_array( $method_[ $key ] ) )
					throw new self::$ExceptionName( $key . 'にスカラを指定することはできません' );
			}
		}
	}
?>
