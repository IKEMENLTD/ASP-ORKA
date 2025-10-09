/**
	@brief 外部ページにメッセージイベントを送信する。
	@param $iURL      外部ページのURL。
	@param $iData     送信するデータ。
	@oaram $iCallback イベントの戻り値を処理するコールバック関数。
	@remarks メッセージを送信する側のページで使用します。
*/
function sendMessage( $iURL , $iData , $iCallback ) //
{
	if( document.body ) //DOMが構築済みの場合
	{
		createMessageFrame();
		submitMessageFrame( $iURL , $iData , $iCallback );
	}
	else //DOMが未構築の場合
		{ window.addEventListener( 'load' , function(){ createMessageFrame(); submitMessageFrame( $iURL , $iData , $iCallback ); } ); }
}

/**
	@brief   メッセージイベントの応答処理を定義する。
	@param   $iCallback 受信データを処理するコールバック関数。
	@remarks メッセージを受信する側のページで使用します。
*/
function dispatchMessage( $iCallback ) //
	{ window.addEventListener( 'message' , function(){ event.source.postMessage( $iCallback( event ) , event.origin );} , false ); }

/**
	@brief   メッセージイベントを送信するためのiframeをbody末尾に埋め込む。
	@remarks 既にiframeが作成済みの場合はスキップされます。
*/
function createMessageFrame() //
{
	if( $MessageFrame ) //iframeが作成済みの場合
		{ return; }

	$MessageFrame = document.createElement( 'iframe' );

	$MessageFrame.setAttribute( 'style' , 'display:none;width:1px;height:1px;' );
	window.addEventListener( 'message' , function(){ $MessageReaction() } );
	$MessageFrame.addEventListener( 'load' , function(){ $MessagePost() } );
	document.body.appendChild( $MessageFrame );
}

/**
	@brief 作成したiframeを使用してメッセージイベントを送信する。
	@param $iURL      外部ページのURL。
	@param $iData     送信するデータ。
	@oaram $iCallback イベントの戻り値を処理するコールバック関数。
*/
function submitMessageFrame( $iURL , $iData , $iCallback ) //
{
	if( $iURL != $MessageFrame.contentDocument.location.href ) //新しいURLにメッセージを送信する場合
	{
		$MessageReaction = function(){ $iCallback( event.data ); };
		$MessagePost     = function(){ $MessageFrame.contentWindow.postMessage( $iData , $iURL ); }

		$MessageFrame.contentDocument.location.replace( $iURL );
	}
	else //接続済みURLにメッセージを送信する場合
		{ $MessageFrame.contentWindow.postMessage( $iData , $iURL ); }
}

var $MessageFrame    = null;
var $MessagePost     = function(){};
var $MessageReaction = function(){};
