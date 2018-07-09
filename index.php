<?php

require_once 'includes/inc.global.php';

// remove any previous game sessions
unset($_SESSION['game_id']);

// grab the message and game counts
$message_count = (int) Message::check_new($_SESSION['player_id']);
$turn_count = (int) Game::check_turns($_SESSION['player_id']);
$turn_msg_count = $message_count + $turn_count;

$meta['title'] = 'Game List';
$meta['head_data'] = '
	<script type="text/javascript" src="scripts/jquery.jplayer.min.js"></script>
	<script type="text/javascript" src="scripts/index.js"></script>
	<script type="text/javascript">//<![CDATA[
		var turn_msg_count = '.$turn_msg_count.';
	//]]></script>
';
$meta['foot_data'] = '<div id="sounds"></div>';


// grab the list of games
$list = Game::get_list($_SESSION['player_id']);
$contents = '';

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no games to show</p>' ,
	'caption' => 'Current Games' ,
);
$table_format = array(
	array('SPECIAL_HTML', 'true', 'id="g[[[game_id]]]"') ,
	array('SPECIAL_CLASS', '(1 == \'[[[my_turn]]]\')', 'highlight') ,
	array('SPECIAL_CLASS', '(0 == \'[[[in_game]]]\')', 'lowlight') ,

	array('ID', 'game_id') ,
	array('State', '###(([[[paused]]]) ? \'Paused\' : (([[[my_turn]]]) ? \'<span class="highlight">[[[state]]]</span>\' : \'[[[state]]]\'))') ,
	array('Player #1', '###((\'[[[white]]]\' == $GLOBALS[\'Player\']->username) ? \'<span class="highlight">[[[white]]]</span>\' : \'[[[white]]]\')') ,
	array('Player #2', '###((\'[[[black]]]\' == $GLOBALS[\'Player\']->username) ? \'<span class="highlight">[[[black]]]</span>\' : \'[[[black]]]\')') ,
	array('Method', 'method') ,
	array('Last Move', '###date(Settings::read(\'long_date\'), strtotime(\'[[[last_move]]]\'))', null, ' class="date"') ,
);
$contents .= '
	<div class="tableholder">
		'.get_table($table_format, $list, $table_meta).'
	</div>';

$table_meta = array(
	'sortable' => true,
	'no_data' => '<p>There are no open games to show</p>',
	'caption' => 'Open Games',
);
$table_format = array(
	array('SPECIAL_HTML', 'true', 'id="g[[[game_id]]]"') ,
	array('ID', 'game_id') ,
	array('Player #1', '[[[username]]]') ,
	array('Method', 'method'),
	array('Fleet Type', '[[[fleet_type]]]'),
	array('Timer', 'timer'),
	array('Created', '[[[create_date]]]'),
	array('Join Game', '
	<form method="post" action="invite.php">
	<input type="hidden" name="join_game" value="[[[game_id]]]">
	<input type="submit" value="Join">
	</form>
	'),
);
$query = "SELECT bs2_game.*, player.username
					FROM bs2_game
					INNER JOIN player ON bs2_game.white_id = player.player_id
					WHERE bs2_game.black_id IS NULL AND bs2_game.state = 'Waiting'";
$pending_list = Mysql::get_instance()->fetch_array($query);
$contents .= '
	<br>
	<div class="tableholder">
		'.get_table($table_format, $pending_list, $table_meta).'
	</div>';

// create the lobby
$Chat = new Chat($_SESSION['player_id'], 0);
$chat_data = $Chat->get_box_list( );

// temp storage for gravatar imgs
$gravatars = array( );

$lobby = '
	<div id="lobby">
		<div class="caption">Lobby</div>
		<div id="chatbox">
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
				<input type="hidden" name="lobby" value="1" />
				<input id="chat" type="text" name="chat" />
			</div></form>';

	if (is_array($chat_data)) {
		$lobby .= '
			<dl id="chats">';

		foreach ($chat_data as $chat) {
			// preserve spaces in the chat text
			$chat['message'] = str_replace("\t", '    ', $chat['message']);
			$chat['message'] = str_replace('  ', ' &nbsp;', $chat['message']);

			if ( ! isset($gravatars[$chat['email']])) {
				$gravatars[$chat['email']] = Gravatar::src($chat['email']);
			}

			$grav_img = '<img src="'.$gravatars[$chat['email']].'" alt="" /> ';

			if ('' == $chat['username']) {
				$chat['username'] = '[deleted]';
			}

			$lobby .= '
				<dt>'.$grav_img.'<span>'.$chat['create_date'].'</span> '.$chat['username'].'</dt>
				<dd>'.htmlentities($chat['message'], ENT_QUOTES, 'ISO-8859-1', false).'</dd>';
		}

		$lobby .= '
			</dl> <!-- #chats -->';
	}

	$lobby .= '
		</div> <!-- #chatbox -->
	</div> <!-- #lobby -->';

$contents .= $lobby;

$hints = array(
	'Select a game from the list and resume play by clicking anywhere on the row.' ,
	'Invite another player to a game by clicking on the Invitations menu item.' ,
	'<span class="highlight">Colored entries</span> indicate that it is your turn.' ,
	'<span class="warning">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
	'Finished games will be deleted after '.Settings::read('expire_finished_games').' days.' ,
);

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer($meta);

