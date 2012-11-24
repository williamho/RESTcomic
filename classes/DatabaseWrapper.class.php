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

	public function executeQuery($query) {
		$result = $this->db->query($query);
		if(!$result)
			die("Error running query [$this->db->error]");
		return $result;
	}

	/**
	 * Add user to database, if no errors encountered
	 * @param User $user The user being added
	 */
	public function addUser(User $user) {
		if (!($user instanceof User))
			throw new Exception('Input argument is not a User object');
		if (!empty($user->id))
			throw new Exception('To add a user, id must be set to 0');

		if ($this->getUserByLogin($user->login)) {
			throw new APIError('User errors',1009); // User exists
		}

		$query = "INSERT INTO {$GLOBALS['config']->tables['users']} 
			VALUES (NULL,?,?,?,?,?,?)";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('isssss',
			$user->group,
			$user->login,
			$user->name,
			$user->password,
			$user->email,
			$user->website
		);
		$stmt->execute();
		$stmt->close();
	}

	/**
	 * Returns an array of users based on the results of a query
	 * @param mysqli_stmt $stmt A mysqli statement, before execute() called
	 * @return array Array of User objects
	 */
	private function getUsersFromStatement(mysqli_stmt &$stmt) {
		$stmt->execute();
		$users = array();
		$stmt->bind_result($id,$group,$login,$name,$password,$email,$website);

		while ($row = $stmt->fetch()) {
			$user = new User($id,$login,$name,$password,$group,
								$email,$website);
			array_push($users,$user);
		}
		return $users;
	}

	/**
	 * Obtains user object based on user's login
	 * @param string $login The user's login
	 * @return User A User object containing the user's info
	 */
	public function getUserByLogin($login) {
		$query = "SELECT * FROM users WHERE login = ?";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('s',$login);
		
		$users = $this->getUsersFromStatement($stmt);
		$stmt->close();

		if (empty($users))
			return null;
		return $users[0];
	}

	/**
	 * Add group to database, if no errors encountered
	 * @param Group $group The group being added
	 */
	public function addGroup(Group $group) {
		if (!($group instanceof Group))
			throw new Exception('Input argument is not a Group object');
		if (!empty($group->id))
			throw new Exception('To add a group, id must be set to 0');

		$query = "INSERT INTO {$GLOBALS['config']->tables['groups']} 
			VALUES (NULL,?,?,?,?,?,?)";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('siiiii',
			$group->name,
			$group->permissions['admin'],
			$group->permissions['make_post'],
			$group->permissions['edit_post'],
			$group->permissions['make_comment'],
			$group->permissions['edit_comment']
		);
		$stmt->execute();
		$stmt->close();
	}
}

?>


