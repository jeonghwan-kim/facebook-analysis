$(window).load(function() {

	// 사용자 정보 출력 (타이틀 바)
	$.get("ajax/get-user-info.php", function(data, statues) {
		show_title(data);
	});

	// 워드 클라우드 생성
	$.get("ajax/get-facebook-words.php", function(data, statues) {
		show_word_cloud(data.none_arr.word_list, 
			data.facebook_arr.messages.length, 
			data.none_arr.text_len, 
			data.elapsed_time);
		console.log('워드클라우드 분석시간:' + data.elapsed_time);
	});

	// 시간 분석 
	$.get("ajax/get-time-analysis.php", function(data, statues) {
		console.log('시간패턴 분석시간:' + data.elapsed_time);
		
		// 활동 그래프 생성
		google.load("visualization", "1", {packages:["corechart"], 
		    callback:function() {
		        draw_chart_time("chart_time", data.time_info, data.elapsed_time);
		    }
		});
	});

	// 관계분석 
	$.get("ajax/get-friends-activity.php", function(data, statues) {
		console.log('친구관계 분석시간:' + data.elapsed_time);

		// 친밀도 그래프 생성
		google.load("visualization", "1", {packages:["corechart"], 
		    callback:function() {
		        draw_chart_close("chart_close", 
		        	data.freq_list, 
		        	Object.keys(data.freq_list).length,
		        	 data.elapsed_time);
		    }
		});

		// 상위 친구 표시
		show_top_friends(data.score_list,
			Object.keys(data.score_list).length,
		    data.elapsed_time);

	});
});


function show_title(data)
{
	var user_name = data.user_name;
	var fb_url = data.fb_url;
	var logout_url = data.logout_url;
	$("#user_name").html('<a href="' + fb_url + '">' +  user_name + '</a>');
	$("#logout_url").html('<a href="' + logout_url + '">Logout</a>');
}

function show_word_cloud(none_arr, post_num, text_len, elapsed_time) 
{
	$("#word_cloud_outer img").fadeOut();
	$("#word_cloud").css("height", 400);

	var word_array = Array();

	for (key in none_arr) {
	    word_array.push({text: key, weight: none_arr[key]});
	}

	$("#word_cloud_outer p").text(
		"총 "+post_num+"개 게시글의 " + text_len + "개 문자를 분석한 결과 입니다. (분석시간: " + elapsed_time +"초)");

    // 워드 클라우드 생성
    $("#word_cloud").jQCloud(word_array, {shape: "rectangular"});
}


/**
 * 타임 차트 생성
 * 입력: array(타입,시간)
 * 
 */
function draw_chart_time(chart_div, arr, elapsed_time) {
	$("#time_analysis img").fadeOut();
	$("#time_analysis p").text("총 " + arr.length +
		"개 게시글의 게시시간을 분석한 결과입니다. (분석시간: " + elapsed_time + "초)");

	// Create and populate the data table.
	var data = new google.visualization.DataTable();
	
	// 타이틀 입력
	data.addColumn('date', 'Date');
	data.addColumn('datetime', 'Post');
	
	// 데이터 입력
	for (key in arr) {
		// 날짜와 시간을 구한다.
		var year = new Number(arr[key].substr(0,4));
		var month = new Number(arr[key].substr(5,2));
		var date = new Number(arr[key].substr(8,2));
		var hour = new Number(arr[key].substr(11,2));
		var min = new Number(arr[key].substr(14,2));
		var sec = new Number(arr[key].substr(17,2));

		// Date 객체에 맞는 한국시간대로 변경 
		var k_time = new Date(year, month, date, hour, min, sec);
		k_time.setMonth(k_time.getMonth()-1); // 0~11
		k_time.setHours(k_time.getHours()+9); // +9 시간대

		// 데이터 입력
		var event_date = new Date(k_time.getFullYear(), k_time.getMonth(), k_time.getDate());
		var event_time = new Date(0,0,0, k_time.getHours(), k_time.getMinutes(), k_time.getSeconds());

		data.addRow([event_date, event_time] );
	}

	var options = {
		height: 400,
		tooltip: {trigger: 'none'},
		vAxis: {title: "",
		      minValue: new Date(0,0,0,0,0,0),
		      maxValue:new Date(0,0,0,23,59,59)},
		hAxis: {title: ""},
		backgroundColor: 'white',
		colors:['#F2685E'],
		legend: 'none'
	};

	// Create and draw the visualization.
	var chart = new google.visualization.ScatterChart(document.getElementById(chart_div));

	chart.draw(data, options);
}


function draw_chart_close(chart_div, arr, num, elapsed_time) {
	$("#frieds_activity img").fadeOut();
	$("#frieds_activity p").text("내 게시글에 댓글이나 좋아요를 남긴 " + num + 
		"명의 친구들을 분석한 결과입니다.(분석시간: " + elapsed_time+ "초)");

	var data = new google.visualization.DataTable();
	data.addColumn('string', 'friend');
	data.addColumn('number', '좋아요');
	data.addColumn('number', '댓글');
	// data.addColumn('string', 'friend');
	data.addColumn('number', '친밀도');

	for (key in arr) {
		data.addRow([key, arr[key][0], arr[key][1], arr[key][0]+arr[key][1] ]);
	}

	var options = {
		title: '',
		// width: 700,
		height: 400,
		hAxis: {title: '댓글수'},		
		vAxis: {title: '좋아요 갯수'},		
		// hAxis: {title: '댓글수', interval:1, format:'#', viewWindow:{min:0, max:2}},		
		// vAxis: {title: '좋아요 갯수', interval:1, format:'##', viewWindow:{min:0, max:2}},		
		bubble: {textStyle: {fontSize: 11}},
		backgroundColor: '#ffffff',
		colors:['#618FFC'],
		// legend: 'none'
		};

	var chart = new google.visualization.BubbleChart(document.getElementById(chart_div));

	chart.draw(data, options);
}

function show_top_friends(arr, num, elapsed_time)
{
	$("#frieds_top_list img").fadeOut();
	$("#frieds_top_list p").text("내 게시글에 댓글과 좋아요를 남김 친구들중 상위 " + num + 
		"명의 목록입니다.(분석시간: " + elapsed_time + ")");

	var i = 0;
	for (key in arr)
	{
		var pic_url = arr[key]['pic_url'];
		var name = key;
		var comment_num = arr[key]['comment_num'];
		var like_num = arr[key]['like_num'];
		var fb_url = arr[key]['fb_url'];

		$("#foo").append('<a href="' + fb_url + '" target="_blank"><img id="image_' + i + '"src="'+ pic_url + '" /></a>');
		$("#image_" + i).attr("data-original-title", name + 
			" / 댓글 " + comment_num + "개 / 좋아요 " + like_num + "개");
		$("#image_" + i).attr("class", "img-rounded");
		$("#image_" + i).tooltip();
		i++;
	}


}
