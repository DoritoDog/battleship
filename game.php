<?php

require_once 'includes/inc.global.php';

// grab the game id
if (isset($_GET['id'])) {
	$_SESSION['game_id'] = (int) $_GET['id'];
}
else {
	header('Location:' . 'index.php');
	exit;
}

// ALL GAME FORM SUBMISSIONS ARE AJAXED THROUGH /scripts/game.js

// load the game
// always refresh the game data, there may be more than one person online
try {
	$Game = new Game((int) $_SESSION['game_id']);

	if ( ! $Game->test_ready( )) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			session_write_close( );
			header('Location: setup.php?id='.$_SESSION['game_id'].$GLOBALS['_&_DEBUG_QUERY']);
		}
		else {
			call('GAME IS INCOMPLETE, REDIRECTED TO setup.php?id='.$_SESSION['game_id'].$GLOBALS['_&_DEBUG_QUERY'].' AND QUIT');
		}

		exit;
	}
}
catch (MyException $e) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('Error Accessing Game !');
	}
	else {
		call('ERROR ACCESSING GAME :'.$e->outputMessage( ));
	}

	exit;
}

$mysql = Mysql::get_instance();

$players = $Game->get_players( );
$Chat = new Chat($_SESSION['player_id'], $_SESSION['game_id']);
$chat_data = $Chat->get_box_list( );

$chat_html = '
		<div id="chatbox">
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
				<input id="chat" type="text" name="chat" autocomplete=off />
				<label for="private" class="inline"><input type="checkbox" name="private" id="private" value="yes" /> Private</label>
			</div></form>
			<dl id="chats">';

if (is_array($chat_data)) {
	foreach ($chat_data as $chat) {
		if ('' == $chat['username']) {
			$chat['username'] = '[deleted]';
		}

		$color = '';
		if (isset($players[$chat['player_id']]['color'])) {
			$color = substr($players[$chat['player_id']]['color'], 0, 3);
		}

		// preserve spaces in the chat text
		$chat['message'] = htmlentities($chat['message'], ENT_QUOTES, 'ISO-8859-1', false);
		$chat['message'] = str_replace("\t", '    ', $chat['message']);
		$chat['message'] = str_replace('  ', ' &nbsp;', $chat['message']);

		$chat_html .= '
				<dt class="'.$color.'"><span>'.$chat['create_date'].'</span> '.$chat['username'].'</dt>
				<dd'.($chat['private'] ? ' class="private"' : '').'>'.$chat['message'].'</dd>';
	}
}

$chat_html .= '
			</dl> <!-- #chats -->
		</div> <!-- #chatbox -->';

// hide the chat from non-players
if (('Finished' == $Game->state) && ! $Game->is_player($_SESSION['player_id'])) {
	$chat_html = '';
}

$shots = $Game->get_shot_count();

// grab the previous shots
list($prev_shots, $prev_color) = $Game->get_previous_shots();

// grab any previous sinkings
$sunk = $Game->get_sunk();

$sunk_text = '';
$win_text = '';
if ($sunk) {
	$sunk_text = ($Game->get_my_turn( )) ? $Game->name.' sunk your ' : 'You sunk '.$Game->name.'\'s ';
	foreach ($sunk as $ship) {
		$sunk_text .= $ship.', ';
	}

	$sunk_text = substr($sunk_text, 0, -2);
}

$turn = ($Game->get_my_turn( )) ? 'Your Turn' : $Game->name.'\'s Turn';
$no_turn = false;

if ($Game->paused) {
	$turn = 'PAUSED';
	$no_turn = true;
}
elseif ('Finished' == $Game->state) {
	$turn = 'GAME OVER';
	$no_turn = true;
	$prev_shots = false;
	$sunk_text = '';
	$shots = 0;
	list($win_text, $outcome) = $Game->get_outcome($_SESSION['player_id']);
}

$info_bar = '<span class="turn">'.$turn.'</span>';

// game is not finished and it's this player's turn
if (('Finished' != $Game->state) && $Game->get_my_turn( ) && ! $no_turn) {
	$info_bar .= ' <span class="shots">'.$Game->method.' '.plural($shots, 'Shot').': </span>';
}

$move_date = $mysql->fetch_assoc("SELECT * FROM bs2_game_board ORDER BY move_date DESC LIMIT 1")['move_date'];
$last_move = date('M d, Y H:i:s', (strtotime($move_date) + $Game->time_to_move));

$total_boats = $Game->fleet_type == 'Russian' ? 10 : 5;
$player_boats = $total_boats - count($Game->get_missing_boats($mine = true));
$opponent_boats = $total_boats - count($Game->get_missing_boats($mine = false));

