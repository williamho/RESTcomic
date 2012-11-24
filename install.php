<?php
require_once 'includes/config.php';

function __autoload($class_name) {
	require_once "classes/$class_name.class.php";
}

// Connect to database
$db = new DatabaseWrapper(SQL_SERVER,SQL_USERNAME,SQL_PASSWORD,SQL_DB);

// Drop existing tables
foreach ($config->tables as $table_name) {
	$query = "DROP TABLE IF EXISTS $table_name";
	$db->execute_query($query,$db);
}

// Create tables (string lengths defined in class files)
$query = "CREATE TABLE {$config->tables['groups']} (
	g_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(".Group::$limits['name'].") NOT NULL,
	admin_perm BOOLEAN DEFAULT FALSE NOT NULL,
	make_post_perm TINYINT NOT NULL,
	edit_post_perm TINYINT NOT NULL,
	make_comment_perm TINYINT NOT NULL,
	edit_comment_perm TINYINT NOT NULL,
	UNIQUE(name),
	PRIMARY KEY(g_id)
)";
$db->execute_query($query,$db);

$query = "CREATE TABLE {$config->tables['users']} (
	u_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	g_id INT UNSIGNED NOT NULL,
	login VARCHAR(".User::$limits['login'].") NOT NULL,
	name VARCHAR(".User::$limits['name'].") NOT NULL,
	password CHAR(".User::$limits['password'].") NOT NULL,
	email VARCHAR(".User::$limits['email']."),
	website VARCHAR(".User::$limits['website']."),
	UNIQUE(login),
	UNIQUE(email),
	PRIMARY KEY(u_id),
	FOREIGN KEY(g_id) REFERENCES groups(g_id) ON DELETE SET DEFAULT
)";
$db->execute_query($query,$db);

$query = "CREATE TABLE {$config->tables['posts']} (
	p_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	u_id INT UNSIGNED NOT NULL,
	title VARCHAR(".Post::$limits['title'].") NOT NULL,
	status TINYINT NOT NULL,
	commentable BOOLEAN DEFAULT TRUE NOT NULL,
	post_date DATETIME NOT NULL,
	image_url TEXT,
	content TEXT,
	PRIMARY KEY(p_id),
	FOREIGN KEY(u_id) REFERENCES users(u_id) ON DELETE SET DEFAULT
)";
$db->execute_query($query,$db);

$query = "CREATE TABLE {$config->tables['tags']} (
	t_id INT UNSIGNED AUTO_INCREMENT,
	name VARCHAR(".Tag::$limits['name'].") NOT NULL,
	PRIMARY KEY(t_id),
	UNIQUE(name)
)";
$db->execute_query($query,$db);

$query = "CREATE TABLE {$config->tables['post_tags']} (
	p_id INT UNSIGNED NOT NULL,
	t_id INT UNSIGNED NOT NULL,
	PRIMARY KEY(p_id,t_id),
	FOREIGN KEY(p_id) REFERENCES posts(p_id) ON DELETE CASCADE,
	FOREIGN KEY(t_id) REFERENCES tags(t_id) ON DELETE CASCADE
)";
$db->execute_query($query,$db);

$query = "CREATE TABLE {$config->tables['comments']} (
	c_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	p_id INT UNSIGNED NOT NULL,
	u_id INT UNSIGNED,
	c_parent INT UNSIGNED, 
	comment_date DATETIME NOT NULL,
	ip BINARY(".Comment::$limits['ip'].") NOT NULL,
	visible BOOLEAN DEFAULT TRUE NOT NULL,
	content TEXT NOT NULL,
	name VARCHAR(".Comment::$limits['name']."),
	PRIMARY KEY(c_id),
	FOREIGN KEY(p_id) REFERENCES posts(p_id) ON DELETE CASCADE,
	FOREIGN KEY(u_id) REFERENCES users(u_id) ON DELETE SET DEFAULT,
	FOREIGN KEY(c_parent) REFERENCES comments(c_id) ON DELETE CASCADE
)";
$db->execute_query($query,$db);

try {
	$group = new Group(0,'groupname',0,0,0,0,0);
	$db->add_group($group);
}
catch (Exception $e) {
	print_r($e->getErrors());
}

try {
	$user = new User(0,'test','testname','testpw',1,'blahblahemail','');
	$db->add_user($user);
}
catch (Exception $e) {
	print_r($e->getErrors());
}

?>

