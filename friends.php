<?php

require_once 'includes/inc.global.php';

$mysql = Mysql::get_instance();
$player_id = $_SESSION['player_id'];

$meta['title'] = 'Friends';

$hints = ['This is the place to add or manage friends so that you can play with those you know.'];

$contents = '';

// ----- START OF FRIEND REQUESTS -----

$requests = $mysql->fetch_array("SELECT * FROM `friend_requests` WHERE `reciever_id` = $player_id");

#Get the friend usernames from their ids.
for ($i = 0; $i < count($requests); $i++) {
  $id = $requests[$i]['sender_id'];
  $query = "SELECT `username` FROM `player` WHERE `player_id` = $id";
  $requests[$i]['sender_username'] = $mysql->fetch_assoc($query)['username'];
}

$table_meta = [
	'sortable' => true,
	'no_data' => '<p>You have no friend requests at the moment.</p>',
	'caption' => 'Friend Requests Recieved',
];
$action = $_SERVER['REQUEST_URI'];
$table_format = [
	['Sender', 'sender_username'],
  ['Sent', 'sent'],
  [
    'Action',
    '<form method="post" action="' . $action . '" style="display: inline">
      <input type="hidden" name="accept" value="[[[id]]]" />
      <input type="submit" value="Accept" />
    </form>
    <form method="post" action="' . $action . '" style="display: inline">
      <input type="hidden" name="decline" value="[[[id]]]" />
      <input type="submit" value="Decline" />
    </form>',
    false
  ],
];
$contents .= get_table($table_format, $requests, $table_meta);

// ----- END OF FRIEND REQUESTS -----

$contents .= '
<form method="post" action="">
  <div class="player-search">
    <input type="text" placeholder="Search for players..." class="search-input" name="player-search">
    <input type="submit" value="Search">
  </div>
</form>
';

if (isset($_POST['friend_request'])) {
  $args = ['sender_id' => $_SESSION['player_id'], 'reciever_id' => $_POST['friend_request']];
  $mysql->insert('friend_requests', $args);
}

if (isset($_POST['unfriend'])) {
  $friend_id = $_POST['unfriend'];
  $mysql->query("DELETE FROM `bs2_friends` WHERE (`player_one` = $friend_id AND `player_two` = $player_id)
                 OR (`player_one` = $player_id AND `player_two` = $friend_id)");
}

if (isset($_POST['accept'])) {
  $request_id = $_POST['accept'];
  $request = $mysql->fetch_assoc("SELECT * FROM `friend_requests` WHERE `id` = $request_id");
  $mysql->query("DELETE FROM `friend_requests` WHERE `id` = $request_id");

  $mysql->insert(
    'bs2_friends',
    ['player_one' => $request['sender_id'], 'player_two' => $request['reciever_id']]
  );

  header('friends.php');
}
else if (isset($_POST['decline'])) {
  $request_id = $_POST['decline'];
  $mysql->query("DELETE FROM `friend_requests` WHERE `id` = $request_id");
  header('friends.php');
}

if (isset($_POST['player-search'])) {
  $wildcard = $_POST['player-search'];
  $players = $mysql->fetch_array("SELECT * FROM `player` WHERE `username` LIKE '%$wildcard%'");
  
  $contents .= '<div class="players-container">';
  for ($i = 0; $i < count($players); $i++) {
    $contents .= '<div class="player">
                    <h3>' . $players[$i]['username'] . '</h3>
                    <form method="post" action="">
                      <input type="hidden" name="friend_request" value="' . $players[$i]['player_id'] . '" />
                      <input type="submit" value="Send Friend Request" />
                    </form>
                  </div>';
  }
  $contents .= '</div>';
}
else {
  $contents .= '<table class="friends-table"><div class="players-container">';
  
  $query = "SELECT player.player_id, player.username
  FROM `bs2_friends`
  INNER JOIN `player` ON
  (bs2_friends.player_one = player.player_id OR bs2_friends.player_two = player.player_id)
  AND player.player_id != $player_id
  WHERE player_one = $player_id OR player_two = $player_id";
  $friends = $mysql->fetch_array($query);

  foreach ($friends as $friend) {
    $contents .= '<tr>
                    <th><h3>' . $friend['username'] . '</h3></th>
                    <th><a href="send.php"><span class="fa fa-comments"></span> Send Message</a></th>
                    <th><a href="invite.php"><span class="fa fa-ship"></span> Challenge</a></th>
                    <form method="post" action="">
                      <input type="hidden" name="unfriend" value="' . $friend['player_id'] . '" />
                      <th><input type="submit" value="Unfriend" /></th>
                    </form>
                  </tr>';
  }

  $contents .= '</div></table>';
}

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

?>

