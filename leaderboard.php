<?php

require_once 'includes/inc.global.php';

$mysql = Mysql::get_instance();
$player_id = $_SESSION['player_id'];

$meta['title'] = 'Leaderboard';
$hints = ['These are currently the top players of the game.'];

$contents = '
<div class="tabs">
  <button class="tab">Classic</button>
  <button class="tab">Russian</button>
</div>';

$contents .= '<div class="leaderboard-columns">';

$players = $mysql->fetch_array("SELECT * FROM bs2_bs_player WHERE 1");
$modes = ['Single', 'Salvo', 'Multi'];
for ($i = 0; $i < 3; $i++) {
  $contents .= '<div class="leaderboard-column">';
  $contents .= '<table>';

  $contents .= '</table></div>';
}

$contents .= '</div>';

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer();

?>