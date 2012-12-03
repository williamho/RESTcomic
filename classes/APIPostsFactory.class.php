<?php
defined('API_PATH') or die('No direct script access.');

class APIPostsFactory {
	/**
	 * Get a post based on array of post IDs (or single ID)
	 * @param array/int $ids
	 * @return array Posts
	 */
	public static function getPostsByIds($ids,$getTags=true,$getGroup=true) {
		global $db;
		$ids = (array)$ids;
		$idString = intArrayToString($ids);
		$posts = array();
		$users = array();
		$commentInfo = array();

		$query = "
			SELECT *, u.name AS user_name, g.name AS group_name
			FROM posts p, users u, groups g
			WHERE p.post_id IN ($idString)
				AND p.user_id = u.user_id
				AND u.group_id = g.group_id
		";
		$stmt = $db->prepare($query);
		$stmt->execute();
		while ($result = $stmt->fetchObject()) 
			array_push($posts, $result);

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
				//$commentsInfo[$index],
				null,
				$tags[$post->post_id]
			);
			array_push($apiPosts,$apiPost);
		}

		return $apiPosts;
	}

}


