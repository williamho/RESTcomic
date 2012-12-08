<?php
defined('API_PATH') or die('No direct script access.');

class APIGroup {
	public $id;
	public $name;
	public $color;

	function __construct($id, $name, $color, $admin, $mp, $ep, $mc, $ec) {
		$this->id = (int)$id;
		$this->name = (string)$name;
		$this->color = toColor((int)$color);

		if (!is_null($admin)) {
			$this->admin = (boolean)$admin;
			$this->permissions = array(
				'make_post' => $this->makePermissionToString($mp),
				'edit_post' => $this->editPermissionToString($ep),
				'make_comment' => $this->makePermissionToString($mc),
				'edit_comment' => $this->editPermissionToString($ec),
			);
		}
		else {
			$this->down = '/groups/id/'.$id;
		}
	}

	function makePermissionToString($val) {
		switch ((int)$val) {
		case 0: return 'no';
		case 1: return 'limited';
		case 2: return 'yes';
		default: return 'undefined';
		}
	}

	function editPermissionToString($val) {
		switch ((int)$val) {
		case 0: return 'no';
		case 1: return 'own';
		case 2: return 'group';
		case 3: return 'yes';
		default: return 'undefined';
		}
	}
}
