<?php

//Setup Database Connections
$sqlconn = @mysql_connect(
  'localhost', $GLOBALS['_DEFAULT_DATABASE']['username'],
  $GLOBALS['_DEFAULT_DATABASE']['password']
);
@mysql_select_db(
  $GLOBALS['_DEFAULT_DATABASE']['database'],
  $sqlconn
) or die ("Unable to connect to Battleship DB");
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


//Select all games in progress and loop through them:
$games = mysql_query("SELECT * FROM bs2_game WHERE state = 'playing' AND paused = '0' ORDER BY game_id ASC", $sqlconn);
while ($games_row = mysql_fetch_array($games)) {
  
	$game_id = $games_row['game_id'];
	$timer = $games_row['timer'];
	
	// get amount of missed turns from DB:
	$white_miss = $games_row['white_miss'];
	$black_miss = $games_row['black_miss'];
	
	//this is to only get the last entry and date for this game
	$game_board = mysql_query("SELECT * FROM bs2_game_board WHERE game_id = '$game_id' ORDER BY move_date DESC LIMIT 1", $sqlconn);
	$board_data = mysql_fetch_array($game_board);
	
	
  $move_date = $board_data['move_date'];
  $white_board = $board_data['white_board'];
	
	
	//LOGIC STARTS HERE:
	///////////////////////////////////////////////////////
  $current_timestamp = mysql_fetch_assoc(mysql_query("SELECT NOW()"))['NOW()'];
  
  // Get both times as integers.
  $expiry = strtotime($move_date) + $timer;
  $now = strtotime($current_timestamp);
	
	//set both moves to false;
	$white_move = FALSE;
	$black_move = FALSE;
	
	//check whose move its supposed to be:
	if ($white_board == NULL) { $black_move = TRUE; } else { $white_move = TRUE; }
	
	//multiply the timer with the amount of missed turns to get when the next expiry time is (add one because you cannot multiply with 0)
	if ($white_move) { $timer = $timer * ($white_miss + 1); }
  if ($black_move) { $timer = $timer * ($black_miss + 1); }
	
	// Check if move time expired.
	if ($now > $expiry) {
		if ($white_move) { $white_miss++; }
		if ($black_move) { $black_miss++; }
		
		if (($white_miss > 3) or ($black_miss > 3)) {
      // End the game by changing the state and registering the winner.
      $winner_id = $white_miss > 3 ? $games_row['black_id'] : $games_row['white_id'];
      mysql_query("UPDATE `bs2_game` SET state = 'Finished' WHERE `game_id` = '$game_id'", $sqlconn);
      mysql_query("UPDATE `bs2_game` SET `winner` = $winner_id WHERE `game_id` = '$game_id'", $sqlconn);
    }
    else {
      mysql_query("UPDATE bs2_game SET white_miss = '$white_miss', black_miss = '$black_miss' WHERE game_id = '$game_id'", $sqlconn);

      // Update the modify date in the game table.
      mysql_query("UPDATE bs2_game SET modify_date = CURRENT_TIMESTAMP WHERE game_id = '$game_id'", $sqlconn);
      
      // Skip the current player's turn.
      if ($white_move) {
        // To skip white's turn, add a row with only a black board and a NULL white board.
        mysql_query("INSERT INTO `bs2_game_board` (`game_id`, `white_board`, `black_board`)
                     VALUES ('$game_id', NULL, '{$board_data['black_board']}')", $sqlconn);
      }
      else if ($black_move) {
        // Since it's black's turn, there is no white board. Get the white board of the second last board.
        $second_last_board = mysql_query("SELECT * FROM bs2_game_board WHERE game_id = '$game_id' ORDER BY move_date DESC", $sqlconn);
        $index = 0;
        while ($row = mysql_fetch_assoc($second_last_board)) {
          if ($index == 1) {
            // To skip black's turn, update the latest board with a white board.
            mysql_query("UPDATE `bs2_game_board` SET `white_board` = '{$row['white_board']}'
                         WHERE `id` = '{$board_data['id']}'", $sqlconn);
          }
          $index++;
        }
      }
		}
  }
  else {  // This is executed if the player's move was on time, so reset their miss counter.
    if (($white_move && $white_miss > 0) || ($black_move && $black_miss > 0)) {
      $row = $white_move ? 'white_miss' : 'black_miss';
      mysql_query("UPDATE bs2_game SET $row = 0 WHERE game_id = '$game_id'", $sqlconn);
    }
  }

}

?>