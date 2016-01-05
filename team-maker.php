// A program that continues to look at a pool of players and groups them in teams of 5 based on their attributes


// Create connection to db
// MAIN LOOP 
	// loop through all our specifity levels 
		// get this specificity level's teams
		// move players in a team from the pool into the "teams" table
	// Assign steam-groups to each team

// Grouping function, takes 1 parameter, the level of specificity to group players on.
	// Array that holds all the attributes we can group on, ordered by the importance
	// Empty string that will hold the words to group on this run
	// Contstruct this groupByString by looping through the attributes the same ammount of times as the level of specificity
	//
	// Query for getting relevant playerID's, only get groups that contain 5 or more players, as they are 
	// the only groups that could hold interesting players this run, and order them by the the time they where added to the pool
	//
	// Send the query to the db
	//
	// loop through the results, "( count - count % 5 ) / 5 =  y ", y should tell us how many teams we can make out of this group, 
	// so make y teams out of the first players (the last ones haven't waited long enough).
                // explode the players collum, and use  array copy to make the teams
	//
	// Also, if we are on a very low specifity level, (aka we are not very picky about who we end up with) Only make a team if all players have waited a long time
	//
// returns an multidimensional array with all ready teams

// Moving function That moves players who are in teams into corresponding lobbies. 
// takes one parameter, an multidimensional array that holds teams (that holds steamIDs) 
	//  loop through each team,
		// generate a uniq_id for this lobby. 
		// loop through each player 
			// add this player into the player-belongs-in-lobby table
			// remove it from players_looking_for_lobby
