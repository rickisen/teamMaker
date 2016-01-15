#!/bin/bash
cd /home/teammaker/teamMaker/
time=$(date +%H:%m:%S)
echo 
echo "runing every 5 seconds from now, $time "

# running every 5 seconds, pause at the end of a minute so that we don run over the minute
while [ $(date +%S) -le 50 ]
do 
	php lobbyMaker.php 
	sleep 5 
done
