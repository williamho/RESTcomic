<?php
	/**
	 * Check if string contains alphanumeric and underscore characters
	 *
	 * @param string $str The string being checked
	 */
	function check_alphanum_underscore($str) {
		return preg_match('/^[a-zA-Z0-9_]+$/',$str);
	}
?>

