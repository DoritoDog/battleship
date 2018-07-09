<?php

require_once 'includes/inc.global.php';

// grab the game id
if (isset($_GET['id'])) {
	$_SESSION['game_id'] = (int) $_GET['id'];
}
else {
	if ( ! isset($_SESSION['game_id'])) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			Flash::store('No Game Id Given !');
		}
		else {
			call('NO GAME ID GIVEN');
		}

		exit;
	}
}

// ALL GAME FORM SUBMISSIONS ARE AJAXED THROUGH /scripts/setup.js

// load the game
// always refresh the game data, there may be more than one person online
try {
	$Game = new Game($_SESSION['game_id']);

	if ($Game->test_ready( )) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			session_write_close( );
			header('Location: game.php?id='.$_SESSION['game_id'].$GLOBALS['_&_DEBUG_QUERY']);
		}
		else {
			call('GAME IS PLAYING, REDIRECTED TO game.php?id='.$_SESSION['game_id'].$GLOBALS['_&_DEBUG_QUERY'].' AND QUIT');
		}

		exit;
	}
	elseif (isset($_GET['accept'])) {
		$Game->set_state('Placing');
	}
}
catch (MyException $e) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('Error Accessing Game !');
	}
	else {
		call('ERROR ACCESSING GAME');
	}

	exit;
}

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

$against = $Game->has_opponent() ? 'Waiting for a player...' : $Game->name;
$meta['title'] = GAME_NAME.' Game #'.$_SESSION['game_id'].' vs '.$against.' Setup';
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />
	<style>' . $style . '</style>
	<script type="text/javascript">//<![CDATA[
		var game_id = "'.$_SESSION['game_id'].'";
		var color = "'.$Game->get_my_color( ).'";
		var rootUrl = "' . $GLOBALS['_ROOT_URI'] . '";
		var theme = "' . $theme['filesdir'] . '";
		var shipGraphics = ' . json_encode($shipGraphics) . ';
	//]]></script>
	<script type="text/javascript" src="scripts/setup.js"></script>
';

$hints = array(
	'Here you can set up your board.' ,
	'Click any two squares to place a boat between those two squares.' ,
	'Click any unplaced boat to randomly place that boat.' ,
	'Click any placed boat to remove that boat.' ,
	'"Random Board" will randomly place ALL the boats, not just the unplaced ones.' ,
	'<span class="warning">NOTE</span>: When you are satisfied with your setup, you must click "Done" to finalize it.' ,
);

$contents = '';

$contents .= '<div id="board_wrapper">'.$Game->get_board_html('first')."</div>\n\n";

// the forms we'll need to submit
$contents .= '
	<div class="forms">
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="formdiv">
			<input type="hidden" name="notoken" value="1" />
			<input type="hidden" name="game_id" value="'.$_SESSION['game_id'].'" />
			<input type="hidden" name="method" id="method" value="" />
			<input type="hidden" name="value" id="value" value="" />
			<input type="button" class="button" id="clear" value="Clear Board" />
			<input type="button" class="button" id="random" value="Random Board" />
			<input type="button" class="button" id="done" value="Done" />
		</div></form>
	</div>';

$contents .= '<div id="boat_wrapper">'.$Game->get_boats_html()."</div>\n\n";

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
echo get_footer();

