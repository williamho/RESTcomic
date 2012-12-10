<?php
defined('API_PATH') or die('No direct script access.');
require_once API_PATH.'includes/checks.php';

class APIPostsFactory {
	/**
	 * Get a post based on array of post IDs (or single ID)
	 * @param array/int $ids
	 * @return array Posts
	 */
	public static function getPostsByIds($ids,$reverse=false,
				$perPage=POSTS_DEFAULT_NUM,$page=1,$user_id=0,
				$getTags=true,$getGroup=false)
	{
		$perPage = (int)$perPage;
		$page = (int)$page;

		if ($perPage <= 0)
			$perPage = POSTS_DEFAULT_NUM;
		else if ($perPage > POSTS_MAX_NUM) 
			$perPage = POSTS_MAX_NUM;
		if ($page < 1)
			$page = 1;

		$lower = ($page-1) * $perPage;

		global $db, $config;
		$ids = (array)$ids;
		$idString = intArrayToString($ids);
		$posts = array();
		$users = array();
		$commentInfo = array();

		$desc = $reverse ? 'DESC' : '';
		$query = "
			SELECT p.*, u.*, g.*, u.name AS user_name, g.name AS group_name
			FROM {$config->tables['posts']} p 
			LEFT JOIN {$config->tables['users']} u on u.user_id = p.user_id 
			LEFT JOIN {$config->tables['groups']} g on g.group_id = u.group_id
			WHERE p.post_id IN ($idString) AND 
				(p.status = 0 OR 
				(u.user_id = :user_id AND ((u.user_id = p.user_id) OR 
				(u.group_id = g.group_id AND g.edit_post_perm >= 2)))
				)
			ORDER BY p.post_id $desc 
			LIMIT $lower,$perPage
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':user_id',$user_id);
		$stmt->execute();
		$ids = array();
		while ($result = $stmt->fetchObject()) {
			array_push($posts, $result);
			array_push($ids, $result->post_id);
		}
		$stmt->closeCursor();
		if (empty($ids) || is_null($ids[0]))
			return array();

		$idString = intArrayToString($ids);

		if ($getTags) {
			$tags = initArrayKeys($ids,array());

			$query = "
				SELECT p.post_id, t.name as tag_name
				FROM {$config->tables['posts']} p, 
					{$config->tables['tags']} t, 
					{$config->tables['post_tags']} pt
				WHERE p.post_id IN ($idString)
					AND pt.post_id = p.post_id
					AND pt.tag_id = t.tag_id
			";
			$stmt = $db->prepare($query);
			$stmt->execute();
			while ($result = $stmt->fetchObject()) 
				array_push($tags[$result->post_id],$result->tag_name);
			$stmt->closeCursor();
		}

		$apiPosts = array();
		foreach ($posts as $post) {
			if ($getGroup) {
				$apiGroup = new APIGroup(
					$post->group_id,
					$post->group_name,
					$post->color,
					null, null, null, null, null
				);
			}
			else
				$apiGroup = null;

			$commentsInfo = APICommentsFactory::getCommentInfo(
				$post->post_id,$post->comment_count);
			$apiUser = new APIUser(
				$post->user_id,
				$apiGroup,
				$post->login,
				$post->user_name,
				$post->website,
				$post->email
			);

			if (is_null($apiGroup)) {
				unset($apiUser->group);
				$apiUser->group_color = toColor((int)$post->color);
			}
		
			if (isset($tags[$post->post_id]))
				$tagList = $tags[$post->post_id];
			else
				$tagList = array();
			$apiPost = new APIPost(
				$post->post_id,
				$post->timestamp,
				$post->title,
				$post->title_slug,
				$post->status,
				$post->commentable,
				$post->image_url,
				$post->content,
				$apiUser,
				$commentsInfo,
				$tagList
			);
			array_push($apiPosts,$apiPost);
		}

