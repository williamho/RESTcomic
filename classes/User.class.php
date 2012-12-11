<?php
defined('API_PATH') or die('No direct script access.');
require_once API_PATH.'includes/checks.php';
require_once API_PATH.'includes/PasswordHash.php'; 

class User {
	public static $hasher;
	public $user_id;
	public $group_id;
	public $login;
	public $name;
	public $password;
	public $date_registered;
	public $email;
	public $website;
	public $api_key;

	static public $limits = array(
		'login' => 24,
		'login_min' => 3,
		'name' => 32,
		'password' => 60,
		'password_min' => 5,
		'email' => 255,
		'api_key' => 40
	);

	public function hashPassword() {
		if (strlen($this->password) < self::$limits['password_min'])
			throw new APIError(1013);
		$this->password = self::$hasher->HashPassword($this->password);
		if (strlen($this->password)<20)
			throw new APIError(1002);
	}

	public function setValues($user_id, $group_id, $login, $name, $password,
			$date_registered='now', $email='', $website='', $api_key='') 
	{
		$this->user_id = $user_id;
		$this->group_id = $group_id;
		$this->login = $login;
		$this->name = $name;

		if ($password == '')
			throw new APIError(1011);
		$this->password = $password;
		$this->date_registered = $date_registered;
		$this->email = $email;
		$this->website = $website;
		$this->api_key = $api_key;
	}

	public function getErrors() {
		$errors = new APIError();

		// Check ID
		if (!is_int($this->user_id) && !ctype_digit($this->user_id))
			$errors->addError(1001); // invalid id
		
		// Check login
		if (strlen($this->login) < self::$limits['login_min'])
			$errors->addError(1012); // login can't be null
		if (!self::checkLength($this->login,'login'))
			$errors->addError(1003); // login too long
		if (!checkAlphanumUnderscore($this->login))
			$errors->addError(1004); // login w/ invalid chars
		$this->login = strtolower($this->login);

		// Check registration date
		if (!($this->date_registered = 
				convertDatetime($this->date_registered)))
			$errors->addError(1204); // Invalid date

		// Check name
		if (!self::checkLength($this->name,'name'))
			$errors->addError(1005); // name too long
		
		// Check group
		if (!is_int($this->group_id) && !ctype_digit($this->group_id))
			$errors->addError(1006); // invalid group

		// Check email
		if (!self::checkLength($this->email,'email'))
			$errors->addError(1007); // email too long
		
		// Generate API Key
		if ($this->user_id === 0 && $this->api_key === '')
			$this->api_key = $this->newAPIKey();
			
		if (!$errors->isEmpty())
			return $errors;
		return null;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}

	private function newAPIKey() {
		return sha1(rand());
	}
}
User::$hasher = new PasswordHash(8, false);

