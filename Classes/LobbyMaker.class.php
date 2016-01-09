<?php 

require_once 'DB.class.php';
require_once 'LobbyHolder.class.php';

class LobbyMaker {
	private static $lobbyHolder ;

	private function __construct(){}
	private function __clone(){}

	static function getLobbyHolder(){
		if(!self::$lobbyHolder){
			self::$lobbyHolder = new LobbyHolder();
			return self::$lobbyHolder;
		}else{
			return self::$lobbyHolder;
		}
	}

	static function runLevel($level){
		switch ( $level ) {
		case 0 :
			self::levelZero();
			break;
		case 1 :
			self::levelOne();
			break;
		case 2 :
			self::levelTwo();
			break;
		case 3 :
			self::levelThree();
			break;
		case 4 :
			self::levelFour();
			break;
		}
	}

	static function levelZero() {
		// method that teams all the users that have waited more than 5 minutes without beeing grouped
		$database = DB::getInstance() ;
		$lobbies  = self::getLobbyHolder();

		// query to get all losers
		$qLevelZero = '
		    SELECT  steam_id 
		    FROM    player_looking_for_lobby LEFT JOIN user
				ON lobby.steam_id = user.steam_id
		    WHERE   started_looking < (UNIX_TIMESTAMP() - 300)
		    ORDER BY rank, country
		';

		// query the db and put all the losers currently in there into new lobbies
		$result = $database->query($qLevelZero);
		while( $row = $result->fetch_assoc()){
			// add the current user into the newest lobby
			$lobbies->addMember($row['steam_id'], 0);
		}
	}

	static function levelOne(){
		$database = DB::getInstance() ;
		$lobbies  = self::getLobbyHolder();

		$qLevelOne = '
		    SELECT  count(user.steam_id) AS size, 
			    GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC ) AS users

		    FROM   player_looking_for_lobby LEFT JOIN user 
			     ON player_looking_for_lobby.steam_id = user.steam_id 

		    GROUP BY rank
		    HAVING size >= 5
		';
		
		$result = $database->query($qLevelOne);
	    	while ($group = $result->fetch_assoc()) {

		        $users = explode(',', $group['users'] );

			foreach ($users as $user){
				$lobbies->addMember($user, 1);
			}
		}
	}

	static function levelTwo(){
		$database = DB::getInstance() ;
		$lobbies  = self::getLobbyHolder();

		$qLevelTwo = '
		    SELECT  count(user.steam_id) AS size, 
			    GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC ) AS users

		    FROM   player_looking_for_lobby LEFT JOIN user 
			     ON player_looking_for_lobby.steam_id = user.steam_id 

		    GROUP BY rank, age_group
		    HAVING size >= 5
		';
		
		$result = $database->query($qLevelTwo);
	    	while ($group = $result->fetch_assoc()) {

		        $users = explode(',', $group['users'] );

			foreach ($users as $user){
				$lobbies->addMember($user, 2);
			}
		}
	}
}
