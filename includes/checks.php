<?php
	/**
	 * Check if string contains alphanumeric and underscore characters
	 * @param string $str The string being checked
	 * @return bool True if string contains only alphanumerics and underscores
	 */
	function checkAlphanumUnderscore($str) {
		return preg_match('/^[a-zA-Z0-9_]+$/',$str);
	}

	/**
	 * Check if date is valid  
	 * @param string $timestamp A timestamp, in whatever format
	 * @return mixed If null input, outputs current time. 
	                 If valid input, converts to mySQL format. 
					 If invalid input, returns null.
	 */
	function convertDatetime($timestamp) {
		if (!$timestamp)
			return date(Config::timeFormat);
		else if ($unixTime = strtotime($timestamp))
			return date(Config::timeFormat,$unixTime);
		else
			return null;
	}

	// toAscii via http://cubiq.org/the-perfect-php-clean-url-generator
	setlocale(LC_ALL, 'en_US.UTF8');
	function makeSlug($str, $replace='\'', $delimiter='-') {
		if( !empty($replace) ) {
			$str = str_replace((array)$replace, ' ', $str);
		}

		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		return $clean;
	}

	function intArrayToString(array $arr) {
		foreach ($arr as &$el) {
			if (!is_int($el) && !ctype_digit($el)) 
				throw new APIError(2001);
			$el = (int)$el;
		}
		return implode(',',$arr);
	}

	function slugifyArray(array $arr) {
		foreach ($arr as &$el)
			$el = makeSlug($el);
		return $arr;
	}

	function slugArrayToString(array $arr) {
		foreach ($arr as &$el)
			$el = '\''.makeSlug($el).'\'';
		return implode(',',$arr);
	}

	function initArrayKeys($keys, $val=null) {
		$arr = array();
		foreach($keys as $key)
			$arr[$key] = $val;

		return $arr;
	}

	function toColor($num) {
		return "#".substr("000000".dechex($num),-6);
	}

	function stringToBool($str) {
		$str = trim(strtolower($str));
		switch($str) {
		case 'false': 
		case 'no': 
		case '0':
		case null:
		case 0:
			return false;
		default:
			return true;
		}
	}

