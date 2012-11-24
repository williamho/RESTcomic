<?php
require_once 'includes/checks.php';
require_once 'includes/PasswordHash.php'; 

class User {
	public static $hasher;
	public $id;
	public $login;
	public $name;
	public $password;
	public $group;
	public $registered;
	public $email;
	public $website;

	static public $limits = array(
		'login' => 24,
		'name' => 32,
		'password' => 60,
		'email' => 255
	);

	function __construct($id, $login, $name, $password, $group, 
						$registered=null, $email=null, $website=null) 
		{
		$errors = new APIError('User errors');

		if (!is_int($this->id = $id))
			$errors->addError(1001); // invalid id
		// If $id is nonzero, assume user info was retrieved from database.
		else if ($id) {
			$this->password = $password; 
			$this->registered = $registered;
		}
		// If $id is 0, assume user doesn't exist and will be added
		else {
			// Hash the password
			$this->password = self::$hasher->HashPassword($password);
			if (strlen($this->password)<20)
				$errors->addError(1002); // Failed to hash pw

			// Ignore the registered field and set it to now
			$this->registered = date('Y-m-d H:i:s');
		}

		// Check login
		if (!self::check_length($login,'login'))
			$errors->addError(1003); // login too long
		if (!check_alphanum_underscore($login))
			$errors->addError(1004); // login w/ invalid chars
		$this->login = strtolower($login);

		// Check name
		if (!self::check_length($name,'name'))
			$errors->addError(1005); // name too long
		$this->name = $name;

		
		// Set group
		if (!is_int($group))
			$errors->addError(1006); // invalid group
		$this->group = $group;


		// Set email
		if (!self::check_length($email,'email'))
			$errors->addError(1007); // email too long
		$this->email = $email;

		// Set website
		$this->website = $website;

		if (!$errors->isEmpty())
			throw $errors;
	}
	
	private static function check_length($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}
}
User::$hasher = new PasswordHash(8, false);
?>

