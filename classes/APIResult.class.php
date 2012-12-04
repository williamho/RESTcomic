<?php
defined('API_PATH') or die('No direct script access.');

class APIResult {
	public $meta;
	public $errors;
	public $response;

	function __construct($response=array(),$errors=array(),$meta=null)
	{
		$this->response = $response;
		if ($meta)
			$this->meta = $meta;
		else
			$this->meta = self::defaultMeta();
		$this->errors = $errors;
	}

	public function setMeta($up,$prev=null,$next=null) {
		$this->meta = array(
			'up' => $up,
			'prev' => $prev,
			'next' => $next
		);
	}

	public function setResponse($response) {
		$this->response = $response;
	}

	public function setErrors(APIError $errors) { 
		$this->errors = $errors->getErrors();
	 }

	public function toJSON() {
		return json_encode($this);
	}

	public static function defaultMeta() {
		return array(
			'up' => null,
			'prev' => null,
			'next' => null
		);
	}
}

