<?php
class Post {
	public $post_id;
	public $user_id;
	public $title;
	public $title_slug;
	public $status;
	public $commentable;
	public $timestamp;
	public $image_url;
	public $content;

	const STATUS_VISIBLE = 0;
	const STATUS_SCHEDULED = 1;
	const STATUS_HIDDEN = 2;

	public static $limits = array(
		'title' => 255
	);

	public function setValues($post_id, $user_id, $title, $title_slug, $status,
			$commentable, $timestamp, $image_url, $content ) {
		$this->post_id = $post_id;
		$this->user_id = $user_id;
		$this->title = $title;
		$this->title_slug = $title_slug;
		$this->status = $status;
		$this->commentable = $commentable;
		$this->timestamp = $timestamp;
		$this->image_url = $image_url;
		$this->content = $content;
	}

	public function getErrors() {
		$errors = new APIError('Post errors');

		// Check post ID
		if (!is_int($this->post_id))
			$errors->addError(1201); // invalid post id

		// Check user ID
		if (!is_int($this->user_id))
			$errors->addError(1001); // invalid user id
	
		// Check title
		if (!self::checkLength($this->title,'title'))
			$errors->addError(1202); // title too long

		if ($this->title_slug) 
			$this->title_slug = makeSlug($this->title_slug);
		else
			$this->title_slug = makeSlug($this->title);
		if (!self::checkLength($this->title_slug,'title'))
			$errors->addError(1208); // Title slug too long

		// Check status
		if (!is_int($this->status) || 
			$this->status<0 || $this->status>self::STATUS_HIDDEN)
			$errors->addError(1203); // Invalid status

		// Set commentable 
		$this->commentable = (bool)$this->commentable;
	
		// Check timestamp
		if (!($this->timestamp = 
				convertDatetime($this->timestamp)))
			$errors->addError(1204); // Invalid date

		// Check image URL
		$this->image_url = (string)$this->image_url;

		// Set body text of the post
		$this->content = (string)$this->content; // Assume markdown format

		if (!$errors->isEmpty())
			return $errors;
		return null;
		
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

?>
