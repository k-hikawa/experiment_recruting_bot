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


if (!empty($_POST['user_id']) && !empty($_POST['name']) && !empty($_POST['title']) && !empty($_POST['description']) && !empty($_POST['max'])) {
	try {
		$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		//var_dump($user_id);
		$user_id = $_POST['user_id'];
		$response_data = [];
		$client = $_POST['name'];
		$google_id = $_POST['google_id'];
		$title = $_POST['title'];
		$description = str_replace(["\r", "\n"], "", $_POST['description']);
		$max = $_POST['max'];
		if (!is_numeric($max)) {
			$max = 100;
		}
		if (!empty($_POST['url'])) {
			$url = $_POST['url'];
			$url = preg_replace('/^\s+/', '', $url);
			/*if (!filter_var($url, FILTER_VALIDATE_URL)) {
				$url = null;
			}*/
		} else {
			$url = null;
		}
		$ex_id = insertExperiment($google_id, $title, $url, $description, $max);
		insertEntrust($ex_id, $user_id);

		$label = ["実験に参加する！", "実験に参加しない"];
		$return_text = [$ex_id.",try", $ex_id.",reject"];
		$action = ["postback", "postback"];
		$response_data[] = response_data("text", $client."から実験の依頼が届いています↓", null);
		$response_data[] = button_message("", $title, $description, $label, $return_text, $action);
		if (isset($_POST['email'])) {
			$email = $_POST['email'];
			$response_data[] = response_data("text", "ご不明点がある際は".$client."にご連絡ください。\n".$email, null);
		} else {
			$email = "";
		}
		for ($i = 0; $i < count($user_id); ++$i) {
			$send_id[] = $user_id[$i];
			if ($i%150 == 149 || $i == count($user_id) - 1) {
				push_message(ACCESSTOKEN, $send_id, $response_data);
				foreach ($send_id as $value) {
					$sended[] = $value;
				}
				$send_id = [];
			}
		}
				
		echo "<p>実験依頼者: $client</p>";
		echo "<p>連絡先: $email</p>";
		echo "<p>実験名: $title</p>";
		if (!empty($url)) {
			echo "<p>実験ページURL: <a href='$url'>$url</a></p>";
		}
		echo "<p>説明: $description</p>";
		echo "<p>依頼を送った人:".count($sended)."人</p>";
		foreach ($sended as $value) {
			echo "$value<br>";
		}
		echo "<p>定員: ".$max."人</p>";
		echo "<form action='send.php'>";
		echo "<input type='submit' value='戻る'>";
		echo "</form>";
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
} else {
	header("Location: https://hikawa.nkmr.io/LINEBOT/lab_experiment/send.php?error=true");
}


/*
 * 実験の詳細をテーブルに入れる
 *
 */
function insertExperiment($google_id, $title, $url, $description, $max) {
	global $pdo;
	$sql = "INSERT INTO `lab_experiment_bot_experiments`(`google_id`, `title`, `url`, `description`, `max`, `created_at`) SELECT ?, ?, ?, ?, ?, cast(now() as datetime)";
	$stmt = $pdo -> prepare($sql);
	$stmt->bindParam(1, $google_id);
	$stmt->bindParam(2, $title);
	$stmt->bindParam(3, $url);
	$stmt->bindParam(4, $description);
	$stmt->bindParam(5, $max);
	$stmt->execute();
	$id = $pdo->lastInsertId();
	return $id;
}

/*
 * 実験と協力者の関係をテーブルに入れる
 *
 */
function insertEntrust($ex_id, $user_id) {
	global $pdo;
	$sql = "INSERT INTO `lab_experiment_bot_entrust`(`experiment_id`, `user_id`, `status`) VALUE";
	for ($i = 0; $i<count($user_id); ++$i) {
		$sql .= " (?, ?, ?)";
		if ($i != count($user_id) - 1) {
			$sql .= ",";
		}
	}
	$stmt = $pdo -> prepare($sql);
	$cnt = 1;
	for ($i = 0; $i<count($user_id); ++$i) {
		$stmt->bindParam($cnt, $ex_id);
		$cnt++;
		$stmt->bindParam($cnt, $user_id[$i]);
		$cnt++;
		$stmt->bindValue($cnt, 0);
		$cnt++;
	}
	$stmt->execute();
}