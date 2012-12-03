<?php
defined('API_PATH') or die('No direct script access.');

class APIUsersFactory {
	public static function getUsersByIds($ids, $getGroup=true) {
		global $db;
		$ids = (array)$ids;
		$idString = intArrayToString($ids);
		$users = array();
		$groups = array();

		if ($getGroup) {
			$query = "
				SELECT *, g.name as group_name 
				FROM users u, groups g
				WHERE u.user_id IN ($idString)
					AND u.group_id = g.group_id
			";
		}
		else {
			$query = "
				SELECT *
				FROM users u
				WHERE u.user_id IN ($idString)
			";
		}

		$stmt = $db->prepare($query);
		$stmt->execute();
		while ($result = $stmt->fetchObject()) 
			array_push($users, $result);

		$apiUsers = array();
		foreach ($users as $index=>$user) {
			if ($getGroup) {
				$apiGroup = new APIGroup(
					$user->group_id,
					$user->group_name,
					$user->color,
					$user->admin_perm,
					$user->make_post_perm,
					$user->edit_post_perm,
					$user->make_comment_perm,
					$user->edit_comment_perm
				);
			}
			else
				$group = null;
				

			$apiUser = new APIUser(
				$user->user_id,
				$apiGroup,
				$user->login,
				$user->name,
				$user->website,
				$user->email
			);
			
			if (!$getGroup)
				unset($apiUser->group);

			array_push($apiUsers,$apiUser);
		}

		return $apiUsers;
	}
}

