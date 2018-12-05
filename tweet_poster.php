<?php
  require_once'request_oauth.php';
  require_once'post_wordpress.php';

  $token_path = "twittertoken.json"; 
  $category_path = "category.json";

  $api_url ='https://api.twitter.com/1.1/statuses/user_timeline.json';  

  $api_key ='';
  $api_secret ='';
  $request_method = 'GET';

  $date = new DateTimeImmutable();
  $date = $date->setTimezone(new DateTimeZone('+00:00'));
  $second = $date->format('s');
  $modify_string = "-$second seconds";
  $date = $date->modify($modify_string);
echo $date->format('D M d H i s'). PHP_EOL;

  $user_data = [];
    
  if(file_exists($token_path)){
  $json = file_get_contents($token_path);
  
  $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
      
  $obj = json_decode(str_replace('&quot;','"',$json),true);

  $user_data = $obj["user_data"];
      
  
  }else{
       exit("データがありません");
  }
  
  foreach($user_data as $value){
    
    
     $oauth_token = $value['oauth_token'];
     $oauth_token_secret = $value['oauth_token_secret'];
     $user_id = $value['user_id'];
     $screen_name = $value['screen_name'];
    
     echo PHP_EOL. $screen_name.  PHP_EOL;
    
     //不正データは無視
     if($oauth_token=="")
     {  
       continue;
     } 


    $option_params = array(
      'user_id' => $user_id,
      'exclude_replies' => 'true',
      'include_rts' => 'false',
      'tweet_mode' => 'extended',
     );   

    $signature_key = rawurlencode( $api_secret ) .'&' .rawurlencode($oauth_token_secret);

    $signature_params = array(
     	'oauth_token' => $oauth_token ,
	'oauth_consumer_key' => $api_key ,
	'oauth_signature_method' => 'HMAC-SHA1' ,
	'oauth_timestamp' => time() ,
	'oauth_nonce' => microtime() ,
	'oauth_version' => '1.0' ,
     ) ;


   $response =  request_oauth($api_url,$request_method,$signature_params,$option_params,$signature_key);
      

   $tweets = json_decode($response);
   
   if($tweets ==null)
   {
     echo "{$screen_name}:tweetが取得できませんでした"; 
   }

  if(file_exists($category_path)){
  $json = file_get_contents($category_path);
  $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
  $category_data = json_decode($json,true);
  
  }else{
       exit("データがありません");
  }

   foreach($tweets as $tweet){
         
     if(!isset($tweet))
     {
       echo "tweet is null";
       continue;
     }
   
	   if(!is_target_time($tweet,$date)){
                   echo "tweet is old ! " ;
		   continue;
	   }
           else{
           echo "tweet is new";
           }
           
            echo '<br><br><br>';
           var_dump($tweet->full_text);
            echo '<br><br><br>';
       
	   $hashtags = $tweet->entities->hashtags;
       
       //カテゴリー追加仕様のハッシュタグが付いている場合jsonファイルに追加する 追加に成功するとtrueがかえる
       if(add_category($tweet,$category_data)) continue;
       
	   if(count($hashtags)!=2 ){

		   continue;

	   }
       
       $first_hash = $hashtags[0]->text;
       $second_hash = $hashtags[1]->text;
	   $result = is_target_hash($first_hash,$category_data);
        
       //一番目のハッシュタグが企画名だった場合(=二番目はタイトル）
	   if($result !=false){
                       
           $content = create_content($tweet,$first_hash,$second_hash);
                   
		   post_new($first_hash,$second_hash,$content);
		   echo "ブログに投稿！" .PHP_EOL;
	   }

	   $result = is_target_hash($second_hash,$category_data);

       //二番目のハッシュタグが企画名だった場合(=一番目はタイトル）
	   if($result !=false){
                      
            $content = create_content($tweet,$second_hash,$first_hash);
		    post_new($second_hash,$first_hash,$content);

		   echo "ブログに投稿！" . PHP_EOL;
	   }
   }
            
     echo PHP_EOL;
 } 



  //***************************************************************************************************
  //*                                                                                                 *
  //*ツイートが対象の時間内になされたものかどうかを調べ,そうであればtrue,そうでなければfalseを返す関数*
  //*                              								      *
  //***************************************************************************************************

  function is_target_time($tweet,$goal_time){
  
     $created_time = new DateTimeImmutable($tweet->created_at);

     $start_time = $goal_time->modify('-5 minutes');
    

     if($created_time>=$start_time && $created_time < $goal_time)
     {
       return true;
     } 
     else
     {
       return false;
     }
} 


  //***************************************************************************************************
  //*                                                                                                 *
  //*       ハッシュタグがカテゴリー名の場合trueを返す     　　　　　 *
  //*       						      *
  //*    										              *
  //***************************************************************************************************

function is_target_hash($hash,$category_data){

	foreach($category_data["category"] as $category){
        
		if($hash == $category){
			return true;
		}
	}
	return false;
}

  //***************************************************************************************************
  //*                                                                                                 *
  //*       ツイートの画像のURLの配列を返す関数　　　　　　　　　　　　　　　　　　　　　　　　　　　 * 
  //*    										              *
  //***************************************************************************************************

