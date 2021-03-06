
var reload = true; // do not change this
var refresh_timer = false;
var refresh_timeout = 2001; // 2 seconds
var board_storage = false;

// no doc.ready, this file is loaded at the bottom of the page

if (('finished' == state) || ('paused' == state)) {
	$('div#boards').removeClass('active');
}

board_storage = $('div.active div.first');
$('div.active div.first').replaceWith('<div class="noboard first panic" style="cursor:pointer;" title="Click to show board">HIDDEN</div>');

// PANIC BUTTON
// hides the players board when it's clicked in
// case the opponent walked in the room
$('div.active')
	.on('click', 'div.first', toggleBoard)
	.find('div.first')
	.css('cursor', 'pointer')
	.attr('title', 'Click to hide board');

function toggleBoard () {
	$this = $('div.first');
	if (board_storage) {
		$this.replaceWith(board_storage);
		board_storage = false;
		document.cookie = "hideBoard=0";
	}
	else {
		board_storage = $this;
		$this.replaceWith('<div class="noboard first panic" style="cursor:pointer;" title="Click to show board">HIDDEN</div>');
		document.cookie = "hideBoard=1";
	}

	//$.post('ajax_helper.php', { hide_board: !hideBoard }, (data, status) => { });
	$('div.active').find('div.first').css('cursor', 'pointer').attr('title', 'Click to hide board');
}

// set the previous shots
var id = 0;
for (var i in prev_shots) {
	id = prev_shots[i];

	if (10 > id) {
		id = '0' + id;
	}

	if (my_turn) {
		$('#dfd-' + id).addClass('prevshot');
	}
	else {
		$('#tgt-' + id).addClass('prevshot');
	}
}

let lastShotId = 0;

// make the board clicks work
if (my_turn) {
	$('div.active div.second div.row:not(div.top, div.bottom) div:not(div.side):not(div:has(img))').click(function (evnt) {
		var $this = $(this);
		var id = $this.attr('id').slice(4);
		let didHit = false;

		// are we adding or removing the square
		if ($this.hasClass('curshot')) { // removing square
			var $shots = $('#shots');
			var value = $shots.val();
			value = value.split(',');
			value.splice(value.indexOf(id), 1);
			value.join(',');
			$shots.val(value);
			++shots;
		}
		else { // adding square
			var $shots = $('#shots');
			var value = $shots.val();
			value = value.split(',');
			value.push(id);
			value.join(',');
			$shots.val(value);
			--shots;

			// Sending one hit for validation.
			$.post("ajax_helper.php", { shot: id }, (data, status) => {
				const response = JSON.parse(data);
				didHit = response.value === 'true' ? true : false;

				// update the shot markers
				update_shots();

				if (gameMode == 'Multi' && didHit) {
					shots++;
					$this.html('');
					$this.append('<img src="images/hit.gif" />');
				}
				else if (gameMode == 'Multi' && !didHit) {

					// Submitting all hits.
					$.ajax({
						type: 'POST',
						url: 'ajax_helper.php',
						data: $('form#game').serialize(),
						success: function (msg) {
							// if something happened, just reload
							if (msg[0] != '{') {
								alert('ERROR: AJAX failed!' + msg);
							}

							const reply = JSON.parse(msg);

							if (reply.error) {
								alert(reply.error);
							}

							if (reload) { window.location.reload(); }
							return;
						},
					});
				}

				// run the shots
				if (gameMode != 'Multi' && shots == 0) {
					if (debug) {
						window.location = 'ajax_helper.php' + debug_query + '&' + $('form#game').serialize();
						return;
					}

					$.ajax({
						type: 'POST',
						url: 'ajax_helper.php',
						data: $('form#game').serialize(),
						success: function (msg) {
							// if something happened, just reload
							if (msg[0] != '{') {
								alert('ERROR: AJAX failed!' + msg);
							}

							const reply = JSON.parse(msg);

							if (reply.error) {
								alert(reply.error);
							}

							if (reload) { window.location.reload(); }
							return;
						},
					});
				}
			});
		}

		$this.toggleClass('curshot');
	}).css('cursor', 'pointer');
}


// nudge button
$('#nudge').click(function () {
	if (confirm('Are you sure you wish to nudge this person?')) {
		if (debug) {
			window.location = 'ajax_helper.php' + debug_query + '&' + $('form#game').serialize() + '&nudge=1';
			return;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: $('form#game').serialize() + '&nudge=1',
			success: function (msg) {
				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				} else {
					alert('Nudge Sent');
				}

				if (reload) { window.location.reload(); }
			}
		});
	}

	return false;
});


// resign button
$('#resign').click(function () {
	if (confirm('Are you sure you wish to resign the game?')) {
		if (debug) {
			window.location = 'ajax_helper.php' + debug_query + '&' + $('form#game').serialize() + '&resign=1';
			return;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: $('form#game').serialize() + '&resign=1',
			success: function (msg) {
				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				}

				if (reload) { window.location.reload(); }
			}
		});
	}

	return false;
});


