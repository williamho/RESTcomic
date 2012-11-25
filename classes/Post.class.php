<?php
class Post {
	public $id;
	public $author;
	public $title;
	public $status;
	public $commentable;
	public $date;
	public $image;
	public $content;

	const STATUS_VISIBLE = 0;
	const STATUS_SCHEDULED = 1;
	const STATUS_HIDDEN = 2;

	public static $limits = array(
		'title' => 255
	);

	function __construct($id, $author, $title, $status, $commentable,
			$date, $image, $content) 
	{
		$errors = new APIError('User errors');

		// Check post ID
		if (!is_int($this->id = $id))
			$errors->addError(1201); // invalid post id

		// Check user ID
		if (!is_int($this->author = $author))
			$errors->addError(1202); // invalid user id

		// Check title
		if (!self::check_length($title,'title'))
			$errors->addError(1203); // title too long
		$this->title = $title;

		// Check status
		if (!is_int($status) || $status<0 || $status>self::STATUS_HIDDEN)
			$errors->addError(1204); // Invalid status
		$this->status = $status;
		
		// Set commentable 
		$this->commentable = (bool)$commentable;

		// Check date
		if ($id)	// If $id specified, post was obtained from database
			$this->date = $date;
		else if (!($this->date = convertDatetime($date)))
			$errors->addError(1205); // Invalid date
				
		// Set image URL
		$this->image = $image; // URL should be sanitized before displayed

		// Set body text of the post
		$this->content = $content; // Assume markdown format for content

		if (!$errors->isEmpty())
			throw $errors;
	}

	private static function check_length($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

?>
