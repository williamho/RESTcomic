<?php
defined('API_PATH') or die('No direct script access.');

class DatabaseWrapper {
	private $db;

	public function __construct($server, $username, $password, $database) {
		try {
			$this->db = new PDO("mysql:host=$server;dbname=$database",
							$username,$password,array(PDO::ATTR_PERSISTENT => true));
			//$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
	public function insertObjectIntoTable($obj, $new=true) {
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
			$this->validateGroup($obj,$new);
			break;
		case 'User': 
			$primary = 'user_id';
			$table = 'users';
			$this->validateUser($obj,$new);
			break; 
		case 'Post': 
			$primary = 'post_id';
			$table = 'posts';
			$this->validatePost($obj,$new);
			break; 
		case 'Tag': 
			$primary = 'tag_id';
			$table = 'tags';
			$this->validateTag($obj);
			break; 
		case 'Comment': 
			$primary = 'comment_id';
			$table = 'comments';
			$this->validateComment($obj,$new);
			break; 
		default:
			throw new Exception('Invalid object');
			break;
		}
		$table = $config->tables[$table];

		if ($new && $obj->$primary) {
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

		if ($new) {
			$query = "INSERT INTO $table ($queryColumns)
				VALUES ($queryBinds)";
		}
		else { // Replacing current row
			$query = "REPLACE INTO $table ($queryColumns)
				VALUES ($queryBinds)";
		}
		$stmt = $this->db->prepare($query);
		foreach($fields as $column=>$value) 
			$stmt->bindValue(":$column",$value);

		$stmt->execute();
		$inserted = $this->db->lastInsertId();

		if (!$new && $table == 'posts')
			$this->deleteTagsFromPost($obj->post_id);

		if ($new && $table == 'comments') {
			$query = "
				UPDATE {$config->tables['posts']} p
				SET p.comment_count = p.comment_count + 1
				WHERE p.post_id = :id
			";
			$stmt = $this->db->prepare($query);
			$stmt->bindValue(':id',$obj->post_id);
			$stmt->execute();
			return $inserted;
		}

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
		$primary = $this->getPrimary($table);

		$query = "SELECT $primary FROM {$config->tables[$table]}
		          WHERE $column = :val";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':val',$value);
		$stmt->execute();

		$index = $stmt->fetchColumn();
		return $index;
	}

	private function validateTag(Tag $tag) {
		// Check if tag name already exists
		if ($this->rowExists('tags','name',$tag->name))
			throw new APIError(1403); // Tag name already exists
		return null;
	}

	private function validateGroup(Group $group, $new=true) {
		// Check if group name already exists
		if ($index = $this->rowExists('groups','name',$group->name)) {
			if (!is_null($index) && $index != $group->group_id)
				throw new APIError(1108); // group name already exists
		}
		return null;
	}

	private function validateUser(User $user, $new=true) {
		$errors = new APIError();

		// Check if user is valid
		$index = $this->rowExists('users','login',$user->login);
		if (($index !== false && $index != $user->user_id) || $index === '0')
			$errors->addError(1008); // User already exists

		if ($new && $user->group_id != 2) 
			$errors->addError(1014); // New users must be in default group

		// Check if group is valid
		if (!($this->rowExists('groups','group_id',$user->group_id)))
			$errors->addError(1109); // Group doesn't exist

		if (!$errors->isEmpty())
			throw $errors;
		return null;
	}
	
	private function validatePost(Post $post, $new=true) {
		$errors = new APIError();
		global $config;

		// Check if user is valid
		if (!$this->rowExists('users','user_id',$post->user_id))
			$errors->addError(1009); // User doesn't exist
		
		if (!$new) { 
			$index = $this->rowExists('posts','title_slug',$post->title_slug);
			if ($index != null && $index != $post->post_id)
				throw new APIError(1209);
		}

		else {
			// Check if post with slug already exists
			$originalSlug = $post->title_slug;
			for ($i=1; 
				$this->rowExists('posts','title_slug',$post->title_slug); 
				$i++) 
			{
				$post->title_slug = $originalSlug.'-'.$i;
			}
		}

		if (!$new) {
			$query = "
				SELECT COUNT(*)
				FROM {$config->tables['posts']} p,
					{$config->tables['comments']} c
				WHERE p.post_id = c.post_id
					AND p.post_id=:post_id
			";
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':post_id',$post->post_id);
			$stmt->execute();
			$post->comment_count = $stmt->fetchColumn();
		}
		else
			$post->comment_count = 0;
			
		if (!$errors->isEmpty())
			throw $errors;
		return null;
	}

	private function validateComment(Comment $comment, $new=true) {
		$errors = new APIError();
		global $config;

		// Check if user is valid
		if (is_null($this->rowExists('users','user_id',$comment->user_id)))
			$errors->addError(1009); // User doesn't exist

		// Check if parent comment is valid
		if ($comment->parent_comment_id) {
			if (!$this->rowExists('comments','comment_id',
					$comment->parent_comment_id)) {
				$errors->addError(1304); // Parent comment doesn't exist
			}
			$comments = self::getObjectsFromTableByIds('comments',
				$comment->parent_comment_id);
			$comment->post_id = $comments[0]->post_id;
		}
		// Check if post is valid
		elseif (!$this->rowExists('posts','post_id',(int)$comment->post_id)) {
			$errors->addError(1205); // Post doesn't exist
		}
		else
			$comment->parent_comment_id = 0;
			
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
			throw new APIError(1205); // Post doesn't exist

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
		$stmt->closeCursor();
		
		// Tag has already been assigned to post
		if ($stmt->rowCount()) 
			return;
		
		$query = "INSERT INTO {$config->tables['post_tags']} 
					(post_id,tag_id) VALUES (:post_id,:tag_id)";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		$stmt->bindParam(':tag_id',$tag->tag_id);
		$stmt->execute();
		$stmt->closeCursor();
	}

	function deleteTagsFromPost($post_id) {
		global $config;
		// Delete posttags rows
		$query = "
			DELETE FROM {$config->tables['post_tags']} 
			WHERE post_id = :post_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		if (!$stmt->execute())
			throw new APIError(2007);

		// Delete orphan tags
		$query = "
			DELETE FROM {$config->tables['tags']}
			WHERE tag_id NOT IN (
				SELECT tag_id
				FROM {$config->tables['post_tags']}
			)
		";
		if (!$this->db->query($query))
			throw new APIError(2007);
	}

	/**
	 * Add array of tags to a post with the given ID
	 * @param array $tags Array of tag names
	 * @param int $post_id The ID of the post to add the tags to
	 */
	public function addTagsToPost($tags, $post_id) {
		$tags = (array)$tags;
		foreach ($tags as $tag) 
			$this->addTagToPost($tag,$post_id);
	}

	public function getPostAuthor($post_id) {
		global $config;
		if (!is_int($post_id) && !ctype_digit($post_id))
			throw new APIError(1201); // Invalid post ID

		$query = "
			SELECT u.*
			FROM {$config->tables['users']} u,
				{$config->tables['posts']} p
			WHERE p.user_id = u.user_id
				AND p.post_id = :post_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		$stmt->execute();
		$result = $stmt->fetchObject();
		$stmt->closeCursor();
		return $result;
	}

	public function getCommentAuthor($comment_id) {
		global $config;
		if (!is_int($comment_id) && !ctype_digit($comment_id))
			throw new APIError(1301); // Invalid comment ID

		$query = "
			SELECT u.*
			FROM {$config->tables['users']} u,
				{$config->tables['comments']} c
			WHERE c.user_id = u.user_id
				AND c.comment_id = :comment_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':comment_id',$comment_id);
		$stmt->execute();
		$result = $stmt->fetchObject();
		$stmt->closeCursor();
		return $result;
	}

