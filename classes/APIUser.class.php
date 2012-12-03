<?php
defined('API_PATH') or die('No direct script access.');

class APIUser {
	public $id;
	public $login;
	public $name;
	public $website;
	public $icon;

	function __construct($id, $group, $login, $name, $website, $email) 
	{
		$this->id = (int)$id;
		$this->group = $group;
		$this->login = (string)$login;
		$this->name = (string)$name;
		$this->website = (string)$website;
		$this->icon = $this->getIcon($email);
	}

	function getIcon ($email, $s=USER_ICON_SIZE, 
		$d=USER_ICON_DEFAULT, $r=USER_ICON_RATING) 
	{
		$url = 'http://www.gravatar.com/avatar/';
		$url .= md5(strtolower(trim($email)));
		$url .= "?s=$s&d=$d&r=$r";
		return $url;
	}
}

