<?php
require 'facebook.php';
require 'key-info.php';

session_start();

$facebook = new Facebook(array('appId'  => $appId, 'secret' => $secret));

$_SESSION['fb_token'] = $facebook->getAccessToken();

header('Location: ../analysis.html');
?>