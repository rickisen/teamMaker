<?php 
$database = DB::getInstance();

// script that simultates usage of the lobby part of our site. 
// Meant to be run once every minute.

// get the amount of users in our DB
$qGetUsers = 'SELECT id from user';
if( $result = $database->query($qGetUsers) ){
  $totalAmmountOfUsers = $result->num_rows;
} else $totalAmmountOfUsers = 100 ; 

// remember theese ID's so that we dont try to put a 
// user into PLFL who is already in a lobby
$usersAlreadyInLobbies = array();
$qGetUsersAlreadyInLobbies =  ' SELECT id from lobby left join user on lobby.steam_id = user.steam_id';
if( $result = $database->query($qGetUsersAlreadyInLobbies) ){
  while ($row = $result->fetch_assoc()){
    $usersAlreadyInLobbies[] = $row['id'];
  }
}

// create the users and insert them
$NumberOfUsersToInsert = rand(5,10);
for ($i = 0 ; $i != $NumberOfUsersToInsert ; $i++){

  // get an id that is not already in a lobby
  $randomUserId = randIdNotInLobby($totalAmmountOfUsers, $usersAlreadyInLobbies);

  // create the user object and upload it
  $user = new User($randomUserId);
  $user->insertIntoPLFL();

  // wait for a while so we get natural data
  sleep(rand(1,5));
}

// Functions =====================================================================
function randIdNotInLobby($maxNumber, $lobby, $try = 0){
  if ($try > 500 ) return FALSE;
  $number = rand(1, $maxNumber);
  if (! in_array($number, $lobby))
    return $number;
  else 
    return randIdNotInLobby($maxNumber, $lobby, ++$try);
}

// CLASSES =====================================================================
class User{
  private $id;
  private $steam_id;

  function __get($val){
    return $this->$val;
  }

  function __construct($id){
    $database = DB::getInstance();
    $this->id = $id ;

    $qGetSteamId = ' SELECT steam_id FROM user WHERE id = '.$this->id.' LIMIT 1 '; 
    if ($result = $database->query($qGetSteamId)){
      $this->steam_id = $result->fetch_assoc()['steam_id'];
    }
  }

  function insertIntoPLFL(){
    $database = DB::getInstance();
    $qInsert = 'INSERT INTO player_looking_for_lobby (steam_id) VALUES ('.$this->steam_id.')';
    $database->query($qInsert);
    if ($error = $database->error)
      echo "something went wrong when trying to move user ".$this->steam_id." into PLFL: $error ";
  }
}

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
