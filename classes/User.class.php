<?php
require_once 'includes/checks.php';
require_once 'includes/PasswordHash.php'; 

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

	static public $limits = array(
		'login' => 24,
		'name' => 32,
		'password' => 60,
		'email' => 255
	);

	public function hashPassword() {
		$this->password = self::$hasher->HashPassword($this->password);
		if (strlen($this->password)<20)
			throw new APIError('User errors',1002);
	}

	public function setValues($user_id, $group_id, $login, $name, $password,
			$date_registered='now', $email='', $website='') 
	{
		$this->user_id = $user_id;
		$this->group_id = $group_id;
		$this->login = $login;
		$this->name = $name;
		$this->password = $password;
		$this->date_registered = $date_registered;
		$this->email = $email;
		$this->website = $website;
	}

	public function getErrors() {
		$errors = new APIError('User errors');

		// Check ID
		if (!is_int($this->user_id))
			$errors->addError(1001); // invalid id
		
		// Check login
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
		if (!is_int($this->group_id))
			$errors->addError(1006); // invalid group

		// Check email
		if (!self::checkLength($this->email,'email'))
			$errors->addError(1007); // email too long

		if (!$errors->isEmpty())
			return $errors;
		return null;
	}

	private static function checkLength($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}
User::$hasher = new PasswordHash(8, false);

