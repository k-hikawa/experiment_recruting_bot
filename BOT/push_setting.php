#!/usr/bin/php
<?php
/*
 * 設定が未設定のユーザに通知を送ります。
 *
 */
require "linebot_func.php";

$secret = file_get_contents("/home/hikawa/public_html/LINEBOT/lab_experiment/secret/.secret");
$params = explode("\n", $secret);
define("DSN", $params[0]);
define("USERNAME", $params[1]);
define("PASSWORD", $params[2]);
define("OPTIONS", [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
define("ACCESSTOKEN", $params[3]);

require "init_table.php";

try {
	$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	//$sql = "SELECT `id` FROM `lab_experiment_bot_users` WHERE `id` = 'U9d452a7c59d7d26e73553579bad5d7bb'";
	$sql = "SELECT `id` FROM `lab_experiment_bot_users` WHERE `birthday` IS NULL";

	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	foreach ($stmt as $row) {
		$user_id[] = $row['id'];
	}
	$response_data[] = response_data("text", "設定を完了させてください。\n未設定では実験依頼を受けられない可能性があります。", null);
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
} catch (PDOException $e) {
	exit($e->getMessage());
}