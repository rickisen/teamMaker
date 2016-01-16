<?php 
// script that removes any lobby older than x minutes

$database = DB::getInstance();
$qClearOldLobbies = ' DELETE FROM lobby WHERE created < (NOW() - INTERVAL 10 MINUTE) ';
$database->query($qClearOldLobbies);

if ($error = $database->error)
  echo "something went wrong when clearing old lobbies: $error";
