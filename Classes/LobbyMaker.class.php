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
    case 5 :
      self::levelFive();
      break;
    case 6 :
      self::levelSix();
      break;
    case 7 :
      self::levelSeven();
      break;
    }
  }

  static function logLevel($level) {
    echo "\n".date('H:i:s')." executing quality level $level ========================================| \n";
  }

  static function movePlayers($moveIncompleteLobbies = FALSE) {
    $lobbies  = self::getLobbyHolder();

    if ( count($lobbies->lobbies) > 0){
      $numOfLobbiesCreated = 0 ;

      foreach ($lobbies->lobbies as $lobby){
        if ($lobby->isComplete() || $moveIncompleteLobbies){
	  // if the team is incomplete by this point, there is no leader.
          if ($moveIncompleteLobbies) $lobby->findLeader();
          // first move the leader, and then all his minions
          self::movePlayer($lobby->lobbyLeader, $lobby->lobbyId, $lobby->quality, $lobby->created, $lobby->steamGroup, TRUE);
          foreach ($lobby->members as $teamMember){
            if($teamMember != $lobby->lobbyLeader)
              self::movePlayer($teamMember, $lobby->lobbyId, $lobby->quality, $lobby->created, $lobby->steamGroup, FALSE);
          }
          $numOfLobbiesCreated++ ;
        }
      }

      echo "created $numOfLobbiesCreated lobbies \n";

      // once we've copied all players from our lobbyHolder, we
      // delete it and create a new lobbyHolder
      self::resetLobbyHolder();
    }
  }

  static function movePlayer($player, $lobbyId, $quality, $created, $steamGroup, $isLeader = FALSE) {
    $database = DB::getInstance() ;

    // convert from bool to int
    if ($isLeader)
      $isLeader = 1;
    else
      $isLeader = 0;

    // move player into the lobby table
    $qMoveMember = '
      INSERT INTO lobby (lobby_id, steam_id, quality, created, is_leader, steam_group)
      VALUES ("'.$lobbyId.'","'.$player.'","'.$quality.'", "'.$created.'", '.$isLeader.', "'.$steamGroup.'");
    ';

    $database->query($qMoveMember);
    if ($error = $database->error){
      echo "Something went wrong when trying to move player $player into lobby $lobbyId : ".$error;
      return FALSE;
    }

    //remove the user from the pool of players that are looking for teams, asd
    $qDeletePlayer = '
      DELETE FROM player_looking_for_lobby
      WHERE steam_id = '.$player.'
    ';

    $database->query($qDeletePlayer);
    if ($error = $database->error){
      echo "Something went wrong when trying to remove player $player from PLFL: ".$error;
      return FALSE;
    }
  }

  // method that teams all the users that have waited a long time
  static function levelZero() {
    $database = DB::getInstance() ;
    $lobbies  = self::getLobbyHolder();

    // query to get all losers
    $qLevelZero = '
        SELECT  user.steam_id
        FROM    player_looking_for_lobby LEFT JOIN user
        ON      player_looking_for_lobby.steam_id = user.steam_id
        WHERE   started_looking < (NOW() - INTERVAL 5 MINUTE)
        ORDER BY rank, age_group, primary_language
    ';

    // query the db and put all the losers currently in there into new lobbies
    if( $result = $database->query($qLevelZero) ) {
      if ($result->num_rows > 1) {
        self::logLevel(0);
        while( $row = $result->fetch_assoc()){
          // add the current user into the newest lobby
          $lobbies->addMember($row['steam_id'], 1);
        }
      }
    } elseif ($error = $database->error){
      echo "something wrong with levelZero: ".$error;
    }

    // this is usually run by HandleGroupResults, but this isn't a group result.
    // And also enable the "move incomplete lobbies" option of the moveplayers function.
    self::movePlayers(TRUE);
  }

  static function levelOne(){
    $database = DB::getInstance() ;
    $lobbies  = self::getLobbyHolder();

    // query to get all semi losers
    $qLevelOne = '
        SELECT  user.steam_id
        FROM    player_looking_for_lobby LEFT JOIN user
        ON player_looking_for_lobby.steam_id = user.steam_id
        WHERE   started_looking < (NOW() - INTERVAL 4 MINUTE)
        ORDER BY rank, primary_language, age_group
    ';

    // query the db and put all the losers currently in there into new lobbies
    if( $result = $database->query($qLevelOne) ) {
      if ($result->num_rows > 3) {
        self::logLevel(1);
        while( $row = $result->fetch_assoc()){
          // add the current user into the newest lobby
          $lobbies->addMember($row['steam_id'], 1);
        }
      }
    } elseif ($error = $database->error){
      echo "something wrong with levelOne: ".$error;
    }

    // this is usually run by HandleGroupResults, but this isn't a group result.
    // And also enable the "move incomplete lobbies" option of the moveplayers function.
    self::movePlayers(TRUE);
  }

  static function levelTwo(){
    $database = DB::getInstance() ;
    $lobbies  = self::getLobbyHolder();

    $qLevelTwo = '
        SELECT  count(user.steam_id) AS size,
                GROUP_CONCAT(user.steam_id ORDER BY primary_language, started_looking ASC) AS users,
                floor(rank / 3) as thridrank,
                floor(age_group / 3) as thridgroup


        FROM   player_looking_for_lobby LEFT JOIN user
           ON  player_looking_for_lobby.steam_id = user.steam_id

        WHERE    started_looking < (NOW() - INTERVAL 3 MINUTE)
        GROUP BY thridrank, thridgroup
        HAVING   size >= 5
    ';

    // query the db, and if we got some results, handle them properly
    if( $result = $database->query($qLevelTwo) ){
        if ( $numberOfRows = $result->num_rows > 0 ){
	  self::HandleGroupResults($result, 2);
	}
    }

    if ($error = $database->error){
      echo "something wrong with levelTwo: ".$error;
    }
  }

  static function levelThree(){
    $database = DB::getInstance() ;
    $lobbies  = self::getLobbyHolder();

    $qLevelThree = '
        SELECT  count(user.steam_id) AS size,
                GROUP_CONCAT(user.steam_id ORDER BY primary_language, started_looking ASC) AS users,
                floor(rank / 2) as halfrank,
                floor(age_group / 2) as halfgroup


        FROM   player_looking_for_lobby LEFT JOIN user
           ON  player_looking_for_lobby.steam_id = user.steam_id

        WHERE    started_looking < (NOW() - INTERVAL 2 MINUTE)
        GROUP BY halfrank, halfgroup
        HAVING   size >= 5
    ';

    // query the db, and if we got some results, handle them properly
    if( $result = $database->query($qLevelThree) ){
        if ( $numberOfRows = $result->num_rows > 0 ){
	  self::HandleGroupResults($result, 3);
	}
    }

    if ($error = $database->error){
      echo "something wrong with levelThree: ".$error;
    }
  }

  static function levelFour(){
    $lobbies  = self::getLobbyHolder();
    $database = DB::getInstance() ;

    // Queries that gets all languages that have speakers in the db
    $qGetAllPriLangs = '
          SELECT DISTINCT primary_language
          FROM user
          WHERE primary_language IS NOT NULL
            AND primary_language != ""';

    $qGetAllSecLangs = '
          SELECT DISTINCT secondary_language
          FROM user
          WHERE secondary_language IS NOT NULL
            AND secondary_language != ""';

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
    // of every spoken language in this array
    $langs = array_unique($langs);

    // for every spoken language we make a new group query
    foreach ($langs as $lang){
      $qLevelFour = '
          SELECT  count(user.steam_id) AS size,
                  GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC) AS users,
                  floor(rank / 3) as thirdrank,
                  floor(age_group / 3) as thirdgroup

          FROM   player_looking_for_lobby LEFT JOIN user
                   ON player_looking_for_lobby.steam_id = user.steam_id

          WHERE  primary_language   = "'.$lang.'"
             OR  secondary_language = "'.$lang.'"
            AND  started_looking < (NOW() - INTERVAL 1 MINUTE)

          GROUP BY thirdrank, thirdgroup
          HAVING size >= 5
      ';

      // query the db, and if we got some results, handle them properly
      if( $result = $database->query($qLevelFour)){
        if ( $numberOfRows = $result->num_rows > 0 ){
          echo "found ".$numberOfRows." $lang speaking player groups \n";
          self::HandleGroupResults($result, 4);
        }
      }

      if ($error = $database->error){
        echo "something wrong with levelFour on language $lang: ".$error;
      }
    }
  }

  static function levelFive(){
    $lobbies  = self::getLobbyHolder();
    $database = DB::getInstance() ;

    // Queries that gets all languages that have speakers in the db
    $qGetAllPriLangs = '
          SELECT DISTINCT primary_language
          FROM user
          WHERE primary_language IS NOT NULL
            AND primary_language != ""';

    $qGetAllSecLangs = '
          SELECT DISTINCT secondary_language
          FROM user
          WHERE secondary_language IS NOT NULL
            AND secondary_language != ""';

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
    // of every spoken language in this array
    $langs = array_unique($langs);

    // for every spoken language we make a new group query
    foreach ($langs as $lang){
      $qLevelFive = '
          SELECT  count(user.steam_id) AS size,
                  GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC) AS users,
                  floor(rank / 2) as halfrank,
                  floor(age_group / 2) as halfgroup

          FROM   player_looking_for_lobby LEFT JOIN user
                   ON player_looking_for_lobby.steam_id = user.steam_id

          WHERE  primary_language   = "'.$lang.'"
             OR  secondary_language = "'.$lang.'"
             AND started_looking < (NOW() - INTERVAL 40 SECOND)

          GROUP BY halfrank, halfgroup
          HAVING size >= 5
      ';

      // query the db, and if we got some results, handle them properly
      if( $result = $database->query($qLevelFive)){
        if ( $numberOfRows = $result->num_rows > 0 ){
          echo "found ".$numberOfRows." $lang speaking player groups \n";
          self::HandleGroupResults($result, 5);
        }
      }

      if ($error = $database->error){
        echo "something wrong with levelFive on language $lang: ".$error;
      }
    }
  }

  static function levelSix(){
    $lobbies  = self::getLobbyHolder();
    $database = DB::getInstance() ;

    // Queries that gets all languages that have speakers in the db
    $qGetAllPriLangs = '
          SELECT DISTINCT primary_language
          FROM user
          WHERE primary_language IS NOT NULL
            AND primary_language != ""';

    $qGetAllSecLangs = '
          SELECT DISTINCT secondary_language
          FROM user
          WHERE secondary_language IS NOT NULL
            AND secondary_language != ""';

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
    // of every spoken language in this array
    $langs = array_unique($langs);

    // for every spoken language we make a new group query
    foreach ($langs as $lang){
      $qLevelSix = '
          SELECT  count(user.steam_id) AS size,
                  GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC) AS users,
                  floor(rank / 2) as halfrank,
                  floor(age_group / 3) as thirdgroup

          FROM   player_looking_for_lobby LEFT JOIN user
                   ON player_looking_for_lobby.steam_id = user.steam_id

          WHERE  primary_language   = "'.$lang.'"
             OR  secondary_language = "'.$lang.'"
             AND started_looking < (NOW() - INTERVAL 20 SECOND)

          GROUP BY halfrank, thirdgroup
          HAVING size >= 5
      ';

      // query the db, and if we got some results, handle them properly
      if( $result = $database->query($qLevelSix)){
        if ( $numberOfRows = $result->num_rows > 0 ){
          echo "found ".$numberOfRows." $lang speaking player groups \n";
          self::HandleGroupResults($result, 6);
        }
      }

      if ($error = $database->error){
        echo "something wrong with levelSix on language $lang: ".$error;
      }
    }
  }


  static function levelSeven(){
    $lobbies  = self::getLobbyHolder();
    $database = DB::getInstance() ;

    // Queries that gets all languages that have speakers in the db
    $qGetAllPriLangs = '
          SELECT DISTINCT primary_language
          FROM user
          WHERE primary_language IS NOT NULL
            AND primary_language != ""';

    $qGetAllSecLangs = '
          SELECT DISTINCT secondary_language
          FROM user
          WHERE secondary_language IS NOT NULL
            AND secondary_language != ""';

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
    // of every spoken language in this array
    $langs = array_unique($langs);

    // for every spoken language we make a new group query
    foreach ($langs as $lang){
      $qLevelSeven = '
          SELECT  count(user.steam_id) AS size,
                  GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC) AS users

          FROM   player_looking_for_lobby LEFT JOIN user
                   ON player_looking_for_lobby.steam_id = user.steam_id

          WHERE  primary_language   = "'.$lang.'"
             OR  secondary_language = "'.$lang.'"
             AND started_looking < (NOW() - INTERVAL 1 SECOND)

          GROUP BY rank, age_group
          HAVING size >= 5
      ';

      // query the db, and if we got some results, handle them properly
      if( $result = $database->query($qLevelSeven)){
        if ( $numberOfRows = $result->num_rows > 0 ){
          echo "found ".$numberOfRows." $lang speaking player groups \n";
          self::HandleGroupResults($result, 7);
        }
      }

      if ($error = $database->error){
        echo "something wrong with levelSeven on language $lang: ".$error;
      }
    }
  }

  static function HandleGroupResults($GResult, $qualityLevel){
    $lobbies  = self::getLobbyHolder();
    self::logLevel($qualityLevel);

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
        for ($j = 0 ; $j != 5 ; $j++) // add exactly 5 users into a team
          $lobbies->lastLobby()->addMember($users[$userCounter++]);
      }
    }
    // move the players now so that we don't try to lobby people twice on level 3
    self::movePlayers();
  }

  static function generateQuery($optionsArr){
    // function that generates a dbquery depending on which options are supplied.
    // It can do two types of select queries, one based on grouping users and one 
    // simpler based on ordering users.
    
    $mode = 'SIMPLE';  // should be overwritten automatically depending on the options supplied

    // default values for the parts  ===========================================================|
    $startedLookingPart  = ' WHERE  started_looking < (NOW() - INTERVAL 5 MINUTE ) ';
    $languagePart        = ' ';
    $groupByPart         = ' ';
    $sizePart            = ' HAVING size >= 5 ';
    $limitPart           = ' '; 
    $orderByPart         = ' ORDER BY rank, age_group, primary_language ';
    $BaseSimpleQuery = '
      SELECT  user.steam_id
      FROM    player_looking_for_lobby LEFT JOIN user
        ON    player_looking_for_lobby.steam_id = user.steam_id ';
    $BaseGroupQuery = '
      SELECT  count(user.steam_id) AS size, 
              GROUP_CONCAT(user.steam_id ORDER BY started_looking ASC) AS users

      FROM   player_looking_for_lobby LEFT JOIN user 
        ON   player_looking_for_lobby.steam_id = user.steam_id ';

    // FORMATS, if mentioned in the options arr they will overwrite defaults ====================|
    $fGroupByPart       = ' GROUP  BY %s '; 
    $fOrderByPart       = ' ORDER  BY %s '; 
    $fSizePart           = ' HAVING size >= %d '; 
    $fLimitPart          = ' LIMIT %d '; 
    $fStartedLookingPart = ' WHERE  started_looking < (NOW() - INTERVAL %s ) ';
    $fLanguagePart = '    
        AND    primary_language   = %s
         OR    secondary_language = %s ';

    // Parse the input options array ========================================|
    foreach($optionsArr as $key => $option){
      switch ($key){
      case 'members':
        $sizePart           = sprintf($fSizePart, $option);
        $limitPart          = sprintf($fLimitPart, $option);
        break;
      case 'language':
        $languagePart       = sprintf($fLanguagePart, $option, $option);
        break;
      case 'started looking':
        $startedLookingPart = sprintf($fStartedLookingPart, $option);
        break;
      case 'order by':
        $mode = 'SIMPLE';
        $orderByPart  = sprintf($fOrderByPart, $option);
        break;
      case 'group by':
        $mode = 'GROUP';
        $groupByPart  = sprintf($fGroupByPart, $option);
        break;
      }
    }

    // construct the Query depending on mode ========================================|
    if ($mode == 'SIMPLE'){
      $query = $BaseSimpleQuery."\n".$startedLookingPart."\n".$languagePart."\n".$orderByPart."\n".$limitPart."\n";
    }elseif ($mode == 'GROUP'){
      $query = $BaseGroupQuery."\n".$startedLookingPart."\n".$languagePart."\n".$groupByPart."\n".$sizePart."\n";
    }

    return $query;
  }

  static function fetchSpokenLanguages(){
    $database = DB::getInstance() ;

    // Queries that gets all languages that have speakers in the db
    $qGetAllPriLangs = ' 
          SELECT DISTINCT primary_language 
          FROM user 
          WHERE primary_language IS NOT NULL 
            AND primary_language != ""';

    $qGetAllSecLangs = ' 
          SELECT DISTINCT secondary_language 
          FROM user 
          WHERE secondary_language IS NOT NULL 
            AND secondary_language != ""';

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
    // of every spoken language in this array
    $langs = array_unique($langs);

    return $langs;
  }

  static function runDynlevel(){
    $database = DB::getInstance();
    $qualities = [ 
      [
        [ 'started looking' => '5 MINUTE', 'members' => '2', 'order by' => 'rank, region, primary_language']
      ], 

      [
        [ 'started looking' => '4 MINUTE', 'members' => '4', 'order by' => 'region, rank, primary_language' ]
      ], 

      [
        [ 'started looking' => '3 MINUTE', 'members' => '5', 'group by' => 'region, floor(rank/3) '],
        [ 'started looking' => '3 MINUTE', 'members' => '5', 'group by' => 'region, ceil (rank/3) ']
      ], 

      [
        [ 'started looking' => '2 MINUTE', 'members' => '5', 'group by' => 'region, floor(rank/2) '],
        [ 'started looking' => '2 MINUTE', 'members' => '5', 'group by' => 'region, ceil (rank/2) '],
        [ 'started looking' => '2 MINUTE', 'members' => '5', 'group by' => 'region, rank, floor(age_group/4) ']
      ], 

      [
        [ 'started looking' => '1 MINUTE', 'members' => '5', 'group by' => 'region, rank, floor(age_group/3) '],
        [ 'started looking' => '1 MINUTE', 'members' => '5', 'group by' => 'region, rank, floor(age_group/2), floor(hours_played/100/2) '],
        [ 'started looking' => '1 MINUTE', 'members' => '5', 'group by' => 'region, rank, age_group, floor(hours_played/100/3) ']
      ], 

      [
        [ 'started looking' =>'1  SECOND', 'members' => '5', 'group by' => 'region, rank, age_group, floor(hours_played/100/2) ']
      ]
    ] ;

    $spokenLanguages = self::fetchSpokenLanguages(); 

    // go through the qualities backwards since we are more specific 
    // at the higher levels, and we want thoose to have a chance before the 
    // levels that catch everybody.
    for ($qLevel = count($qualities) - 1; $qLevel >= 2; $qLevel--){
      // add a language to the queries above, and then run them
      foreach ($spokenLanguages as $lang){
        foreach ($qualities[$qLevel] as $subLevel){

          // add the language part to the options array
          $subLevel['language'] = $lang; 

          //constructs the query
          $query = self::generateQuery($subLevel);

          if( $result = $database->query($query)){
            if ( $numberOfRows = $result->num_rows > 0 ){
              echo "found $numberOfRows $lang speaking player groups \n";
              self::HandleGroupResults($result, $qLevel);
            }
          }

          if ($error = $database->error){
            echo "something wrong on level $qLevel on language $lang: $error ";
          }
        }
      }
    }
  }
}

