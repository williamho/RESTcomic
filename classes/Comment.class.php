<?php
class Comment {
	public $comment_id;
	public $post_id;
	public $user_id;
	public $parent_comment_id;
	public $timestamp;
	public $ip;
	public $visible;
	public $content;
	public $name;

	public static $limits = array(
		'ip' => 45,
		'name' => 64
	);

	public function setValues($comment_id, $post_id, $user_id,
		$parent_comment_id, $timestamp, $ip, $visible, $content, $name='') 
	{
		$this->comment_id = $comment_id;
		$this->post_id = $post_id;
		$this->user_id = $user_id;
		$this->parent_comment_id = $parent_comment_id;
		$this->timestamp = $timestamp;
		$this->ip = $ip;
		$this->visible = $visible;
		$this->content = $content;
		$this->name = $name;
	}

	public function getErrors() {
		$errors = new APIError();

		// Check comment ID
		if (!is_int($this->comment_id))
			$errors->addError(1301); // invalid comment id

		// Check user ID
		if (!is_int($this->user_id))
			$errors->addError(1001); // invalid user id

		// Check post ID
		if (!is_int($this->post_id))
			$errors->addError(1201); // invalid post id

		// Check name
		if (!self::checkLength($this->name,'name'))
			$errors->addError(1302); // name too long
	
		// Set visible
		$this->visible = (bool)$this->visible;

		// Set content
		$this->content = (string)$this->content;

		// Check parent comment ID
		if (!is_int($this->parent_comment_id) && 
		    !is_null($this->parent_comment_id))
			$errors->addError(1303); // invalid parent id

		// Set IP
		if (!$this->ip)
			$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->ip = (string)$this->ip;

		// Check timestamp
		if (!($this->timestamp = 
				convertDatetime($this->timestamp)))
			$errors->addError(1204); // Invalid date

		if (!$errors->isEmpty())
			return $errors;
		return null;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

