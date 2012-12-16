<?php
defined('API_PATH') or die('No direct script access.');

class APIGroupsFactory {
	public static function getGroups() {
		global $db, $config;
		$query = "
			SELECT *
			FROM {$config->tables['groups']}
			ORDER BY group_id
		";
		$stmt = $db->prepare($query);
		$stmt->execute();
		$groups = array();
		while($result = $stmt->fetchObject())
			array_push($groups,$result);

		$apiGroups = array();
		foreach ($groups as $group) {
			$apiGroup = new APIGroup(
				$group->group_id,
				$group->name,
				$group->color,
				$group->admin_perm,
				$group->make_post_perm,
				$group->edit_post_perm,
				$group->make_comment_perm,
				$group->edit_comment_perm
			);

			array_push($apiGroups,$apiGroup);
		}

		return $apiGroups;
	}

	public static function getGroupsByIds($ids) {
		global $db;
		$ids = (array)$ids;
		$groups = $db->getObjectsFromTableByIds('groups',$ids);

		$apiGroups = array();
		foreach ($groups as $group) {
			$apiGroup = new APIGroup(
				$group->group_id,
				$group->name,
				$group->color,
				$group->admin_perm,
				$group->make_post_perm,
				$group->edit_post_perm,
				$group->make_comment_perm,
				$group->edit_comment_perm
			);

			array_push($apiGroups,$apiGroup);
		}

		return $apiGroups;
	}
	
}

