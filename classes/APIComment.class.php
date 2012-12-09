<?php
defined('API_PATH') or die('No direct script access.');

class APIComment {
	public $id;
	public $post_id;
	public $timestamp;
	public $content;
	public $author;
	public $parent;

	function __construct($id,$post_id,$timestamp,$content,$author,$parent) {
		$this->id = (int)$id;
		if (is_null($post_id))
			unset($this->post_id);
		else
			$this->post_id = (int)$post_id;
		$this->content = $content;
		$this->timestamp = $timestamp;
		$this->author = $author;
		$this->parent = (int)$parent;
	}
}

