<?php
define('SQL_SERVER','localhost');
define('SQL_USERNAME','comics');
define('SQL_PASSWORD','comics');
define('SQL_DB','comics');
define('SQL_TABLE_PREFIX','');

/* Default Gravatar. Valid options:
 * 404, mm, identicon, monsterid, wavatar, retro, blank
 * See: https://en.gravatar.com/site/implement/images/ 
 */
define('USER_ICON_DEFAULT','blank'); 

// Gravatar icon size
define('USER_ICON_SIZE','32');

// Gravatar icon rating. Valid options: g, pg, r, x
define('USER_ICON_RATING','g'); 

define('POSTS_DEFAULT_NUM','10');
define('POSTS_MAX_NUM','50');

/*
 * Stop editing below this line
 */
define('API_PATH',dirname(__FILE__).'/../');

function restcomic_autoload($class_name) {
	$file = API_PATH."classes/$class_name.class.php";
	if (file_exists($file)) 
		require_once $file;
	else
		return;
}
spl_autoload_register('restcomic_autoload');
require_once API_PATH.'includes/checks.php';
require_once API_PATH.'lib/php-markdown/markdown.php';

// Create new config object
$config = new Config(SQL_TABLE_PREFIX);

// Connect to database
$db = new DatabaseWrapper(SQL_SERVER,SQL_USERNAME,SQL_PASSWORD,SQL_DB);
