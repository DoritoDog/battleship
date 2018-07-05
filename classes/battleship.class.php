<?php
/*
+---------------------------------------------------------------------------
|
|   battleship.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to play the game of Battleship, it cares not about
|	database structure or the goings on of the website, only about Battleship
|
+---------------------------------------------------------------------------
|
|   > Battleship Board module
|   > Date started: 2009-05-12
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

class Battleship
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** protected property board
	 *		Holds the game board
	 *
	 * @var string
	 */
	protected $board;


	/** protected property target_human
	 *		Holds the human readable target
	 *		(A5, E2, J10, etc)
	 *
	 * @var string
	 */
	protected $target_human;


	/** protected property target_index
	 *		Holds the computer readable
	 *		string index (4, 41, 99, etc)
	 *
	 * @var int
	 */
	protected $target_index;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param string optional board
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($board = null)
	{
		call(__METHOD__);

		$this->clear_board( );

		if ( ! empty($board)) {
			try {
				$this->_set_board($board);
			}
			catch (MyException $e) {
				throw $e;
			}
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
	 */
	public function __get($property)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		return $this->$property;
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
	 */
	public function __set($property, $value)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		switch ($property) {
			case 'board' :
				try {
					$this->_set_board($value);
				}
				catch (MyException $e) {
					throw $e;
				}
				break;

			default :
				// do nothing
				break;
		}

		$this->$property = $value;
	}


	/** public function __toString
	 *		Returns the ascii version of the board
	 *		when asked to output the object
	 *
	 * @param void
	 * @return string ascii version of the board
	 */
	public function __toString( )
	{
		return $this->get_board_ascii( );
	}


	/** public function clean_board
	 *		Cleans the board of any visible boats
	 *		because then you'd just aim at the boats...
	 *
	 * @param void
	 * @return string cleaned board
	 */
	public function clean_board( )
	{
		return preg_replace('/[a-t]/i', '0', $this->board);
	}


	/** public function clear_board
	 *		Clears the board by setting all
	 *		squares to '0'
	 *
	 * @param void
	 * @action clears the board
	 * @return void
	 */
	public function clear_board( )
	{
		call(__METHOD__);

		$this->board = str_repeat('0', 100);
	}


	/** Tests to see if the selected coordinate will result in a hit.
	 * @return bool
	 */
	public function test_hit($coordinate, $return_string = false) {
		call(__METHOD__);

		// get the item at the location
		$site = $this->board[$coordinate];
		
		// test the location for previous action
		switch ($site) {
			// if we alreay shot this target
			case 'X' : // no break
			case 'Y' :
				throw new MyException(__METHOD__.': Already shot that target');
				return $return_string ? 'false' : false;
				break;
				
			// if we missed
			case '0' :
				return $return_string ? 'false' : false;
				break;

			// anything else must be a hit
			default :
				return $return_string ? 'true' : true;
				break;
		}
	}


	/** public function do_shot
	 *		Performs a shot and returns
	 * 		true on hit, and false on miss
	 *
	 * @param string human readable target
	 * @return bool hit
	 */
	public function do_shot($target)
	{
		call(__METHOD__);

		try {
			$target = $this->target_to_index($target);
		}
		catch (MyException $e) {
			throw $e;
		}

		// get the item at the location
		$site = $this->board[$this->target_index];

		// test the location for previous action
		switch ($site) {
			// if we alreay shot this target
			case 'X' : // no break
			case 'Y' :
				throw new MyException(__METHOD__.': Already shot that target');
				break;

			// if we missed
			case '0' :
				call('--MISS--');
				$this->_put_miss( );
				return false;
				break;

			// anything else must be a hit
			default :
				call('--HIT--');
				$this->_put_hit( );
				return true;
				break;
		} // end site test switch
	}


	/** public function random_board
	 *		Places all 5 boats on the board randomly
	 *
	 * @param void
	 * @action randomly fills the board
	 * @return void
	 */
	public function random_board($is_russian = false)
	{
		call(__METHOD__);

		// clear the board
		$this->clear_board();
		// 20 | 17

		// go through the array of boat lengths
		$sizes  = $is_russian ? array(4, 3, 3, 2, 2, 2, 1, 1, 1, 1) : array(5, 4, 3, 3, 2);
		foreach ($sizes as $key => $size) {
			try {
				$this->random_boat($size, $is_russian);
			}
			catch (MyException $e) {
				$code = $e->getCode( );

				if (103 == $code) { // this boat already used
					continue;
				}

				throw $e;
			}
		} // end foreach size loop
	}


	/** public function get_board_ascii
	 *		Gets an ascii version of the board
	 *		for debuging
	 *
	 * @param void
	 * @return string ascii version of the board
	 */
	public function get_board_ascii($board = null)
	{
		call(__METHOD__);

		if (is_null($board)) {
			$board = $this->board;
		}

		$output = '';
		for ($i = 0; $i < 10; ++$i) {
			for ($j = 0; $j < 10; ++$j) {
				if ('0' != $board[($i * 10) + $j]) {
					$output .= $board[($i * 10) + $j] . ' ';
				}
				else {
					$output .= '. ';
				}
			}

			$output = trim($output) . "\n";
		}

		return $output;
	}


	/** public function get_missing_boats
	 *		Finds all the boats that need to be placed
	 *		format: $boats[$bow] = $size
	 *
	 * @param void
	 * @return array missing boats
	 */
	public function get_missing_boats( )
	{
		call(__METHOD__);

		$boats = array( );

		if (0 == preg_match('/[a-e]/i', $this->board)) $boats['a'] = 5; // carrier
		if (0 == preg_match('/[f-i]/i', $this->board)) $boats['f'] = 4; // battleship
		if (0 == preg_match('/[jkl]/i', $this->board)) $boats['j'] = 3; // cruiser
		if (0 == preg_match('/[mno]/i', $this->board)) $boats['m'] = 3; // submarine
		if (0 == preg_match('/[pq]/i',  $this->board)) $boats['p'] = 2; // destroyer
		call($boats);

		return $boats;
	}

	public function get_missing_boats_russian() {
		call(__METHOD__);

		$boats = array( );

		if (0 == preg_match('/[abcd]/i', $this->board)) $boats['a'] = 4;

		if (0 == preg_match('/[efg]/i', $this->board)) $boats['e'] = 3;
		if (0 == preg_match('/[hij]/i', $this->board)) $boats['h'] = 3;

		if (0 == preg_match('/[kl]/i',  $this->board)) $boats['k'] = 2;
		if (0 == preg_match('/[mn]/i',  $this->board)) $boats['m'] = 2;
		if (0 == preg_match('/[op]/i',  $this->board)) $boats['o'] = 2;

		if (0 == preg_match('/[q]/i',  $this->board)) $boats['q'] = 1;
		if (0 == preg_match('/[r]/i',  $this->board)) $boats['r'] = 1;
		if (0 == preg_match('/[s]/i',  $this->board)) $boats['s'] = 1;
		if (0 == preg_match('/[t]/i',  $this->board)) $boats['t'] = 1;

		call($boats);

		return $boats;
	}


	/** public function get_salvo_shots
	 *		Returns the number of shots available
	 *		in 'Salvo' mode (one per boat)
	 *
	 * @param void
	 * @return int number of shots
	 */
	public function get_salvo_shots( )
	{
		$missing = $this->get_missing_boats( );
		return (5 - count($missing));
	}


	/** public function boat_between
	 *		Places a boat between the given squares
	 *
	 * @param int computer string index
	 * @param int computer string index
	 * @action places the boat
	 * @return bool success
	 */
	public function boat_between($value1, $value2, $is_ruski = false)
	{
		call(__METHOD__);

		try {
			$value1 = $this->target_to_index($value1);
			$value2 = $this->target_to_index($value2);
		}
		catch (MyException $e) {
			throw $e;
		}
		call('val1 = '.$value1);
		call('val2 = '.$value2);

		// make sure we have data
		if ((empty($value1) && (0 !== $value1)) || (empty($value2) && (0 !== $value2))) {
			throw new MyException(__METHOD__.': Missing required data');
			return false;
		}

		// make sure the two points are in a straight line from each other
		// and grab our orientation: 0 = horiz; 1 = vert
		if (floor($value1 * 0.1) == floor($value2 * 0.1)) {
			// because the two points have the same 10s value, they must be on the same horizontal line
			$orient = 0;
		}
		else {
			if (($value1 % 10) == ($value2 % 10)) {
				$orient = 1;
			}
			else {
				throw new MyException(__METHOD__.': The points given are not in a straight line');
			}
		}

		// horiz: $size = abs($value1 - $value2) + 1;
		// vert:  $size = (abs($value1 - $value2) * 0.1) + 1;
		// must use round here, because we run into rounding errors if we just typecast to int
		$size = round((abs($value1 - $value2) * (1 - (0.9 * $orient))) + 1);

		if (!$is_ruski) {
			// make sure we need this size
			$boats = $this->get_missing_boats();

			if ( ! in_array($size, $boats)) {
				$errorMsg = ': That boat is not available (already placed, or wrong size) ('.$size.')';
				throw new MyException(__METHOD__ . $errorMsg);
				return false;
			}
		}

		// find the front of the boat
		$bow = ($value1 < $value2) ? $value1 : $value2;

		// place the boat
		if ($is_ruski)
			return $this->_place_boat_russian($bow, $size, $orient);
		else
			return $this->_place_boat($bow, $size, $orient);
	}


	/** Used during ruski setup after all the longer boats have been placed. */
	public function single_boat($coordinates) {
		try {
			$this->_place_boat_russian($coordinates, 1, 0);
		} catch(Exception $e) {
			var_dump($e);
		}
	}


	/** public function random_boat
	 *		Places the given boat on the board randomly
	 *
	 * @param int boat size (5,4,3,2)
	 * @action randomly fills the board
	 * @return void
	 */
	public function random_boat($args, $is_russian = false)
	{
		$args = explode(',', $args);
		$size = $args[0];
		$id = $args[1];
		call(__METHOD__);
		call($size);

		// find out which boats are not yet on the board
		$boats = $this->get_missing_boats();

		if (!$is_russian && !in_array($size, $boats)) {
			throw new MyException(__METHOD__.': That boat is already on the board');
		}

		// init the placed flag
		$placed = false;
		while ( ! $placed) {
			// make a random number between 0-99
			$bow = mt_rand(0, 99);

			// get another random number between 0-1 (horiz, vert)
			$orient = (int) round(mt_rand(0, 100) * .01);

			// see if we can place a boat there (do it if we can)
			try {
				if ($is_russian)
					$this->_place_boat_russian($bow, $size, $orient, $id);
				else
					$this->_place_boat($bow, $size, $orient, $id);

				$placed = true;
			}
			catch (MyException $e) {
				$code = $e->getCode( );
				$allowed = array(
					101, // boat off the board
					102, // another boat in the way
				);

				if ( ! in_array($code, $allowed)) {
					throw $e;
				}
			}
		} // end while not placed loop
	}


	/** public function remove_boat
	 *		Removes the boat on the given square
	 *
	 * @param int computer string index
	 * @action removes the boat
	 * @return bool success
	 */
	public function remove_boat($square, $is_russian = false)
	{
		call(__METHOD__);

		try {
			$square = $this->target_to_index($square);
		}
		catch (MyException $e) {
			throw $e;
		}

		// get the value of the square
		$item = $this->board[$square];

		// if there was no boat at that location
		if ('0' == $item) {
			throw new MyException(__METHOD__.': There is no boat there');
			return false;
		}

		if ($is_russian) {
			// get the boat pattern based on the value of the square
			switch (strtolower($item)) {
				case 'a' : // no break
				case 'b' : // no break
				case 'c' : // no break
				case 'd' : // no break
					$pattern = '/[a-d]/i';
					break;

				case 'e' : // no break
				case 'f' : // no break
				case 'g' : // no break
					$pattern = '/[efg]/i';
					break;

				case 'h' : // no break
				case 'i' : // no break
				case 'j' :
					$pattern = '/[hij]/i';
					break;

				case 'k':
				case 'l':
					$pattern = '/[kl]/i';
					break;

				case 'm':
				case 'n':
					$pattern = '/[mn]/i';
					break;

				case 'o':
				case 'p':
					$pattern = '/[op]/i';
					break;

				case 'q' : // no break
					$pattern = '/[q]/i';
					break;
				case 'r':
						$pattern = '/[r]/i';
						break;
				case 's':
						$pattern = '/[s]/i';
						break;
				case 't':
						$pattern = '/[t]/i';
						break;

				default  :
					throw new MyException(__METHOD__.': Unknown item found');
					return false;
					break;
			}
		}
		else {
			// get the boat pattern based on the value of the square
			switch (strtolower($item)) {
				case 'a' : // no break
				case 'b' : // no break
				case 'c' : // no break
				case 'd' : // no break
				case 'e' :
					$pattern = '/[a-e]/i';
					break;

				case 'f' : // no break
				case 'g' : // no break
				case 'h' : // no break
				case 'i' :
					$pattern = '/[f-i]/i';
					break;

				case 'j' : // no break
				case 'k' : // no break
				case 'l' :
					$pattern = '/[jkl]/i';
					break;

				case 'm' : // no break
				case 'n' : // no break
				case 'o' :
					$pattern = '/[mno]/i';
					break;

				case 'p' : // no break
				case 'q' :
					$pattern = '/[pq]/i';
					break;

				default  :
					throw new MyException(__METHOD__.': Unknown item found');
					return false;
					break;
			}
		}

		// remove the boat
		$this->board = preg_replace($pattern, '0', $this->board);

		return true;
	}


	/** public function target_to_index
	 *		Converts a human target (E5) to
	 *		a computer string index
	 *
	 * @param optional string human target (E5)
	 * @return int computer string index
	 */
	public function target_to_index($target = false)
	{
		call(__METHOD__);
		call($target);

		// if it's already an index, just return it
		if ((string) $target === str_pad((string) (int) $target, 2, '0', STR_PAD_LEFT)) {
			$this->target_index = (int) $target;
			return $this->target_index;
		}

		if (false === $target) {
			$target = $this->target_human;
		}
		else {
			try {
				$target = $this->_validate_target($target);
				$this->target_human = $target;
			}
			catch (MyException $e) {
				throw $e;
			}
		}

		switch (strtoupper($target[0])) {
			case 'A' : $index = 00; break;
			case 'B' : $index = 10; break;
			case 'C' : $index = 20; break;
			case 'D' : $index = 30; break;
			case 'E' : $index = 40; break;
			case 'F' : $index = 50; break;
			case 'G' : $index = 60; break;
			case 'H' : $index = 70; break;
			case 'I' : $index = 80; break;
			case 'J' : $index = 90; break;
		}

		$number = (int) substr($target, 1);

		$this->target_index = (int) ($index + ($number - 1));

		return $this->target_index;
	}


	/** protected function _place_boat
	 *		Places the a boat on the board
	 *
	 * @param int bow location index
	 * @param int size of the boat
	 * @param int orientation of the boat (0 = horiz, 1 = vert)
	 * @action places the boat
	 * @return void
	 */
	protected function _place_boat($bow, $size, $orient, $id = null)
	{
		call(__METHOD__);
		call($bow);
		call($size);
		call($orient);

		// get a local copy of the board
		// so we don't bork things later if it fails
		$board = $this->board;

		$boats = $this->get_missing_boats();
		$bows = array_flip($boats);

		if (empty($bows[$size])) {
			throw new MyException(__METHOD__.': This boat is not available to place', 103);
		}
		$bow_value = is_null($id) ? $bows[$size] : $id;

		// change the testing coefficient depending on our orientation
		$bow_test = ((bool) $orient) ? floor($bow * 0.1) : ($bow % 10);

		if ((0 > $bow) || (10 >= ($bow_test + $size))) {
			//echo $size . '<br>';
			for ($i = 0; $i < $size; ++$i) {
				// set the current index based on our orientation
				$cur_index = ($orient) ? ($bow + ($i * 10)) : ($bow + $i);
				// echo $size . ' ' . $cur_index . "<br>";

				// if we found a boat in the way
				if ('0' != $board[$cur_index]) {
					throw new MyException(__METHOD__.': There is another boat in the way', 102);
				}

				// it's a temp board, so just start placing the boat
				// even if we haven't tested all locations
				// if it throws an exception, no harm done
				$board[$cur_index] = ($orient) ? strtoupper($bow_value) : strtolower($bow_value);
				++$bow_value; // alpha characters increment as well
			}
			call($board);

			// save the local copy
			$this->board = $board;
		}
		else {
			throw new MyException(__METHOD__.': The boat does not fit in that location', 101);
		}
	}

	protected function _does_assoc_array_contain($array, $value) {
		foreach ($array as $key => $val) {
			if ($val == $value)
				return true;
		}

		return false;
	}

	protected function _get_first_assoc_key($array, $value) {
		foreach ($array as $key => $val) {
			if ($val == $value)
				return $key;
		}

		return null;
	}

	protected function _place_boat_russian($bow, $size, $orient, $id = null)
	{
		call(__METHOD__);
		call($bow);
		call($size);
		call($orient);

		// get a local copy of the board
		// so we don't bork things later if it fails
		$board = $this->board;

		$boats = $this->get_missing_boats_russian();
		
		//  Check if the size is available to be placed.
		if (!$this->_does_assoc_array_contain($boats, $size))
			throw new MyException(__METHOD__.': This boat is not available to place', 103);

		$bow_value = is_null($id) ? $this->_get_first_assoc_key($boats, $size) : $id;
		$bow_test = ((bool) $orient) ? floor($bow * 0.1) : ($bow % 10);
		
		if ((0 > $bow) || (10 >= ($bow_test + $size))) {
			//echo $size . '<br>';
			for ($i = 0; $i < $size; ++$i) {
				// set the current index based on our orientation
				$cur_index = ($orient) ? ($bow + ($i * 10)) : ($bow + $i);
				// echo $size . ' ' . $cur_index . "<br>";

				// if we found a boat in the way
				if ('0' != $board[$cur_index]) {
					throw new MyException(__METHOD__.': There is another boat in the way', 102);
				}

				// it's a temp board, so just start placing the boat
				// even if we haven't tested all locations
				// if it throws an exception, no harm done
				$board[$cur_index] = ($orient) ? strtoupper($bow_value) : strtolower($bow_value);
				++$bow_value; // alpha characters increment as well
			}
			call($board);

			// save the local copy
			$this->board = $board;
		}
		else {
			throw new MyException(__METHOD__.': The boat does not fit in that location', 101);
		}
	}


	/** protected function _put_hit
	 *		Puts a hit at the current index
	 *
	 * @param void
	 * @action adds a hit at the current index
	 * @return void
	 */
	protected function _put_hit( )
	{
		call(__METHOD__);

		$this->board[$this->target_index] = 'X';
	}


	/** protected function _put_miss
	 *		Puts a miss at the current index
	 *
	 * @param void
	 * @action adds a miss at the current index
	 * @return void
	 */
	protected function _put_miss( )
	{
		call(__METHOD__);

		$this->board[$this->target_index] = 'Y';
	}


	/** protected function _validate_target
	 *		Validates the given human readable target
	 *		and saves it internally
	 *
	 * @param string human readable target
	 * @action validates and stores human readable target
	 * @return string human readable target
	 */
	protected function _validate_target($target)
	{
		call(__METHOD__);

		// make sure the first character is A-J
		// and the second is 1-10

		$target = strtoupper((string) $target);

		if (0 == preg_match('/^[A-J]/', $target)) {
			throw new MyException(__METHOD__.': Target has invalid first character');
		}

		// strip off the first character to get the number
		$number = (int) substr($target, 1);

		if ((0 >= $number) || (11 <= $number)) {
			throw new MyException(__METHOD__.': Target has invalid second characters');
		}

		// if it makes it here, it's all good
		$this->target_human = $target;
		return $this->target_human;
	}


	/** protected function _set_board
	 *		Sets the board
	 *
	 * @param string board
	 * @action validation
	 * @return void
	 */
	protected function _set_board($board)
	{
		call(__METHOD__);

		$board = (string) $board;

		if (100 != strlen($board)) {
			throw new MyException(__METHOD__.': The board given is not the right size');
		}

		$this->board = $board;
	}

} // end of Battleship class

