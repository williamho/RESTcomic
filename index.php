<?php
require_once 'includes/config.php';
require 'lib/Slim/Slim/Slim.php';
\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->notFound(function() {
	$result = new APIResult(array());
	$error = new APIError(404);
	$result->setErrors($error);
	output($result);
});

$app->get('/', function() {
	echo 'hi';	
});

$app->get('/posts/id/:idList', function($idList) {
	try { 
		$ids = explode(',',$idList);
		$posts = APIPostsFactory::getPostsByIds($ids);
		$result = new APIResult($posts);
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/tagged/:tagList', function($tagList) {
	global $app;
	try { 
		$and = !stringToBool($app->request()->get('any'));
		$tags = explode(',',$tagList);
		
		$include = array();
		$exclude = array();
		foreach ($tags as $tag) {
			if ($tag[0] === '-')
				array_push($exclude, $tag);
			else
				array_push($include, $tag);
		}

		if (empty($exclude))
			$posts = APIPostsFactory::getPostsByTags($tags,$and);
		else
			$posts = APIPostsFactory::getPostsByTagsExclude(
						$include,$exclude,$and);

		$result = new APIResult($posts);
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}

	output($result);
});

$app->get('/posts/id/:id/comments', function($id) {
	try { 
		$id = (int)$id;
		$comments = APICommentsFactory::getCommentsByPostId($id);
		$result = new APIResult($comments);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/users/id/:idList', function($idList) {
	try {
		$ids = explode(',',$idList);
		$users = APIUsersFactory::getUsersByIds($ids);
		$result = new APIResult($users);
	}	
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}

	output($result);
});

$app->get('/groups/id/:idList', function($idList) {
	try {
		$ids = explode(',',$idList);
		$groups = APIGroupsFactory::getGroupsByIds($ids);
		$result = new APIResult($groups);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}

	output($result);
});



$app->post('/post', function() {
	
});

$app->put('/put', function () {
    echo 'This is a PUT route';
});

$app->delete('/delete', function () {
    echo 'This is a DELETE route';
});

$app->run();

function output(APIResult $content) {
	global $app;
	$app->contentType('application/json');

	$json = json_encode($content);

	// Output JSONP if callback requested
	if (($callback = $app->request()->get('callback')) !== null)
		$json = "$callback($json)";

	echo $json;
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

