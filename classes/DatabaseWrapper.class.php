<?php
class DatabaseWrapper {
	private $db;

	public function __construct($server, $username, $password, $database) {
		try {
			$this->db = new PDO("mysql:host=$server;dbname=$database",
							$username,$password);
			$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		}
		catch (PDOException $e) {
			die($e->getMessage());
		}
	}

	function __destruct() {
		$this->db = null;
	}

	public function executeQuery($query) {
		$result = $this->db->query($query);
		return $result;
	}

	/**
	 * Inserts a row into the specified table based on the fields of the
	 * object. The affected table depends on the class of the input object.
	 */
	public function insertObjectIntoTable($obj) {
		// Check for errors
		if ($e = $obj->getErrors()) 
			throw $e;

		// Check if primary key is null, like it should be
		// Also set the table name based on the object class
		$primary = null;
		switch (get_class($obj)) {
		case 'Group': 
			$primary = $obj->group_id; 
			$table = 'groups';
			break;
		case 'User': 
			$primary = $obj->user_id; 
			$table = 'users';
			$this->validateUser($obj);
			break; 
		case 'Post': 
			$primary = $obj->post_id; 
			$table = 'posts';
			$this->validatePost($obj);
			break; 
		case 'Tag': 
			$primary = $obj->tag_id; 
			$table = 'tags';
			break; 
		case 'Comment': 
			$primary = $obj->comment_id; 
			$table = 'comments';
			$this->validateComment($obj);
			break; 
		default:
			throw new Exception('Invalid object');
		}
		$table = $GLOBALS['config']->tables[$table];

		if ($primary) {
			throw new Exception('To insert a new object into the 
						table, the primary key must be null');
		}

		// Insert the row into the database
		$fields = get_object_vars($obj);
		$columns = array();
		$binds = array();
		$values = array();
		foreach($fields as $column=>$value) {
			array_push($columns,$column);
			array_push($binds,":$column");
			array_push($values,$value);
		}
		$queryColumns = implode(',',$columns);
		$queryBinds = implode(',',$binds);

		$query = "INSERT INTO $table ($queryColumns)
			VALUES ($queryBinds)";

		$stmt = $this->db->prepare($query);
		foreach($fields as $column=>$value) 
			$stmt->bindValue(":$column",$value);

		$stmt->execute();
	}

	private function rowExists($table,$column,$value) {
		$query = "SELECT 1 FROM {$GLOBALS['config']->tables[$table]}
		          WHERE $column = :val";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':val',$value);
		$stmt->execute();

		return (bool)$stmt->rowCount();
	}

	private function validateUser(User $user) {
		// Check if group is valid
		if (!$this->rowExists('groups','group_id',$user->group_id))
			throw new APIError('User errors',1109); // Group doesn't exist
		return null;
	}
	
	private function validatePost(Post $post, $new=true) {
		$errors = new APIError('Post errors');

		// Check if user is valid
		if (!$this->rowExists('users','user_id',$post->user_id))
			$errors->addError(1009); // User doesn't exist

		// Check if user has valid permissions to make a post
		$query = "SELECT g.make_post_perm, g.edit_post_perm
		          FROM {$GLOBALS['config']->tables['groups']} g, 
			           {$GLOBALS['config']->tables['users']} u
			      WHERE u.group_id = g.group_id AND u.user_id = :user_id";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':user_id',$post->user_id);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if ($new) {
			switch($result[0]['make_post_perm']) {
			case Group::PERM_MAKE_NONE:
				$errors->addError(1206); // Invalid post permissions
				break;
			case Group::PERM_MAKE_HIDDEN:
				$post->status = Post::STATUS_HIDDEN;
				break;
			}
		}
		else {
			switch($result[0]['edit_post_perm']) {
			case Group::PERM_EDIT_NONE:
				$errors->addError(1207); // Invalid edit permissions
				break;
			case Group::PERM_EDIT_OWN: // Figure out a way to handle this
				break;
			case Group::PERM_EDIT_GROUP:
				break;
			}
		}
			
		if (!$errors->isEmpty())
			throw $errors;
		return null;
	}

	private function validateComment(Comment $comment, $new=true) {
		$errors = new APIError('Comment errors');

		// Check if user is valid
		if (!$this->rowExists('users','user_id',$comment->user_id))
			$errors->addError(1009); // User doesn't exist

		// Check if post is valid
		if (!$this->rowExists('posts','post_id',$comment->post_id))
			$errors->addError(1205); // Post doesn't exist

		// Check if parent comment is valid
		if ($comment->parent_comment_id) {
			if (!$this->rowExists('comments','comment_id',
					$comment->parent_comment_id)) {
				$errors->addError(1304); // Parent comment doesn't exist
			}
		}
		else // Set zero values to null
			$comment->parent_comment_id = null;

		// Check if user has valid permissions to make a comment
		$query = "SELECT g.make_comment_perm, g.edit_comment_perm
		          FROM {$GLOBALS['config']->tables['groups']} g, 
			           {$GLOBALS['config']->tables['users']} u
			      WHERE u.group_id = g.group_id AND u.user_id = :user_id";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':user_id',$comment->user_id);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if ($new) {
			switch($result[0]['make_comment_perm']) {
			case Group::PERM_MAKE_NONE:
				$errors->addError(1305); // Invalid post permissions
				break;
			case Group::PERM_MAKE_HIDDEN:
				$post->visible = false;
				break;
			}
		}
		else {
			switch($result[0]['edit_comment_perm']) {
			case Group::PERM_EDIT_NONE:
				$errors->addError(1306); // Invalid edit permissions
				break;
			case Group::PERM_EDIT_OWN: // Figure out a way to handle this
				break;
			case Group::PERM_EDIT_GROUP:
				break;
			}
		}
			
