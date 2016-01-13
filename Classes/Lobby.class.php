<?php

class Lobby {

  private $quality;
  private $lobbyLeader;
  private $members = array();
  private $lobbyId;

  function __construct($quality){
    $this->quality = $quality;
    $this->lobbyId = uniqid();
  }

  function __get($name){
    return $this->$name;
  }

  function isComplete(){
    if ( count($this->members) == 4 && $this->lobbyLeader )
      return TRUE;
    else  
      return FALSE;
  }

  function addMember($member){
    if (!isset($this->lobbyLeader)){
      $this->lobbyLeader = $member;
    } elseif (count($this->members) < 4 ) {
      $this->members[] = $member;
    } else {
      return FALSE;
    }

    return TRUE;
  }
}
