<?php
/**
 * 로그 남기기
 *
 */
function save_log($result) {
	$file_name = basename($_SERVER['PHP_SELF']);
	date_default_timezone_set("Asia/Seoul");
	$access_time = date('Y-m-d H:i:s');
	$log_file_name = '../log/'.date('Ymd-His').'-'.$file_name.'.txt';

	$log = array('file_name'=>$file_name,
				 'access_time'=>$access_time,
				 'result'=>$result);	
	file_put_contents($log_file_name, json_encode($log));
}

?>