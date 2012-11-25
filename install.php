<?php
require_once 'includes/config.php';

function __autoload($class_name) {
	require_once "classes/$class_name.class.php";
}

// Connect to database
$db = new DatabaseWrapper(SQL_SERVER,SQL_USERNAME,SQL_PASSWORD,SQL_DB);

/*===============*
 | Set up tables |
 *===============*/

// Drop existing tables
foreach ($config->tables as $table_name) {
	$query = "DROP TABLE IF EXISTS $table_name";
	$db->executeQuery($query);
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
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['users']} (
	u_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	g_id INT UNSIGNED NOT NULL,
	login VARCHAR(".User::$limits['login'].") NOT NULL,
	name VARCHAR(".User::$limits['name'].") NOT NULL,
	password CHAR(".User::$limits['password'].") NOT NULL,
	registered DATETIME NOT NULL,
	email VARCHAR(".User::$limits['email']."),
	website TEXT,
	UNIQUE(login),
	PRIMARY KEY(u_id),
	FOREIGN KEY(g_id) REFERENCES groups(g_id) ON DELETE SET DEFAULT
)";
$db->executeQuery($query);

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
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['tags']} (
	t_id INT UNSIGNED AUTO_INCREMENT,
	name VARCHAR(".Tag::$limits['name'].") NOT NULL,
	PRIMARY KEY(t_id),
	UNIQUE(name)
)";
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['post_tags']} (
	p_id INT UNSIGNED NOT NULL,
	t_id INT UNSIGNED NOT NULL,
	PRIMARY KEY(p_id,t_id),
	FOREIGN KEY(p_id) REFERENCES posts(p_id) ON DELETE CASCADE,
	FOREIGN KEY(t_id) REFERENCES tags(t_id) ON DELETE CASCADE
)";
$db->executeQuery($query);

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
$db->executeQuery($query);

/*=====================*
 | Set up default rows |
 *=====================*/

// Set up the unregistered group
$unregGroup = new Group(0,'Unregistered',
	Group::PERM_MAKE_NONE,
	Group::PERM_EDIT_NONE,
	Group::PERM_MAKE_NONE,
	Group::PERM_EDIT_NONE,
	false
);	
$db->addGroup($unregGroup);

// Set the unregistered group to have group id 0
$query = "UPDATE {$config->tables['groups']} SET g_id=0 WHERE g_id=1";
$db->executeQuery($query);
$query = "ALTER TABLE {$config->tables['groups']} AUTO_INCREMENT=1";
$db->executeQuery($query);

// Add unregistered user
$unregUser = new User(0,'unregistered','Unregistered','',0);
$db->addUser($unregUser);

// Set the unregistered user to have user id 0
$query = "UPDATE {$config->tables['users']} SET u_id=0 WHERE u_id=1";
$db->executeQuery($query);
$query = "ALTER TABLE {$config->tables['users']} AUTO_INCREMENT=1";
$db->executeQuery($query);

// Set up admin group
$adminGroup = new Group(0,'Administrators',
	Group::PERM_MAKE_OK,
	Group::PERM_EDIT_ALL,
	Group::PERM_MAKE_OK,
	Group::PERM_EDIT_ALL,
	true
);	
$db->addGroup($adminGroup);

// Add admin user
$adminUser = new User(0,'admin','Administrator','password',1);
$db->addUser($adminUser);

// Set up regular users group
$regGroup = new Group(0,'Users',
	Group::PERM_MAKE_NONE,
	Group::PERM_EDIT_NONE,
	Group::PERM_MAKE_OK,
	Group::PERM_EDIT_OWN,
	true
);	
$db->addGroup($regGroup);

// Add sample post
$samplePost = new Post(0,1,'test title"\'$*$^@#)(%&!$',0,true,null,'http://google.com','hi this is the content of the post');
$db->addPost($samplePost);

?>

