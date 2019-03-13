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

if (isset($_GET['client'])) {
	$client = $_GET['client'];
	try {
		$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$sql = "SELECT * FROM `lab_experiment_bot_experiments` WHERE client = ? ORDER BY `id` DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $client);
		$stmt->execute();
		foreach($stmt as $row){
			array_push($result, ['id' => $row['id'], 'title' => $row['title'], 'client' => $row['client'], 'url' => $row['url'], 'description' => $row['description']]);
		}
	} catch(PDOException $e) {
		exit($e->getMessage());
	}
}

$json = json_encode($result);
echo $json;