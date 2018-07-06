<?php

require_once 'includes/inc.global.php';

$Game = new Game((int) $_SESSION['game_id']);

if (isset($_POST['timer'])) {
  $color = $Game->get_my_color();
  $board = $mysql->fetch_assoc("SELECT * FROM `bs2_game_board` WHERE `game_id` = $Game->id
                                ORDER BY `move_date` ASC LIMIT 1");
  $old_move_date = strtotime($board['move_date']);

  if (isset($_SERVER['HTTP_CACHE_CONTROL'])) {
    $timout = $Game->time_to_move;
    sleep($timout);

    $board = $mysql->fetch_assoc("SELECT * FROM `bs2_game_board` WHERE `game_id` = $Game->id																				          ORDER BY `move_date` ASC LIMIT 1");
    $new_move_date = strtotime($board['move_date']);

    // Don't nudge a player if they ended up making a move in that time.
    if ($new_move_date == $move_date && $Game->state != 'Finished') {
      if ($color == 'white') {
        if (!$Game->black_focused) {
          $Game->nudge();
        }
      }
      else if ($color == 'black') {
        if (!$Game->white_focused) {
          $Game->nudge();
        }
      }
    }
  }
}
