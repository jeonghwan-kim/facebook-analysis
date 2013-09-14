<?php

/**
 * 친구들의 활동을 불러온다.
 * 인풋: 없음
 * 리턴: json
 *
 */

header('Content-type: application/json');
require '../facebook/facebook.php';
require '../facebook/key-info.php';
define('FRIENDS_NUM', 42); // 분석결과 상위 친구
// define('SINCE_DATE', '2013-07-01'); // 조회 날짜 기간
session_start();

$t1 = microtime(true);

// facebook 객체 생성
$facebook = new Facebook(array('appId'  => $appId,'secret' => $secret));

// 토큰 설정
$facebook->setAccessToken($_SESSION['fb_token']);

// 사용자 정보 불러오기 
$my_id = $facebook->api('/me')['id']; 

// 정보 요청 (반환값: 배열)
// $statuses = $facebook->api('/me/statuses','GET', array("limit"=>500, "since"=> SINCE_DATE));
$statuses = $facebook->api('/me/statuses','GET', array("limit"=>500));

// 작성한 포스트 모두 가져오기
list($comments, $likes) = get_comments_likes($statuses, $my_id);

// 1. 빈도 계산하기.
$comment_freq = get_freq($comments); 
$like_freq = get_freq($likes);
$freq_list = get_freq_list($comment_freq, $like_freq); // 계산한 빈도 합치기

// 2. 상위 친구 리스트 구하기 (사진포함)
$friends_info = get_friends_info($comments, $likes, $facebook); 
$score_top_list = add_freq($comment_freq, $like_freq, $friends_info, FRIENDS_NUM);


// 결과 저장
$result = array('freq_list'=>$freq_list, 'score_list'=>$score_top_list);
$t2 = microtime(true);
$result['elapsed_time'] = round($t2 - $t1, 2);


require_once 'save-log.php';
save_log($result);
echo json_encode($result);;






/**
 * 코맨트와 좋아요를 가져온다.
 *
 */
