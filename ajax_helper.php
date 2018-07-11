<?php

$GLOBALS['NODEBUG'] = true;
$GLOBALS['AJAX'] = true;


// don't require log in when testing for used usernames and emails
if (isset($_POST['validity_test']) || (isset($_GET['validity_test']) && isset($_GET['DEBUG']))) {
	define('LOGIN', false);
}


require_once 'includes/inc.global.php';


// if we are debugging, change some things for us
// (although REQUEST_METHOD may not always be valid)
if (('GET' == $_SERVER['REQUEST_METHOD']) && defined('DEBUG') && DEBUG) {
	$GLOBALS['NODEBUG'] = false;
	$GLOBALS['AJAX'] = false;
	$_GET['token'] = $_SESSION['token'];
	$_GET['keep_token'] = true;
	$_POST = $_GET;
	$DEBUG = true;
	call('AJAX HELPER');
	call($_POST);
}


// run the index page refresh checks
if (isset($_POST['timer'])) {
	$message_count = (int) Message::check_new($_SESSION['player_id']);
	$turn_count = (int) Game::check_turns($_SESSION['player_id']);
	echo $message_count + $turn_count;
	exit;
}


// run registration checks
if (isset($_POST['validity_test'])) {
#	if (('email' == $_POST['type']) && ('' == $_POST['value'])) {
#		echo 'OK';
#		exit;
#	}

	$player_id = 0;
	if ( ! empty($_POST['profile'])) {
		$player_id = (int) $_SESSION['player_id'];
	}

	switch ($_POST['validity_test']) {
		case 'username' :
		case 'email' :
			$username = '';
			$email = '';
			${$_POST['validity_test']} = sani($_POST['value']);

			$player_id = (isset($_POST['player_id']) ? (int) $_POST['player_id'] : 0);

			try {
				Player::check_database($username, $email, $player_id);
			}
			catch (MyException $e) {
				echo $e->getCode( );
				exit;
			}
			break;

		default :
			break;
	}

	echo 'OK';
	exit;
}