$hide_board = $Game->get_my_color() == 'white' ? $Game->hide_white : $Game->hide_black;

$theme = Mysql::get_instance()->fetch_assoc("SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)");
$shipGraphics = [
	'v-bow' => 'v_bow.gif',
	'v-fore' => 'v_fore.gif',
	'v-mid' => 'v_mid.gif',
	'v-aft' => 'v_aft.gif',
	'v-stern' => 'v_stern.gif',
	'v-sub-bow' => 'v_sub_bow.png',
	'v-sub-mid' => 'v_sub_mid.png',
	'v-sub-stern' => 'v_sub_stern.png',
	'v-frig-bow' => 'v_frig_bow.png',
	'v-frig-stern' => 'v_frig_stern.png',
	'v-cartel' => 'cartel.png',
	'v-corvette' => 'corvette.png',
	'v-escourt' => 'escourt.png',
	'v-gunboat' => 'gunboat.png',
	'h-bow' => 'h_bow.gif',
	'h-fore' => 'h_fore.gif',
	'h-mid' => 'h_mid.gif',
	'h-aft' => 'h_aft.gif',
	'h-stern' => 'h_stern.gif',
	'h-sub-bow' => 'h_sub_bow.png',
	'h-sub-mid' => 'h_sub_mid.png',
	'h-sub-stern' => 'h_sub_stern.png',
	'h-frig-bow' => 'h_frig_bow.png',
	'h-frig-stern' => 'h_frig_stern.png',
	'h-cartel' => 'cartel.png',
	'h-corvette' => 'corvette.png',
	'h-escourt' => 'escourt.png',
	'h-gunboat' => 'gunboat.png',
];
$style = '';
foreach ($shipGraphics as $class => $graphic) {
	$style .= "div.$class { background-image: url('".$GLOBALS['_ROOT_URI'].'images/'.$theme['filesdir'].'/'."$graphic') } \n";
}

$meta['title'] = $turn.' - '.$Game->name.' (#'.$_SESSION['game_id'].')';
$meta['show_menu'] = false;
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/game.css" />
	<style>' . $style . '</style>
	<script type="text/javascript" src="scripts/jquery.jplayer.min.js"></script>
	<div id="sounds"></div>

	<script type="text/javascript">/*<![CDATA[*/
		var state = "'.(( ! $Game->paused) ? strtolower($Game->state) : 'paused').'";
		var shots = '.$shots.';
		var prev_shots = ['.implode(',', (array) $prev_shots).'];
		var prev_color = "'.$prev_color.'";
		var last_move = '.$Game->last_move.';
		var my_turn = '.(( ! $Game->get_my_turn( ) || $no_turn) ? 'false' : 'true').';
		var pre_hide_board = '.(($GLOBALS['Player']->pre_hide_board) ? 'true' : 'false').';
		var gameMode = "' . $Game->method . '";
		var hideBoard = "' . ($hide_board ? true : false) . '";
		var rootUri = "' . $GLOBALS['_ROOT_URI'] . '";
		var theme = "' . $theme['filesdir'] . '";
		var lastMove = "' . $last_move . '";
	/*]]>*/</script>
';

$meta['foot_data'] = '
	<script type="text/javascript" src="scripts/game.js"></script>
';

echo get_header($meta);

?>

		<div id="contents">
			<ul id="buttons">
				<li><a href="index.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Main Page</a></li>
				<li><a href="game.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Reload Game Board</a></li>
			</ul>
			<h2>Game #<?php echo $_SESSION['game_id'].' vs '.htmlentities($Game->name, ENT_QUOTES, 'ISO-8859-1', false); ?> <?php echo $info_bar; ?></h2>

			<?php if ('' != $sunk_text) { ?>
			<div class="msg sunk"><?php echo $sunk_text; ?></div>
			<?php } ?>

			<?php if ('' != $win_text) { ?>
			<div class="msg <?php echo $outcome; ?>"><?php echo $win_text; ?></div>
			<?php } ?>

			<div id="boards" class="active">
				<div id="board_data">
					<div class="board_info"><span class="fleet"><?php echo $Game->first_name; ?> Fleet</span><span class="ships" id="my_ships"><?php echo $player_boats.' '.plural($player_boats, 'Ship'); ?> Remaining</span></div>
					<div class="board_info"><span class="fleet"><?php echo $Game->second_name; ?> Fleet</span><span class="ships" id="opp_ships"><?php echo $opponent_boats.' '.plural($opponent_boats, 'Ship'); ?> Remaining</span></div>
				</div>
				<?php echo $Game->get_board_html('first'); ?>
				<?php echo $Game->get_board_html('second'); ?>
			</div>

			<div id="chat">
				<?php echo $chat_html; ?>
			</div>

			<div class="timer-container">
				<h4>Time Left</h4>
				<div class="timer">
					<div id="days">00</div>:
					<div id="hours">00</div>:
					<div id="minutes">00</div>:
					<div id="seconds">00</div>
				</div>
			</div>

			<form id="game" method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"><div class="formDiv">
				<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
				<input type="hidden" name="game_id" value="<?php echo $_SESSION['game_id']; ?>" />
				<input type="hidden" name="player_id" value="<?php echo $_SESSION['player_id']; ?>" />
				<input type="hidden" name="shots" id="shots" value="" />
				<?php if ('Playing' == $Game->state) { ?>
					<input type="button" name="resign" id="resign" value="Resign" />
				<?php } ?>
				<?php if ($Game->test_nudge( )) { ?>
					<input type="button" name="nudge" id="nudge" value="Nudge" />
				<?php } ?>
			</div></form>

		</div> <!-- #contents -->
		<audio id="audio" src="sounds/turn.mp3" autostart="false" ></audio>
