<?php
class Comment {
	public $id;
	public $post;
	public $author;
	public $parent;
	public $ip;
	public $visible;
	public $content;
	public $name;
	public $date;

	public static $limits = array(
		'ip' => 45,
		'name' => 64
	);

	function __construct($id, $post, $author, $visible,
			$content, $name=null, $parent=0, $date=null, $ip=null) {
		$errors = new APIError('Comment errors');

		// Check comment ID
		if (!is_int($this->id = $id))
			$errors->addError(1301); // invalid comment id

		// Check user ID
		if (!is_int($this->author = $author))
			$errors->addError(1001); // invalid user id

		// Check post ID
		if (!is_int($this->post = $post))
			$errors->addError(1201); // invalid post id

		// Check name
		if (!self::checkLength($name,'name'))
			$errors->addError(1302); // name too long
		$this->name = $name;
	
		// Set visible
		$this->visible = (bool)$visible;

		// Set content
		$this->content = (string)$content;

		// Check parent comment ID
		if (!is_int($this->parent = $parent))
			$errors->addError(1303); // invalid parent id

		// Set IP
		if (!$ip)
			$ip = $_SERVER['REMOTE_ADDR'];
		$this->ip = $ip;

		// Check date
		if ($id)	// If $id specified, comment was obtained from database
			$this->date = $date;
		else if (!($this->date = convertDatetime($date)))
			$errors->addError(1204); // Invalid date
			
		if (!$errors->isEmpty())
			throw $errors;
	}


	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

?>



