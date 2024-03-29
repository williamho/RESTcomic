<?php
/**
 * Check if string contains alphanumeric and underscore characters
 * @param string $str The string being checked
 * @return bool True if string contains only alphanumerics and underscores
 */
function checkAlphanumUnderscore($str) {
	return preg_match('/^[a-zA-Z0-9_]+$/',$str);
}

/**
 * Check if date is valid  
 * @param string $timestamp A timestamp, in whatever format
 * @return mixed If null input, outputs current time. 
				 If valid input, converts to mySQL format. 
				 If invalid input, returns null.
 */
function convertDatetime($timestamp) {
	if (!$timestamp)
		return date(Config::timeFormat);
	else if ($unixTime = strtotime($timestamp))
		return date(Config::timeFormat,$unixTime);
	else
		return null;
}

// toAscii via http://cubiq.org/the-perfect-php-clean-url-generator
setlocale(LC_ALL, 'en_US.UTF8');
function makeSlug($str, $replace='\'', $delimiter='-') {
	if( !empty($replace) ) {
		$str = str_replace((array)$replace, ' ', $str);
	}

	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
	$clean = strtolower(trim($clean, '-'));
	$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

	return $clean;
}

function intArrayToString(array $arr) {
	foreach ($arr as &$el) {
		if (!is_int($el) && !ctype_digit($el)) 
			throw new APIError(2001);
		$el = (int)$el;
	}
	return implode(',',$arr);
}

function slugifyArray(array $arr) {
	foreach ($arr as &$el)
		$el = makeSlug($el);
	return $arr;
}

function slugArrayToString(array $arr) {
	foreach ($arr as &$el)
		$el = '\''.makeSlug($el).'\'';
	return implode(',',$arr);
}

function initArrayKeys($keys, $val=null) {
	$arr = array();
	foreach($keys as $key)
		$arr[$key] = $val;

	return $arr;
}

function toColor($num) {
	return "#".substr("000000".dechex($num),-6);
}

function stringToBool($str) {
	$str = trim(strtolower((string)$str));

	if ($str === 'false' ||
		$str === 'no' ||
		$str === '0' ||
		$str === 'null' ||
		$str === '')
		return false;
	return true;
}

function getCurrentDomain() {
	$scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on") ?
		'http' : 'https';
	$pageURL = $scheme . '://' . $_SERVER['SERVER_NAME'];
	if ($_SERVER['SERVER_PORT'] != '80')
		$pageURL .= ':'.$_SERVER['SERVER_PORT'];
	return $pageURL;
}

function getCurrentAPIURL() {
	$pageURL = getCurrentDomain();
	$pageURL .= parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
	return $pageURL;
}

function getCurrentFileURL() {
	$pageURL = getCurrentDomain();
	$pageURL .= dirname($_SERVER['PHP_SELF']);
	return $pageURL;
}

function setUp(APIResult &$content, $uri) {
	$content->meta->up = getCurrentFileURL() . $uri;
}

function paginate(APIResult &$content,$perPage=0,$page=1,$desc=true) {
	global $app;
	$baseURL = getCurrentAPIURL();
	$params = $app->request()->get();

	if ($perPage <= 0)
		$perPage = POSTS_DEFAULT_NUM;

	if ($page < 1)
		$page = 1;

	$params = array('page'=>$page,'perpage'=>$perPage,'reverse'=>$desc);

	if ($params['page'] <= 1)
		$content->meta->prev = null;
	else {
		$params['page'] = $page-1;
		$content->meta->prev = $baseURL.'?'.
			http_build_query($params);
	}

	if (count($content->response) < $perPage)
		$content->meta->next = null;
	else {
		$params['page'] = $page+1;
		$content->meta->next = $baseURL.'?'.
			http_build_query($params);
	}
}

// Array of APIPost objects
function convertMarkdown(array &$apiPosts) {
	global $app;
	$format = $app->request()->get('format');

	if (is_null($format))
		$format = 'html';
	else
		$format = trim(strtolower($format));
	
	switch($format) {
	case 'html':
		foreach ($apiPosts as $apiPost)
			$apiPost->content = markdown($apiPost->content);
		break;
	case 'md': 
	case 'markdown':
		break; // Do nothing
	default:
		$app->response()->status(400);
		throw new APIError(1210); // Invalid format specified
	}
}