function get_comments_likes($statuses, $my_id)
{
	$likes = array();
	$comments = array();

	while(1)
	{
		// 데이터를 배열에 저장 (번호, 시간, 내용)
		if (!isset($statuses['data'])) break;

		foreach ($statuses['data'] as $key => $value) {
			// 메세지 상태를 좋아한 사람이 있는 경우
			if ( isset($value['likes']['data']) ) {
				$likes = get_likes($likes, $value['likes']['data'], $my_id);
			}

			// 메세지 상태에 코맨트가 있는 경우
			if ( isset($value['comments']['data']) ) {
				$comments = get_comments($comments, $value['comments']['data'], $my_id);
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

	return array($comments, $likes);
}

function get_likes($arr, $list, $my_id) 
{
	foreach ($list as $key => $value) {
		// 내가 남긴 글은 제외
		if ( isset($value['id']) && $value['id'] == $my_id )
			continue;

		if ( isset($value['name']) && isset($value['id']) )
			array_push($arr, array(
				'id'=>$value['id'],
				'name'=>$value['name']));
	}
	return $arr;
}

function get_comments($arr, $list, $my_id)
{
	foreach ($list as $key => $value) {
		// 내가 남긴 글은 제외
		if ( isset($value['from']['id']) && $value['from']['id'] == $my_id)
			continue;

		if ( isset($value['from']['name']) && isset($value['created_time']) ) {
			array_push($arr, array(
				'id'=>$value['from']['id'],
				'name'=>$value['from']['name'], 
				'time'=>$value['created_time']));
		}
	}

	return $arr;
}

/**
 * 코멘트나 좋아요의 user별 빈도수를 계산한다.
 *
 */
function get_freq($list)
{
	$freq = array();
	foreach ($list as $key => $value) {
		$name = $value['name'];
		if (isset($freq[$name])) {
			$freq[$name]++;
		}
		else {
			$freq[$name] = 1;
		}
	}

	return $freq;
}

/**
 * 두개의 빈도수 배열을 하나로 합친다. (기준: 이름)
 *
 */
function get_freq_list($comment_freq, $like_freq)
{
	$name_list = get_name_list($comment_freq, $like_freq); 

	foreach ($name_list as $k => $v) {
		if (isset($comment_freq[$k]))
			$c_freq = $comment_freq[$k];
		else
			$c_freq = 0;

		if (isset($like_freq[$k]))
			$l_freq = $like_freq[$k];
		else
			$l_freq = 0;

		// 댓글수, 좋아요수, 점수(친밀도)
		$name_list[$k] = array($c_freq, $l_freq);
	}

	return $name_list;
}

/**
 * 코맨트와 like리스트에서 사용자 이름, id, 프로필사진url 가져온다.
 * 반환: array(id, 이름, 사진url)
 */
function get_friends_info($comments, $likes, $fb)
{

	// 전체 친구 사진 가져오기 (id, 사진 url)
	$friends = $fb->api('/me/friends', 'get', array('fields'=>'picture,link', 'limkt'=>500));
	$url_list = array();
	while(1)
	{
		// 데이터를 배열에 저장 (번호, 시간, 내용)
		if (!isset($friends['data'])) break;
		foreach ($friends['data'] as $key => $value) {
			if ( isset($value['id']) && isset($value['picture']['data']['url']) ) {
				// 배열에 사진주소 저장
				$url_list[$value['id']] = array(
					'pic_url'=>$value['picture']['data']['url'],
					'fb_url'=>$value['link']);
			}
		}

		// 다음 페이지 확인
		if ( isset($friends['paging']['next']) ){
			$next = $friends['paging']['next']; 
			if ($next) {
				$friends = json_decode(@file_get_contents($next));
			} else {
				break;
			}
		}
	}
	// echo json_encode($url_list); exit();

	// 사용자 이름, id, 사진url 가져오기
	// 친국 관계가 아닌경우 사진과 페북링크를 가져올수 없다 (null로 저장)
	$friends_info = array();
	foreach ($comments as $key => $value) {
		if ( !isset($friends_info[$value['name']]) ) 
		{
			$friends_info[$value['name']] = array(
				'id'=>$value['id'], 
				'pic_url'=>isset($url_list[$value['id']]['pic_url']) ? $url_list[$value['id']]['pic_url'] : null,
				'fb_url'=>isset($url_list[$value['id']]['fb_url']) ? $url_list[$value['id']]['fb_url'] : null
				);
		}
	}

	foreach ($likes as $key => $value) {
		{
			$friends_info[$value['name']] = array(
				'id'=>$value['id'], 
				'pic_url'=>isset($url_list[$value['id']]['pic_url']) ? $url_list[$value['id']]['pic_url'] : null,
				'fb_url'=>isset($url_list[$value['id']]['fb_url']) ? $url_list[$value['id']]['fb_url'] : null
				);
		}
	}

	// echo json_encode($friends_info); exit();
	return $friends_info;
}

/**
 * 상위 #명의 정보를 가져온다.
 *
 */
function add_freq($comment_freq, $like_freq, $friends_list, $num)
{
	// 점수 계산
	$name_list = get_name_list($comment_freq, $like_freq); 
	foreach ($name_list as $k => $v) {
		if (isset($comment_freq[$k]))
			$c_freq = $comment_freq[$k];
		else
			$c_freq = 0;

		if (isset($like_freq[$k]))
			$l_freq = $like_freq[$k];
		else
			$l_freq = 0;

		// 댓글수, 좋아요수, 점수(친밀도)
		$name_list[$k] = array($c_freq, $l_freq, $c_freq*2 + $l_freq);
	}

	// 점수순으로 정렬 
	foreach ($name_list as $key => $row) 
	{ 
	    $score[$key] = $row[2]; 
	    $name[$key] = $key;
	} 
	array_multisort($score, SORT_DESC, $name_list) ;

	// 상위 리스트 계산 
	$top_list = array();
	$i = 1;
	foreach ($name_list as $key => $value) {
		// 사용자 정보 불러오기 
		$id = $friends_list[$key]['id'];
		$top_list[$key]['comment_num'] = $value[0];
		$top_list[$key]['like_num'] = $value[1];
		$top_list[$key]['score'] = $value[2];
		$top_list[$key]['fb_url'] = $friends_list[$key]['fb_url'];
		$top_list[$key]['pic_url'] = $friends_list[$key]['pic_url'];

		// echo json_encode($top_list);exit();
		if ( ++$i > $num) break;
	}

	return $top_list;
}

function get_name_list($a, $b)
{
	$name_list = array();

	foreach ($a as $k => $v) {
		if (isset($v)) {
			$name_list[$k] = "";
		}
	}

	foreach ($b as $k => $v) {
		if (isset($v)) {
			$name_list[$k] = "";
		}
	}

	return $name_list;
}





?>