(function(){
    var id_list = null;
    var id_count = 0;
    var now_pt = 0;
    var success_cnt = 0;

	//idの長さ、
    var id_length = 8;
	//一度にメールを送信するユーザー数
	//phpとの通信がタイムアウトする場合はこちらを下げてテストしてください。
    var set = 50;

function sendMailStart(){
    id_list = document.mail.receive_id.value + "/";
    id_count = (id_list.length) / (id_length+1);
    now_pt = 0;
    
    sendMail( );
    
}

function nextMail(data){
    //PHP出力の確認
    if( !data.success ){
        $('#mail_success').html("error:認証エラーが発生しました。("+data.error+")");
        return;
    }else{
        success_cnt += (data.count);
    }
    if(now_pt*( id_length + 1 )*set < id_list.length ){

        sendMail();
    }else{
        sendMailEnd();
    }
}

function sendMailEnd(){
    //完了画面へ遷移
    $('#main_message').html( ' メールの送信が完了しました。<br><br>'+
        id_count + '件中' + success_cnt +'件のメールの送信に成功しました。<br><br>' + 
        '<a href="index.php" class="table1">トップページ</a>に戻ります。');

}

function sendMailError(){
    now_pt--;
    successed_id = id_list.substring( 0 , now_pt * set * (id_length+1) );
    errors_id = id_list.substring( now_pt * set * (id_length+1) , (now_pt+1) * set * (id_length+1) );
    untra_id = id_list.substring( (now_pt+1) * set * (id_length+1) , id_list.length );



	$('#main_message').html('<font color="red">error:通信エラーが発生しました。</font><br>'+
        id_count + '件中' + success_cnt +'件のメールの送信に成功しています。<br><br>' + 
		'以下は送信に成功したID一覧です。<br>'+
		'<textarea>'+successed_id.replace('/',"¥n")+'</textarea><br><br>'+
		'以下は送信中にエラーが発生したID一覧です。<br>'+
		'<textarea>'+errors_id.replace('/',"¥n")+'</textarea><br><br>'+
		'以下は未送信のID一覧です。<br>'+
		'<textarea>'+untra_id.replace('/',"¥n")+'</textarea><br><br>');
}

function sendMail(){
    ids = id_list.substring( now_pt * set * (id_length+1) , (now_pt+1) * set * (id_length+1) );
    setProgress();
    now_pt++;

    jQuery.ajax({
      url : 'multimail_send.php',
      type : 'POST',
      dataType : "json",
	  data : {
		 'send_id' : ids ,
		 'sub' : encodeURIComponent(document.mail.sub.value) ,
		 'main' : encodeURIComponent(document.mail.main.value)
	  } ,
      success : function(data){ nextMail(data); } ,
      error   : function(){ sendMailError(); }
    });

    
}

function setProgress(  ){
    upper = (now_pt+1)*set;
    if( (now_pt+1)*set > id_count ){ upper = id_count;}
    $('#mail_count').html( "全"+id_count+"件中、"+now_pt*set+"-"+upper+"件目を処理中。" );
    $('#mail_success').html( "成功数："+success_cnt+"件。" );
    
}

window.sendMailStart = sendMailStart;

})();