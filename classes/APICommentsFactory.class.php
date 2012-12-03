<?php
defined('API_PATH') or die('No direct script access.');

class APICommentsFactory {
	public static function getCommentsByPostIds($ids) {
		global $db;
		$ids = (array)$ids;
		$comments = array();

		foreach ($ids as $id) {
		}
	}

	public static function getCommentInfoByPostIds($id) {
		global $db;
		$ids = (array)$ids;
		$commentsInfo = array();

		foreach ($ids as $id) {

		}
	}

}

