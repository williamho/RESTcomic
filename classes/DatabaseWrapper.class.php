<?php
class DatabaseWrapper {
	private $db;

	public function __construct($server, $username, $password, $database) {
		$this->db = new mysqli($server, $username, $password, $database);
		if($this->db->connect_errno > 0)
			die("Unable to connect to database [$this->db->connect_error]");
	}

	function __destruct() {
		$this->db->close();
	}

	public function execute_query($query) {
		$result = $this->db->query($query);
		if(!$result)
			die("Error running query [$this->db->error]");
		return $result;
	}

	/**
	 * Add user to database, if no errors encountered
	 *
	 * @param User $user The user being added
	 */
	public function add_user($user) {
		if (!($user instanceof User))
			throw new Exception('Input argument is not a User object');
		if (!empty($user->id))
			throw new Exception('User with this id already exists');

		$query = "INSERT INTO {$GLOBALS['config']->tables['users']} 
			VALUES (NULL,?,?,?,?,?,?)";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('isssss',$user->group,$user->login,$user->name,
							$user->password,$user->email,$user->website);
		$stmt->execute();
		$stmt->close();
	}
}

?>


