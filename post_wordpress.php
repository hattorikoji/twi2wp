<?php
   require_once "IXR_Library.php";

   function upload_image($img,$name)
   {
    //wordpress username and password
    $wp_user = "";
    $wp_pwd = ""; 
    
    //wordpress xmlrpc.php location
    //e.g. http://hogehoge.com/xml.php
    $url="";
       
    $client = new IXR_Client($url);
       
    $params = array('name' => $name, 'type' => 'image/jpg', 'bits' => new IXR_Base64($img), 'overwrite' => false);
    $status = $client->query('wp.uploadFile',1, $wp_user, $wp_pwd, $params);
   
    $res = $client->getResponse();
    echo"image url";
    var_dump($res['url']);
              
    if(!$status){
	//投稿失敗時に、エラーメッセージ・コードを出力
  	echo $client->getErrorCode().' : '.$client->getErrorMessage() . "]\n";
}

    return $res['url'];
        
   }

   function post_new($project_name,$title,$content){
   
   //wordpress username and password
   $wp_user = "";
   $wp_pwd = "";
       
       
   //wordpress xmlrpc.php location
   //e.g. http://hogehoge.com/xml.php
   $url = "";
       
      
   if(strpos($project_name,date('Y'))===false)
   {
     $category = $project_name . date('Y');
   }
  
       
   $client = new IXR_Client($url);
   
   $status = $client->query(
      "wp.newPost", //使うapiを指定
      1,       //blog Id 通常は１
      $wp_user, //user name
      $wp_pwd, //user password
      array(
              'post_author' => 'AutoPoster',
              'post_status' => 'publish', //投稿状態　(draftにすると下書きにできる）
              'post_title' => $title,
              'post_content' => $content,
              'terms_names' => array(
                   'category' => array($category)
               )
       ) 
    );
     
       var_dump($client->getResponse());

    if(!$status){
	//投稿失敗時に、エラーメッセージ・コードを出力
  	echo $client->getErrorCode().' : '.$client->getErrorMessage() . "]\n";
}}
 ?>