		return $apiPosts;
	}

	public static function getPostsBetweenIds($from=null,$to=null,
				$reverse=true,$perPage=POSTS_DEFAULT_NUM,$page=1,
				$user_id=0,$getTags=true,$getGroup=false)
	{
		global $db, $config;

		$perPage = (int)$perPage;
		$page = (int)$page;
		$from = (int)$from;
		$to = (int)$to;
		if (!$to)
			$to = PHP_INT_MAX;

		if ($perPage <= 0)
			$perPage = POSTS_DEFAULT_NUM;
		else if ($perPage > POSTS_MAX_NUM) 
			$perPage = POSTS_MAX_NUM;
		if ($page < 1)
			$page = 1;
		$lower = ($page-1) * $perPage;

		$desc = $reverse ? 'DESC' : '';

		$query = "
			SELECT p.post_id
			FROM {$config->tables['posts']} p 
			LEFT JOIN {$config->tables['users']} u on u.user_id = p.user_id 
			LEFT JOIN {$config->tables['groups']} g on g.group_id = u.group_id
			WHERE p.post_id >= :from
				AND p.post_id <= :to AND
				(p.status = 0 OR 
				(u.user_id = :user_id AND ((u.user_id = p.user_id) OR 
				(u.group_id = g.group_id AND g.edit_post_perm >= 2)))
				)
			ORDER BY p.post_id $desc 
			LIMIT $lower,$perPage
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':from',$from);
		$stmt->bindParam(':to',$to);
		$stmt->bindParam(':user_id',$user_id);
		$stmt->execute();

		$ids = array();
		while ($result = (int)$stmt->fetchColumn())
			array_push($ids,$result);
		$stmt->closeCursor();

		if (empty($ids))
			return array();

		return self::getPostsByIds($ids,$reverse,$perPage,
					0,$user_id,$getTags,$getGroup);
	}


	// Find posts that contain all the tags in the $names array
	public static function getPostsByTags($names,$and=true,
				$reverse=false,$perPage=POSTS_DEFAULT_NUM,$page=0,
				$user_id=0,$getTags=true,$getGroup=false)
	{
		global $db, $config;
		$names = (array)$names;
		$nameString = slugArrayToString($names);
		$numTags = count($names);
		$postIds = array();

		// Show post IDs for posts that match ANY tag
		$query = "
			SELECT p.post_id
			FROM {$config->tables['post_tags']} pt, 
				{$config->tables['posts']} p, 
				{$config->tables['tags']} t
			WHERE pt.tag_id = t.tag_id
			AND t.name IN ($nameString)
			AND p.post_id = pt.post_id 
			GROUP BY p.post_id 
		";
		if ($and) // Show post IDs for posts that match ALL the tags
			$query .= "HAVING COUNT(p.post_id) = $numTags ";

		//$query .= "LIMIT $lower,$perPage";

		$stmt = $db->prepare($query);
		$stmt->execute();
		while ($result = $stmt->fetchObject()) 
			array_push($postIds,(int)$result->post_id);
		$stmt->closeCursor();

		if (empty($postIds))
			return array();
		return self::getPostsByIds($postIds,$reverse,$perPage,
					$page,$user_id,$getTags,$getGroup);
	}

	public static function getPostsByTagsExclude($include,$exclude,$and=true,
				$reverse=false,$perPage=POSTS_DEFAULT_NUM,$page=0,
				$user_id=0,$getTags=true,$getGroup=false)
	{
		global $db, $config;
		$include = (array)$include;
		$exclude = (array)$exclude;
		$includeString = slugArrayToString($include);
		$excludeString = slugArrayToString($exclude);
		$numInclude = count($include);
		$postIds = array();

		// Show post IDs for posts that match ANY tag
		$query = "
			SELECT p.post_id
			FROM {$config->tables['post_tags']} pt, 
				{$config->tables['posts']} p, 
				{$config->tables['tags']} t
			WHERE p.post_id = pt.post_id
			AND pt.tag_id = t.tag_id
			AND t.name IN ($includeString)
			AND p.post_id NOT IN (
				SELECT p.post_id 
				FROM {$config->tables['post_tags']} pt, 
					{$config->tables['posts']} p, 
					{$config->tables['tags']} t
				WHERE p.post_id = pt.post_id
				AND pt.tag_id = t.tag_id
				AND t.name IN ($excludeString)
			)
			GROUP BY p.post_id 
		";
		if ($and) // Show post IDs for posts that match ALL the tags
			$query .= "HAVING COUNT(t.tag_id) = $numInclude";
		//$query .= "LIMIT $lower,$perPage";
		echo $query;

		$stmt = $db->prepare($query);
		$stmt->execute();
		while ($result = $stmt->fetchObject()) 
			array_push($postIds,(int)$result->post_id);
		$stmt->closeCursor();

		if (empty($postIds))
			return array();
		return self::getPostsByIds($postIds,$reverse,$perPage,
					0,$user_id,$getTags,$getGroup);
	}
		
	public static function getPostsByAuthorId($id,$reverse=true,
				$perPage=POSTS_DEFAULT_NUM,$page=1,$user_id=0,
				$getTags=true,$getGroup=false)
	{
		global $config, $db;
		$id = (int)$id;
		$query = "
			SELECT p.post_id
			FROM {$config->tables['users']} u, 
				{$config->tables['posts']} p 
			WHERE u.user_id = :id
				AND u.user_id = p.user_id
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':id',$id);
		$stmt->execute();

		$postIds = array();
		while($result = $stmt->fetchObject())
			array_push($postIds,(int)$result->post_id);
		$stmt->closeCursor();

		if (empty($postIds))
			return array();
		return self::getPostsByIds($postIds,$reverse,$perPage,
					0,$user_id,$getTags,$getGroup);
	}

	public static function getPostsByAuthorLogin($login,$reverse=true,
				$perPage=POSTS_DEFAULT_NUM,$page=1,$user_id=0,
				$getTags=true,$getGroup=false)
	{
		global $config, $db;
		$query = "
			SELECT p.post_id
			FROM {$config->tables['users']} u, 
				{$config->tables['posts']} p 
			WHERE u.login = :login
				AND u.user_id = p.user_id
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':login',$login);
		$stmt->execute();

		$postIds = array();
		while($result = $stmt->fetchObject())
			array_push($postIds,(int)$result->post_id);
		$stmt->closeCursor();

		if (empty($postIds))
			return array();
		return self::getPostsByIds($postIds,$reverse,$perPage,
					0,$user_id,$getTags,$getGroup);
	}

	public static function getPostBySlug($slug,$user_id=0,
				$getTags=true,$getGroup=false)
	{
		global $config, $db;
		$query = "
			SELECT p.post_id
			FROM {$config->tables['posts']} p 
			WHERE p.title_slug = :slug
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':slug',$slug);
		$stmt->execute();

		if(!($postId = $stmt->fetchColumn()))
			return array();

		return self::getPostsByIds((int)$postId,0,0,
					0,$user_id,$getTags,$getGroup);
	}

}


