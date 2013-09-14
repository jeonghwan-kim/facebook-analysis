<?php

/**
 * 분석데이터를  가져온다.
 * 인풋: 없음
 * 리턴: json
 *
 */

header('Content-type: application/json');
require '../facebook/facebook.php';
require '../facebook/key-info.php';
// define('SINCE_DATE', '2011-07-01'); // 조회 날짜 기간

$t1 = microtime(true);

session_start();

// facebook 객체 생성
$facebook = new Facebook(array('appId'  => $appId,'secret' => $secret));

// 토큰 설정
$facebook->setAccessToken($_SESSION['fb_token']);

// 요청 보내기
// $statuses = $facebook->api('/me/statuses','GET', array("limit"=>500, "since"=> SINCE_DATE));
$statuses = $facebook->api('/me/statuses','GET', array("limit"=>500));

// 작성한 포스트 모두 가져오기
$time_info = array();
while(1)
{
	// 데이터를 배열에 저장 
	if (!isset($statuses['data'])) break;

	foreach ($statuses['data'] as $key => $value) {
		// 메세지 기록 시간만 저장한다.
		if (isset($value['message'])) {
			array_push($time_info, $value['updated_time']);
		}

	}

	// 다음 페이지 확인
	if ( isset($statuses['paging']['next']) ){
		$next = $statuses['paging']['next']; 
		if ($next) {
			$statuses = json_decode(@file_get_contents($next));
		} else {
			break;
		}
	}
}

$t2 = microtime(true);
$result['time_info'] = $time_info;
$result['elapsed_time'] = round($t2 - $t1, 2);


require_once 'save-log.php';
save_log($result);
echo json_encode($result);;

?>