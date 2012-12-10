<?php
defined('API_PATH') or die('No direct script access.');

class APICommentsFactory {
	public static function getCommentsByIds($ids,$reverse=false,
				$perPage=POSTS_DEFAULT_NUM,$page=1) {
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
		$desc = $reverse ? 'DESC' : '';

		$query = "
			SELECT c.*, c.name AS comment_name, g.color, 
				u.login, u.name AS user_name, u.email, u.website
			FROM {$config->tables['comments']} c
			LEFT JOIN {$config->tables['users']} u on u.user_id = c.user_id
			LEFT JOIN {$config->tables['posts']} p on p.post_id = c.post_id
			LEFT JOIN {$config->tables['groups']} g on g.group_id = u.group_id
			WHERE c.comment_id IN ($idString)
			ORDER BY c.comment_id $desc 
			LIMIT $lower,$perPage
		";
		$stmt = $db->prepare($query);
		$stmt->execute();

		$comments = array();
		while($result = $stmt->fetchObject())
			array_push($comments,$result);
		$stmt->closeCursor();
		if (empty($comments) || is_null($comments[0]))
			return array();

		$apiComments = array();
		foreach ($comments as $comment) {
			if ($comment->user_id == 0)
				$comment->user_name = $comment->comment_name;

			$apiUser = new APIUser(
				$comment->user_id,
				null,
				$comment->login,
				$comment->user_name,
				$comment->website,
				$comment->email
			);
			unset($apiUser->group);
			$apiUser->group_color = toColor((int)$comment->color);

			$apiComment = new APIComment(
				$comment->comment_id,
				$comment->post_id,
				$comment->timestamp,
				$comment->content,
				$apiUser,
				$comment->parent_comment_id
			);
			
			array_push($apiComments,$apiComment);
		}
		return $apiComments;
	}

	public static function getCommentsByPostSlug($slug,$nested=true) {
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
			throw new APIError(1201);

		return self::getCommentsByPostId($postId,$nested);
	}

	public static function getCommentsByPostId($id,$nested=true) {
		global $db,$config;
		if (!is_int($id) && !ctype_digit($id))
			throw new APIError(1301); // Invalid comment ID
		$id = (int)$id;

		$query = "
			SELECT c.*, c.name AS comment_name, g.color, 
				u.login, u.name AS user_name, u.email, u.website
			FROM {$config->tables['comments']} c
			LEFT JOIN {$config->tables['users']} u on u.user_id = c.user_id
			LEFT JOIN {$config->tables['posts']} p on p.post_id = c.post_id
			LEFT JOIN {$config->tables['groups']} g on g.group_id = u.group_id
			WHERE c.post_id = :post_id
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':post_id',$id);
		$stmt->execute();

		$comments = array();
		if ($nested) {
			while ($result = $stmt->fetchObject()) 
				$comments[$result->comment_id] = $result;
		}
		else {
			while ($result = $stmt->fetchObject()) 
				array_push($comments, $result);
		}
		$stmt->closeCursor();

		if (empty($comments))
			return array();

		$apiComments = array();
		$mapping = array();
		foreach ($comments as $key=>$comment) {
			if ($comment->user_id == 0)
				$comment->user_name = $comment->comment_name;

			$apiUser = new APIUser(
				$comment->user_id,
				null,
				$comment->login,
				$comment->user_name,
				$comment->website,
				$comment->email
			);
			unset($apiUser->group);
			$apiUser->group_color = toColor((int)$comment->color);

			$apiComment = new APIComment(
				$comment->comment_id,
				$comment->post_id,
				$comment->timestamp,
				$comment->content,
				$apiUser,
				$comment->parent_comment_id
			);
			unset($apiComment->post_id);

			if ($nested) {
				if ($apiComment->parent === 0) {
					array_push($apiComments,$apiComment);
				}
				else {
					$parent = &$mapping[$apiComment->parent];

					if (!isset($parent->replies))
						$parent->replies = array();
					array_push($parent->replies,$apiComment);
				}
				unset($apiComment->parent);
				$mapping[$apiComment->id] = $apiComment;
			}
			else
				array_push($apiComments,$apiComment);
		}
		return $apiComments;
	}

	public static function getCommentInfo($postId,$count) {
		return array(
			'count' => (int)$count,
			'down' => getCurrentFileURL() . "/posts/id/$postId/comments"
		);
	}

	public static function getCommentInfoByPostIds($id) {
		global $db;
		$ids = (array)$ids;
		$commentsInfo = array();

		foreach ($ids as $id) {

		}
	}

}

