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

$contents .= '
<div class="tabs leaderboard-modes">
  <button class="tab">Single</button>
  <button class="tab">Five</button>
  <button class="tab">Salov</button>
  <button class="tab">Multi</button>
</div>';

$contents .= '<div class="leaderboard-columns">';

$players = $mysql->fetch_array("SELECT * FROM bs2_bs_player INNER JOIN
                                player ON player.player_id = bs2_bs_player.player_id;");
$contents .= '<div class="leaderboard-column">';
$contents .= '<table><tr><th>Username</th><th>Games Won</th><th>Games Played</th><th>Win Rate</th></tr>';
foreach ($players as $player) {
  $contents .=
  "<tr>
    <td>{$player['username']}</td>
    <td>{$player['wins']}</td> 
    <td>{$player['username']}</td>
    <td>{$player['username']}</td>
  </tr>";
}
$contents .= '</table></div>';

$contents .= '</div>';

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer();

?>