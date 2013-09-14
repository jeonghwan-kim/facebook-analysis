<?php

/**
 * 페이스북 게시글을 가져와 단어를 뽑는다.
 * 인풋: 없음
 * 리턴: json
 *
 */

header('Content-type: application/json');

require_once ('none-extractor.php'); // 명사 추출기

// 설정값.
// define('MAX_TEXT', 3*5000); // 최대 한글 2000 글자만 한글 명사 추출한다. 시간이 너무 오래 걸림.
// define('SINCE_DATE', '2011-07-01'); // 조회 날짜 기간
define('MIN_WORD_FREQ', 1); // 최소 빈도 단어 수

$t1 = microtime(true);

// 시간 체킹
$elapsed_time = array();

// 1. 페이스북 상태 정보 모두 가져오기
$fb_data = get_facebook_statuses();

// 2. 명사리스트 가져오기
$word_list = get_none_arr($fb_data, MIN_WORD_FREQ);

// 총시간 계산
// $elapsed_time['total'] = $elapsed_time['get_facebook_statuses()'] 
 					   // + $elapsed_time['get_none_arr()'];

// 결과 합치기
$result = array('none_arr'=>$word_list, 'facebook_arr'=>$fb_data);

$t2 = microtime(true);
$result['elapsed_time'] = round($t2 - $t1, 2);


require_once 'save-log.php';
save_log($result);
echo json_encode($result);;


/**
 * 페이스북 statues에 있는 정보를 가져온다.
 * 인풋: 없음
 * 리턴: 배열 (총 문장 길이, 단어 리스트 )
 *
 */
function get_facebook_statuses() 
{
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

	// 분석할 데이터 저장소
	$messages = array(); // 게시글 (게시시간, 내용)

	$statuses = $facebook->api('/me/statuses','GET', array("limit"=>500));

	// 작성한 포스트 모두 가져오기
	while(1)
	{
		// 데이터를 배열에 저장 (번호, 시간, 내용)
		if (!isset($statuses['data'])) break;

		foreach ($statuses['data'] as $key => $value) {
			// 메세지 저장 
			if (isset($value['message'])) {
				array_push($messages, array('message'=>$value['message'], 'time'=>$value['updated_time']));
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

	$result = array('messages'=>$messages);
	
	$t2 = microtime(true);
	$result['elapsed_time'] = $t2 - $t1;

	return $result;
}


function get_none_arr($facebook_data, $min_freq) {
	$t1 = microtime(true);

	// 분석할 데이터 저장소
	$text = "";

	// 명사 추출을 위해 문자열로 변환
	foreach ($facebook_data['messages'] as $key => $value) {
		$text .= ' ' . $value['message'];
		// if (strlen($text) > MAX_TEXT) break;
	}

	//명사 추출 한다.
	$none_arr = none_extractor($text, $min_freq); // 3 번 이상 나온 단어는 명사로 추출한다.

	$num_of_text = round(strlen($text)/3, 0) ; // 한글 글자수 계산

	$result = array('text_len'=>$num_of_text, 'word_list'=>$none_arr['none_freq'] );
	$t2 = microtime(true);
	$result['elapsed_time'] = $t2 - $t1;

	return $result;
}

?>