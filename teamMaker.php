<?php 
// A program that continues to look at a pool of players and groups them in teams of 5 based on their attributes

require_once "Classes/DB.class.php";


// MAIN LOOP 
	// loop through all our specifity levels 
		// get this specificity level's teams
		// move players in a team from the pool into the "teams" table
	// Assign steam-groups to each team

// Grouping function, takes 1 parameter, the level of specificity to group players on.
// returns an multidimensional array with all ready teams
function groupPlayers($specificity){
  // Create connection to db
  $database = DB::getInstance() ;
  // array that will come to hold the grouped players
  $ret = array();

  // Array that holds all the attributes we can group on, ordered by importance
  $attributes = ['rank', 'hours_played', 'kills', 'age' ];
  // Empty string that will hold the words to group on this run
  $activeAttributes = '';
  // Contstruct this string by looping through the attributes the same ammount of 
  // times as the level of specificity
  for ($i = 0 ; $i < $specificity ; $i++){
    $activeAttributes .= $attributes[$i] ;
    // add a ',' after the attribute 
    // unless this is the last attribute
    if ($i < $specificity - 1 ) $activeAttributes .= ',' ;
  }

  // the generated Query for getting relevant playerID's, only get groups that contain 5 or more players, as they are 
  // the only groups that could hold interesting players this run, and order them by the the time they where added to the pool
  $qGetPlayerGroups = '
    SELECT count(user.steam_id) AS size, 
           GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC SEPERATOR ",") AS users

    FROM   players_looking_for_lobby LEFT JOIN user 
             ON players_looking_for_lobby.steam_id = user.steam_id;
    
    GROUP BY '.$activeAttributes.'
    HAVING size > 4
  ';

  // Send the query to the db
  if ( $result = $database->query($qGetPlayerGroups) ){
    // every row should hold one group of similair players, and we could 
    // potentially make multiple teams out of theese groups 
    while ($group = $result->fetch_assoc()) {
      // Moudulas should tell us how many teams we can make out of this group, 
      $ammountOfTeamsInGroup = ($group['size'] - ( $group['size'] % 5 )) / 5  ;

      // the query should reutrn a users collumn, that consits of the 
      // users in that group's steamIDs sepperated by a ','
      // Explode them into an array
      $users = explode($group['users'], ',');

      // To be used as a index for accesseing values in the exploded array
      $player = 0 ;
    
      // put the users in this player group into their teams in the return-array, 
      // and ignore the last users who don't fit in a team
      for ( $i = 0 ; $i != $ammountOfTeamsInGroup ; $i++ ) {
        $ret[] = [$users[$player++], $users[$player++], $users[$player++], $users[$player++], $users[$player++] ];
      }
    }
    // TODO if we are on a very low specifity level, (aka we are not very picky about who we end up with) Only make a team if all players have waited a long time
  }
  return $ret ;
}

// Moving function That moves players who are in teams into corresponding lobbies. 
// takes one parameter, an multidimensional array that holds teams (that holds steamIDs) 
	//  loop through each team,
		// generate a uniq_id for this lobby. 
		// loop through each player 
			// add this player into the player-belongs-in-lobby table
			// remove it from players_looking_for_lobby
