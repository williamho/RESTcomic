<?php
defined('API_PATH') or die('No direct script access.');

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

	public function prepare($query) {
		return $this->db->prepare($query);
	}

	/**
	 * Inserts a row into the specified table based on the fields of the
	 * object. The affected table depends on the class of the input object.
	 * @param mixed $obj The object to add. 
	 *                   Valid classes: Group, User, Post, Tag, or Comment
	 */
	public function insertObjectIntoTable($obj) {
		// Check for errors
		if ($e = $obj->getErrors()) 
			throw $e;

		global $config;
		// Check if primary key is null, like it should be
		// Also set the table name based on the object class
		$primary = null;
		switch (get_class($obj)) {
		case 'Group': 
			$primary = 'group_id';
			$table = 'groups';
			break;
		case 'User': 
			$primary = 'user_id';
			$table = 'users';
			$this->validateUser($obj);
			break; 
		case 'Post': 
			$primary = 'post_id';
			$table = 'posts';
			$this->validatePost($obj);
			break; 
		case 'Tag': 
			$primary = 'tag_id';
			$table = 'tags';
			$this->validateTag($obj);
			break; 
		case 'Comment': 
			$primary = 'comment_id';
			$table = 'comments';
			$this->validateComment($obj);
			break; 
		default:
			throw new Exception('Invalid object');
			break;
		}
		$table = $config->tables[$table];

		if ($obj->$primary) {
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
		return $this->db->lastInsertId($primary);
	}

	private function getPrimary($table) {
		switch (strtolower($table)) {
		case 'groups': return 'group_id';
		case 'users': return 'user_id';
		case 'posts': return 'post_id';
		case 'tags': return 'tag_id'; 
		case 'comments': return 'comment_id'; 
		default: throw new Exception("Invalid table name $table"); 
		}
	}

	public function getObjectsFromTableByIds($table,$ids) {
		$ids = (array)$ids;

		$primary = $this->getPrimary($table);
		foreach($ids as $index=>$id)
			$ids[$index] = (int)$id;

		$idString = implode(',',$ids);
		$query = "SELECT * FROM $table WHERE $primary IN ($idString)";
		$stmt = $this->db->prepare($query);
		$stmt->execute();

		$results = $stmt->fetchAll(PDO::FETCH_OBJ);
		return $results;
	}

	// This function name is really long...
	public function getObjectsFromTableByIdsAndForeign($table1,$ids,
														$table2,$col) 
		{
		$ids = (array)$ids;
		$primary = $this->getPrimary($table1);
		foreach($ids as $index=>$id)
			$ids[$index] = (int)$id;

		$idString = implode(',',$ids);
		$query = "
			SELECT t1.$primary, t2.*
			FROM $table1 t1, $table2 t2
			WHERE t1.$primary IN ($idString) AND t1.$col = t2.$col
		";
		$stmt = $this->db->prepare($query);

		$stmt->execute();

		$results = $stmt->fetchAll(PDO::FETCH_OBJ);
		return $results;
	}

	private function rowExists($table,$column,$value) {
		global $config;

		$query = "SELECT 1 FROM {$config->tables[$table]}
		          WHERE $column = :val";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':val',$value);
		$stmt->execute();

		return (bool)$stmt->rowCount();
	}

	private function validateTag(Tag $tag) {
		// Check if tag name already exists
		if ($this->rowExists('tags','name',$tag->name))
			throw new APIError('Tag errors',1403); // Tag name already exists
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
		global $config;

		// Check if user is valid
		if (!$this->rowExists('users','user_id',$post->user_id))
			$errors->addError(1009); // User doesn't exist
		
		// Check if post with slug already exists
		if ($this->rowExists('posts','title_slug',$post->title_slug))
			$errors->addError(1209); 

		// Check if user has valid permissions to make a post
		$query = "SELECT g.make_post_perm, g.edit_post_perm
		          FROM {$config->tables['groups']} g, 
			           {$config->tables['users']} u
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
		global $config;

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
		          FROM {$config->tables['groups']} g, 
			           {$config->tables['users']} u
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

	/**
	 * Add a single tag to a post with the given ID
	 * @param string $tag_name The name of the tag
	 * @param int $post_id The ID of the post to add the tag to
	 */
	function addTagToPost($tag_name,$post_id) {
		// Make new tag object based on tag name
		$tag = new Tag;
		$tag->setValues(0,$tag_name);
		$tag->getErrors();
		global $config;

		// Check that the post actually exists
		if (!$this->rowExists('posts','post_id',$post_id))
			throw new APIError('Post Tag errors',1205); // Post doesn't exist

		// Get ID of existing tag with the same name, if it exists
		$query = "SELECT tag_id FROM {$config->tables['tags']}
		          WHERE name = :tag_name";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':tag_name',$tag->name);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if ($result) 
			$tag->tag_id = $result[0]['tag_id'];
		else
			$tag->tag_id = $this->insertObjectIntoTable($tag);

		// Check that tag is not already assigned to the post
		$query = "SELECT 1 FROM {$config->tables['post_tags']}
		          WHERE post_id = :post_id AND tag_id = :tag_id";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		$stmt->bindParam(':tag_id',$tag->tag_id);
		$stmt->execute();
		
		// Tag has already been assigned to post
		if ($stmt->rowCount()) 
			return;
		
		$query = "INSERT INTO {$config->tables['post_tags']} 
					(post_id,tag_id) VALUES (:post_id,:tag_id)";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		$stmt->bindParam(':tag_id',$tag->tag_id);
		$stmt->execute();
	}

	/**
	 * Add array of tags to a post with the given ID
	 * @param array $tags Array of tag names
	 * @param int $post_id The ID of the post to add the tags to
	 */
	public function addTagsToPost($tags, $post_id) {
		foreach ($tags as $tag) 
			$this->addTagToPost($tag,$post_id);
	}
}

