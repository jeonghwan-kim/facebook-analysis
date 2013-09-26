<?php

/**
 * 사용자 정보를 불러온다.
 * 인풋: 없음
 * 리턴: json
 *
 */

header('Content-type: application/json');

$t1 = microtime(true);

require '../facebook/facebook.php';
require '../facebook/key-info.php';
session_start();

// facebook 객체 생성
$facebook = new Facebook(array('appId'  => $appId,'secret' => $secret));

// 토큰 설정
$facebook->setAccessToken($_SESSION['fb_token']);

// 사용자 정보 불러오기 
$me = $facebook->api('/me'); 
$logout_url = $facebook->getLogoutUrl( array('next'=>$logout_redirect_url) );
$user_name = $me['name'];
$fb_url = $me['link'];

// 결과 합치기
$result = array('user_name'=>$user_name, 'fb_url'=>$fb_url, 'logout_url'=>$logout_url);

$t2 = microtime(true);
$result['elapsed_time'] = round($t2 - $t1, 2);

require_once 'save-log.php';
save_log($result);
echo json_encode($result);;

?>