<?php
defined('API_PATH') or die('No direct script access.');

class APIPost {
	public $id;
	public $published;
	public $title;
	public $title_slug;
	public $status;
	public $commentable;
	public $image;
	public $content;
	public $author;
	public $comments;
	public $tags;

	function __construct($id, $published, $title, $title_slug, $status,
		$commentable, $image, $content, $author, $comments, $tags) 
	{
		$this->id = (int)$id;
		$this->published = $published;
		$this->title = $title;
		$this->title_slug = $title_slug;
		$this->status = $status;
		$this->commentable = (boolean)$commentable;
		$this->image = $image;
		$this->content = $content;
		$this->author = $author;
		$this->comments = $comments;
		$this->tags = $tags;
	}
}

