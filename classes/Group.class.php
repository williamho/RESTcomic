<?php
require_once 'includes/checks.php';

class Group {
	public $id;
	public $name;

	// Permissions
	const PERM_MAKE_NONE = 0;	// Cannot make posts/comments
	const PERM_MAKE_HIDDEN = 1; // Posts/comments hidden by default
	const PERM_MAKE_OKAY = 2;	// No restriction to making posts/comments

	const PERM_EDIT_NONE = 0; 	// Cannot edit any posts/comments
	const PERM_EDIT_OWN = 1;	// Can edit own posts/comments
	const PERM_EDIT_GROUP = 2;	// Can edit content made by group members
	const PERM_EDIT_ANY = 3;	// Can edit anybody's content

	public $permissions;
	public static $limits = array(
		'name' => 64
	);

	/**
	 * Construct the group object
	 * @param int $id The group's id
	 * @param string $name Group name (alphanumeric and underscores only)
	 * @param int mp 'make posts' permission
	 * @param int ep 'edit posts' permission
	 * @param int mc 'make comments' permission
	 * @param int ec 'edit comments' permission
	 * @param boolean a Is this an admin group?
	 */
	function __construct($id, $name, $mp, $ep, $mc, $ec, $a) {
		$errors = array();
		
		if (!is_int($this->id = $id))
			APIError::pushErrorArray($errors,1101); // Invalid id
		$tihs->id = $id;

		// Check group name
		if (!self::check_length($name,'name'))
			APIError::pushErrorArray($errors,1102); // name too long
		if (!check_alphanum_underscore($name))
			APIError::pushErrorArray($errors,1103); // name w/ invalid chars
		$this->name = $name;

		// Check permissions
		if ($mp < 0 || $mp > self::PERM_MAKE_OKAY) // Invalid mp value
			APIError::pushErrorArray($errors,1104); 
		if ($ep < 0 || $ep > self::PERM_EDIT_ANY) // Invalid ep value
			APIError::pushErrorArray($errors,1105);
		if ($mc < 0 || $mc > self::PERM_MAKE_OKAY) // Invalid mc value
			APIError::pushErrorArray($errors,1106);
		if ($ec < 0 || $ec > self::PERM_EDIT_ANY) // Invalid ec value
			APIError::pushErrorArray($errors,1107);
		$this->permissions = array(
			'make_post' => $mp,
			'edit_post' => $ep,
			'make_comment' => $mc,
			'edit_comment' => $ec, 
			'admin' => (bool)$a
		);

		if (!empty($errors))
			throw new APIError($errors,"Group errors");
	}

	private static function check_length($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

?>
