<?php
require "linebot_func.php";

$secret = file_get_contents("./secret/.secret");
$params = explode("\n", $secret);
define("DSN", $params[0]);
define("USERNAME", $params[1]);
define("PASSWORD", $params[2]);
define("OPTIONS", [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
define("ACCESSTOKEN", $params[3]);

require "init_table.php";
require "linebot_init.php";

try {
	$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	// follow typeの時
	switch ($type) {
		case "follow":
			if (registUser($user_id, $user_name, $user_icon, $status_message)) {
				$response_data[] = response_data("text", "ともだち登録ありがとうございます！\n中村研で実験の募集がある際にこちらに通知を送らせていただきます。", null);
				$response_data[] = response_data("text", "まずは「システムメニュー」の「設定」から学年、生年月日、性別を設定してください。\nこちらの情報は実験参加者を募る際の参考に使用します。", null);
			} else {
				updateUserTable($user_id, $user_name, $user_icon, $status_message);
			}
			break; 
		case "unfollow":
			blockUserTable($user_id, $user_name, $user_icon, $status_message);
			break;
		default:
			updateUserTable($user_id, $user_name, $user_icon, $status_message);
			break;
	}

	// postback typeの時
	switch ($type) {
		case "postback":
			switch ($postback) {
				case "birthday":
					updateBirthday($user_id, $postback_date);
					$response_data[] = response_data("text", "生年月日を".date('Y年n月j日', strtotime($postback_date))."で設定しました。", null);
					$setting_data = getSettingData($user_id);
					// 性別の設定
					$thumb = "";
					$title = "性別の設定";
					if (!is_null($setting_data['sex'])) {
						$sex_text = sexTextConverter($setting_data['sex']);
						$content = "現在の設定: ".$sex_text;
					} else {
						$content = " ";
					}
					$label = ['男性', '女性', 'その他'];
					$return_text = ['1,sex', '2,sex', '3,sex'];
					$action = ['postback', 'postback', 'postback'];
					$response_data[] = button_message($thumb, $title, $content, $label, $return_text, $action);
					break;
				default:
				$postback_data = explode(",", $postback);
				switch ($postback_data[1]) {
					case "try":
						$experiment_max = false;
						switch (entrustExperiment($user_id, $postback_data[0])) {
							case 0:
								$response_data[] = response_data("text", "ご協力ありがとうございます！\n実験については「システムメニュー」の「参加中の実験リスト」から確認してください。", null);
								break;
							case 1:
								$response_data[] = response_data("text", "申し訳ございません。\n参加者が定員に達してしまったようです。", null);
								$response_data[] = response_data("text", "キャンセル待ちをしたい場合は「実験に参加しない」を押さず、そのままでお待ちください。", null);
								break;
							case 2:
								$response_data = [response_data("text", "この処理はできません。", null)];
								break;
						}
						break;
					case "reject":
						if (rejectExperiment($user_id, $postback_data[0])) {
							$response_data = [response_data("text", "残念です、また依頼があり次第お送りします。", null)];
						} else {
							$response_data = [response_data("text", "この処理はできません。", null)];
						}
						break;
					case "rejectcheck":
						$thumb = "";
						$title = "参加をキャンセルしますか？";
						$content = "この操作は取り消せません！";
						$label = ['参加キャンセルする'];
						$return_text = [$postback_data[0].',reject'];
						$action = ['postback'];
						$response_data[] = button_message($thumb, $title, $content, $label, $return_text, $action);
						break;
					case "complete":
						if (completeExperiment($user_id, $postback_data[0])) {
							$response_data = [response_data("text", "お疲れ様でした。\nご協力ありがとうございました。", null)];
						} else {
							$response_data = [response_data("text", "この処理はできません。", null)];
						}
						break;
					case "uncomplete":
						if (uncompleteExperiment($user_id, $postback_data[0])) {
							$response_data = [response_data("text", "実験完了を取り消しました。\n実験が完了しましたら、「実験終了」ボタンを押してください。", null)];
						} else {
							$response_data = [response_data("text", "この処理はできません。", null)];
						}
						break;
					case "url_is_null":
						$response_data = [response_data("text", "この実験は実験ページが存在しません。", null)];
						break;
					case "sex":
						updateSex($user_id, $postback_data[0]);
						$sex_text = sexTextConverter($postback_data[0]);
						$response_data = [response_data("text", "性別を".$sex_text."で設定しました。", null)];
						break;
					case "grade":
						updateGrade($user_id, $postback_data[0]);
						$grade_text = gradeTextConverter($postback_data[0]);
						$response_data[] = response_data("text", "学年を".$grade_text."で設定しました。", null);
						// 生年月日の設定
						$setting_data = getSettingData($user_id);
						$thumb = "";
						$title = "生年月日の設定";
						if (!is_null($setting_data['birthday'])) {
							$content = "現在の設定: ".date('Y年n月j日', strtotime($setting_data['birthday']));
						} else {
							$content = " ";
						}
						$label = ['変更する'];
						$return_text = [['birthday', 'date']];
						$action = ['datetimepicker'];
						$response_data[] = button_message($thumb, $title, $content, $label, $return_text, $action);
						break;
				}
			}
			break;
		// text typeの時
		case "text":
			switch ($text) {
				case "参加中の実験リスト":
					$experiments = getExperimentList($user_id, 1, "ASC");
					if (count($experiments) > 0) {
						$thumb = $title = $content = $label = $return_text = $action = [];
						for ($i = 0; $i < count($experiments); ++$i) {
							$thumb[] = "";
							$title[] = $experiments[$i]['title'];
							$content[] = $experiments[$i]['description'];
							$label[] = ['実験ページを開く', '実験終了', '参加キャンセル'];
							if (isset($experiments[$i]['url'])) {
								$return_text[] = [$experiments[$i]['url'], $experiments[$i]['ex_id'].",complete", $experiments[$i]['ex_id'].",reject"];
								$action[] = ['uri', 'postback', 'postback'];
							} else {
								$return_text[] = [$experiments[$i]['ex_id'].",url_is_null", $experiments[$i]['ex_id'].",complete", $experiments[$i]['ex_id'].",rejectcheck"];
								$action[] = ['postback','postback', 'postback'];
							}
							if ($i%10 == 9 || $i == count($experiments) - 1) {
								$response_data[] = carousel_message($thumb, $title, $content, $label, $return_text, $action);
								//$response_data = [response_data("text", json_encode(carousel_message($thumb, $title, $content, $label, $return_text, $action)), null)];
								$thumb = $title = $content = $label = $return_text = $action = [];
							}
						}
					} else {
						$response_data = [response_data("text", "ありません", null)];
					}
					break;
				case "届いている実験リスト":
					$experiments = getExperimentList($user_id, 0, "DESC");
					if (count($experiments) > 0) {
						$thumb = $title = $content = $label = $return_text = $action = [];
						$response_data = [];
						for ($i = 0; $i < count($experiments); ++$i) {
							$thumb[] = "";
							$title[] = $experiments[$i]['title'];
							$content[] = $experiments[$i]['description'];
							$label[] = ['実験に参加する！', '実験に参加しない'];
							$return_text[] = [$experiments[$i]['ex_id'].",try", $experiments[$i]['ex_id'].",reject"];
							$action[] = ['postback', 'postback'];
							if($i%10 == 9 || $i == count($experiments)-1){
								$response_data[] = carousel_message($thumb, $title, $content, $label, $return_text, $action);
								$thumb = $title = $content = $label = $return_text = $action = [];
							}
						}
					} else {
						$response_data = [response_data("text", "ありません", null)];
					}
					break;
				case "完了した実験リスト":
					$experiments = getExperimentList($user_id, 2, "DESC");
					if (count($experiments) > 0) {
						$thumb = $title = $content = $label = $return_text = $action = [];
						$response_data = [];
						for ($i = 0; $i < count($experiments); ++$i) {
							$thumb[] = "";
							$title[] = $experiments[$i]['title'];
							$content[] = $experiments[$i]['description'];
							$label[] = ['完了取り消し'];
							$return_text[] = [$experiments[$i]['ex_id'].",uncomplete"];
							$action[] = ['postback'];
							if ($i % 10 == 9 || $i == count($experiments) - 1) {
								$response_data[] = carousel_message($thumb, $title, $content, $label, $return_text, $action);
								$thumb = $title = $content = $label = $return_text = $action = [];
							}
						}
					} else {
						$response_data = [response_data("text", "ありません", null)];
					}
					break;
				case "設定":
					$setting_data = getSettingData($user_id);
					$response_data = [];
					// 学年の設定
					$thumb = ["", "", ""];
					$title = ['学年の設定', ' ', ' '];
					if (!is_null($setting_data['grade'])) {
						$grade_text = gradeTextConverter($setting_data['grade']);
						$content = ['現在の設定: '.$grade_text, ' ', ' '];
					} else {
						$content = [' ', ' ', ' '];
					}
					$label = [['B1', 'B2', 'B3'], ['B4', 'M1', 'M2'], ['学生', '教員', 'その他']];
					$return_text = [['1,grade', '2,grade', '3,grade'], ['4,grade', '5,grade', '6,grade'], ['7,grade', '8,grade', '9,grade']];
					$action = [['postback', 'postback', 'postback'], ['postback', 'postback', 'postback'], ['postback', 'postback', 'postback']];
					$response_data[] = carousel_message($thumb, $title, $content, $label, $return_text, $action);
					break;
				default:
					switch (rand(0,5)) {
						case 0:
							$response_data[] = response_data("text", "ごめんなさい、よくわかりません。", null);
							break;
						case 1:
							$response_data[] = response_data("text", "😪", null);
							break;
						case 2:
							$response_data[] = response_data("text", "いいと思います。", null);
							break;
						case 3:
							$response_data[] = response_data("text", "問題がありましたら中村研に来てください。", null);
							break;
						case 4:
							$response_data[] = response_data("text", "難しいことはわからないんです。", null);
							break;
						case 5:
							$response_data[] = response_data("text", "こんにちは。".$user_name."さん。", null);
							break;
					}
					
					break;
			}
	}

} catch (PDOException $e) {
	exit($e->getMessage());
}

//$response_data = [response_data("text", json_encode($experiments), null)];
if (count($response_data) != 0) {
	response(ACCESSTOKEN, $replyToken, $response_data);
}
var_dump($response_data);

// 参加した実験が定員に達した実験だったらステータス0のユーザに通知を送る
if ($experiment_max) {
	$sql = "SELECT `user_id` FROM `lab_experiment_bot_entrust` WHERE `experiment_id` = ? AND `status` = 0";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $postback_data[0]);
	$stmt->execute();
	$send_user = [];
	foreach ($stmt as $row) {
		$send_user[] = $row['user_id'];
	}
	//$sql = "SELECT `google_id`, `title` FROM `lab_experiment_bot_experiments` WHERE `id` = ?";
	$sql = "SELECT * FROM `lab_experiment_bot_experiments` INNER JOIN `lab_experiment_bot_google_users` ON `lab_experiment_bot_experiments`.`google_id` = `lab_experiment_bot_google_users`.`id` AND `lab_experiment_bot_experiments`.`id` = ?";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $postback_data[0]);
	$stmt->execute();
	foreach ($stmt as $row) {
		$google_id = $row['google_id'];
		$title = $row['title'];
		$client = $row['name'];
	}
	$send_data = [];
	$send_data[] = response_data("text", $client."の".$title."ですが、定員に達しました。\nキャンセル待ちをしたい場合は「実験に参加しない」を押さずにこのままでお待ちください。", null);
	push_message(ACCESSTOKEN, $send_user, $send_data);
}

