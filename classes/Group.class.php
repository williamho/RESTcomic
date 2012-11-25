<?php
require_once 'includes/checks.php';

class Group {
	public $id;
	public $name;

	// Permissions
	const PERM_MAKE_NONE = 0;	// Cannot make posts/comments
	const PERM_MAKE_HIDDEN = 1; // Posts/comments hidden by default
	const PERM_MAKE_OK = 2;	// No restriction to making posts/comments

	const PERM_EDIT_NONE = 0; 	// Cannot edit any posts/comments
	const PERM_EDIT_OWN = 1;	// Can edit own posts/comments
	const PERM_EDIT_GROUP = 2;	// Can edit content made by group members
	const PERM_EDIT_ALL = 3;	// Can edit anybody's content

	public $permissions;
	public static $limits = array(
		'name' => 64
	);

	/**
	 * Construct the group object
	 * @param int $id The group's id
	 * @param string $name Group name (alphanumeric and underscores only)
	 * @param int $mp 'make posts' permission
	 * @param int $ep 'edit posts' permission
	 * @param int $mc 'make comments' permission
	 * @param int $ec 'edit comments' permission
	 * @param boolean $a Is this an admin group?
	 */
	function __construct($id, $name, $mp, $ep, $mc, $ec, $a) {
		$errors = new APIError('Group errors');
		
		if (!is_int($this->id = $id))
			$errors->addError(1101); // Invalid id
		$this->id = $id;

		// Check group name
		if (!self::checkLength($name,'name'))
			$errors->addError(1102); // name too long
		if (!checkAlphanumUnderscore($name))
			$errors->addError(1103); // name w/ invalid chars
		$this->name = $name;

		// Check permissions
		if (!is_int($mp) || $mp < 0 || $mp > self::PERM_MAKE_OK)
			$errors->addError(1104); // Invalid mp value 
		if (!is_int($ep) || $ep < 0 || $ep > self::PERM_EDIT_ALL)
			$errors->addError(1105); // Invalid ep value
		if (!is_int($mc) || $mc < 0 || $mc > self::PERM_MAKE_OK)
			$errors->addError(1106); // Invalid mc value
		if (!is_int($ec) || $ec < 0 || $ec > self::PERM_EDIT_ALL)
			$errors->addError(1107); // Invalid ec value
		$this->permissions = array(
			'make_post' => $mp,
			'edit_post' => $ep,
			'make_comment' => $mc,
			'edit_comment' => $ec, 
			'admin' => (bool)$a
		);

		if (!$errors->isEmpty())
			throw $errors;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

?>
