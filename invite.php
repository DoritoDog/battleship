<?php

require_once 'includes/inc.global.php';

// this has nothing to do with creating a game
// but I'm running it here to prevent long load
// times on other pages where it would be run more often
GamePlayer::delete_inactive(Settings::read('expire_users'));
Game::delete_inactive(Settings::read('expire_games'));
Game::delete_finished(Settings::read('expire_finished_games'));

$Game = new Game();
$mysql = Mysql::get_instance();

if (isset($_POST['invite'])) {
	// make sure this user is not full
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !', false);
	}

	test_token();

	try {
		$game_id = $Game->invite($_POST['opponent']);
		Flash::store('Invitation Sent Successfully', 'setup.php?id='.$game_id.$GLOBALS['_&_DEBUG_QUERY']);
	}
	catch (MyException $e) {
		Flash::store('Invitation FAILED !', false);
	}
}

if (isset($_POST['find_game'])) {
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !', false);
	}

	$insert_data = [
		'player_id' => $_SESSION['player_id'],
		'method' => $_POST['method'],
		'fleet_type' => $_POST['fleet_type'],
		'timer' => $_POST['timer'],
	];
	
	$pending_game = $mysql->fetch_assoc("SELECT * FROM `bs2_game` WHERE `black_id` IS NULL AND `method` = '{$insert_data['method']}' AND `fleet_type` = '{$insert_data['fleet_type']}' AND `timer` = '{$insert_data['timer']}' AND `white_id` != {$_SESSION['player_id']}");

	try {
		if (empty($pending_game)) {
			$game_id =  $Game->invite();
			Flash::store('A new game was created.', 'setup.php?id='.$game_id.$GLOBALS['_&_DEBUG_QUERY']);
		}
		else {
			$pending_id = $pending_game['game_id'];
			$mysql->query("UPDATE `bs2_game` SET `black_id` = {$_SESSION['player_id']}
										 WHERE `game_id` = $pending_id");
			Flash::store('Game joined successfully!', 'setup.php?id='.$pending_id.$GLOBALS['_&_DEBUG_QUERY']);
		}
	}
	catch (MyException $e) {
		Flash::store('An error occoured!', false);
	}
}