// chat box functions
$('#chatbox form').submit(function () {
	if ('' == $.trim($('#chatbox input#chat').val())) {
		return false;
	}

	if (debug) {
		window.location = 'ajax_helper.php' + debug_query + '&' + $('#chatbox form').serialize();
		return false;
	}

	$.ajax({
		type: 'POST',
		url: 'ajax_helper.php',
		data: $('#chatbox form').serialize(),
		success: function (msg) {
			// if something happened, just reload
			if ('{' != msg[0]) {
				alert('ERROR: AJAX failed');
				if (reload) { window.location.reload(); }
			}

			var reply = JSON.parse(msg);

			if (reply.error) {
				alert(reply.error);
			}
			else {
				var entry = '<dt><span>' + reply.create_date + '</span> ' + reply.username + '</dt>' +
					'<dd' + (('1' == reply.private) ? ' class="private"' : '') + '>' + reply.message + '</dd>';

				$('#chats').prepend(entry);
				$('#chatbox input#chat').val('');
			}
		}
	});

	return false;
});


// sunk ship display
$('span.ships').click(function () {
	var id = $(this).attr('id').slice(0, -6);

	if (debug) {
		window.location = 'ajax_helper.php' + debug_query + '&' + 'shipcheck=1&id=' + id;
		return false;
	}

	$.ajax({
		type: 'POST',
		url: 'ajax_helper.php',
		data: 'shipcheck=1&id=' + id,
		success: (msg) => { alert(msg) }
	});

	return false;
}).css('cursor', 'pointer');


// run the ajax refresher
if (!my_turn && ('finished' != state)) {
	ajax_refresh();

	// set some things that will halt the timer
	$('#chatbox form input').focus(function () {
		clearTimeout(refresh_timer);
	});

	$('#chatbox form input').blur(function () {
		if ('' != $(this).val()) {
			refresh_timer = setTimeout('ajax_refresh()', refresh_timeout);
		}
	});
}

update_shots();


function update_shots() {
	$('span.shots img').remove();
	for (var i = 0; i < shots; ++i) {
		$('span.shots').append('<img src="images/hit.gif" />');
	}
}

function playTurnSound() {
	var audio = new Audio('sounds/turn.mp3');
	audio.play();
}

var highestShotId = 0;

function ajax_refresh() {
	// no debug redirect, just do it

	// Keep checking for shots.
	$.post('ajax_helper.php', { get_shots: 1 }, (data, status) => {
		const response = JSON.parse(data);
		// If there are no shots, ajax_helper.php returns [].
		if (response.length !== 0 && response.id > highestShotId) {
			highestShotId = response.id;

			let flash = response.hit ? 'flash-hit' : 'flash-miss';
			$('#contents').addClass(flash);
			// Remove the class after one second.
			setTimeout(() => { $('#contents').removeClass(flash) }, 1000);
		}
	});

	$.ajax({
		type: 'POST',
		url: 'ajax_helper.php',
		data: 'refresh=1',
		success: function (msg) {
			if (msg != last_move) {
				// The turns have changed. Play the sound.
				playTurnSound();

				// don't just reload( ), it tries to submit the POST again
				if (reload) {
					setTimeout(() => { window.location = window.location.href; }, 1000);
				}
			}
		}
	});

	// successively increase the timeout time in case someone
	// leaves their window open, don't poll the server every
	// two seconds for the rest of time
	if (0 == (refresh_timeout % 5)) {
		refresh_timeout += Math.floor(refresh_timeout * 0.001) * 1000;
	}

	++refresh_timeout;

	refresh_timer = setTimeout('ajax_refresh()', refresh_timeout);
}

var updateFocus = true;
if (updateFocus) {
	$(window).blur(function () {
		$.post('ajax_helper.php', { focus: 0 }, (data, status) => { });
	});
	$(window).focus(function () {
		$.post('ajax_helper.php', { focus: 1 }, (data, status) => { });
	});
}

function countdown() {
	var countdownDate = new Date(lastMove).getTime();
	var x = setInterval(function () {
		var now = new Date().getTime();
		var difference = countdownDate - now;

		var days = Math.floor(difference / (1000 * 60 * 60 * 24));
		var hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
		var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
		var seconds = Math.floor((difference % (1000 * 60)) / 1000);

		days = days > 9 ? days : '0' + days;
		hours = hours > 9 ? hours : '0' + hours;
		minutes = minutes > 9 ? minutes : '0' + minutes;
		seconds = seconds > 9 ? seconds : '0' + seconds;

		if (difference < 0) {
			days = '00';
			hours = '00';
			minutes = '00';
			seconds = '00';
		}

		$('#days').html(days);
		$('#hours').html(hours);
		$('#minutes').html(minutes);
		$('#seconds').html(seconds);
	}, 1000);
}


window.onload = countdown;

function getCookie(key) {
	var decodedCookie = decodeURIComponent(document.cookie);
	var cookieArray = decodedCookie.split(';');
	for (let i = 0; i < cookieArray.length; i++) {
		let cookie = cookieArray[i];
		// Cookies have spaces after their semicolons.
		if (cookie.startsWith(' ' + key)) {
			let index = (cookie.indexOf('=') + 1);
			return cookie.substring(index);
		}
	}

	return false;
}

var lastPlayerId = getCookie('lastPlayerId');

// Check.
if (player_id != lastPlayerId && lastPlayerId != '') {
	alert('bug averted!');
	// if ($.browser.mozilla)
	location.reload();
}
else if (state != 'finished') {
	let hideBoard = getCookie('hideBoard') == 0 ? false : true;
	if (!hideBoard) {
		$('div.first').replaceWith(board_storage);
		board_storage = false;
		$('div.active').find('div.first').css('cursor', 'pointer').attr('title', 'Click to hide board');
	}
}

// Update the lastPlayerId as a cookie for next time.
document.cookie = "lastPlayerId=" + player_id;
