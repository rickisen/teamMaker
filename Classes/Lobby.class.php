<?php

class Lobby {

  private $quality;
  private $lobbyLeader;
  private $members = array();
  private $lobbyId;
  private $created;

  function __construct($quality){
    $this->quality = $quality;
    $this->lobbyId = uniqid();
    $this->created = date("Y-m-d H:i:s");
  }

  function __get($name){
    return $this->$name;
  }

  function isComplete(){
    if ( count($this->members) == 5 && $this->lobbyLeader )
      return TRUE;
    else  
      return FALSE;
  }

  function findLeader(){
     $database = DB::getInstance();

     // dynamically build this query to maintain compatibility 
     // with when a lobby consists of less than 5 members
     // 
     // first part of the query:
     $qGetLobbyLeader = '
          SELECT user.steam_id
          FROM user
          WHERE steam_id = '.$this->members[0]; 

     // add every elses steam id to the query
     for ($i = 1 ; $i != count($this->members) ; $i++){
       $qGetLobbyLeader .= ' OR steam_id = '.$this->members[$i];
     }

     // and the rest of the query
     $qGetLobbyLeader .= '
          ORDER BY register_date ASC
          LIMIT 1
    ';

    if($result = $database->query($qGetLobbyLeader)){
    	$this->lobbyLeader = $result->fetch_assoc()['steam_id'];
    	return TRUE;
    } elseif($error = $database->error){
    	echo "couldn't find a lobby leader: $error";
    	return FALSE;
    }
    return FALSE;
  }

  function addMember($member){
    if (count($this->members) < 5 ) {
      $this->members[] = $member;
    } else {
      return FALSE;
    }

    if (count($this->members) == 5 ) {
    	$this->findLeader();
    }
    return TRUE;
  }
}
