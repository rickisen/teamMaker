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
		} else {
			return self::$lobbyHolder;
		}
	}

	static function resetLobbyHolder(){
		if(!self::$lobbyHolder){
			return FALSE;
		} else {
			self::$lobbyHolder = new LobbyHolder();
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

	static function movePlayers() {
		$lobbies  = self::getLobbyHolder();
		foreach (self::getLobbyHolder()->lobbies as $lobby){
			if ($lobby->isComplete()){
				// first move the leader, and then all his minions
				self::movePlayer($lobby->lobbyLeader, $lobby->lobby_id, $lobby->quality, TRUE);
				foreach ($lobby->members as $teamMember){
					self::movePlayer($teamMember, $lobby->lobby_id, $lobby->quality);
				}
			}
		}

		// once we've copied all players from our lobbyHolder, we 
		// delete it and create a new lobbyHolder
		self::resetLobbyHolder();
	}

	static function movePlayer($player, $lobbyId, $quality, $isLeader = FALSE) {
		$database = DB::getInstance() ;

                // convert from bool to int
                if ($isLeader)
                  $isLeader = 1;
                else
                  $isLeader = 0;
                
                // move player into the lobby table
		$qMoveMember = '
			INSERT INTO lobby (lobby_id, steam_id, quality, isLeader)
			VALUES ("'.$lobbyId.'","'.$player.'","'.$quality.'", '.$isLeader.');
		';
                $database->query($qMoveMember);
		if ($database->error){
			echo "Something went wrong when trying to move player $player into lobby $lobbyId : ".$database-error;
			break;
		}

		//remove the user from the pool of players that are looking for teams
		$qDeletePlayer = '
			DELETE FROM player_looking_for_lobby 
			WHERE steam_id = '.$player.'
		';
		$database->query($qDeletePlayer);
		if ($database->error){
			echo "Something went wrong when trying to remove player $player from PLFL: ".$database-error;
			break;
		}
	}

	// method that teams all the users that have waited a long time
	static function levelZero() {
		$database = DB::getInstance() ;
		$lobbies  = self::getLobbyHolder();

		// query to get all losers
		$qLevelZero = '
		    SELECT  steam_id 
		    FROM    player_looking_for_lobby LEFT JOIN user
				ON lobby.steam_id = user.steam_id
		    WHERE   started_looking < (UNIX_TIMESTAMP() - 250)
		    ORDER BY rank, age_group, country
		';

		// query the db and put all the losers currently in there into new lobbies
                if( $result = $database->query($qLevelZero)) {
                  while( $row = $result->fetch_assoc()){
                    // add the current user into the newest lobby
                    $lobbies->addMember($row['steam_id'], 0);
                  }
                } 

                if ($database->error){
                  echo "something wrong with levelZero: ".$database-error;
                }

                self::movePlayers(); // is usually run by HandleGroupResults
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

                if ($database->error){
                  echo "something wrong with levelOne: ".$database-error;
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

                if ($database->error){
                  echo "something wrong with levelTwo: ".$database-error;
                }
	}

	static function levelThree(){
		$lobbies  = self::getLobbyHolder();

                // Queries that gets all languages that have speakers in the db
                $qGetAllPriLangs = ' SELECT DISTINCT primary_language from user ';
                $qGetAllSecLangs = ' SELECT DISTINCT secondary_language from user ';

                // this will come to hold all the spoken languages, both primary and secondary
                $langs = array(); 

                // add all primary languages into langs
                $priLangResult = $database->query($qGetAllPriLangs);
                while ($row = $priLangResult->fetch_assoc())
                  $langs[] = $row['primary_language'];

                // and secondaries
                $secLangResult = $database->query($qGetAllSecLangs);
                while ($row = $secLangResult->fetch_assoc())
                  $langs[] = $row['secondary_language'];

                // this makes sure that there is only one copy 
                // of each language in this array
                array_unique($langs);

                // for every spoken language we make a new group query
                foreach ($langs as $lang){
                      $qLevelThree = '
                          SELECT  count(user.steam_id) AS size, 
                                  GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC) AS users

                          FROM   player_looking_for_lobby LEFT JOIN user 
                                   ON player_looking_for_lobby.steam_id = user.steam_id 

                          WHERE  primary_language   = "'.$lang.'"
                             OR  secondary_language = "'.$lang.'"
                             AND started_looking < (UNIX_TIMESTAMP() - 100)

                          GROUP BY rank, age_group
                          HAVING size >= 5
                      ';
                      
                      // query the db, and if we got some results, handle them properly
                      if( $result = $database->query($qLevelThree)){
                        self::HandleGroupResults($result, 3);
                      } 

                      if ($database->error){
                        echo "something wrong with levelThree on language $lang: ".$database-error;
                      }
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
                          $lobbies->addLobby($qualityLevel); 
                          for ($i = 0 ; $i != 5 ; $i++) // add exactly 5 users into a team
                            $lobbies->lastLobby()->addMember($users[$userCounter++]);
                        }
                        
		}
                // move the players now so that we don't try to lobbie people twice on level 3
                self::movePlayers();
        }
}
