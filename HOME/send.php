<?php
$user = 'XXXXXX';
$pass = 'XXXXXX';

if(isset($_SERVER['PHP_AUTH_USER']) && ($_SERVER["PHP_AUTH_USER"]==$user && $_SERVER["PHP_AUTH_PW"]==$pass)){
	$secret = file_get_contents("./secret/.secret");
	$params = explode("\n", $secret);
	define("DSN", $params[0]);
	define("USERNAME", $params[1]);
	define("PASSWORD", $params[2]);
	define("OPTIONS", [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
	define("ACCESSTOKEN", $params[3]);

	require "init_table.php";

	session_start();
	if (isset($_SESSION['id'])) {
		$login_id = $_SESSION['id'];
	} else {
		try {
			$TOKEN_URL = 'https://accounts.google.com/o/oauth2/token';
			$INFO_URL = 'https://www.googleapis.com/oauth2/v1/userinfo';
			//$APP_URL; = $_GET["state"];
			// HTTPヘッダの内容(※ここがかなり重要っぽい)
			$header = [
				'Content-Type: application/x-www-form-urlencoded',
			];
			$params = [
				'code' => $_GET['code'],
				'grant_type' => 'authorization_code',
				'redirect_uri' => 'https://hikawa.nkmr.io/LINEBOT/lab_experiment/send.php',
				'client_id' => 'XXXXXX',
				'client_secret' => 'XXXXXX'
			];
			$options = [
				'http' => [
					'header' => $header,
					'method' => 'POST',
					'content' => http_build_query($params)
				]
			];
			$res = file_get_contents($TOKEN_URL, false, stream_context_create($options));
			$token = json_decode($res, true);
			if (isset($token['error'])) {
				echo 'LoginError';
				exit;
			}
			$access_token = $token['access_token'];
			$params = ['access_token' => $access_token];
			$res = file_get_contents($INFO_URL . '?' . http_build_query($params));
			$res = json_decode($res, true);
			// var_dump($res);
			// $res["email"] email
			// $res["name"] ユーザー名
			insertGoogleUser($res['id'], $res['email'], $res['name'], $res['picture']);
		} catch (Exception $e) {
			header("Location: https://hikawa.nkmr.io/LINEBOT/lab_experiment/login.php");
		}
		$login_id = $res['id'];
		$_SESSION['id'] = $login_id;
	}
	
	try {
		$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$google_user_data = getGoogleUser($login_id);
		$user_data = getUsers();
		$experiment_data = getExperiments($login_id);
	} catch (PDOException $e) {
		exit($e->getMessage());
	}
} else {
	header("WWW-Authenticate: Basic realm=\"test1\"");
	header("HTTP/1.0 401 Unauthorized - test");
	echo "<p>キャンセルボタンが押されました</p>";
	exit;
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
	<meta charset="UTF-8">
	<title>中村研究室実験協力LINEBOT</title>
	<link rel="stylesheet" type="text/css" href="./css/main.css">
	<script src="./js/main.js"></script>
</head>
<body>
	<?php
	if (isset($_GET['error'])) {
		if ($_GET['error']) {
			echo "<img src='./img/error.png' width=300>";
		}
	}

	?>

	<a href="logout.php">ログアウト</a>
	
	<h3><input type="radio" name="type" id="create_experiment_radio">実験作成</h3>
	<form action="send_experiment.php" method="POST" id="create_experiment_form">
		<h4>実験依頼者:
			<?php
			echo $google_user_data['name'];
			echo "<input type='hidden' name='name' id='client_id' value='".$google_user_data['name']."'>";
			echo "<input type='hidden' name='google_id' id='client_name' value='".$google_user_data['id']."'>";
			//echo "<input type='hidden' name='email' id='client_email' value='".$google_user_data['email']."'>";
			?>
		</h4>
		<h4>連絡先メールアドレス（任意）</h4>
		<?php
			echo "<input type='text' size=40 name='email' value='".$google_user_data['email']."'>";
		?>
		<h4>実験名（40文字以内）</h4><input type="text" size=80 name="title" id="experiment_title" maxlength='40'>
		<h4>実験ページURL（任意）</h4><input type="text" size=40 name="url" id="experiment_url">
		<p>※実験ページは1つしか設定できないのでうまいことやってくださいね
			<?php
			$rand = rand(0, 4);
			switch ($rand) {
				case 0:
					echo "❤️";
					break;
				case 1:
					echo "💕";
					break;
				case 2:
					echo "💖";
					break;
				case 3:
					echo "💗";
					break;
				case 4:
					echo "☠️";
					break;
			}
			?>
		</p>
		<h4>説明（60文字以内）</h4><textarea name="description" rows="4" cols="80" maxlength="60" id="experiment_description"></textarea>
		<p>※LINEの仕様が謎なので一度自分のLINEアカウントだけに送信し、テストすることを推奨しています！</p>
		<h4>実験依頼送信先選択</h4>
		<p><input type="button" value="全選択" id="experiment_select_all"><input type="button" value="B1" id="experiment_select_1"><input type="button" value="B2" id="experiment_select_2"><input type="button" value="B3" id="experiment_select_3"><input type="button" value="B4" id="experiment_select_4"><input type="button" value="M1" id="experiment_select_5"><input type="button" value="M2" id="experiment_select_6"></p>
		<table border=1>
			<tr><th></th><th>学年</th><th>性別</th><th>年齢</th><th>名前</th><th>アイコン</th><th>ひとこと</th><th>実験依頼受託数</th><th>実験依頼達成数</th><th>最終利用日時</th></tr>
			<?php
			$now = date("Ymd");
			foreach ($user_data as $value) {
				if (!is_null($value['birthday'])) {
					$birthday = str_replace("-", "", $value['birthday']);//ハイフンを除去しています。
					$age = floor(($now - $birthday)/10000);
				} else {
					$age = "未設定";
				}
				echo "<tr>";
				if ($value['block'] == 0) {
					echo "<td><input type='checkbox' class='experiment_user_checkbox experiment_user_checkbox".$value['grade']."' name='user_id[]' value='".$value['id']."'></td>";
					echo "<td>".gradeTextConverter($value['grade'])."</td>";
					echo "<td>".sexTextConverter($value['sex'])."</td>";
					echo "<td>".$age."</td>";
					echo "<td>".htmlspecialchars($value['name'])."</td>";
					echo "<td><img src='".$value['icon']."' width=50></td>";
					echo "<td>".htmlspecialchars($value['message'])."</td>";
					echo "<td>".$value['try']."</td>";
					echo "<td>".$value['complete']."</td>";
					echo "<td>".date('Y年n月j日G時i分', strtotime($value['login']))."</td>";
					echo "</tr>";
				}
			}
			?>
		</table>
		<h4>定員（最大で実験を受けられる人数です。）</h4>
		<p>※半角数字でちゃんといれないと100人ってことにしまーす</p>
		<p><input type="text" name="max" value="100">人</p>
		<input type="submit" id="create_experiment_submit">
	</form>
	
	<h3><input type="radio" name="type" id="send_message_radio">実験メニュー</h3>
	<form action="send_message.php" method="POST" id="send_message_form">
		<h4>実験者
			<?php
			echo $google_user_data['name'];
			echo "<input type='hidden' id='experiment_client_name' name='client' value='".$google_user_data['name']."'>";
			?>
		</h4>
			<h4>実験タイトル</h4>
				<select id="experiment_select" name="ex_id">
					<option>選んでください</option>
					<?php
					foreach ($experiment_data as $value) {
						echo "<option value='".$value['id']."'>".$value['title']."</option>";
					}
					echo "<input type='hidden' value='".$value['title']."' id='experiment_hidden_title' name='title'>";
					?>
				</select>
		<h4>実験を送ったユーザ</h4>
		<p><input type="button" value="全選択" id="message_select_all"><input type="button" value="実験中" id="message_select_experiment1"><input type="button" value="実験完了" id="message_select_experiment2"></p>

		<table border=1 id="experiment_user">
			<tr><th></th><th>ステータス</th><th>学年</th><th>性別</th><th>年齢</th><th>名前</th><th>アイコン</th><th>ひとこと</th><th>実験依頼受託数</th><th>実験依頼達成数</th><th>最終利用日時</th></tr>
		</table>
		<h4>メッセージタイプ</h4>
		<p><input type="radio" name="mess_type" value="complete_check" checked>達成確認（達成したかどうかのボタンを送信します）</p>
		<p><input type="radio" name="mess_type" value="message">文章（自由記述の文章を送ります）</p>
		<p><textarea type="textarea" name="message_content" rows="4" cols="40"></textarea></p>
		<p><input type="radio" name="mess_type" value="quite">選択したユーザを実験から外す</p>
		<!-- <p><input type="radio" name="mess_type" value="add">新たな実験協力者を追加する（未実装機能）</p> -->
		<input type="submit">
	</form>
	<h3>ログ</h3>
	<table border=1>
		<tr><th>最終ログ</th><th>名前</th></tr>
	<?php
	$sql = "SELECT * FROM `lab_experiment_bot_users` INNER JOIN `lab_experiment_bot_log` ON `lab_experiment_bot_users`.`id` = `lab_experiment_bot_log`.`user_id` AND `lab_experiment_bot_log`.`type`='text' AND `lab_experiment_bot_log`.`content` != '参加中の実験リスト' AND `lab_experiment_bot_log`.`content` != '届いている実験リスト' AND `lab_experiment_bot_log`.`content` != '完了した実験リスト' AND `lab_experiment_bot_log`.`content` != '設定' AND `lab_experiment_bot_log`.`type` = 'text' ORDER BY `lab_experiment_bot_users`.`name` ASC";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
	foreach ($stmt as $row) {
		if (!isset($temp_id)) {
			$temp_id = $row['user_id'];
		}
		if ($row['user_id'] != $temp_id) {
			echo "<tr><td>".date('Y年n月j日G時i分', strtotime($temp_date))."</td><td><a href='https://hikawa.nkmr.io/LINEBOT/lab_experiment/log.php?id=".$temp_id."&name=".htmlspecialchars($temp_name)."'>".htmlspecialchars($temp_name)."</td></tr>";
			$temp_id = $row['user_id'];
		}
		$temp_date = $row['date'];
		$temp_name = $row['name'];
	}
	echo "<tr><td>".date('Y年n月j日G時i分', strtotime($temp_date))."</td><td><a href='https://hikawa.nkmr.io/LINEBOT/lab_experiment/log.php?id=".$temp_id."&name=".htmlspecialchars($temp_name)."'>".htmlspecialchars($temp_name)."</td></tr>";
	?>
	</table>
</body>
</html>


<?php
/*
 * Googleユーザを登録
 *
 */
function insertGoogleUser($id, $email, $name, $picture) {
	global $pdo;
	$sql = "INSERT INTO `lab_experiment_bot_google_users`(`id`, `name`, `email`, `picture`) SELECT ?, ?, ?, ? WHERE NOT EXISTS (SELECT * FROM `lab_experiment_bot_google_users` WHERE `id` = ?)";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $id);
	$stmt->bindParam(2, $name);
	$stmt->bindParam(3, $email);
	$stmt->bindParam(4, $picture);
	$stmt->bindParam(5, $id);
	$stmt->execute();
	$count = $stmt->rowCount();
	if ($count != 1) {
		$sql = "UPDATE `lab_experiment_bot_google_users` SET `name` = ?, `email` = ?, `picture` = ? WHERE `id` = ?";
		$stmt = $pdo -> prepare($sql);
		$stmt->bindParam(1, $name);
		$stmt->bindParam(2, $email);
		$stmt->bindParam(3, $picture);
		$stmt->bindParam(4, $id);
		$stmt->execute();
	}
}

function getGoogleUser($id) {
	global $pdo;
	$sql = "SELECT * FROM `lab_experiment_bot_google_users` WHERE id = ?";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $id);
	$stmt->execute();
	$data = null;
	foreach ($stmt as $row) {
		$data = [
			'id' => $id,
			'name' => $row['name'],
			'email' => $row['email'],
			'picture' => $row['picture']
		];
	}
	return $data;
}

/*
 * LINEBOTユーザを取得
 *
 */
function getUsers() {
	global $pdo;
	$users = [];
	$data = [];
	$sql = "SELECT * FROM `lab_experiment_bot_users` ORDER BY grade, login DESC, complete, try ASC";
	foreach ($pdo->query($sql) as $row) {
		$data['id'] = $row['id'];
		$data['block'] = $row['block'];
		$data['name'] = $row['name'];
		$data['icon'] = $row['icon'];
		$data['message'] = $row['message'];
		$data['try'] = $row['try'];
		$data['complete'] = $row['complete'];
		$data['login'] = $row['login'];
		$data['sex'] = $row['sex'];
		$data['grade'] = $row['grade'];
		$data['birthday'] = $row['birthday'];
		$users[] = $data;
	}
	return $users;
}

/*
 * 実験リストを取得
 *
 */
function getExperiments($id) {
	global $pdo;
	$experiments = [];
	$sql = "SELECT * FROM `lab_experiment_bot_experiments` WHERE `google_id` = ? ORDER BY `id` DESC";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(1, $id);
	$stmt->execute();
	foreach ($stmt as $row) {
		$experiments[] = [
			'id' => $row['id'],
			'title' => $row['title'],
			'url' => $row['url'],
			'description' => $row['description'],
			'max' => $row['max']
		];
	}
	return $experiments;
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
		default:
			$sex_text = "未設定";
			break;
	}
	return $sex_text;
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
		default:
			$grade_text = "未設定";
			break;
	}
	return $grade_text;
}
?>