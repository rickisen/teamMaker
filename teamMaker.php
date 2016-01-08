<?php 
// A program that continues to look at a pool of players and groups them in teams of 5 based on their attributes

require_once "Classes/DB.class.php";

// testing only
/* insertTestUsers(); */

// loop through all our specifity levels, be most specific first
for ($i = 4 ; $i != 0; $i--){
  echo "running on specificity level $i \n";
  // get this specificity level's teams
  $teams = groupPlayers($i);

  echo '\n'.date();
  print_r($teams);

  // move the players into their lobbies
  if (!empty($teams))
    movePlayers($teams);

}

// Grouping function, takes 1 parameter, the level of specificity to group players on.
// returns an multidimensional array with all ready teams
function groupPlayers($specificity){
  // Create connection to db
  $database = DB::getInstance() ;
  // array that will come to hold the grouped players
  $ret = array();

  // Array that holds all the attributes we can group on, ordered by importance
  $attributes = ['rank', 'primary_language', 'secondary_language', 'age_group' ];
  // Empty string that will hold the words to group on this run
  $activeAttributes = '';
  // Contstruct this string by looping through the attributes the same ammount of 
  // times as the level of specificity
  for ($i = 0 ; $i < $specificity ; $i++){
    $activeAttributes .= $attributes[$i] ;
    // add a ',' after the attribute 
    // unless this is the last attribute
    if ($i < $specificity - 1 ) $activeAttributes .= ',' ;

    // stuff to be added before and after the atrtributes
    // do this here so that everything works on specifity level 0 
    $prefixAttributes  = 'GROUP BY ';
    $postfixAttributes = ' HAVING size > 4 ';
    $activeAttributes  = $prefixAttributes.$activeAttributes.$postfixAttributes;
  }

  if ($specificity == 0 ) $activeAttributes .= ' LIMIT 5 ' ;

  // the generated Query for getting relevant playerID's, only get groups that contain 5 or more players, as they are 
  // the only groups that could hold interesting players this run, and order them by the the time they where added to the pool
  $qGetPlayerGroups = '
    SELECT  count(user.steam_id) AS size, 
            GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC ) AS users

    FROM   player_looking_for_lobby LEFT JOIN user 
             ON player_looking_for_lobby.steam_id = user.steam_id 
    
    '.$activeAttributes.'
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
      $users = explode(',', $group['users'] );
    
      // put the users in this player group into their teams in the return-array, 
      // and ignore the last users who don't fit into a team, (they will probably fit into a team next run)
      for ( $i = 0 ; $i != $ammountOfTeamsInGroup ; $i++ ) {
        $playerOne   = 0 + $i * 5;
        $playerTwo   = 1 + $i * 5;
        $playerThree = 2 + $i * 5;
        $playerFour  = 3 + $i * 5;
        $playerFive  = 4 + $i * 5;

        $ret[] = [ $users[$playerOne], $users[$playerTwo], $users[$playerThree], $users[$playerFour], $users[$playerFive] ];
      }
    }
    // TODO if we are on a very low specifity level, (aka we are not very picky about who we end up with) Only make a team if all players have waited a long time
  }
  return $ret ;
}

// Moving function That moves players who are in teams into corresponding lobbies. 
// takes one parameter, an multidimensional array that holds teams (that holds steamIDs) 
function movePlayers($teams){
  $database = DB::getInstance() ;

  //  loop through each team,
  foreach ($teams as $team){
    // generate a uniq_id for this lobby. 
    $lobbyId = uniqid();
    // loop through each player in this team
    foreach ($team as $player){// add this player into the lobby table
      // query to load a player into the lobby table
      $qInsertPlayerIntoLobby = '
        INSERT INTO lobby (steam_id, lobby_id)
        VALUES ( "'.$player.'", "'.$lobbyId.'" )
      ';

      if ( ! $result = $database->query($qInsertPlayerIntoLobby)){
        echo 'error occured when trying to insert player: '.$player.' into the lobby'.$lobbyId.': '.$database->error;
      }

      // remove it from players_looking_for_lobby
      // query to remove a player from the players_looking_for_lobby table
      $qDeleteUserFromLooking = '
        DELETE FROM player_looking_for_lobby WHERE steam_id = '.$player.'
      ';

      if ( ! $result = $database->query($qDeleteUserFromLooking)){
        echo 'error occured when trying to remove player: '.$player.' from players_looking_for_lobby: '.$database->error;
      }
    }
  }
}

