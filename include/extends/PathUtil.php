<?php

	//★クラス //

	/**
		@brief ファイルパス関係のユーティリティクラス。
	*/
	class PathUtil //
	{
		//■処理 //

		/**
			@brief     優先ディレクトリを追加する。
			@details   相対パスによるinclude/requireやPathUtilによるパス修飾の際、カレントディレクトリよりも優先して使用されるディレクトリを追加します。
			@param[in] $iPath 任意のディレクトリパス。存在しないディレクトリであった場合は無視されます。
			@remarks   AddDeployPathを複数回実行した場合、$iPathは単に先頭に順に追加されていきます。つまり、最後に実行したAddDeployPathの$iPathが最も優先度の高いパスになります。
			@remarks   include './foobar.php' のように明示的にカレントディレクトリからの相対パスを指定している場合、この関数で設定したパスは使用されない点に注意してください。\n
			           そのような表記は include 'foobar.php' に修正する必要があります。これはinclude/requireの仕様によるものです。
			@remarks   この関数の設定はModifyLSTFilePath等にも反映されます。
		*/
		static function AddDeployPath( $iPath ) //
		{
			if( !is_dir( $iPath ) ) //存在しないディレクトリの場合
				{ return; }

			$currentPath = get_include_path();

			if( FALSE !== strpos( $currentPath , ';' ) ) //パス区切りが;の場合(Windows)
				{ set_include_path( $iPath . ';' . $currentPath ); }
			else //パス区切りが:の場合(UNIX)
				{ set_include_path( $iPath . ':' . $currentPath ); }

			array_unshift( self::$DeployPaths , $iPath );
		}

		/**
			@brief     入出力のためのファイルパスを修飾する。
			@param[in] $iPath       任意のファイルパス。
			@param[in] $iModifyMode 'exists'を指定した場合、優先ディレクトリのファイルパスが存在しない場合は次のパスを検索するようになります。
			@return    修飾されたファイルパス。
			@remarks   AddDepolyPathで指定された優先ディレクトリがある場合はそのディレクトリ内のパスを返します。そうでない場合、$iPathをそのまま返します。\n
			           存在するファイルを検索して取得したい場合は$iModifyModeを指定してください。
		*/
		static function ModifyPath( $iPath , $iModifyMode = '' ) //
		{
			foreach( self::$DeployPaths as $deployPath ) //全ての優先ディレクトリを処理
			{
				$deployPath = self::JoinPath( Array( $deployPath , $iPath ) );

				if( 'exists' != $iModifyMode ) //ファイルの存在確認が指定されていない場合
					{ return $deployPath; }
				else if( is_file( $deployPath ) ) //優先ディレクトリ内にファイルがある場合
					{ return $deployPath; }
			}

			return $iPath;
		}

		/**
			@brief     ファイルパスを結合する。
			@param[in] $iPaths 結合するファイルパス配列。
			@return    結合されたファイルパス。
		*/
		static function JoinPath( $iPaths ) //
		{
			$checkedPaths = Array();

			foreach( $iPaths as $path ) //全てのパスを処理
			{
				$path = self::TrimPath( $path );

				if( !$path ) //パスが空白の場合
					{ continue; }

				$checkedPaths[] = $path;
			}

			return implode( '/' , $checkedPaths );
		}

		/**
			@brief     ファイルパスの先頭にある./と末尾にある/を取り除く。
			@param[in] $iPath 任意のファイルパス。
			@return    トリミングされたファイルパス。
		*/
		static function TrimPath( $iPath ) //
		{
			$iPath = preg_replace( '/^\.\//' , '' , $iPath );
			$iPath = preg_replace( '/\/$/'   , '' , $iPath );

			return $iPath;
		}

		/**
			@brief     テンプレートファイルのパスを適切に修飾する。
			@param[in] $iTemplatePath 任意のテンプレートファイルパス。
			@exception Logic テンプレート格納パスが未定義の時点で呼び出された場合。
			@return    修飾されたテンプレートファイルパス。
		*/
		static function ModifyTemplateFilePath( $iTemplatePath ) //
		{
			global $template_path;

			if( !isset( $template_path ) ) //テンプレートパスが定義されていない場合
				{ throw new LogicException(); }

			return self::ModifyLoadPath( $iTemplatePath , $template_path );
		}

		/**
			@brief     LSTファイルのパスを適切に修飾する。
			@param[in] $iLSTPath 任意のLSTファイルパス。
			@exception Logic LST格納パスが未定義の時点で呼び出された場合。
			@return    修飾されたLSTファイルパス。
		*/
		static function ModifyLSTFilePath( $iLSTPath )
		{
			global $lst_path;

			if( !isset( $lst_path ) ) //LSTパスが定義されていない場合
				{ throw new LogicException(); }

			return self::ModifyLoadPath( $iLSTPath , $lst_path );
		}

		/**
			@brief     TDBファイルのパスを適切に修飾する。
			@param[in] $iTDBPath 任意のTDBファイルパス。
			@exception Logic TDB格納パスが未定義の時点で呼び出された場合。
			@return    修飾されたTDBファイルパス。
		*/
		static function ModifyTDBFilePath( $iTDBPath )
		{
			global $tdb_path;

			if( !isset( $tdb_path ) ) //TDBパスが定義されていない場合
				{ throw new LogicException(); }

			return self::ModifyLoadPath( $iTDBPath , $tdb_path );
		}

		/**
			@brief     インデックスファイルのパスを適切に修飾する。
			@param[in] $iIndexPath 任意のインデックスファイルパス。
			@exception Logic インデックス格納パスが未定義の時点で呼び出された場合。
			@return    修飾されたインデックスファイルパス。
		*/
		static function ModifyIndexFilePath( $iIndexPath )
		{
			global $index_path;

			if( !isset( $index_path ) ) //インデックスパスが定義されていない場合
				{ throw new LogicException(); }

			return self::ModifyLoadPath( $iIndexPath , $index_path );
		}

		/**
			@brief     Systemクラスファイルのパスを取得する。
			@param[in] $iTableName            取得したいSystemクラスのテーブル名。
			@param[in] $iPiroritizeModuleName 優先して検索したいモジュールパスがある場合は、そのモジュール名。
			@exception Logic Systemクラス格納パスが未定義の時点で呼び出された場合。
			@return    修飾されたSystemクラスファイルパス。
			@remarks   通常パスとモジュールパスに同名のSystemクラスがある場合は、$iPrioritizeModuleNameで指定されている場合を除き通常パスを優先して返します。
		*/
		static function ModifySystemFilePath( $iTableName , $iPrioritizeModuleName = null ) //
		{
			global $MODULES;
			global $system_path;

			if( !isset( $system_path ) ) //システムパスが定義されていない場合
				{ throw new LogicException(); }

			$basePath   = self::TrimPath( $system_path ) . '/' . $iTableName . 'System.php';
			$modifyPath = $basePath;

			if( $iPrioritizeModuleName ) //優先モジュールの指定がある場合
			{
				$prioritizePath = 'module/' . $iPrioritizeModuleName . '/' . $basePath;

				if( is_file( $prioritizePath ) ) //優先モジュール内にファイルがある場合
					{ $modifyPath = $prioritizePath; }
			}

			if( !is_file( $modifyPath ) ) //優先モジュール・通常パスにファイルがない場合
			{
				if( isset( $MODULES ) ) //モジュール配列が定義されている場合
				{
					foreach( array_keys( $MODULES ) as $moduleName ) //全てのモジュール名を処理
					{
						$modulePath = 'module/' . $moduleName . '/' . $basePath;

						if( is_file( $modulePath ) ) //モジュール内にファイルがある場合
						{
							$modifyPath = $modulePath;

							break;
						}
					}
				}
			}

			foreach( self::$DeployPaths as $deployPath ) //全ての優先ディレクトリを処理
			{
				$deployPath = self::JoinPath( Array( self::TrimPath( $deployPath ) , $modifyPath ) );

				if( is_file( $deployPath ) ) //優先ディレクトリ内にファイルがある場合
				{
					$modifyPath = $deployPath;

					break;
				}
			}

			return $modifyPath;
		}

		//■内部処理 //

		/**
			@brief     システムファイルのパスを修飾する。
			@param[in] $iPath         任意のファイルパス。
			@param[in] $iModifyPrefix $iPathを前置修飾するためのパス。モジュールlstなど例外的なパス展開が必要な場合に使用します。
			@return    修飾されたファイルパス。
			@remarks   $iPath内にモジュール変数([moduleName])が含まれる場合はモジュールパスとして展開します。ただし、モジュールファイルが存在しない場合は通常パスを返します。
			@remarks   AddDepolyPathで指定された優先ディレクトリ内にファイルが存在する場合はそちらのパスを返します。\n
			           ファイルが存在しない場合は通常パスを返す点に注意してください。
		*/
		private static function ModifyLoadPath( $iPath , $iModifyPrefix = '' ) //
		{
			$iPath         = self::TrimPath( $iPath );
			$iModifyPrefix = self::TrimPath( $iModifyPrefix );

			$modifyPath = self::JoinPath( Array( $iModifyPrefix , $iPath ) );

			if( preg_match( '/(.*)\[(\w+)\]\/?(.*)/' , $iPath , $matches ) ) //パス内にモジュール変数がある場合
			{
				$modulePath = self::JoinPath( Array( $matches[ 1 ] , 'module' , $matches[ 2 ] , $iModifyPrefix , $matches[ 3 ] ) );

				if( is_file( $modulePath ) ) //モジュール内にファイルがある場合
					{ $modifyPath = $modulePath; }
			}

			foreach( self::$DeployPaths as $deployPath ) //全ての優先ディレクトリを処理
			{
				$deployPath = self::JoinPath( Array( $deployPath , $modifyPath ) );

				if( is_file( $deployPath ) ) //優先ディレクトリ内にファイルがある場合
				{
					$modifyPath = $deployPath;

					break;
				}
			}

			return $modifyPath;
		}

		//■変数 //

		private static $DeployPaths = Array(); ///<AddDeployPathで追加したパスの排列。
	}
