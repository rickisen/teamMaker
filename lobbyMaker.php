<?php
require_once 'Classes/LobbyMaker.class.php';

// loop through all our specifity levels, be most specific first
for ($i = 5 ; $i >= 0 ; $i--){
  LobbyMaker::runLevel($i);
}