// ユーザが何をしたかをログに残す
if (isset($text)) {
	$log = $text;
} else if (isset($postback)) {
	$log = $postback;
}
putLog($user_id, $type, $log);

/*
 * ユーザをデータベースに登録
 *
 */
function registUser($user_id, $name, $icon, $status_message) {
	global $pdo;
	$sql = "INSERT INTO `lab_experiment_bot_users`(`id`, `block`, `name`, `icon`, `message`, `try`, `complete`, `login`) SELECT ?, ?, ?, ?, ?, ?, ?, cast(now() as datetime) WHERE NOT EXISTS (SELECT * FROM `lab_experiment_bot_users` WHERE `id` = ?)";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindValue(2, 0);
	$stmt->bindParam(3, $name);
	$stmt->bindParam(4, $icon);
	$stmt->bindParam(5, $status_message);
	$stmt->bindValue(6, 0);
	$stmt->bindValue(7, 0);
	$stmt->bindParam(8, $user_id);
	$stmt->execute();
	$count = $stmt->rowCount();
	if ($count == 1) {
		$result = true;
	} else {
		$result = false;
	}
	return $result;
}

/*
 * ユーザデータの更新
 *
 */
function updateUserTable($user_id, $name, $icon, $status_message) {
	global $pdo;
	$sql = "UPDATE `lab_experiment_bot_users` SET `block` = 0, `name` = ?, `icon` = ?, `message` = ?, `login` = cast(now() as datetime) WHERE id = ?";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $name);
	$stmt->bindParam(2, $icon);
	$stmt->bindParam(3, $status_message);
	$stmt->bindParam(4, $user_id);
	$stmt->execute();
}


