<?php
require_once 'request_curl.php';
 
  //token取得はこちらの関数
  function get_oauth_token($request_url,$request_method,$params,$signature_key){

	  // 各パラメータをURLエンコードする	
	  foreach( $params as $key => $value ) {
		  // コールバックURLはエンコードしない
		  if( $key == "oauth_callback" ) {
			  continue ;
		  }

		  // URLエンコード処理
		  $params[ $key ] = rawurlencode( $value ) ;
	  }

	// 連想配列をアルファベット順に並び替える
	ksort( $params ) ;

	// パラメータの連想配列を[キー=値&キー=値...]の文字列に変換する
	$request_params = http_build_query( $params , "" , "&" ) ;
//        var_dump($request_params);
        //一部の文字列をフォロー
//　　　　$request_params = str_replace( array( '+', '%7' ) , array( '%20' , '~' ) , $request_params );
 
	// 変換した文字列をURLエンコードする
	$request_params = rawurlencode( $request_params ) ;
 
	// リクエストメソッドをURLエンコードする
	$encoded_request_method = rawurlencode( $request_method ) ;
 
	// リクエストURLをURLエンコードする
	$encoded_request_url = rawurlencode( $request_url ) ;
 
	// リクエストメソッド、リクエストURL、パラメータを[&]で繋ぐ
	$signature_data = $encoded_request_method . "&" . $encoded_request_url . "&" . $request_params ;

	// キー[$signature_key]とデータ[$signature_data]を利用して、HMAC-SHA1方式のハッシュ値に変換する
	$hash = hash_hmac( "sha1" , $signature_data , $signature_key , TRUE ) ;

	// base64エンコードして、署名[$signature]が完成する
	$signature = base64_encode( $hash ) ;

	// パラメータの連想配列、[$params]に、作成した署名を加える
	$params["oauth_signature"] = $signature ;

	// パラメータの連想配列を[キー=値,キー=値,...]の文字列に変換する
	$header_params = http_build_query( $params , "" , "," ) ;

	// リクエスト用のコンテキストを作成する
	$context = array(
		"http" => array(
			"method" => $request_method , // リクエストメソッド 
			"header" => array(			  // カスタムヘッダー
				"Authorization: OAuth " . $header_params ,
			) ,
		) ,
	) ;




        return request_curl($request_url,$context); //curlでリクエストして結果を返す
}


   function request_oauth($request_url,$request_method,$signature_params,$option_params,$signature_key){
    

     $merged_params = array_merge( $option_params , $signature_params ) ;

     // 連想配列をアルファベット順に並び替える
     ksort( $merged_params ) ;

     // パラメータの連想配列を[キー=値&キー=値...]の文字列に変換する
     $request_params = http_build_query( $merged_params , '' , '&' ) ;

     // 一部の文字列をフォロー
     $request_params = str_replace( array( '+' , '%7E' ) , array( '%20' , '~' ) , $request_params ) ;

     // 変換した文字列をURLエンコードする
     $request_params = rawurlencode( $request_params ) ;

     // リクエストメソッドをURLエンコードする
     // ここでは、URL末尾の[?]以下は付けないこと
     $encoded_request_method = rawurlencode( $request_method ) ;

     // リクエストURLをURLエンコードする
     $encoded_request_url = rawurlencode( $request_url ) ;

     // リクエストメソッド、リクエストURL、パラメータを[&]で繋ぐ
     $signature_data = $encoded_request_method . '&' . $encoded_request_url . '&' . $request_params ;

     // キー[$signature_key]とデータ[$signature_data]を利用して、HMAC-SHA1方式のハッシュ値に変換する
     $hash = hash_hmac( 'sha1' , $signature_data , $signature_key , TRUE ) ;

     // base64エンコードして、署名[$signature]が完成する
     $signature = base64_encode( $hash ) ;

     // パラメータの連想配列、[$mergerd_params]に、作成した署名を加える
     $merged_params['oauth_signature'] = $signature ;

     // パラメータの連想配列を[キー=値,キー=値,...]の文字列に変換する
     $header_params = http_build_query( $merged_params , '' , ',' ) ;

     // リクエスト用のコンテキスト
     $context = array(
		     'http' => array(
			     'method' => $request_method , // リクエストメソッド
			     'header' => array(			  // ヘッダー
				     'Authorization: OAuth ' . $header_params ,
				     ) ,
			     ) ,
		     ) ;

     // パラメータがある場合、URLの末尾に追加
     if( $option_params ) {
	     $request_url .= '?' . http_build_query( $option_params ) ;
     }

     //curlでリクエストしてレスポンスを返す
     $response =  request_curl($request_url,$context);
     return $response;
  }

?>
