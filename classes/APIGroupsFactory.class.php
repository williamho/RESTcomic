<?php
defined('API_PATH') or die('No direct script access.');

class APIGroupsFactory {
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