/*
 * ブロックしたユーザの処理
 *
 */
function blockUserTable($user_id, $name, $icon, $status_message) {
	global $pdo;
	$sql = "UPDATE `lab_experiment_bot_users` SET `block` = 1 WHERE id = ?";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->execute();
}

/*
 * 実験受託した時の処理
 *
 * return 0 実験受託
 * return 1 募集人数いっぱい
 * return 2 処理失敗
 */
function entrustExperiment($user_id, $ex_id) {
	global $pdo;
	// 実験をすでに受けているかどうかをチェック
	$sql = "SELECT `status` FROM `lab_experiment_bot_entrust` WHERE `user_id` = ? AND `experiment_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $ex_id);
	$stmt->execute();
	foreach ($stmt as $row) {
		$status = $row['status'];
	}

	// ステータスが0の時、募集人数に達していないかをチェックする
	if ($status == 0) {
		$sql = "SELECT COUNT(*) AS `num` FROM `lab_experiment_bot_entrust` WHERE `experiment_id` = ? AND `status` >= 1";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $ex_id);
		$stmt->execute();
		foreach ($stmt as $row) {
			$num = $row['num'];
		}

		// 募集人数が何人だったかを取得
		$sql = "SELECT `max` FROM `lab_experiment_bot_experiments` WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $ex_id);
		$stmt->execute();
		foreach ($stmt as $row) {
			$max = $row['max'];
		}

		if ($max > $num) {
			$sql = "UPDATE `lab_experiment_bot_entrust` SET `status` = ? WHERE `user_id` = ? AND `experiment_id` = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(1, 1);
			$stmt->bindParam(2, $user_id);
			$stmt->bindParam(3, $ex_id);
			$stmt->execute();

			$sql = "UPDATE `lab_experiment_bot_users` SET `try` = `try`+1 WHERE `id` = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(1, $user_id);
			$stmt->execute();

			// ちょうど定員がいっぱいになったら未読状態のユーザに通知を送る
			if ($max == $num + 1) {
				global $experiment_max;
				$experiment_max = true;
			}
			return 0;
		} else {
			return 1;
		}
	} else {
		return 2;
	}
	
	/*$change = $stmt->rowCount();
	if($change == 1){
		if($status == 1){
			$sql = "UPDATE `lab_experiment_bot_users` SET `try` = `try`+1 WHERE `id` = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(1, $user_id);
			$stmt->execute();
		}else if($status == 2){
			$sql = "UPDATE `lab_experiment_bot_users` SET `complete` = `complete`+1 WHERE `id` = ?";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(1, $user_id);
			$stmt->execute();
		}
		return true;
	}else{
		return false;
	}*/
}