	public function deleteRowFromTable($tableName,$row_id) {
		$primary = $this->getPrimary($tableName);
		$query = "
			DELETE FROM $tableName
			WHERE $primary = $row_id
		";
		$result = $this->db->query($query);
		if ($result === false)
			throw new APIError(2007);
	}

	public function deletePost($post_id) {
		global $config;
		$query = "
			DELETE FROM {$config->tables['posts']} 
			WHERE post_id = :post_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		if (!$stmt->execute())
			throw new APIError(2007); // db problem

		// Now delete comments made on the post
		$query = "
			DELETE FROM {$config->tables['comments']} 
			WHERE post_id = :post_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':post_id',$post_id);
		if (!$stmt->execute())
			throw new APIError(2007); 

		$this->deleteTagsFromPost($post_id);
	}

	public function deleteGroup($group_id) {
		global $config;

		// Assign all users currently in the group to default group
		$query = "
			UPDATE {$config->tables['users']} u
			SET u.group_id = 2
			WHERE u.group_id = :group_id
		";

		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':group_id',$group_id);
		$stmt->execute();
		$stmt->closeCursor();

		$query = "
			DELETE FROM {$config->tables['groups']} 
			WHERE group_id = :group_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':group_id',$group_id);
		if (!$stmt->execute())
			throw new APIError(2007); 
	}

	public function deleteComment($comment_id) {
		global $config;
		// First check if comment has child comments
		$query = "
			SELECT 1
			FROM {$config->tables['comments']} c
			WHERE c.parent_comment_id = :comment_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':comment_id',$comment_id);
		$stmt->execute();
		$stmt->closeCursor();
	
		// Child comments exist. Don't actually delete the comment.
		if ($stmt->rowCount()) {
			$query = "
				UPDATE {$config->tables['comments']} c
				SET c.user_id = 0, c.content = '[deleted]',
					c.name = 'deleted'
				WHERE c.comment_id = :comment_id
			";
			$stmt = $this->db->prepare($query);
			$stmt->bindParam(':comment_id',$comment_id);
			$stmt->execute();
			$stmt->closeCursor();
			return;
		}

		// Decrement the comment count
		$query = "
			UPDATE {$config->tables['posts']} p,
				{$config->tables['comments']} c
			SET p.comment_count = p.comment_count - 1
			WHERE p.post_id = c.post_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->execute();
		$stmt->closeCursor();

		// Then delete the comment
		$query = "
			DELETE FROM {$config->tables['comments']} 
			WHERE comment_id = :comment_id
		";
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':comment_id',$comment_id);
		$stmt->execute();
		$stmt->closeCursor();

	}

	public function getPostIdFromCommentId($comment_id) {
		global $config;
		$query = "
			SELECT p.post_id
			FROM {$config->tables['posts']} p,
				{$config->tables['comments']} c
			WHERE p.post_id = c.post_id
				AND c.comment_id = :comment_id
		";
		$comment_id = (int)$comment_id;
		$stmt = $this->db->prepare($query);
		$stmt->bindParam(':comment_id',$comment_id);
		$stmt->execute();
		$result = $stmt->fetchColumn();
		return $result;
	}

}