// run the in game chat
if (isset($_POST['chat'])) {
	try {
		if ( ! isset($_SESSION['game_id'])) {
			$_SESSION['game_id'] = 0;
		}

		$Chat = new Chat((int) $_SESSION['player_id'], (int) $_SESSION['game_id']);
		$Chat->send_message($_POST['chat'], isset($_POST['private']), isset($_POST['lobby']));
		$return = $Chat->get_box_list(1);
		$return = $return[0];
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run the invites stuff
if (isset($_POST['action']) && ('delete' == $_POST['action'])) {
	try {
		Game::delete($_POST['game_id']);
		echo 'Game Deleted';
	}
	catch (MyEception $e) {
		echo 'ERROR: Could not delete game';
	}

	exit;
}


// init our game
$Game = new Game((int) $_SESSION['game_id']);


// run the game refresh check
if (isset($_POST['refresh'])) {
	echo $Game->last_move;
	exit;
}

// Used by game.js to detect when the opponent has fired.
// Returns all rows from the shots table.
if (isset($_POST['get_shots'])) {
	$mysql = Mysql::get_instance();

	$game_id = $_SESSION['game_id'];
	$response = $mysql->fetch_assoc("SELECT * FROM `shots`
																	 WHERE `id` = ( SELECT MAX(`id`) FROM `shots` )
																	 AND `game_id` = $game_id
																	 LIMIT 1;");

	/*if (count($response) > 0) {
		// Delete all shots of this game in case there is more than one.
		$mysql->delete("shots", "WHERE shots.game_id = $game_id");
	}*/

	// Fill in the hit.
	$response['hit'] = $Game->test_hit($response['coordinate']);

	echo json_encode($response);
	exit;
}

if (isset($_POST['shot'])) {
	$coordinate = $_POST['shot'];
	$game_id = $_SESSION['game_id'];
	$args = [
		'player_id' => (int)$_SESSION['player_id'],
		'game_id' => $game_id,
		'coordinate' => $coordinate,
	];

	$response = new stdClass();
	$response->value = $Game->test_hit($coordinate, true);
	echo json_encode($response);

	Mysql::get_instance()->insert('shots', $args);
	exit;
}

if (isset($_POST['focus'])) {
	$post = $_POST['focus'];
	$column = $Game->get_my_color() . '_focused';

	Mysql::get_instance()->query("UPDATE `bs2_game` SET $column = $post WHERE `game_id` = $Game->id");
	exit;
}

if (isset($_POST['hide_board'])) {
	$value = $_POST['hide_board'] == 'true' ? 1 : 0;
	$row = 'hide_' . $Game->get_my_color();
	Mysql::get_instance()->query("UPDATE `bs2_game` SET $row = $value WHERE `game_id` = $Game->id");
	exit;
}


// run the ship count clicks
if (isset($_POST['shipcheck'])) {
	try {
		echo $Game->get_sunk_ships($_POST['id']);
	}
	catch (MyException $e) {
		echo 'ERROR';
	}

	exit;
}


// do some more validity checking for the rest of the functions

if (empty($DEBUG) && empty($_POST['notoken'])) {
	test_token( ! empty($_POST['keep_token']));
}


if ($_POST['game_id'] != $_SESSION['game_id']) {
	echo 'ERROR: Incorrect game id given';
	exit;
}


// run the board setup
if (isset($_POST['method'])) {
	$return = array( );

	try {
		if (isset($_POST['done'])) {
			$Game->setup_done( );
			$return['redirect'] = (($Game->test_ready( )) ? 'game.php?id='.$Game->id : 'invite.php');
		}
		else {
			switch ($_POST['method']) {
				case 'clear':
					$Game->setup_action('clear_board');
					break;

				case 'random':
					$Game->setup_action('random_board');
					break;

				case 'between':
					list($value1, $value2) = explode(':', $_POST['value']);
					$Game->setup_action('boat_between', $value1, $value2);
					break;

				case 'single_boat':
					$Game->setup_action('single_boat', $_POST['value']);
					break;

				case 'random_boat':
					$Game->setup_action('random_boat', $_POST['value']);
					break;

				case 'remove':
					$Game->setup_action('remove_boat', $_POST['value']);
					break;
			} // end method switch

			$return['board'] = $Game->get_board_html('first', true);
			$return['boats'] =  $Game->get_boats_html();
			$return['missingBoats'] = $Game->get_missing_boats(true);
			$return['fleetType'] = $Game->fleet_type;
		}
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}



// make sure we are the player we say we are
// unless we're an admin, then it's ok
$player_id = (int) $_POST['player_id'];
if (($player_id != $_SESSION['player_id']) && ! $GLOBALS['Player']->is_admin) {
	echo 'ERROR: Incorrect player id given';
	exit;
}


// run the 'Nudge' button
if (isset($_POST['nudge'])) {
	$return = array( );
	$return['token'] = $_SESSION['token'];

	try {
		$Game->nudge($player_id);
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run the 'Resign' button
if (isset($_POST['resign'])) {
	$return = array( );
	$return['token'] = $_SESSION['token'];

	try {
		$Game->resign($_SESSION['player_id']);
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}

function post($url, $data) {
	// use key 'http' even if you send the request to https://...
	$options = array(
		'http' => array(
			'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => 'POST',
			'content' => http_build_query($data),
		),
	);

	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	return $result;
}

// run the shots
if (isset($_POST['shots'])) {
	$return = array( );
	$return['token'] = $_SESSION['token'];

	// clean up the shots
	$_POST['shots'] = explode(',', trim($_POST['shots'], ', '));

	$args = array();
	try {
		$Game->do_shots($_POST['shots']);
		$return['action'] = 'RELOAD';
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}
