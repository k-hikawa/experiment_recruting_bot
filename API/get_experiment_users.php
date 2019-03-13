<?php

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$secret = file_get_contents("./secret/.secret");
$params = explode("\n", $secret);
define("DSN", $params[0]);
define("USERNAME", $params[1]);
define("PASSWORD", $params[2]);
define("OPTIONS", [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
define("ACCESSTOKEN", $params[3]);

$result = [];

if (isset($_GET['ex_id'])) {
	$ex_id = $_GET['ex_id'];
	try {
		$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT * FROM `lab_experiment_bot_entrust` INNER JOIN `lab_experiment_bot_users` ON `lab_experiment_bot_entrust`.`user_id` = `lab_experiment_bot_users`.`id` AND `lab_experiment_bot_entrust`.`experiment_id` = ? AND `lab_experiment_bot_entrust`.`status` != -1 ORDER BY `status` DESC, `grade`, `login` DESC, `complete`, `try` ASC";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $ex_id);
		$stmt->execute();
		$now = date("Ymd");
		foreach ($stmt as $row) {
			if (isset($row['birthday'])) {
				$birthday = str_replace("-", "", $row['birthday']);//ハイフンを除去しています。
				$age = floor(($now - $birthday)/10000);
			} else {
				$age = "未設定";
			}
			if(is_null($row['message'])){
				$message = "";
			}else{
				$message = $row['message'];
			}
			$login = date('Y年n月j日G時i分', strtotime($row['login']));
			$result[] = ['id' => $row['user_id'], 'name' => $row['name'], 'icon' => $row['icon'], 'message' => $message, 'status' => $row['status'], 'try' => $row['try'], 'complete' => $row['complete'], 'login' => $login, 'age' => $age, 'grade' => $row['grade'], 'sex' => $row['sex']];
		}
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
}

$json = json_encode($result);
echo $json;