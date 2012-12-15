<?php
require_once 'includes/config.php';

define('ADMIN_COLOR','#ff0000');
define('UNREG_COLOR','#000000');
define('REG_COLOR','#000000');

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
	group_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(".Group::$limits['name'].") NOT NULL,
	color MEDIUMINT UNSIGNED DEFAULT 0 NOT NULL,
	admin_perm BOOLEAN DEFAULT FALSE NOT NULL,
	make_post_perm TINYINT NOT NULL,
	edit_post_perm TINYINT NOT NULL,
	make_comment_perm TINYINT NOT NULL,
	edit_comment_perm TINYINT NOT NULL,
	UNIQUE(name),
	PRIMARY KEY(group_id)
)";
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['users']} (
	user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	group_id INT UNSIGNED NOT NULL,
	login VARCHAR(".User::$limits['login'].") NOT NULL,
	name VARCHAR(".User::$limits['name'].") NOT NULL,
	password CHAR(".User::$limits['password'].") NOT NULL,
	date_registered DATETIME NOT NULL,
	email VARCHAR(".User::$limits['email']."),
	website TEXT,
	api_key VARCHAR(".User::$limits['api_key']."),
	UNIQUE(login),
	PRIMARY KEY(user_id),
	FOREIGN KEY(group_id) REFERENCES groups(group_id) ON DELETE SET DEFAULT
)";
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['posts']} (
	post_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	title VARCHAR(".Post::$limits['title'].") NOT NULL,
	title_slug VARCHAR(".Post::$limits['title'].") NOT NULL,
	status TINYINT NOT NULL,
	commentable BOOLEAN DEFAULT TRUE NOT NULL,
	timestamp DATETIME NOT NULL,
	image_url TEXT,
	content TEXT,
	comment_count INT UNSIGNED NOT NULL,
	UNIQUE(title_slug),
	PRIMARY KEY(post_id),
	FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE SET DEFAULT
)";
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['tags']} (
	tag_id INT UNSIGNED AUTO_INCREMENT,
	name VARCHAR(".Tag::$limits['name'].") NOT NULL,
	UNIQUE(name),
	PRIMARY KEY(tag_id)
)";
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['post_tags']} (
	post_id INT UNSIGNED NOT NULL,
	tag_id INT UNSIGNED NOT NULL,
	PRIMARY KEY(post_id,tag_id),
	FOREIGN KEY(post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
	FOREIGN KEY(tag_id) REFERENCES tags(tag_id) ON DELETE CASCADE
)";
$db->executeQuery($query);

$query = "CREATE TABLE {$config->tables['comments']} (
	comment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	post_id INT UNSIGNED NOT NULL,
	user_id INT UNSIGNED,
	parent_comment_id INT UNSIGNED, 
	timestamp DATETIME NOT NULL,
	ip VARCHAR(".Comment::$limits['ip'].") NOT NULL,
	visible BOOLEAN DEFAULT TRUE NOT NULL,
	content TEXT NOT NULL,
	name VARCHAR(".Comment::$limits['name']."),
	PRIMARY KEY(comment_id),
	FOREIGN KEY(post_id) REFERENCES posts(post_id) ON DELETE CASCADE,
	FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE SET DEFAULT,
	FOREIGN KEY(parent_comment_id) REFERENCES comments(comment_id) 
		ON DELETE CASCADE
)";
$db->executeQuery($query);

try{
/*=====================*
 | Set up default rows |
 *=====================*/
// Create unregistered group
$unregGroup = new Group;
$unregGroup->setValues(0,'Unregistered',UNREG_COLOR,false,
	Group::PERM_MAKE_NONE, Group::PERM_EDIT_NONE, 
	Group::PERM_MAKE_OK, Group::PERM_EDIT_NONE);
$db->insertObjectIntoTable($unregGroup);

// Set the unregistered group to have group id 0
$query = "UPDATE {$config->tables['groups']} SET group_id=0 WHERE group_id=1";
$db->executeQuery($query);
$query = "ALTER TABLE {$config->tables['groups']} AUTO_INCREMENT=1";
$db->executeQuery($query);

// Create admin group
$adminGroup = new Group();
$adminGroup->setValues(0,'Administrators',ADMIN_COLOR,true,
	Group::PERM_MAKE_OK, Group::PERM_EDIT_ALL, 
	Group::PERM_MAKE_OK, Group::PERM_EDIT_ALL);
$db->insertObjectIntoTable($adminGroup);

// Create regular group
$regGroup = new Group();
$regGroup->setValues(0,'Users',REG_COLOR,false,
	Group::PERM_MAKE_NONE, Group::PERM_EDIT_NONE, 
	Group::PERM_MAKE_OK, Group::PERM_EDIT_OWN);
$db->insertObjectIntoTable($regGroup);

// Add unregistered user
$unregUser = new User;
$unregUser->setValues(0,2,'unregistered','Unregistered','unreguserpass');
$unregUser->hashPassword();
$db->insertObjectIntoTable($unregUser);

// Set the unregistered user to have user id 0
$query = "UPDATE {$config->tables['users']} SET user_id=0 WHERE user_id=1";
$db->executeQuery($query);
$query = "ALTER TABLE {$config->tables['users']} AUTO_INCREMENT=1";
$db->executeQuery($query);

$query = "UPDATE {$config->tables['users']} SET api_key='' 
	WHERE user_id=0";
$db->executeQuery($query);
$query = "UPDATE {$config->tables['users']} SET group_id=0 
	WHERE user_id=0";
$db->executeQuery($query);

// Add admin user
$adminUser = new User;
$adminUser->setValues(0,2,'admin','Administrator','password');
$adminUser->hashPassword();
$db->insertObjectIntoTable($adminUser);
$query = "UPDATE {$config->tables['users']} SET group_id=1 
	WHERE user_id=1";
$db->executeQuery($query);

// Add sample post
$firstPost = new Post;
$firstPost->setValues(0,1,"Welcome to restcomic!",null,0,true,'now','',"This is the first post! Log in to the admin user to edit this.");
$db->insertObjectIntoTable($firstPost);

} catch(APIError $e) {
	echo json_encode($e->getErrors());
}