/*
 * 実験に参加しないを選んだ時の処理
 *
 */
function rejectExperiment($user_id, $ex_id) {
	global $pdo;
	// 実験を受けていないかをチェック
	$sql = "SELECT `status` FROM `lab_experiment_bot_entrust` WHERE `user_id` = ? AND `experiment_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $ex_id);
	$stmt->execute();
	foreach ($stmt as $row) {
		$status = $row['status'];
	}
	if ($status == 0 || $status == 1) {
		$sql = "UPDATE `lab_experiment_bot_entrust` SET `status` = ? WHERE `user_id` = ? AND `experiment_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(1, -1);
		$stmt->bindParam(2, $user_id);
		$stmt->bindParam(3, $ex_id);
		$stmt->execute();

		$sql = "UPDATE `lab_experiment_bot_entrust` SET `status` = ? WHERE `user_id` = ? AND `experiment_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(1, -1);
		$stmt->bindParam(2, $user_id);
		$stmt->bindParam(3, $ex_id);
		$stmt->execute();
		return true;
	} else {
		return false;
	}
}

/*
 * 実験を達成した時の処理
 *
 */
function completeExperiment($user_id, $ex_id) {
	global $pdo;
	// 実験を受けていないかをチェック
	$sql = "SELECT `status` FROM `lab_experiment_bot_entrust` WHERE `user_id` = ? AND `experiment_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $ex_id);
	$stmt->execute();
	foreach ($stmt as $row) {
		$status = $row['status'];
	}
	if ($status == 1) {
		$sql = "UPDATE `lab_experiment_bot_entrust` SET `status` = ? WHERE `user_id` = ? AND `experiment_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(1, 2);
		$stmt->bindParam(2, $user_id);
		$stmt->bindParam(3, $ex_id);
		$stmt->execute();

		$sql = "UPDATE `lab_experiment_bot_users` SET `complete` = `complete`+1 WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $user_id);
		$stmt->execute();
		return true;
	} else {
		return false;
	}
}

