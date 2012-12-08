<?php
defined('API_PATH') or die('No direct script access.');

class APICommentsFactory {
	public static function getCommentsByPostId($id,$nested=false) {
		global $db;
		$id = (int)$id;
		$comments = array();

		foreach ($ids as $id) {
		}
	}

	public static function getCommentInfo($postId,$count) {
		return array(
			'count' => (int)$count,
			'down' => "/posts/id/$postId/comments"
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

