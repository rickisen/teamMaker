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
		if ( count($this->members) == 4 && isset($lobbyLeader) )
			return TRUE;
		else	
			return FALSE;
	}

	function addMember($member, $qualitylevel){
		if (!isset($this->lobbyLeader)){
			$this->lobbyLeader = $member;
		} elseif (count($this->members) < 3 ) {
			$this->members [] = $member;
		} else {
			return FALSE;
		}

		// update the quality of this lobby, if this 
		// user came from another level, might be fucked up
		if ($this->quality > $qualitylevel){
			$this->quality -= $qualitylevel / 5 ;
		} elseif ($this-quality < $qualitylevel){
			$this->quality += $qualitylevel / 5 ;
		}

		return TRUE;
	}
}
