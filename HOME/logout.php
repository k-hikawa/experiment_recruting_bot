<?php
session_start();
$_SESSION = [];
session_destroy();
header("Location: https://hikawa.nkmr.io/LINEBOT/lab_experiment/send.php");
?>