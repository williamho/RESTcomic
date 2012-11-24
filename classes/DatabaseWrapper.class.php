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

	/*==============*
	 | User-related |
	 *==============*/
	/**
	 * Add user to database, if no errors encountered
	 * @param User $user The user being added
	 */
	public function addUser(User $user) {
		if (!empty($user->id))
			throw new Exception('To add a user, id must be set to 0');

		if ($this->getUserByLogin($user->login)) 
			throw new APIError('User errors',1008); // User exists

		$query = "INSERT INTO {$GLOBALS['config']->tables['users']} 
			VALUES (NULL,?,?,?,?,?,?,?)";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('issssss',
			$user->group,
			$user->login,
			$user->name,
			$user->password,
			$user->registered,
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
		$stmt->bind_result($id,$group,$login,$name,$password,
								$registered,$email,$website);

		while ($row = $stmt->fetch()) {
			$user = new User($id,$login,$name,$password,$group,
								$registered,$email,$website);
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
		$query = "SELECT * 
			FROM {$GLOBALS['config']->tables['users']} 
			WHERE login = ?";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('s',$login);
		
		$users = $this->getUsersFromStatement($stmt);
		$stmt->close();

		if (empty($users))
			return null;
		return $users[0];
	}

	/**
	 * Obtains user object based on user's id
	 * @param int $id The user's id
	 * @return User A User object containing the user's info
	 */
	public function getUserById($id) {
		$query = "SELECT * 
			FROM {$GLOBALS['config']->tables['users']} 
			WHERE u_id = ?";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('i',$id);
		
		$users = $this->getUsersFromStatement($stmt);
		$stmt->close();

		if (empty($users))
			return null;
		return $users[0];
	}
	
	/*===============*
	 | Group-related |
	 *===============*/
	/**
	 * Add group to database, if no errors encountered
	 * @param Group $group The group being added
	 */
	public function addGroup(Group $group) {
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

	/**
	 * Returns an array of groups based on the results of a query
	 * @param mysqli_stmt $stmt A mysqli statement, before execute() called
	 * @return array Array of Group objects
	 */
	private function getGroupsFromStatement(mysqli_stmt &$stmt) {
		$stmt->execute();
		$groups = array();
		$stmt->bind_result($id,$name,$a,$mp,$ep,$mc,$ec);

		while ($row = $stmt->fetch()) {
			$user = new Group($id,$name,$mp,$ep,$mc,$ec,$a);
			array_push($groups,$group);
		}
		return $groups;
	}

	/**
	 * Obtains group object based on group's id
	 * @param int $id The group's id
	 * @return Group A Group object containing the group's info
	 */
	public function getGroupById($id) {
		$query = "SELECT * 
			FROM {$GLOBALS['config']->tables['groups']} 
			WHERE g_id = ?";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('i',$id);
		
		$groups = $this->getUsersFromStatement($stmt);
		$stmt->close();

		if (empty($groups))
			return null;
		return $groups[0];
	}
	
	/*==============*
	 | Post-related |
	 *==============*/
	/**
	 * Add post to database, if no errors encountered
	 * @param Post $post The post being added
	 */
	public function addPost(Post $post) {
		if (!empty($post->id))
			throw new Exception('To add a post, id must be set to 0');

		if (!$this->getUserById($post->author)) 
			throw new APIError('Post errors',1009); // User doesn't exist

		$query = "INSERT INTO {$GLOBALS['config']->tables['posts']} 
			VALUES (NULL,?,?,?,?,?,?,?)";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('ssiisss',
			$post->author,
			$post->title,
			$post->status,
			$post->commentable,
			$post->date,
			$post->image,
			$post->content
		);
		$stmt->execute();
		$stmt->close();
	}
}

?>


