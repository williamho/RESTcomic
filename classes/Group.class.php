<?php
defined('API_PATH') or die('No direct script access.');

class Group {
	public $group_id;
	public $name;
	public $color;
	public $admin_perm;
	public $make_post_perm;
	public $edit_post_perm;
	public $make_comment_perm;
	public $edit_comment_perm;

	// Permissions
	const PERM_MAKE_NONE = 0;	// Cannot make posts/comments
	const PERM_MAKE_HIDDEN = 1; // Posts/comments hidden by default
	const PERM_MAKE_OK = 2;	// No restriction to making posts/comments

	const PERM_EDIT_NONE = 0; 	// Cannot edit any posts/comments
	const PERM_EDIT_OWN = 1;	// Can edit own posts/comments
	const PERM_EDIT_GROUP = 2;	// Can edit content made by group members
	const PERM_EDIT_ALL = 3;	// Can edit anybody's content

	public static $limits = array(
		'name' => 64
	);

	public function setValues($group_id, $name, $color, 
								$a, $mp, $ep, $mc, $ec) 
	{
		$this->group_id = $group_id;
		$this->name = $name;
		$this->color = $color;
		$this->admin_perm = $a;
		$this->make_post_perm = $mp;
		$this->edit_post_perm = $ep;
		$this->make_comment_perm = $mc;
		$this->edit_comment_perm = $ec;
	}

	public function getErrors() {
		$errors = new APIError();
		
		if (!is_int($this->group_id))
			$errors->addError(1101); // Invalid id

		// Check group name
		if (!self::checkLength($this->name,'name'))
			$errors->addError(1102); // name too long
		if (!checkAlphanumUnderscore($this->name))
			$errors->addError(1103); // name w/ invalid chars

		// Check color
		if (is_string($this->color)) 
			$this->color = hexdec($this->color);
		else if (!is_int($this->color))
			$errors->addError(1110); // Invalid color

		// Check permissions
		$mp = $this->make_post_perm;
		$ep = $this->edit_post_perm;
		$mc = $this->make_comment_perm;
		$ec = $this->edit_comment_perm;

		if (!is_int($mp) || $mp < 0 || $mp > self::PERM_MAKE_OK)
			$errors->addError(1104); // Invalid mp value 
		if (!is_int($ep) || $ep < 0 || $ep > self::PERM_EDIT_ALL)
			$errors->addError(1105); // Invalid ep value
		if (!is_int($mc) || $mc < 0 || $mc > self::PERM_MAKE_OK)
			$errors->addError(1106); // Invalid mc value
		if (!is_int($ec) || $ec < 0 || $ec > self::PERM_EDIT_ALL)
			$errors->addError(1107); // Invalid ec value
		$this->admin_perm = (bool)$this->admin_perm;

		if (!$errors->isEmpty())
			return $errors;
		return null;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