		if (!$errors->isEmpty())
			throw $errors;
		return null;
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
		$params = array (
			$user->group,
			$user->login,
			$user->name,
			$user->password,
			$user->registered,
			$user->email,
			$user->website
		);
		$stmt->execute($params);
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
		$stmt->execute(array($login));

		$users = $stmt->fetchAll(PDO::FETCH_CLASS,"User");

		print_r($users);

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
			WHERE user_id = ?";
		$stmt = $this->db->prepare($query);
		$stmt->execute(array($id));
		
		$users = $stmt->fetchAll(PDO::FETCH_CLASS,"User");
		print_r($users);

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
			VALUES (NULL,:group_id,:name,:admin_perm,
				:make_post_perm,:edit_post_perm,
				:make_comment_perm, :edit_comment_perm)";
		$stmt = $this->db->prepare($query);
		$params = get_object_vars($group);
		$stmt->execute($params);
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
		$params = array(
			$post->author,
			$post->title,
			$post->status,
			$post->commentable,
			$post->date,
			$post->image,
			$post->content
		);
		$stmt->execute($params);
	}	

	/**
	 * Returns an array of posts based on the results of a query
	 * @param mysqli_stmt $stmt A mysqli statement, before execute() called
	 * @return array Array of Post objects
	 */
	private function getPostsFromStatement(mysqli_stmt &$stmt) {
		$stmt->execute();
		$posts = array();
		$stmt->bind_result($id,$author,$title,$status,$commentable,
							$date,$image,$content);

		while ($row = $stmt->fetch()) {
			$post = new Post($id,$author,$title,$status,$commentable,
								$image,$content,$date);
			array_push($posts,$post);
		}
		return $posts;
	}

	/**
	 * Obtains Post object based on post ID
	 * @param int $id The post's ID
	 * @return Post A post object
	 */
	public function getPostById($id) {
		$query = "SELECT * 
			FROM {$GLOBALS['config']->tables['posts']} 
			WHERE p_id = ?";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('i',$id);
		
		$posts = $this->getPostsFromStatement($stmt);
		$stmt->close();

		if (empty($posts))
			return null;
		return $posts[0];
	}


	/*=================*
	 | Comment-related |
	 *=================*/
	/**
	 * Add comment to database, if no errors encountered
	 * @param Comment $comment The comment being added
	 */
	public function addComment($comment) {
		$errors = new APIError('Comment errors');
		if (!empty($comment->id))
			throw new Exception('To add a comment, id must be set to 0');
		
		$author = $this->getUserById($comment->author);
		if ($author) {
		}
		else
			$errors->addError(1009); // User doesn't exist

		if (!$this->getPostById($comment->post)) 
			$errors->addError(1205); // Post doesn't exist

		if ($comment->parent && !$this->getCommentById($comment->parent)) 
			$errors->addError(1304); // Parent comment doesn't exist

		if (!$errors->isEmpty())
			throw $errors;

		$query = "INSERT INTO {$GLOBALS['config']->tables['comments']} 
			VALUES (NULL,?,?,?,?,?,?,?,?)";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('iiississ',
			$comment->post,
			$comment->author,
			$comment->parent,
			$comment->date,
			$comment->ip,
			$comment->visible,
			$comment->content,
			$comment->name
		);
		$stmt->execute();
		$stmt->close();

	}

	/**
	 * Returns an array of comments based on the results of a query
	 * @param mysqli_stmt $stmt A mysqli statement, before execute() called
	 * @return array Array of Comment objects
	 */
	private function getCommentsFromStatement(mysqli_stmt &$stmt) {
		$stmt->execute();
		$comments = array();
		$stmt->bind_result($id,$post,$author,$parent,$date,$ip,
							$visible,$content,$name);

		while ($row = $stmt->fetch()) {
			$comment = new Comment($id,$post,$author,$visible,$content,
									$name,$parent,$date,$ip);
			array_push($comments,$comment);
		}
		return $comments;
	}

	/**
	 * Obtains comment object based on comment ID
	 * @param int $id The comment's ID
	 * @return Comment A comment object
	 */
	public function getCommentById($id) {
		$query = "SELECT * 
			FROM {$GLOBALS['config']->tables['comments']} 
			WHERE c_id = ?";
		$stmt = $this->db->prepare($query);
		$stmt->bind_param('i',$id);
		
		$comments = $this->getCommentsFromStatement($stmt);
		$stmt->close();

		if (empty($comments))
			return null;
		return $comments[0];
	}


}

?>