if (isset($_POST['join_game'])) {
	try {
		$pending_id = $_POST['join_game'];
		$game = $mysql->fetch_assoc("SELECT * FROM bs2_game WHERE game_id = $pending_id");
		if ((int)$game['white_id'] == (int)$_SESSION['player_id'])
			throw new MyException("You can't join your own game!");
			
		$mysql->query("UPDATE `bs2_game` SET `black_id` = {$_SESSION['player_id']}
										WHERE `game_id` = $pending_id");
		Flash::store('Game joined successfully!', 'setup.php?id='.$pending_id.$GLOBALS['_&_DEBUG_QUERY']);
	}
	catch (MyException $e) {
		Flash::store('An error occoured when joining the game!', false);
	}
}

$player_id = $_SESSION['player_id'];

$friends = $mysql->fetch_array("SELECT player.player_id, player.username
FROM `bs2_friends` INNER JOIN `player` ON
(bs2_friends.player_one = player.player_id OR bs2_friends.player_two = player.player_id)
AND player.player_id != $player_id");

$friends_selection = '';
foreach ($friends as $friend) {
	$friends_selection .= '<option value="' . $friend['player_id'] . '">' . $friend['username'] . '</option>';
}

$methods = [
	'Five',
	'Salvo',
	'Single',
	'Multi',
];
$method_selection = '';
foreach ($methods as $method) {
	$selected_html = $method === 'Multi' ? 'selected="selected"' : '';
	$method_selection .= '<option value="'.$method.'"'.$selected_html.'>'.$method.'</option>';
}

$fleet_types = ['Classic', 'Russian'];
$fleet_selection = '';
foreach ($fleet_types as $fleet) {
	$selected_html = $fleet === 'Russian' ? 'selected="selected"' : '';
	$fleet_selection .= '<option value="'.$fleet.'"'.$selected_html.'>'.$fleet.'</option>';
}

$timers = [
	'Blitz' => 60,
	'2 Minutes' => 120,
	'5 Minutes' => 300,
	'10 Minutes' => 600,
	'30 Minutes' => 1800,
	'1 Week' => 10080
];
$timer_selection = '';
foreach ($timers as $timer => $seconds) {
	$selected_html = $seconds === 600 ? 'selected="selected"' : '';
	$timer_selection .= '<option value="'.$seconds.'"'.$selected_html.'>'.$timer.'</option>';
}


$meta['title'] = '';
$meta['foot_data'] = '
	<script type="text/javascript" src="scripts/invite.js"></script>
';

$hints = array(
	'Invite a player to a game by filling out your desired game options.' ,
	'Salvo means you get one shot per ship per turn.  If you have two ships sunk, then you\'ll get 3 shots this turn.' ,
	'Five means you get five shots per turn, no matter what.' ,
	'Single means that you get one shot per turn.  This is the original method for Battleship, but may take a long time online.' ,
	'Multi means that you get one shot per turn unless you hit, in that case you will be rewarded with another shot.',
	'Classic mode has the 5 classic battleships: Carrier, Battleship, Cruiser, Submarine and Destroyer.',
	'Russian mode has one 4 tile ship, two 3 tile ships, three 2 tile ships and four 1 tile ships. You can play Russian mode with all methods except for Five.',
	'<span class="highlight">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
);

// make sure this user is not full
$submit_button = '<div><input type="submit" name="invite" value="Invite" /></div>';
$warning = '';
if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
	$submit_button = $warning = '<p class="warning">You have reached your maximum allowed games, you can not create this game !</p>';
}

#region Old
/*
$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="send"><div class="formdiv">

		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		{$warning}

		<div><label for="opponent">Opponent</label><select id="opponent" name="opponent">{$opponent_selection}</select></div>
		<div><label for="method">Method</label><select id="method" name="method">{$method_selection}</select></div>
		<div>
			<label for="timer">Time per Move</label><select id="timer" name="timer">{$timer_selection}</select>
		</div>
		<div>
			<label for="fleet_type">Fleet Type</label>
			<select id="fleet_type" name="fleet_type">{$fleet_selection}</select>
		</div>

		{$submit_button}

		<div class="clr"></div>
	</div></form>
EOF;
*/
#endregion

$contents = '
<form method="post" action="' . $_SERVER['REQUEST_URI'] . '" id="send">
<input type="hidden" name="token" value="' . $_SESSION['token'] . '" />
<input type="hidden" name="player_id" value="' . $_SESSION['player_id'] . '" />

<table>
	<tr>
	  <td align="left">Opponent</td>
	  <td align="center">Method</td>
	  <td align="center">Time per Move</td>
	  <td align="center">Fleet Type</td>
	  <td align="center">Action</td>
	</tr>

	<!-- invite -->
	<tr>
		<td><select name="opponent">' . $friends_selection . '</select></td> <!-- Dropdown list of Friends -->
		<td><select name="method">'.$method_selection.'</select></td>
		<td><select name="timer">' . $timer_selection . '</select></td>
		<td><select name="fleet_type">'.$fleet_selection.'</select></td>
		<td>'.$submit_button.'</td>
	</tr>

	<!-- find -->
	<tr>
		<td>Random Opponent</td> <!-- just plain words: Random Opponent -->
		<td><select id="method">'.$method_selection.'</select></td>
		<td><select id="time">' . $timer_selection . '</select></td>
		<td><select id="fleet_type">'.$fleet_selection.'</select></td>
		<td><input type="submit" name="find_game" value="Search" /></td>
	</tr>
</table>

</form>
';

// create our invitation tables
$invites = Game::get_invites($_SESSION['player_id']);

$in_vites = $out_vites = array( );
if (is_array($invites)) {
	foreach ($invites as $game) {
		if ($game['invite']) {
			$in_vites[] = $game;
		}
		else {
			$out_vites[] = $game;
		}
	}
}

$contents .= <<< EOT
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv" id="invites">
EOT;

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no received invites to show</p>' ,
	'caption' => 'Invitations Received' ,
);
$table_format = array(
	array('ID', 'game_id') ,
	array('Player #1', 'white') ,
	array('Player #2', 'black') ,
	array('Method', 'method') ,
	array('Fleet Type', 'fleet_type') ,
	array('Timer', 'timer') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" /><input type="button" id="decline-[[[game_id]]]" value="Decline" />', false) ,
);
$contents .= get_table($table_format, $in_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no sent invites to show</p>' ,
	'caption' => 'Invitations Sent' ,
);
$table_format = array(
	array('ID', 'game_id') ,
	array('Player #1', 'white') ,
	array('Player #2', 'black') ,
	array('Method', 'method') ,
	array('Fleet Type', 'fleet_type') ,
	array('Timer', 'timer') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '###\'<input type="button" id="setup-[[[game_id]]]" value="Edit Setup" /><input type="button" id="withdraw-[[[game_id]]]" value="Withdraw" />\'.((strtotime(\'[[[create_date]]]\') >= strtotime(\'[[[resend_limit]]]\')) ? \'\' : \'<input type="button" id="resend-[[[game_id]]]" value="Resend" />\')', false) ,
);
$contents .= get_table($table_format, $out_vites, $table_meta);

$left_content = '
<h2>Find me an opponent</h2>
<form method="post" action="">
	<div><label for="method">Method</label><select id="method" name="method">'.$method_selection.'</select></div>
	<div>
		<label for="fleet_type">Fleet Type</label>
		<select id="fleet_type" name="fleet_type">'.$fleet_selection.'</select>
	</div>
	<div>
		<label for="timer">Time To Move</label><select id="timer" name="timer">'.$timer_selection.'</select>
	</div>
	<input type="hidden" name="player_id" value="'.$_SESSION['player_id'].'">
	<input type="submit" name="find_game" value="Search">
</form>
';

$contents .= <<< EOT
	</div></form>
EOT;


echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer($meta);