function get_media_urls($tweet){

        //画像データを含む場合存在するオブジェクト
	$extended_entitie;

	//画像データがあるか判定し,あればURLを抽出していく
	if(property_exists($tweet,"extended_entities")){
		$extended_entities = $tweet->extended_entities; 

		//ここに画像URLを代入していく
		$media_urls = [];
			foreach($extended_entities->media as $media){
				if(isset($media)){
					$media_urls[] =  $media->media_url_https;

				}  
			}

        return $media_urls;
	}
           
        return null;
} 
 
  //***************************************************************************************************
  //*                                                                                                 *
  //*       ツイートの動画のURLを返す関数　　　　　　　　　　　　　　　　　　　　　　　　　　　                    * 
  //*    										                                                      *
  //***************************************************************************************************

/*function get_movie_url($tweet){

        //画像データを含む場合存在するオブジェクト
	$extended_entitie;

	//画像データがあるか判定し,あればURLを抽出していく
	if(property_exists($tweet,"extended_entities")){
		$extended_entities = $tweet->extended_entities; 

        $media =$extended_entities->media[0];
				if(isset($media) && property_exists($media,"video_info")){
                    echo "<br>動画url";
                    var_dump($media->video_info->variants[0]->url);
                    echo "<br>";
					return  $media->video_info->variants[0]->url;
                   
				}      
	}
                   
    return null;
} */
 
  //***************************************************************************************************
  //*                                                                                                 *
  //*             blogに投稿するテキストを作成する関数					              *
  //*    										              *
  //***************************************************************************************************

function create_content($tweet,$category_hash,$title_hash){
        
   $media_urls = get_media_urls($tweet);
   //$movie_url = get_movie_url($tweet);
 
  //blogに投稿するテキスト
  $content = "";

  //画像がある場合先頭に追加
  if(count($media_urls)>=1){
         $count = 1;
         foreach($media_urls as $url){
           $img = file_get_contents($url);
           $img_name = $title_hash . $count . "_" . date("y-m-d") . '.jpg';
             
           $img_url=upload_image($img,$img_name);
             
           $content .= "<img src =\"" .$img_url. "\"/><br><br>";
           $count++;
         }
         $content .= "<br><br>";
    }
        
        //動画がある場合先頭に追加
  /*if(isset($movie_url)){      
      
           $data = file_get_contents($movie_url);
           $path = './media/'. $title_hash .$count . '.webm';
             if(file_put_contents($path,$data)==false){
               echo "failed saving media!";
           }
           $content .= "<video src = \"{$path}\"><br><br>";
    }*/

   //tweetの文章を追加
   $content .= $tweet->full_text;
  
   $unnecessary_url = "";
   //tweetに画像がある場合に文章に仕様的に追加されてしまう画像へのリンクurl
   if(property_exists($tweet->entities,"media")){
	   if(count($tweet->entities->media)>0){
		   $unnecessary_url = $tweet->entities->media[0]->url; 
	   }
   }


   //unnecessary_urlの除去
   $content = str_replace($unnecessary_url,"",$content);

   //hashtagを削除
   $content = str_replace("#{$category_hash}","",$content); //#半角
   $content = str_replace("＃{$category_hash}","",$content); //#全角
  
   //同上 
   $content = str_replace("#{$title_hash}","",$content);//#半角
   $content = str_replace("＃{$title_hash}","",$content); //#全角;
   
   return $content;
}
   

   //***************************************************************************************************
  //*                                                                                                 *
  //*             認識するハッシュタグを追加する関数					              *
  //*    										              *
  //***************************************************************************************************


function add_category($tweet,$category_data) {
    
    $CATEGORY = "category";
    $category_path = "category.json";
    $HASHTAG_ADD="追加";
    
    $hashtags = $tweet->entities->hashtags;
    
    if(count($hashtags)!=2) return false;     
    
    try
    {
        if($hashtags[0]->text == $HASHTAG_ADD)
        {       
            $new_array = array($hashtags[1]->text);
            $category_data[$CATEGORY] = array_merge($category_data[$CATEGORY],$new_array);
            echo "{$hashtags[1]->text}をcategory.jsonに追加します";
        }
        else
        {
               if($hashtags[1]->text == $HASHTAG_ADD)
            {       
                $new_array = array($hashtags[0]->text);
                $category_data[$CATEGORY] = array_merge($category_data[$CATEGORY],$new_array);  
                   
                echo "{$hashtags[0]->text}をcategory.jsonに追加します";
            }            
            else return false;

        }     

        $json = json_encode($category_data,JSON_UNESCAPED_UNICODE);

        file_put_contents($category_path,$json);  
        echo "ハッシュタグの追加完了";

        return true;
    }
    
    catch(Exception $e)
    {
        echo "{$e->getMessage()}";
        return false;
    }
  
}

?>
