<?php

require 'facebook.php';
require 'key-info.php';

$facebook = new Facebook(array('appId'  => $appId, 'secret' => $secret));

$user = $facebook->getUser();

$loginUrl = $facebook->getLoginUrl(array(
    'scope'         => array('user_status', 'friends_photos'), // 퍼미션 조정 
    'redirect_uri'  => $redirect_url
));

header('Location: '.$loginUrl);

?>