<?php

var_dump($GLOBALS['which_login']);
var_dump($_SESSION);
var_dump($GLOBALS['Player']);

call($GLOBALS);
echo get_footer($meta); 

?>
<script id="Firefox --WRONG--">
/*
object(Game)#6 (19) { ["id"]=> int(11) ["state"]=> string(7) "Playing" ["method"]=> string(5) "Multi" ["fleet_type"]=> string(7) "Russian" ["time_to_move"]=> string(3) "600" ["white_focused"]=> string(1) "0" ["black_focused"]=> string(1) "0" ["turn"]=> string(5) "white" ["paused"]=> bool(false) ["create_date"]=> int(1531233135) ["modify_date"]=> int(1531298790) ["last_move"]=> int(1531298790) ["hide_white"]=> string(1) "0" ["hide_black"]=> string(1) "0" ["_players":protected]=> array(4) { ["white"]=> &array(6) { ["player_id"]=> string(1) "6" ["object"]=> object(GamePlayer)#7 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "1" ["color":protected]=> string(10) "blue_white" ["skin_id":protected]=> int(1) ["wins":protected]=> int(0) ["losses":protected]=> int(0) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(6) ["username":protected]=> string(12) "Kareem's alt" ["firstname":protected]=> string(3) "Not" ["lastname":protected]=> string(7) "Telling" ["email":protected]=> string(25) "belgharbikareem@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "956201b6a42dacab66dab4750238c899" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "white" ["opp_color"]=> string(5) "black" ["ready"]=> bool(true) ["turn"]=> bool(true) } ["black"]=> &array(5) { ["player_id"]=> string(1) "1" ["object"]=> object(GamePlayer)#8 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "4" ["color":protected]=> string(0) "" ["skin_id":protected]=> int(1) ["wins":protected]=> int(2) ["losses":protected]=> int(1) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(1) ["username":protected]=> string(12) "Kareem | Dev" ["firstname":protected]=> string(6) "Kareem" ["lastname":protected]=> string(9) "Belgharbi" ["email":protected]=> string(25) "kareembelgharbi@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "74a5cdad4ba30f0a5d742f2715a1493a" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "black" ["opp_color"]=> string(5) "white" ["ready"]=> bool(true) } ["player"]=> &array(5) { ["player_id"]=> string(1) "1" ["object"]=> object(GamePlayer)#8 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "4" ["color":protected]=> string(0) "" ["skin_id":protected]=> int(1) ["wins":protected]=> int(2) ["losses":protected]=> int(1) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(1) ["username":protected]=> string(12) "Kareem | Dev" ["firstname":protected]=> string(6) "Kareem" ["lastname":protected]=> string(9) "Belgharbi" ["email":protected]=> string(25) "kareembelgharbi@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "74a5cdad4ba30f0a5d742f2715a1493a" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "black" ["opp_color"]=> string(5) "white" ["ready"]=> bool(true) } ["opponent"]=> &array(6) { ["player_id"]=> string(1) "6" ["object"]=> object(GamePlayer)#7 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "1" ["color":protected]=> string(10) "blue_white" ["skin_id":protected]=> int(1) ["wins":protected]=> int(0) ["losses":protected]=> int(0) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(6) ["username":protected]=> string(12) "Kareem's alt" ["firstname":protected]=> string(3) "Not" ["lastname":protected]=> string(7) "Telling" ["email":protected]=> string(25) "belgharbikareem@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "956201b6a42dacab66dab4750238c899" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "white" ["opp_color"]=> string(5) "black" ["ready"]=> bool(true) ["turn"]=> bool(true) } } ["_winner":protected]=> int(0) ["_boards":protected]=> array(4) { ["white"]=> &object(Battleship)#9 (3) { ["board":protected]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y0Y0000JYX000YYY000Y00" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } ["black"]=> &object(Battleship)#10 (3) { ["board":protected]=> string(100) "00000s0000000000000t000000000000000000000000000r00XXXXXXY000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } ["player"]=> &object(Battleship)#10 (3) { ["board":protected]=> string(100) "00000s0000000000000t000000000000000000000000000r00XXXXXXY000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } ["opponent"]=> &object(Battleship)#9 (3) { ["board":protected]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y0Y0000JYX000YYY000Y00" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } } ["_history":protected]=> array(9) { [0]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y0Y0000JYX000YYY000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00XXXXXXY000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:46:30" } [1]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y000000JYX000YYY000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:41:36" } [2]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y000000JYX000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000YY0000Y00YXX00q00YXXX0000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:40:33" } [3]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YOC0kl00H00PD00000I000000000JYX000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000Y00000Y00YXX00q00YXXX0000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:40:11" } [4]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000JYX000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000Y00000Y00YXX00q00YeXX00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:39:34" } [5]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0X000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000Y00000Y00YXX00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:39:11" } [6]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0X0000Y0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000000000Y00YXX00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:38:44" } [7]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0X000000000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000000000Y000kl00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:38:13" } [8]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0s000000000000" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn000000000000000kl00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-10 14:32:31" } } ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } } */
</script>
<script id="Chrome">/*
object(Game)#6 (19) { ["id"]=> int(11) ["state"]=> string(7) "Playing" ["method"]=> string(5) "Multi" ["fleet_type"]=> string(7) "Russian" ["time_to_move"]=> string(3) "600" ["white_focused"]=> string(1) "0" ["black_focused"]=> string(1) "0" ["turn"]=> string(5) "white" ["paused"]=> bool(false) ["create_date"]=> int(1531233135) ["modify_date"]=> int(1531298790) ["last_move"]=> int(1531298790) ["hide_white"]=> string(1) "0" ["hide_black"]=> string(1) "0" ["_players":protected]=> array(4) { ["white"]=> &array(6) { ["player_id"]=> string(1) "6" ["object"]=> object(GamePlayer)#7 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "1" ["color":protected]=> string(10) "blue_white" ["skin_id":protected]=> int(1) ["wins":protected]=> int(0) ["losses":protected]=> int(0) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(6) ["username":protected]=> string(12) "Kareem's alt" ["firstname":protected]=> string(3) "Not" ["lastname":protected]=> string(7) "Telling" ["email":protected]=> string(25) "belgharbikareem@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "956201b6a42dacab66dab4750238c899" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "white" ["opp_color"]=> string(5) "black" ["ready"]=> bool(true) ["turn"]=> bool(true) } ["black"]=> &array(5) { ["player_id"]=> string(1) "1" ["object"]=> object(GamePlayer)#8 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "4" ["color":protected]=> string(0) "" ["skin_id":protected]=> int(1) ["wins":protected]=> int(2) ["losses":protected]=> int(1) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(1) ["username":protected]=> string(12) "Kareem | Dev" ["firstname":protected]=> string(6) "Kareem" ["lastname":protected]=> string(9) "Belgharbi" ["email":protected]=> string(25) "kareembelgharbi@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "74a5cdad4ba30f0a5d742f2715a1493a" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "black" ["opp_color"]=> string(5) "white" ["ready"]=> bool(true) } ["player"]=> &array(5) { ["player_id"]=> string(1) "1" ["object"]=> object(GamePlayer)#8 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "4" ["color":protected]=> string(0) "" ["skin_id":protected]=> int(1) ["wins":protected]=> int(2) ["losses":protected]=> int(1) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(1) ["username":protected]=> string(12) "Kareem | Dev" ["firstname":protected]=> string(6) "Kareem" ["lastname":protected]=> string(9) "Belgharbi" ["email":protected]=> string(25) "kareembelgharbi@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "74a5cdad4ba30f0a5d742f2715a1493a" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "black" ["opp_color"]=> string(5) "white" ["ready"]=> bool(true) } ["opponent"]=> &array(6) { ["player_id"]=> string(1) "6" ["object"]=> object(GamePlayer)#7 (21) { ["allow_email":protected]=> bool(false) ["pre_hide_board":protected]=> bool(true) ["max_games":protected]=> int(5) ["current_games":protected]=> string(1) "1" ["color":protected]=> string(10) "blue_white" ["skin_id":protected]=> int(1) ["wins":protected]=> int(0) ["losses":protected]=> int(0) ["last_online":protected]=> int(1531298790) ["id":protected]=> int(6) ["username":protected]=> string(12) "Kareem's alt" ["firstname":protected]=> string(3) "Not" ["lastname":protected]=> string(7) "Telling" ["email":protected]=> string(25) "belgharbikareem@gmail.com" ["timezone":protected]=> string(0) "" ["is_admin":protected]=> bool(false) ["is_logged":protected]=> NULL ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } ["_DEBUG":protected]=> bool(false) ["_ident":"Player":private]=> string(32) "956201b6a42dacab66dab4750238c899" ["_token":"Player":private]=> NULL } ["color"]=> string(5) "white" ["opp_color"]=> string(5) "black" ["ready"]=> bool(true) ["turn"]=> bool(true) } } ["_winner":protected]=> int(0) ["_boards":protected]=> array(4) { ["white"]=> &object(Battleship)#9 (3) { ["board":protected]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y0Y0000JYX000YYY000Y00" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } ["black"]=> &object(Battleship)#10 (3) { ["board":protected]=> string(100) "00000s0000000000000t000000000000000000000000000r00XXXXXXY000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } ["player"]=> &object(Battleship)#10 (3) { ["board":protected]=> string(100) "00000s0000000000000t000000000000000000000000000r00XXXXXXY000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } ["opponent"]=> &object(Battleship)#9 (3) { ["board":protected]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y0Y0000JYX000YYY000Y00" ["target_human":protected]=> NULL ["target_index":protected]=> NULL } } ["_history":protected]=> array(9) { [0]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y0Y0000JYX000YYY000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00XXXXXXY000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:46:30" } [1]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y000000JYX000YYY000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000YY0000Y00YXX00q00YXXXY000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:41:36" } [2]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YXC0kl00H00XD00000I00Y000000JYX000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000YY0000Y00YXX00q00YXXX0000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:40:33" } [3]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0X0AT0000F0X0B00000G0YOC0kl00H00PD00000I000000000JYX000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000Y00000Y00YXX00q00YXXX0000YXXj0000" ["move_date"]=> string(19) "2018-07-11 08:40:11" } [4]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000JYX000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000Y00000Y00YXX00q00YeXX00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:39:34" } [5]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0X000YY0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000Y00000Y00YXX00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:39:11" } [6]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0X0000Y0000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000000000Y00YXX00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:38:44" } [7]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0X000000000Y00" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn00000000000Y000kl00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-11 08:38:13" } [8]=> array(4) { ["game_id"]=> string(2) "11" ["white_board"]=> string(100) "000R00000000000000000000Q0000000000E0M0AT0000F0N0B00000G00OC0kl00H00PD00000I000000000J0s000000000000" ["black_board"]=> string(100) "00000s0000000000000t000000000000000000000000000r00opabcd0000mn000000000000000kl00q000efg00000hij0000" ["move_date"]=> string(19) "2018-07-10 14:32:31" } } ["_mysql":protected]=> object(Mysql)#1 (24) { ["link_id":protected]=> resource(13) of type (mysql link) ["query":protected]=> string(108) "SELECT * FROM themes WHERE themes.id = (SELECT skin_id FROM bs2_bs_player WHERE bs2_bs_player.player_id = 1)" ["result":protected]=> resource(44) of type (mysql result) ["query_time":protected]=> float(0.000177145004272) ["query_count":protected]=> int(23) ["error":protected]=> NULL ["_host":protected]=> string(9) "localhost" ["_user":protected]=> string(10) "battleship" ["_pswd":protected]=> string(10) "B@77l3$#!p" ["_db":protected]=> string(19) "battlesh_battleship" ["_page_query":protected]=> NULL ["_page_result":protected]=> NULL ["_num_results":protected]=> NULL ["_page":protected]=> NULL ["_num_per_page":protected]=> NULL ["_num_pages":protected]=> NULL ["_error_debug":protected]=> bool(false) ["_query_debug":protected]=> bool(false) ["_log_errors":protected]=> string(1) "1" ["_log_path":protected]=> string(45) "/home/battleship/public_html/battleship/logs/" ["_email_errors":protected]=> string(1) "1" ["_email_subject":protected]=> string(22) "Battleship Query Error" ["_email_from":protected]=> string(22) "admin@battleship.co.za" ["_email_to":protected]=> string(20) "pieter.net@gmail.com" } }*/
</script>