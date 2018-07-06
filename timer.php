<?php

require_once 'includes/inc.global.php';

$Game = new Game((int) $_SESSION['game_id']);
$mysql = Mysql::get_instance();

if (isset($_GET['timer'])) {
  $color = $Game->get_my_color();
  $board = $mysql->fetch_assoc("SELECT * FROM `bs2_game_board` WHERE `game_id` = $Game->id
                                ORDER BY `move_date` ASC LIMIT 1");
  $old_move_date = strtotime($board['move_date']);

  $timout = $Game->time_to_move;
  sleep(10);

  $board = $mysql->fetch_assoc("SELECT * FROM `bs2_game_board` WHERE `game_id` = $Game->id
                                ORDER BY `move_date` ASC LIMIT 1");
  $new_move_date = strtotime($board['move_date']);

  // Don't nudge a player if they ended up making a move in that time.
  if ($new_move_date == $old_move_date && $Game->state != 'Finished') {
    if ($color == 'white') {
      if (!$Game->black_focused) {
        // $Game->nudge();
        $mysql->query('INSERT INTO test_emails () VALUES ()');
      }
    }
    else if ($color == 'black') {
      if (!$Game->white_focused) {
        // $Game->nudge();
        $mysql->query('INSERT INTO test_emails () VALUES ()');
      }
    }
  }
}
