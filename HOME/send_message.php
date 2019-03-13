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


if (!empty($_POST['user_id']) && !empty($_POST['ex_id']) && !empty($_POST['client']) && !empty($_POST['title']) && !empty($_POST['mess_type'])) {
	try {
		$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$user_id = $_POST['user_id'];
		$ex_id = $_POST['ex_id'];
		$client = $_POST['client'];
		$title = $_POST['title'];
		$mess_type = $_POST['mess_type'];
		if ($mess_type == "complete_check") {
			$label = ["実験終了"];
			$return_text = [$ex_id.",complete"];
			$action = ["postback"];
			$text = "実験が完了しましたら、「実験終了」ボタンを押してください。";
			$response_data = [button_message("", $title, $text, $label, $return_text, $action)];
			
		} else if($mess_type == "message") {
			if (!empty($_POST['message_content'])) {
				$message_content = $_POST['message_content'];
				$response_data[] = response_data("text", $title."の".$client."からのメッセージです↓", null);
				$response_data[] = response_data("text", $message_content, null);
				
			} else {
				header("Location: https://hikawa.nkmr.io/LINEBOT/lab_experiment/send.php?error=true");
			}
		} else if ($mess_type == "quite") {
			foreach ($user_id as $value){
				$sql = "UPDATE `lab_experiment_bot_entrust` SET status = -1 WHERE experiment_id = ? AND user_id = ?";
				$stmt = $pdo->prepare($sql);
				$stmt->bindParam(1, $ex_id);
				$stmt->bindParam(2, $value);
				$stmt->execute();
				$sql = "UPDATE `lab_experiment_bot_users` SET `try` = `try`-1 WHERE `id` = ?";
				$stmt = $pdo->prepare($sql);
				$stmt->bindParam(1, $value);
				$stmt->execute();
			}
			$response_data[] = response_data("text", $client."の".$title."ですが、都合により中止します。\n申し訳ございません。", null);
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
		echo "<form action='send.php'>";
		echo "<input type='submit' value='戻る'>";
		echo "</form>";
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
} else {
	header("Location: https://hikawa.nkmr.io/LINEBOT/lab_experiment/send.php?error=true");
}