/*
 * 実験達成を取り消した時の処理
 *
 */
function uncompleteExperiment($user_id, $ex_id) {
	global $pdo;
	// 実験を受けていないかをチェック
	$sql = "SELECT `status` FROM `lab_experiment_bot_entrust` WHERE `user_id` = ? AND `experiment_id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $ex_id);
	$stmt->execute();
	foreach ($stmt as $row) {
		$status = $row['status'];
	}
	if ($status == 2) {
		$sql = "UPDATE `lab_experiment_bot_entrust` SET `status` = ? WHERE `user_id` = ? AND `experiment_id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(1, 1);
		$stmt->bindParam(2, $user_id);
		$stmt->bindParam(3, $ex_id);
		$stmt->execute();

		$sql = "UPDATE `lab_experiment_bot_users` SET `complete` = `complete`-1 WHERE `id` = ?";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $user_id);
		$stmt->execute();
		return true;
	} else {
		return false;
	}
}

/*
 * 指定したユーザのstatus番号の実験リストを返す
 *
 */
function getExperimentList($user_id, $status, $sort) {
	global $pdo;
	$experiments = [];
	$sql = "SELECT * FROM `lab_experiment_bot_entrust` INNER JOIN `lab_experiment_bot_experiments` ON `lab_experiment_bot_entrust`.`experiment_id` = `lab_experiment_bot_experiments`.`id` AND `lab_experiment_bot_entrust`.`user_id` = ? AND `lab_experiment_bot_entrust`.`status` = ? ORDER BY `lab_experiment_bot_experiments`.`id` $sort LIMIT 50";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $status);
	$stmt->execute();
	foreach ($stmt as $row) {
		$experiments[] = ['ex_id' => $row['experiment_id'], 'google_id' => $row['google_id'], 'title' => $row['title'], 'url' => $row['url'], 'description' => $row['description']];
	}
	return $experiments;
}

