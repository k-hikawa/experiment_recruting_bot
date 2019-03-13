<?php
$AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
$params = [
  'client_id' => '732845476967-966eca4klht4lagmdvp6e5jtorqr5013.apps.googleusercontent.com',
  'redirect_uri' => 'https://hikawa.nkmr.io/LINEBOT/lab_experiment/send.php',
  'scope' => 'profile email',
  'response_type' => 'code',
  'access_type' => 'offline'
];
header("Location: " . $AUTH_URL. '?' . http_build_query($params));