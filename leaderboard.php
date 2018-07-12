<?php

require_once 'includes/inc.global.php';

$mysql = Mysql::get_instance();
$player_id = $_SESSION['player_id'];

$meta['title'] = 'Leaderboard';
$hints = ['These are currently the top players of the game.'];

$fleet_type = isset($_POST['fleet_type']) ? $_POST['fleet_type'] : 'Classic';
$method = isset($_POST['method']) ? $_POST['method'] : 'Single';

$selected_html = 'selected="selected"';
$fleet_types = ['Classic', 'Russian'];
$methods = ['Single', 'Salvo', 'Multi', 'Five'];
$contents = '
<div class="tabs">
  <form method="post">
    <select name="fleet_type" onchange="this.form.submit()">';

foreach ($fleet_types as $type) {
  $contents .= '<option value="'.$type.'" ' . ($fleet_type == $type ? $selected_html : '') . ">$type</option>";
}
$contents .= '
    </select>
    <select name="method" onchange="this.form.submit()">';
foreach ($methods as $m) {
  $contents .= '<option value="'.$m.'" ' . ($method == $m ? $selected_html : '') . ">$m</option>";
}
$contents .= '
    </select>
  </form>
</div>
';

$contents .= '<div class="leaderboard-columns">';

$players = $mysql->fetch_array("SELECT * FROM bs2_bs_player INNER JOIN
                                player ON player.player_id = bs2_bs_player.player_id;");

$contents .= '<div class="leaderboard-column">';
$contents .= '<table><tr><th>Username</th><th>Games Won</th><th>Games Played</th><th>Win Rate</th></tr>';
foreach ($players as $player) {
  $games = (int)$mysql->fetch_value("SELECT COUNT(`game_id`) FROM `bs2_game` WHERE (`white_id` = {$player['id']} OR `black_id` = {$player['id']}) AND `state` = 'Finished' AND `method` = '$method' AND `fleet_type` = '$fleet_type'");
  $games_won = (int)$mysql->fetch_value("SELECT COUNT(`game_id`) FROM `bs2_game` WHERE `winner` = {$player['id']} AND `state` = 'Finished' AND `method` = '$method' AND `fleet_type` = '$fleet_type'");

  $contents .=
  "<tr>
    <td>{$player['username']}</td>
    <td>$games_won</td> 
    <td>$games</td>
    <td>" . ( $games > 0 ? (($games_won / $games) * 100) : 0) . "%</td>
  </tr>";
}
$contents .= '</table></div>';

$contents .= '</div>';

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer();

?>