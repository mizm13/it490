#!/bin/bash
jq '.response[].id' /home/mizm13/it490/DMZ/testAPI/nba_games.json > /home/mizm13/it490/DMZ/testAPI/game_ids.txt
