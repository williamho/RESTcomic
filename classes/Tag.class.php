<?php
class Tag {
	public $tag_id;
	public $name;
	public $name_slug;

	public static $limits = array(
		'name' => 255
	);

	public function setValues($tag_id, $name, $name_slug=null) {
		$this->tag_id = $tag_id;
		$this->name = $name;
		$this->name_slug = $name_slug;
	}

	public function getErrors() {
		$errors = new APIError('Tag errors');

		// Check tag ID
		if (!is_int($this->tag_id))
			$errors->addError(1401); // invalid tag id

		if (!self::checkLength($this->name,'name'))
			$errors->addError(1402); // name too long

		// If name slug not set, set it based on the name
		if ($this->name_slug) 
			$this->name_slug = makeSlug($this->name_slug);
		else
			$this->name_slug = makeSlug($this->name);

		if (!self::checkLength($this->name_slug,'name'))
			$errors->addError(1403); // name slug too long

		if (!$errors->isEmpty())
			return $errors;
		return null;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}

?>

