<?php
require_once 'Classes/LobbyMaker.class.php';

// loop through all our specifity levels, be most specific first
for ($i = 3 ; $i != 0 ; $i--){
	LobbyMaker::runLevel($i);

        echo '\n'.date('H-i-s');
        echo "running on quality level $i";
		
}
