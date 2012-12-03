<?php
defined('API_PATH') or die('No direct script access.');

class Tag {
	public $tag_id;
	public $name;

	public static $limits = array(
		'name' => 255
	);

	public function setValues($tag_id, $name) {
		$this->tag_id = $tag_id;
		$this->name = makeSlug($name);
	}

	public function getErrors() {
		$errors = new APIError('Tag errors');

		// Check tag ID
		if (!is_int($this->tag_id))
			$errors->addError(1401); // invalid tag id

		$this->name = makeSlug($this->name);
		if (!self::checkLength($this->name,'name'))
			$errors->addError(1402); // name too long

		if (!$errors->isEmpty())
			return $errors;
		return null;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

