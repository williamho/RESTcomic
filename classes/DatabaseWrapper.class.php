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
			$this->validateTag($obj);
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

	private function validateTag(Tag $tag) {
		// Check if tag name already exists
		if ($this->rowExists('tags','name',$tag->name))
			throw new APIError('User errors',1404); // Group doesn't exist
		return null;
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
		
		// Check if post with slug already exists
		if ($this->rowExists('posts','title_slug',$post->title_slug))
			$errors->addError(1209); 

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
}

?>


