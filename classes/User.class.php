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
	public $email;
	public $website;

	static public $limits = array(
		'login' => 24,
		'name' => 32,
		'password' => 60,
		'email' => 255,
		'website' => 255
	);

	function __construct($id, $login, $name, $password, $group, 
							$email=null, $website=null) {
		$errors = array();

		if (!is_int($this->id = $id))
			APIError::pushErrorArray($errors,1001); // Invalid id
		// If $id is nonzero, assume user info was retrieved from database.
		else if ($id) 
			$this->password = $password; 
		// If $id is 0, assume user doesn't exist and will be added to database.
		else {
			// Hash the password
			$this->password = self::$hasher->HashPassword($password);
			if (strlen($this->password)<20)
				APIError::pushErrorArray($errors,1002); // Failed to hash pw
		}

		// Check login
		if (!self::check_length($login,'login'))
			APIError::pushErrorArray($errors,1003); // login too long
		if (!check_alphanum_underscore($login))
			APIError::pushErrorArray($errors,1004); // login w/ invalid chars
		$this->login = $login;

		// Check name
		if (!self::check_length($name,'name'))
			APIError::pushErrorArray($errors,1005); // name too long
		$this->name = $name;

		
		// Set group
		if (!is_int($group))
			APIError::pushErrorArray($errors,1006); // invalid group
		$this->group = $group;


		// Set email
		if (!self::check_length($email,'email'))
			APIError::pushErrorArray($errors,1007); // email too long
		$this->email = $email;

		// Set website
		$this->website = urlencode($website);
		if (!self::check_length($this->website,'website'))
			APIError::pushErrorArray($errors,1008); // website url too long

		if (!empty($errors))
			throw new APIError($errors,"User errors");
	}
	
	private static function check_length($string,$field) {
		return strlen($string) <= self::$limits[$field];
	}


}
User::$hasher = new PasswordHash(8, false);
?>

