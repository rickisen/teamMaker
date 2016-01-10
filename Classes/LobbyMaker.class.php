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
		    WHERE   started_looking < (UNIX_TIMESTAMP() - 250)
		    ORDER BY rank, country, age_group
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

		$qLevelOne = '
		    SELECT  count(user.steam_id) AS size, 
			    GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC, age_group, primary_language, secondary_language) AS users

		    FROM   player_looking_for_lobby LEFT JOIN user 
			     ON player_looking_for_lobby.steam_id = user.steam_id 

		    WHERE   started_looking < (UNIX_TIMESTAMP() - 200)
		    GROUP BY rank
		    HAVING size >= 5
		';
		
                // query the db, and if we got some results, handle the properly
                if( $result = $database->query($qLevelOne)){
                  self::HandleGroupResults($result, 1);
                } 
	}

	static function levelTwo(){
		$lobbies  = self::getLobbyHolder();

		$qLevelTwo = '
		    SELECT  count(user.steam_id) AS size, 
			    GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC, primary_language, secondary_language) AS users

		    FROM   player_looking_for_lobby LEFT JOIN user 
			     ON player_looking_for_lobby.steam_id = user.steam_id 

		    WHERE   started_looking < (UNIX_TIMESTAMP() - 150)
		    GROUP BY rank, age_group
		    HAVING size >= 5
		';
		
                // query the db, and if we got some results, handle them properly
                if( $result = $database->query($qLevelTwo)){
                  self::HandleGroupResults($result, 2);
                } 
	}

        static function HandleGroupResults($GResult, $qualityLevel){
		$lobbies  = self::getLobbyHolder();
	    	while ($group = $GResult->fetch_assoc()) {
		        $users = explode(',', $group['users'] );
                        $ammountOfTeamsInGroup = ($group['size'] - ( $group['size'] % 5 )) / 5  ;

                        // loop like this so that we'll make as many teams as possible 
                        // out of this group of users, and leave the people who didn't 
                        // fit into a team in the pool of users who are looking for teams
                        // They'll surely find mates on the next run
                        $userCounter = 0 ; 
                        for ($i = 0; $i != $ammountOfTeamsInGroup ; $i++){
                          $lobbies->addLobby($qualityLevel); // the number indicates this lobbies quality
                          for ($i = 0 ; $i != 5 ; $i++) // add exactly 5 users into a team
                            $lobbies->lastLobby()->addMember($users[$userCounter++]);
                        }
		}
        }
}
