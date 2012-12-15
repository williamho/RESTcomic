<?php
defined('API_PATH') or die('No direct script access.');

class APIUsersFactory {
	public static function getUserByLogin($login, $getGroup=true, $getEmail=false) {
		global $config, $db;
		$query = "
			SELECT u.user_id
			FROM {$config->tables['users']} u 
			WHERE u.login = :login
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':login',$login);
		$stmt->execute();

		if(is_null($userId = $stmt->fetchColumn()))
			return array();
		return self::getUsersByIds($userId,$getGroup);
	}

	public static function getUsersByIds($ids, $getGroup=true, $getEmail=false) {
		global $db, $config;
		$ids = (array)$ids;
		$idString = intArrayToString($ids);
		$users = array();
		$groups = array();

		if ($getGroup) {
			$query = "
				SELECT *, u.name as user_name, g.name as group_name 
				FROM {$config->tables['users']} u, 
					{$config->tables['groups']} g
				WHERE u.user_id IN ($idString)
					AND u.group_id = g.group_id
			";
		}
		else {
			$query = "
				SELECT *
				FROM {$config->tables['users']} u
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
				$apiGroup = null;
				
			$apiUser = new APIUser(
				$user->user_id,
				$apiGroup,
				$user->login,
				$user->user_name,
				$user->website,
				$user->email
			);
			
			if (!$getEmail)
				unset($apiUser->email);

			if (!$getGroup)
				unset($apiUser->group);

			array_push($apiUsers,$apiUser);
		}

		return $apiUsers;
	}
}

