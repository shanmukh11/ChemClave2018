<?php
 include 'Facebook/autoload.php';
 include 'dbconfig.php';
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

  $fb = new Facebook([
    'app_id' => '197449487494188',
    'app_secret' => 'b713569ad59f009b8910cb1a0af3431b',
    'default_graph_version' => 'v2.2',
  ]);

  $helper = $fb->getJavaScriptHelper();

  try {
    $accessToken = $helper->getAccessToken();
  } catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
  } catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
  }

  if (! isset($accessToken)) {
    echo 'No cookie set or no OAuth data could be obtained from cookie.';
    exit;
  }
  $accessToken = (string) $accessToken;
  try{
    $response = $fb->get('/me?fields=id,name,email,link,gender,picture',$accessToken);
  }catch(Facebook\Exceptions\FacebookResponseException $e) {
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
  } catch(Facebook\Exceptions\FacebookSDKException $e) {
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
  }
  $userData = json_decode($response->getGraphUser());
  //echo json_encode($userData);
  //Convert JSON data into PHP variable
  if(!empty($userData)){
    $userData->oauth_provider = "facebook";
    //Check whether user data already exists in database
    $prevquery = "SELECT * FROM fb_users WHERE oauth_provider = '".$userData->oauth_provider."' AND oauth_uid = '".$userData->id."'";
    $prev_result = $db->query($prevquery); 
    //$prevResult = $db->query($prevQuery);
    $access_token = bin2hex(openssl_random_pseudo_bytes(32));
    if(mysqli_num_rows($prev_result) > 0){ 
        //echo $userData->picture->data->url;
        //Update user data if already exists
        $query = $db->prepare("UPDATE fb_users SET access_token = ?, name = ?, email = ?, gender = ?, picture = ?, link = ? WHERE oauth_provider = ? AND oauth_uid = ?");
        if($query===false) echo $db->error;
        $rc = $query->bind_param("ssssssss",$access_token,$userData->name,$userData->email,$userData->gender,$userData->picture->url,$userData->link,$userData->oauth_provider,$userData->id);
        if($rc===false) echo $db->error;
        $status = $query->execute();
        if($status === false) echo $db->error;  
    }else{
        //Insert user data
        $query = $db->prepare("INSERT INTO fb_users(access_token,oauth_provider,oauth_uid,name,email,gender,picture,link) VALUES(?,?,?,?,?,?,?,?)");
        if($query===false) echo $db->error;
        $rc = $query->bind_param("ssssssss",$access_token,$userData->oauth_provider,$userData->id,$userData->name,$userData->email,$userData->gender,$userData->picture->url,$userData->link);
        if($rc===false) echo $db->error;
        $status = $query->execute();
        if($status  ===false) echo $db->error;
    }
    $userData->access_token = $access_token;
    echo json_encode($userData);
    $query->close();
    $db->close();
  }
  
  


?>