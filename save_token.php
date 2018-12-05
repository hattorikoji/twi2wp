<?php
    function save_as_json($query) {
	    //jsonに取得したパラメータを格納
        
        $jsonPath = "twittertoken.json"; //JSONファイルの場所とファイル名を記述
if(file_exists($jsonPath)){
  $json = file_get_contents($jsonPath);
  $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
  $obj = json_decode($json,true);
    
  $result = true;
    
  foreach ($obj["user_data"] as $value){
    
      if($value["user_id"] == $query['user_id'])
      {
          $result = false;
      }
      
  }
    
  if($result)
  {
      $array = array(
      count($obj["user_data"]) => array(
      "oauth_token" =>$query['oauth_token'],
      "oauth_token_secret"=>$query['oauth_token_secret'],
      "user_id"=>$query['user_id'],
      "screen_name"=>$query['screen_name']
      )
      );
      
      $obj["user_data"] = array_merge($obj["user_data"],$array);
      
      //var_dump($obj["user_data"]);
      
      $json = json_encode($obj);
      
      file_put_contents("twittertoken.json",$json);
      
      echo "連携が完了しました！！作業は以上で終了です。お疲れ様でした!";
           
  }else{
       echo 'すでに登録されているユーザーです';    
  }
        
    
}else {
  echo "データがありません";
}
    }
?>
