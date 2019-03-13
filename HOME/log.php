<?php
$secret = file_get_contents("./secret/.secret");
$params = explode("\n", $secret);
define("DSN", $params[0]);
define("USERNAME", $params[1]);
define("PASSWORD", $params[2]);
define("OPTIONS", [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
define("ACCESSTOKEN", $params[3]);

require "init_table.php";
?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>Document</title>
</head>
<body>

	<?php
	if (isset($_GET['id']) && isset($_GET['name'])) {
		$id = $_GET['id'];
		$name = $_GET['name'];
		echo "<h3>".$name."のログ</h3>";
		echo "<table border=1>";
		echo "<tr><th>時間</th><th>発言</th></tr>";
		$sql = "SELECT * FROM `lab_experiment_bot_log` WHERE `user_id` = ? AND `type` = ? ORDER BY `id` DESC";
		$stmt = $pdo->prepare($sql);
		$stmt->bindParam(1, $id);
		$stmt->bindValue(2, "text");
		$stmt->execute();
		foreach ($stmt as $row) {
			switch ($row['content']) {
				case "参加中の実験リスト":
				case "届いている実験リスト":
				case "参加中の実験リスト":
				case "完了した実験リスト":
				case "設定":
					break;
				default:
					echo "<tr><td>".date('Y年n月j日G時i分', strtotime($row['date']))."</td><td>".htmlspecialchars($row['content'])."</td></tr>";
					break;
			}
		}
	}
	?>
	</table>
</body>
</html>