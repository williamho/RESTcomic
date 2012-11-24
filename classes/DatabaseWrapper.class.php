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
	 * @param User $user The user being added
	 */
	public function add_user(User $user) {
		if (!($user instanceof User))
			throw new Exception('Input argument is not a User object');
		if (!empty($user->id))
			throw new Exception('To add a user, id must be set to 0');

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
	 * Add group to database, if no errors encountered
	 * @param Group $group The group being added
	 */
	public function add_group(Group $group) {
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


