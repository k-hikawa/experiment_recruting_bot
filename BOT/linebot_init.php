<?php
	// ユーザのメッセージ取得
	$json_string = file_get_contents('php://input');
	$jsonObj = json_decode($json_string);

	$type = $jsonObj->{"events"}[0]->{"message"}->{"type"};
	if(!isset($type)){
		$type = $jsonObj->{"events"}[0]->{"type"};
	}

	// メッセージの種類からデータ取得
	switch($type){
		case "text":
			$text = $jsonObj->{"events"}[0]->{"message"}->{"text"};
		break;
		case "image":
			$MessageID = $jsonObj->{"events"}[0]->{"message"}->{"id"};
			$timestamp = $jsonObj->{"events"}[0]->{"timestamp"};
		break;
		case "location":
			$lat = $jsonObj->{"events"}[0]->{"message"}->{"latitude"};
			$lng = $jsonObj->{"events"}[0]->{"message"}->{"longitude"};
		break;
		case "audio":
			$MessageID = $jsonObj->{"events"}[0]->{"message"}->{"id"};
			$timestamp = $jsonObj->{"events"}[0]->{"timestamp"};
		break;
		case "video":
			$MessageID = $jsonObj->{"events"}[0]->{"message"}->{"id"};
			$timestamp = $jsonObj->{"events"}[0]->{"timestamp"};
		break;
		case "postback";
			$postback = $jsonObj->{"events"}[0]->{"postback"}->{"data"};
			$postback_date = $jsonObj->{"events"}[0]->{"postback"}->{"params"}->{"date"};
			$postback_time = $jsonObj->{"events"}[0]->{"postback"}->{"params"}->{"time"};	
			$postback_datetime = $jsonObj->{"events"}[0]->{"postback"}->{"params"}->{"datetime"};
		break;
	}

	// ReplyToken取得
	$replyToken = $jsonObj->{"events"}[0]->{"replyToken"};
	// userID取得
	$user_id = $jsonObj->{"events"}[0]->{"source"}->{"userId"};
	// groupID取得
	$groupID = $jsonObj->{"events"}[0]->{"source"}->{"groupId"};
	// roomID取得
	$roomID = $jsonObj->{"events"}[0]->{"source"}->{"roomId"};

	// profile取得
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.ACCESSTOKEN));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_URL, 'https://api.line.me/v2/bot/profile/'.$user_id);
	$output = curl_exec($curl);
	curl_close($curl);
	$de_output = json_decode($output);
	$user_name = $de_output->{"displayName"};
	$icon_path = $de_output->{"pictureUrl"};
	$status_message = $de_output->{"statusMessage"};

	//サーバーに一旦プロフィール画像を保存
	$target_url = $icon_path;
	$filename = strrchr( $target_url, "/" );
	$filename = substr($filename, 1);
	if(isset($target_url)){
		$data = file_get_contents($target_url);
		file_put_contents("./user_icons/".$filename.".jpg", $data);
		$user_icon = "https://hikawa.nkmr.io/LINEBOT/lab_experiment/user_icons/".$filename.".jpg";
	}else{
		$user_icon = null;
	}
	//$user_icon = $icon_path;