// testing only 
function insertTestUsers(){
  $database = DB::getInstance() ;

  $qInsertTesters = '
    INSERT INTO player_looking_for_lobby (steam_id)
    VALUES 
        (76561197960325993),
        (76561197960329953),
        (76561197960423662),
        (76561197960670668),
        (76561197960815531),
        (76561197960964569),
        (76561197961049556),
        (76561197961109569),
        (76561197961289216),
        (76561197961395244),
        (76561197961825525),
        (76561197963624696),
        (76561197964408980),
        (76561197967090145),
        (76561197967774803),
        (76561197967790391),
        (76561197978857993),
        (76561197979337863),
        (76561197979573240),
        (76561197980971260),
        (76561197981707560),
        (76561197991983765),
        (76561197992700833),
        (76561197993803426),
        (76561197997562376),
        (76561198004464720),
        (76561198004727120),
        (76561198006920295),
        (76561198008480668),
        (76561198010808320),
        (76561198017223611),
        (76561198018841704),
        (76561198023969636),
        (76561198034699157),
        (76561198039027545),
        (76561198040102084),
        (76561198040893884),
        (76561198041744686),
        (76561198043190871),
        (76561198044176436),
        (76561198046242939),
        (76561198049216744),
        (76561198053049402),
        (76561198055388995),
        (76561198062157703),
        (76561198065045875),
        (76561198066757946),
        (76561198069816792),
        (76561198069989420),
        (76561198071773272),
        (76561198073443107),
        (76561198074312453),
        (76561198076648989),
        (76561198077953708),
        (76561198079853120),
        (76561198081102250),
        (76561198083003715),
        (76561198083725555),
        (76561198083924003),
        (76561198084007222),
        (76561198084153596),
        (76561198084883593),
        (76561198085445299),
        (76561198085702308),
        (76561198086935539),
        (76561198089822614),
        (76561198089919478),
        (76561198090730839),
        (76561198090833356),
        (76561198096090094),
        (76561198096213808),
        (76561198103175948),
        (76561198107532730),
        (76561198110565757),
        (76561198111584887),
        (76561198112682075),
        (76561198114518657),
        (76561198116258160),
        (76561198116529557),
        (76561198117007680),
        (76561198117505021),
        (76561198118458062),
        (76561198118998734),
        (76561198119133793),
        (76561198119380486),
        (76561198120432937),
        (76561198121413467),
        (76561198122434179),
        (76561198122587225),
        (76561198124703871),
        (76561198127078265),
        (76561198127537132),
        (76561198129326579),
        (76561198134155118),
        (76561198139978951),
        (76561198140065442),
        (76561198142529727),
        (76561198142830832),
        (76561198147424033),
        (76561198149737734),
        (76561198152888143),
        (76561198154156138),
        (76561198154163731),
        (76561198155921383),
        (76561198158226409),
        (76561198159331481),
        (76561198161109970),
        (76561198161446763),
        (76561198161608460),
        (76561198165835665),
        (76561198166994467),
        (76561198169464445),
        (76561198170008233),
        (76561198170296070),
        (76561198172316119),
        (76561198172383546),
        (76561198177806779),
        (76561198179153337),
        (76561198183902322),
        (76561198185709861),
        (76561198186097033),
        (76561198191137295),
        (76561198193558745),
        (76561198195554278),
        (76561198196893734),
        (76561198197390186),
        (76561198204137849),
        (76561198207850200),
        (76561198217983724),
        (76561198218363395),
        (76561198220220848),
        (76561198223300936),
        (76561198240647466),
        (76561198241329272),
        (76561198242788226),
        (76561198244197258),
        (76561198257486674),
        (76561198268013278),
        (76561198268190933)
  ';

  $database->query($qInsertTesters);
}
