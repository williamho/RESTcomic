<?php
defined('API_PATH') or die('No direct script access.');

class APIResult {
	public $meta;
	public $errors;
	public $response;

	function __construct($response=array(),$errors=null,$meta=null)
	{
		if (is_null($response))
			$this->response = array();
		else
			$this->response = $response;
		if (is_null($meta))
			$this->meta = self::defaultMeta();
		else
			$this->meta = $meta;

		if (is_null($errors))
			$this->errors = array();
		else
			$this->setErrors($errors);
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

