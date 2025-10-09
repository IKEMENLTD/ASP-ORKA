<?php

	/**
		@brief   例外オブジェクト。
		@details 不正なクエリパラメータを受け取った場合にスローされます。¥n
		         この例外をキャッチした場合は、操作が受け付けられない旨をユーザーに通知しなければいけません。
	*/
	class InvalidQueryException extends Exception
	{}

	/**
		@brief   例外オブジェクト。
		@details 不正なアクセスが発生した場合にスローされます。¥n
		         この例外をキャッチした場合は、アクセス権限がない旨をユーザーに通知しなければいけません。
	*/
	class IllegalAccessException extends Exception
	{}

	/**
		@brief   例外オブジェクト。
		@details ファイルの入出力に失敗した場合にスローされます。¥n
		         この例外をキャッチした場合は、現在システムが利用できない旨をユーザーに通知しなければいけません。
	*/
	class FileIOException extends Exception
	{}

	/**
		@brief   例外オブジェクト。
		@details データベースの更新に失敗した場合にスローされます。¥n
		         この例外をキャッチした場合は、操作が適用されなかった可能性がある旨をユーザーに通知しなければいけません。
	*/
	class UpdateFailedException extends Exception
	{}

	/**
		@brief   例外オブジェクト。
		@details 何らかの理由で画面出力に失敗した場合にスローされます。¥n
		         この例外をキャッチした場合は、現在システムが利用できない旨をユーザーに通知しなければいけません。
	*/
	class OutputFailedException extends Exception
	{}

	if( !class_exists( RuntimeException ) )
	{
		/**
			@brief   例外オブジェクト。
			@details 実行時にエラーが発生した場合にスローされます。¥n
			         この例外をキャッチした場合は、現在システムが利用できない旨をユーザーに通知しなければいけません。
		*/
		class RuntimeException extends Exception
		{}
	}

	if( !class_exists( InvalidArgumentException ) )
	{
		/**
			@brief   例外オブジェクト。
			@details 関数のパラメータに不正な値が指定された場合にスローされます。¥n
			         この例外をキャッチした場合は、現在システムが利用できない旨をユーザーに通知しなければいけません。
		*/
		class InvalidArgumentException extends Exception
		{}
	}

	/**
		@brief   例外オブジェクト。
		@details コマンドコメントのパラメータに不正な値が指定された場合にスローされます。¥n
		         この例外をキャッチした場合は、現在システムが利用できない旨をユーザーに通知しなければいけません。
	*/
	class InvalidCCArgumentException extends Exception
	{}
?>
