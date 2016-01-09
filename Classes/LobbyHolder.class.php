<?php

require_once 'Lobby.class.php';

class LobbyHolder {
	private $lobbies = array();

	function __get($name){
		return $this->$name;
	}

	function lastLobby(){
		if (empty($this->lobbies)){
			$this->lobbies[] = new Lobby(0);
			$lastLobby = $this->lobbies[0];
		} else {
			$lastLobby = $this->lobbies[count($this->lobbies) -1];
		}

		return $lastLobby;
	}

	function addMember($member, $qualitylevel){
		// add new lobby if needed
		if ($this->lastLobby()->isComplete())	{
			$this->lobbies[] = new Lobby($qualitylevel);
		}
		$this->lastLobby()->addMember($member, $qualitylevel);
	}
}

