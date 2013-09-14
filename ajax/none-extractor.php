<?php

define('DIC_FILE_NAME', "total.dic"); // 사전 파일명 
define('WORD_START', "$@"); // 길이와 분석속도 비례


/**
 * 명사 추출기
 * input: 추출할 대상 문자열, 최소 빈도수 (최소 빈도수 이상의 문자만 반환한다.)
 * output: (명사, 빈도수) 형식의 배열
 *
 */
function none_extractor($target_string, $min_freq) 
{
	$elapsed_time = array();

	// 사전파일 로딩 
	$time_start = microtime(true);
	
	$dic = load_dic(); 
	
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	$elapsed_time['loading_dictionary'] = $time;

	// 분석 데이터 로딩
	$time_start = microtime(true);
	
	$target = load_target($target_string);

	$time_end = microtime(true);
	$time = $time_end - $time_start;
	$elapsed_time['loading_target_string'] = $time;

	
	$time_start = microtime(true);
	
	// 사전과 대조하여 빈도수 계산 
	$none_freq = get_freq($target, $dic, $min_freq);
	
	// 최소 빈도수 미만의 단어 삭제
	// $none_freq = del_under_freq($none_freq, $min_freq);

	$time_end = microtime(true);
	$time = $time_end - $time_start;
	$elapsed_time['analysing_time'] = $time;

	// 총 경과 시간 계산
	$elapsed_time['total_time'] = $elapsed_time['loading_dictionary']
		+ $elapsed_time['loading_target_string']
		+ $elapsed_time['analysing_time'];

	// 결과 반환
	$result['elapsed_time'] = $elapsed_time; 
	$result['none_freq'] = $none_freq;
	return $result; // (단어, 빈도) 형태의 배열
}

/**
 * 사전 파일 로딩
 * input:
 * output: 로딩된 사전 배열
 *
 */
function load_dic() 
{
	$file = fopen(DIC_FILE_NAME, "r");

	// 첫줄 제외
	$buf = fgets($file); 

	// 사전을 배열에 저장 (key: 단어, value: 단어 길이)
	$dic = array();
	
	while ($buf = fgets($file)) {
		// 사전을 ',' 기준으로 자른다.
		$word = preg_split("/,/", trim($buf), -1, PREG_SPLIT_NO_EMPTY);
		
		// 사전의 잘못된 부분은 제외한다. 
		if (count($word) != 2) 
			continue;

		// 한 글자는 제외
		if (strlen($word[0]) <= 3) { 	
			continue;
		} 

		// 명사인 경우만 등록 (코드패턴: 100___)
		$code = trim($word[1]);
		if (preg_match('/^100\d\d\w$/', $code)) {
			$dic[$word[0]] = strlen($word[0]);
		}
	}

	// 긴 단어 순으로 정렬 (단어 비교시 길이우선 기준)
	arsort($dic);

	return $dic;
}

/**
 * 분석 대상 데이터 로딩
 * input: 대상 파일 명
 * output: (시작문자+말뭉치) 형태의 긴 문자열
 *
 */
function load_target($target) 
{
	// 분석 대상 문자열을 말뭉치 단위로 자르기
	$target = preg_split("/ /", trim($target), -1, PREG_SPLIT_NO_EMPTY);

	// 분석용 문자열
	$str = "";

	// 말뭉치에 시작문자를 추가하여 배열에 저장한다.
	foreach ($target as $key => $value) {
		$word = trim($value); // 공백 제거
		$word = WORD_START . $word; // 시작 문자 표시
		$str = $str . $word; // 분석용 문자열에 추가
	}

	return $str; 
}


/**
 * 빈도 계산
 * input: 단어 목록, 사전
 * output: (단어, 빈도) 배열
 *
 */
function get_freq($target, $dic, $min_freq) 
{
	// 출현 단어와 빈도수를 저장할 배열 
	$result = array();

	foreach ($dic as $key => $value) {
		$cnt = 0; // 빈도수 초기화
		$compare_key = WORD_START. $key; // 첫 단어만 검색하므로
			// 첫문자 키원드인 WORD_START를 추가한다.
		
		$cnt = substr_count($target, $compare_key); // 단어 빈도수 계산
		
		if ($cnt >= $min_freq)  { // 찾은 경우
			$result[$key] = $cnt; // 빈도수 추가
			$target = str_replace($compare_key, "", $target); 
				// 타겟 스트링에서 검색한 문자 삭제 
				// 빈칸이 나올때 까지 삭제하는게 좋지 않을까? (추후 고려)
		}
	}

	arsort($result); // 빈도수 역순 정렬 

	return $result;
}


/**
 * 최소 빈도수 미만의 단어는 삭제함.
 * input: 단어 목록, 최소 빈도수 
 * output: (단어, 빈도) 배열
 *
 */
// function del_under_freq($none_freq, $min_freq) 
// {
// 	foreach ($none_freq as $key => $value) {
// 		if ($value < $min_freq) {
// 			unset($none_freq[$key]);	
// 		}
// 	}
// }

?>