/*
 * 指定したユーザの設定情報を取得
 *
 */
function getSettingData($user_id) {
	global $pdo;
	$sql = "SELECT `birthday`, `grade`, `sex` FROM `lab_experiment_bot_users` WHERE `id` = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->execute();
	foreach($stmt as $row){
		$setting_data = ['birthday' => $row['birthday'], 'grade' => $row['grade'], 'sex' => $row['sex']];
	}
	return $setting_data;
}

/*
 * 性別の更新
 *
 */
function updateSex($user_id, $sex) {
	global $pdo;
	$sql = "UPDATE `lab_experiment_bot_users` SET `sex` = ? WHERE `id` = ?";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $sex);
	$stmt->bindParam(2, $user_id);
	$stmt->execute();
}

/*
 * 学年の更新
 *
 */
function updateGrade($user_id, $grade) {
	global $pdo;
	$sql = "UPDATE `lab_experiment_bot_users` SET `grade` = ? WHERE `id` = ?";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $grade);
	$stmt->bindParam(2, $user_id);
	$stmt->execute();
}

/*
 * 生年月日の更新
 *
 */
function updateBirthday($user_id, $birthday) {
	global $pdo;
	$sql = "UPDATE `lab_experiment_bot_users` SET `birthday` = cast(? as date) WHERE `id` = ?";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $birthday);
	$stmt->bindParam(2, $user_id);
	$stmt->execute();
}

/*
 * ログをデータベースに残す
 *
 */
function putLog($user_id, $type, $content) {
	global $pdo;
	$sql = "INSERT INTO `lab_experiment_bot_log`(`user_id`, `type`, `content`, `date`) SELECT ?, ?, ?, cast(now() as datetime)";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $user_id);
	$stmt->bindParam(2, $type);
	$stmt->bindParam(3, $content);
	$stmt->execute();
}

/*
 * 性別を文字列に変換
 *
 */
function sexTextConverter($num) {
	switch ($num[0]) {
		case 1:
			$sex_text = "男性";
			break;
		case 2:
			$sex_text = "女性";
			break;
		case 3:
			$sex_text = "その他";
			break;
	}
	return $sex_text;
}

/*
 * google_idをclient名に変換
 *
 */
function convertClientName($google_id) {
	global $pdo;
	$sql = "SELECT `name` FROM `lab_experiment_bot_google_users` WHERE `id` = ?";
	$stmt->prepare($sql);
	$stmt->bindParam(1, $google_id);
	$stmt->execute();
	foreach ($stmt as $row) {
		$name = $row['name'];
	}
	return $name;
}


/*
 * 学年を文字列に変換
 *
 */
function gradeTextConverter($num) {
	switch ($num[0]) {
		case 1:
			$grade_text = "B1";
			break;
		case 2:
			$grade_text = "B2";
			break;
		case 3:
			$grade_text = "B3";
			break;
		case 4:
			$grade_text = "B4";
			break;
		case 5:
			$grade_text = "M1";
			break;
		case 6:
			$grade_text = "M2";
			break;
		case 7:
			$grade_text = "学生";
			break;
		case 8:
			$grade_text = "教員";
			break;
		case 9:
			$grade_text = "その他";
			break;
	}
	return $grade_text;
}