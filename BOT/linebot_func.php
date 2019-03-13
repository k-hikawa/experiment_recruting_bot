<?php
	// LINEAPIに関わる関数

	// 返信データ整形(返すデータの種類, データの内容, データの内容その2)
	function response_data($type, $contents, $contents2){
		if(!isset($contents2)){
			$contents2 = $contents;
		}
		switch($type){
			// テキスト
			case "text":
			$response_format = array(
				"type" => "text",
				"text" => $contents
			);
			break;
			// 画像
			case "image":
			$response_format = array(
				"type" => "image",
				"originalContentUrl" => $contents,
				"previewImageUrl" => $contents2
			);
			break;
			// 位置情報
			case "location":
			$response_format = array(
				"type" => "location",
				"title" => "batta location",
				"address" => "〒150-0002 東京都渋谷区渋谷２丁目２１−１",
				"latitude" => 35.65910807942215,
				"longitude" => 139.70372892916203
			);
			break;
			// スタンプ
			case "sticker":
			$response_format = array(
				"type" => "sticker",
				"packageId" => $contents,
				"stickerId" => $contents2
			);
			break;
		}
		return $response_format;
	}

	// ボタンのメッセージデータ整形
	// labelとreturn_textとactionは配列で同じ数にすること(1~4)
	// actionがdatetimepickerの列はreturn_textは("", date|time|datetime)の形で
	function button_message($thumb_url, $title, $text, $label, $return_text, $action){
/*
		if(count($label) == 1){
			$actions = array(
				"type" => $action,
				"label" => $label[0],
				$action_name => $return_text[0]
			);
			$response_format = array(
				"type" => "template",
				"altText" => $title,
				"template" => array(
					"type" => "buttons",
					"thumbnailImageUrl" => $thumb_url,
					"title" => $title,
					"text" => $text,
					"actions" => array($actions)
				)
			);

		}else{*/
			$actions = array();
			for($i=0; $i<count($label); $i++){
				switch($action[$i]){
					case "datetimepicker":
						$now = date("Y-m-d");
						array_push($actions,
							array(
								"type" => $action[$i],
								"label" => $label[$i],
								"data" => $return_text[$i][0],
								"mode" => $return_text[$i][1],
								"initial" => "2000-01-01",
								"max" => $now
							)
						);
					break;
					default:
						switch($action[$i]){
							case "postback":
								$action_name = "data";
							break;
							case "message":
								$action_name = "text";
							break;
							case "uri":
								$action_name = "uri";
							break;
						}
						array_push($actions,
						array(
							"type" => $action[$i],
							"label" => $label[$i],
							$action_name => $return_text[$i]
							)
						);
					break;
				}
				
			}
			$temp_actions = array();
			for($i=0; $i<count($actions); $i++){
				array_push($temp_actions, $actions[$i]);
			}

			$response_format = array(
				"type" => "template",
				"altText" => $title,
				"template" => array(
					"type" => "buttons",
					"thumbnailImageUrl" => $thumb_url,
					"title" => $title,
					"text" => $text,
					"actions" => $temp_actions
				)
			);
		//}
		if($title == ""){
			unset($response_format['template']['title']);
		}
		if($thumb_url == ""){
			unset($response_format['template']['thumbnailImageUrl']);
		}
		return $response_format;
	}


	function datepicker_button($title, $text, $label, $data, $mode){
		if(count($label) == 1){
			$actions = array(
				"type" => $action,
				"label" => $label[0],
				$action_name => $return_text[0]
			);
			$response_format = array(
				"type" => "template",
				"altText" => $title,
				"template" => array(
					"type" => "buttons",
					"thumbnailImageUrl" => $thumb_url,
					"title" => $title,
					"text" => $text,
					"actions" => array($actions)
				)
			);

		}else{
			$actions = array();
			for($i=0; $i<count($label); $i++){
				array_push($actions,
					array(
						"type" => $action,
						"label" => $label[$i],
						$action_name => $return_text[$i]
					)
				);
			}
			$temp_actions = array();
			for($i=0; $i<count($actions); $i++){
				array_push($temp_actions, $actions[$i]);
			}

			$response_format = array(
				"type" => "template",
				"altText" => $title,
				"template" => array(
					"type" => "buttons",
					"thumbnailImageUrl" => $thumb_url,
					"title" => $title,
					"text" => $text,
					"actions" => $temp_actions
				)
			);
		}
		if($title == ""){
			unset($response_format['template']['title']);
		}
		if($thumb_url == ""){
			unset($response_format['template']['thumbnailImageUrl']);
		}
		return $response_format;
	}

	// ＊未完成
	function confirm_message($text, $action){
		switch($action){
			case "postback":
			$action_name = "data";
			break;
			case "message":
			$action_name = "text";
			break;
			case "uri":
			$action_name = "uri";
			break;
		}
		array(
			"type"=> "template",
			"altText"=> "確認が送られています",
			"template"=> array(
				"type"=> "confirm",
				"text"=> $text,
			"actions"=> array(
				"type"=> $action,
				"label"=> "Yes",
				"text"=> "yes"
				),
				array(
					"type"=> $action,
					"label"=> "No",
					"text"=> "no"
				)
			)
		);
	}

	// カルーセル型のメッセージデータ整形
	// thumb_urlとtitleと$textは同じ数(1~5)
	// labelとreturn_textとactionは同じ数(1~3)
	// $thumb_url & $title & $text = array(hoge, hage)
	// $label & $return_text & $action = array(array(a), array(b))
	function carousel_message($thumb_url, $title, $text, $label, $return_text, $action){
		$columns = array();
		$actions = array();

		for($i=0; $i<count($thumb_url); $i++){
			for($j=0; $j<count($label[$i]); $j++){
				switch($action[$i][$j]){
					case "postback":
						$action_name = "data";
					break;
					case "message":
						$action_name = "text";
					break;
					case "uri":
						$action_name = "uri";
					break;
				}
				array_push($actions,
					array(
						"type" => $action[$i][$j],
						"label" => $label[$i][$j],
						$action_name => $return_text[$i][$j]
					)
				);
			}
			if(count($label[$i]) == 1){
				if($thumb_url[$i] != ""){
					array_push($columns,
						array(
							"thumbnailImageUrl" => $thumb_url[$i],
							"title" => $title[$i],
							"text" => $text[$i],
							"actions" => array($actions[$i*count($label[0])])
						)
					);
				}else{
					array_push($columns,
						array(
							"title" => $title[$i],
							"text" => $text[$i],
							"actions" => array($actions[$i*count($label[0])])
						)
					);
				}
			}else{
				$temp_action = array();
				for($j=0; $j<count($label[$i]); $j++){
					array_push($temp_action, $actions[$i*count($label[$i])+$j]);
				}
				if($thumb_url[$i] != ""){
					array_push($columns,
						array(
							"thumbnailImageUrl" => $thumb_url[$i],
							"title" => $title[$i],
							"text" => $text[$i],
							"actions" => $temp_action
						)
					);
				}else{
					array_push($columns,
						array(
							"title" => $title[$i],
							"text" => $text[$i],
							"actions" => $temp_action
						)
					);
				}
			}
		}

		$response_format = array(
			"type" => "template",
			"altText" => "メッセージがあります",
			"template" => array(
				"type" => "carousel",
				"columns" => $columns
			)
		);
		return $response_format;
	}

	// イメージマップ
	// 判定1つ
	function image_map($base_url, $base_size, $return_text){
		$return_message = array(
			"type" => "imagemap",
			"baseUrl" => $base_url,
			"altText" => "タスク一覧が送られています",
			"baseSize" => array(
				"height" => $base_size,
				"width" => $base_size
			),
			"actions" => array(
				array(
					"type" => "message",
					"text" => $return_text,
					"area" => array(
						"x" => 0,
						"y" => 0,
						"width" => $base_size,
						"height" => $base_size
					)
				)
			)
		);
		return $return_message;
	}

	// 返信データの最終整形
	function response($accessToken, $replyToken, $response_data){
		// 単品データの場合
		if(count($response_data['type']) == 1){
			$post_data = array(
				"replyToken" => $replyToken,
				"message" => array($response_data)
			);
		// 複数データの場合
		}else{
			$post_data = array(
				"replyToken" => $replyToken,
				"messages" => $response_data
			);
		}
		$result = POST_for_LINEAPI($accessToken, "https://api.line.me/v2/bot/message/reply", $post_data);
		return $result;
	}

	// botにpush通知を送る
	function push_message($accessToken, $post_userID, $response_data){
		$post_data = array(
			"to" => $post_userID,
			"messages" => $response_data
		);
		POST_for_LINEAPI($accessToken, "https://api.line.me/v2/bot/message/multicast", $post_data);
	}

	// LINEAPIに送る
	function GET_for_LINEAPI($accessToken, $url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$accessToken));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	// LINEAPIにPOST送信
	function POST_for_LINEAPI($accessToken, $url, $post_data){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
		curl_setopt($ch, CURLOPT_HTTPHEADER,
			array(
			'Content-Type: application/json; charser=UTF-8',
			'Authorization: Bearer '.$accessToken
			)
		);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}