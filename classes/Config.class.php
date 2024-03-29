<?php
	class Config {
		public $tables = array(
			'users'=>'users',
			'posts'=>'posts',
			'comments'=>'comments',
			'groups'=>'groups',
			'tags'=>'tags',
			'post_tags'=>'post_tags'
		);
		const timeFormat = 'Y-m-d H:i:s';

		function __construct($prefix) {
			foreach($this->tables as &$table_name) 
				$table_name = $prefix . $table_name;
		}
	}
?>
