<?php
defined('API_PATH') or die('No direct script access.');
require_once API_PATH.'includes/checks.php';

class APIPostsFactory {
	/**
	 * Get a post based on array of post IDs (or single ID)
	 * @param array/int $ids
	 * @return array Posts
	 */
	public static function getPostsByIds($ids,$getTags=true,$getGroup=true, 
				$perPage=POSTS_DEFAULT_NUM,$page=0)
	{
		if ((int)$perPage == 0)
			$perPage = POSTS_DEFAULT_NUM;
		if ($perPage > POSTS_MAX_NUM) 
			$perPage = POSTS_MAX_NUM;
		if ($page < 0)
			$page = 0;

		global $db;
		$ids = (array)$ids;
		$idString = intArrayToString($ids);
		$posts = array();
		$users = array();
		$commentInfo = array();

		$lower = $page * $perPage;
		$upper = ($page+1) * $perPage - 1;

		$query = "
			SELECT *, u.name AS user_name, g.name AS group_name, 
					COUNT(c.comment_id) AS comment_count
			FROM posts p, users u, groups g, comments c
			WHERE p.post_id IN ($idString)
				AND p.user_id = u.user_id
				AND u.group_id = g.group_id
				AND c.post_id = p.post_id
			LIMIT $lower,$upper
		";

		$stmt = $db->prepare($query);
		$stmt->execute();
		$ids = array();
		while ($result = $stmt->fetchObject()) {
			array_push($posts, $result);
			array_push($ids, $result->post_id);
		}
		if (empty($ids) || is_null($ids[0]))
			return array();

		// there's a problem here
		$idString = intArrayToString($ids);

		if ($getTags) {
			$tags = initArrayKeys($ids,array());

			$query = "
				SELECT p.post_id, t.name as tag_name
				FROM posts p, tags t, post_tags pt
				WHERE p.post_id IN ($idString)
					AND pt.post_id = p.post_id
					AND pt.tag_id = t.tag_id
			";
			$stmt = $db->prepare($query);
			$stmt->execute();
			while ($result = $stmt->fetchObject()) 
				array_push($tags[$result->post_id],$result->tag_name);
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
				$tags[$post->post_id]
			);
			array_push($apiPosts,$apiPost);
		}

		return $apiPosts;
	}

	// Find posts that contain all the tags in the $names array
	public static function getPostsByTags($names,$and=true) {
		global $db;
		$names = (array)$names;
		$nameString = slugArrayToString($names);
		$numTags = count($names);
		$postIds = array();

		// Show post IDs for posts that match ANY tag
		$query = "
			SELECT p.post_id
			FROM post_tags pt, posts p, tags t
			WHERE pt.tag_id = t.tag_id
			AND t.name IN ($nameString)
			AND p.post_id = pt.post_id
			GROUP BY p.post_id 
		";
		if ($and) // Show post IDs for posts that match ALL the tags
			$query .= "HAVING COUNT(p.post_id) = $numTags";

		$stmt = $db->prepare($query);
		$stmt->execute();
		while ($result = $stmt->fetchObject()) 
			array_push($postIds,(int)$result->post_id);

		if (empty($postIds))
			return array();
		return self::getPostsByIds($postIds);
	}

	public static function getPostsByTagsExclude($include,$exclude,
													$and=true) 
	{
		global $db;
		$include = (array)$include;
		$exclude = (array)$exclude;
		$includeString = slugArrayToString($include);
		$excludeString = slugArrayToString($exclude);
		$numInclude = count($include);
		$postIds = array();

		// Show post IDs for posts that match ANY tag
		$query = "
			SELECT p.post_id
			FROM post_tags pt, posts p, tags t
			WHERE p.post_id = pt.post_id
			AND pt.tag_id = t.tag_id
			AND t.name IN ($includeString)
			AND p.post_id NOT IN (
				SELECT p.post_id 
				FROM posts p, post_tags pt, tags t
				WHERE p.post_id = pt.post_id
				AND pt.tag_id = t.tag_id
				AND t.name IN ($excludeString)
			)
			GROUP BY p.post_id 
		";
		if ($and) // Show post IDs for posts that match ALL the tags
			$query .= "HAVING COUNT(p.post_id) = $numInclude";

		$stmt = $db->prepare($query);
		$stmt->execute();
		while ($result = $stmt->fetchObject()) 
			array_push($postIds,(int)$result->post_id);

		if (empty($postIds))
			return array();
		return self::getPostsByIds($postIds);
	}

}


