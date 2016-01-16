<?php 
// script that removes any lobby older than x minutes

$database = DB::getInstance();
$qClearOldLobbies = ' DELETE FROM lobby WHERE created < (NOW() - INTERVAL 10 MINUTE) ';
$database->query($qClearOldLobbies);

if ($error = $database->error)
  echo "something went wrong when clearing old lobbies: $error";


// CLASSES =====================================================================
class DB{
  private static $instance;
  private function __construct(){}
  private function __clone(){}

  public static function getInstance(){
    if(!self::$instance){
        $config = parse_ini_file('mysqliConfig.ini');
      self::$instance = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
      return self::$instance;
    }else{
      return self::$instance;
    }
  }
}
