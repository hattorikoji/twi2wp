<?php
require_once "request_oauth.php";
require_once "save_token.php";

//設定項目
$api_key = "" ;	// API Key
$api_secret = "" ;	// API Secret
$callback_url = "http://hogehoge.com/register.php" ;	// Callback URL (このプログラムのURL)


/*** [手順4] ユーザーが戻ってくる ***/

if (isset( $_GET['oauth_token']) || isset($_GET["oauth_verifier"])){

	/*** [手順5] [手順5] アクセストークンを取得する ***/

	//[リクエストトークン・シークレット]をセッションから呼び出す
	session_start() ;
	$request_token_secret = $_SESSION["oauth_token_secret"] ;

	// リクエストURL
	$request_url = "https://api.twitter.com/oauth/access_token" ;

	// リクエストメソッド
	$request_method = "POST" ;

	// キーを作成する
	$signature_key = rawurlencode( $api_secret ) . "&" . rawurlencode( $request_token_secret ) ;

	// パラメータ([oauth_signature]を除く)を連想配列で指定
	$params = array(
		"oauth_consumer_key" => $api_key ,
		"oauth_token" => $_GET["oauth_token"] ,
		"oauth_signature_method" => "HMAC-SHA1" ,
		"oauth_timestamp" => time() ,
		"oauth_verifier" => $_GET["oauth_verifier"] ,
		"oauth_nonce" => microtime() ,
		"oauth_version" => "1.0" ,
	) ;
       
        
        $response = get_oauth_token($request_url,$request_method,$params,$signature_key);
	$query = [] ;
	parse_str( $response, $query ) ;

        save_as_json($query);


// 認証画面から戻ってきた時 (認証NG)
} elseif ( isset( $_GET["denied"] ) ) {
	// エラーメッセージを出力して終了
	echo "連携を拒否しました。" ;
	exit ;


// 初回のアクセス
} else {
	/*** [手順1] リクエストトークンの取得 ***/

	// [アクセストークンシークレット] (まだ存在しないので「なし」)
	$access_token_secret = "" ;

	// エンドポイントURL
	$request_url = "https://api.twitter.com/oauth/request_token" ;

	// リクエストメソッド
	$request_method = "POST" ;

	// キーを作成する (URLエンコードする)
	$signature_key = rawurlencode( $api_secret ) . "&" . rawurlencode( $access_token_secret ) ;


	// パラメータ([oauth_signature]を除く)を連想配列で指定
	$params = array(
			"oauth_callback" => $callback_url ,
			"oauth_consumer_key" => $api_key ,
			"oauth_signature_method" => "HMAC-SHA1" ,
			"oauth_timestamp" => time() ,
			"oauth_nonce" => microtime() ,
			"oauth_version" => "1.0" ,
		       ) ;


	$response = get_oauth_token($request_url,$request_method,$params,$signature_key);

        
	// リクエストトークンを取得できなかった場合
	if( !$response ) {
		echo "<p>リクエストトークンを取得できませんでした…。$api_keyと$callback_url、そしてTwitterのアプリケーションに設定しているCallback URLを確認して下さい。</p>" ;
		exit ;
	}

	// $responseの内容(文字列)を$query(配列)に直す
	// aaa=AAA&bbb=BBB → [ "aaa"=>"AAA", "bbb"=>"BBB" ]
	$query = [] ;
	parse_str( $response, $query ) ;


	// セッション[$_SESSION["oauth_token_secret"]]に[oauth_token_secret]を保存する
	session_start() ;
	session_regenerate_id( true ) ;
	$_SESSION["oauth_token_secret"] = $query["oauth_token_secret"] ;

	/*** [手順2] ユーザーを認証画面へ飛ばす ***/

	// ユーザーを認証画面へ飛ばす (毎回ボタンを押す場合)
	header( "Location: https://api.twitter.com/oauth/authorize?oauth_token=" . $query["oauth_token"] ) ;
}
?>
