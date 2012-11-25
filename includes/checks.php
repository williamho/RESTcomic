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
?>

