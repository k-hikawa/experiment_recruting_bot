<?php
try {
	$pdo = new PDO(DSN, USERNAME, PASSWORD, OPTIONS);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	createUserTable();
	createGoogleUserTable();
	createEntrustTable();
	createExperimentTable();
	createLogTable();
} catch(PDOException $e) {
	exit($e->getMessage());
}

/*
 * ユーザテーブルの準備
 *
 */
function createUserTable() {
	global $pdo;
	$sql = "CREATE TABLE IF NOT EXISTS `lab_experiment_bot_users`"
	."("
	. "`id` TEXT NOT NULL,"
	. "`block` TINYINT(1) NOT NULL,"
	. "`name` TEXT,"
	. "`icon` TEXT,"
	. "`message` TEXT,"
	. "`try` INT NOT NULL,"
	. "`complete` INT NOT NULL,"
	. "`login` DATETIME NOT NULL,"
	. "`sex` INT,"
	. "`grade` INT,"
	. "`birthday` DATE"
	.")DEFAULT CHARSET=utf8mb4;";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
}

/*
 * 実験者テーブルの準備
 *
 */
function createGoogleUserTable() {
	global $pdo;
	$sql = "CREATE TABLE IF NOT EXISTS `lab_experiment_bot_google_users`"
	."("
	. "`id` TEXT NOT NULL,"
	. "`name` TEXT NOT NULL,"
	. "`email` TEXT NOT NULL,"
	. "`picture` TEXT NOT NULL"
	.")DEFAULT CHARSET=utf8mb4;";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
}

/*
 * 実験を受けているユーザのテーブルを作る
 * status = -1 受託拒否
 * status = 0 未受託
 * status = 1 実験協力中
 * status = 2 実験終了
 */
function createEntrustTable() {
	global $pdo;
	$sql = "CREATE TABLE IF NOT EXISTS `lab_experiment_bot_entrust`"
	."("
	. "`id` INT NOT NULL auto_increment primary key,"
	. "`experiment_id` INT NOT NULL,"
	. "`user_id` TEXT NOT NULL,"
	. "`status` INT NOT NULL"
	.");";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
}

/*
 * 実験テーブルの準備
 *
 */
function createExperimentTable() {
	global $pdo;
	$sql = "CREATE TABLE IF NOT EXISTS `lab_experiment_bot_experiments`"
	."("
	. "`id` INT NOT NULL auto_increment primary key,"
	. "`google_id` TEXT NOT NULL,"
	. "`title` TEXT NOT NULL,"
	. "`url` TEXT,"
	. "`description` TEXT NOT NULL,"
	. "`max` INT NOT NULL,"
	. "`created_at` DATETIME NOT NULL"
	.")DEFAULT CHARSET=utf8mb4;";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
}

/*
 * ログテーブルの準備
 *
 */
function createLogTable() {
	global $pdo;
	$sql = "CREATE TABLE IF NOT EXISTS `lab_experiment_bot_log`"
	."("
	. "`id` INT NOT NULL auto_increment primary key,"
	. "`user_id` TEXT NOT NULL,"
	. "`type` TEXT NOT NULL,"
	. "`content` TEXT,"
	. "`date` DATETIME NOT NULL"
	.")DEFAULT CHARSET=utf8mb4;";
	$stmt = $pdo->prepare($sql);
	$stmt->execute();
}