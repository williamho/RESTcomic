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

$app->error(function(\Exception $e) {
	$result = new APIResult(array());
	$error = new APIError(500);
	$result->setErrors($error);
	output($result);
});

$app->get('/posts', function() use ($app) {
	// Get most recent posts
	try {
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$from = $app->request()->get('from');
		$to = $app->request()->get('to');

		$posts = APIPostsFactory::getPostsBetweenIds(
			$from,$to,$desc,$perPage,$page);
		$result = new APIResult($posts);
		paginate($result);
		setUp($result,'');
		convertMarkdown($result->response);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->post('/posts', function() use($app) {
	// Add a new post
	try {
		global $db;
		$user = APIOAuth::validate();

		if ($user->make_post == Group::PERM_MAKE_NONE) {
			throw new APIError(1206); // Invalid post permissions
			$app->response()->status(401);
		}

		// Check if params are set
		$user_id = (int)$user->user_id;
		$title = param_post('title');
		$status = (int)param_post('status');
		if ($user->make_post == Group::PERM_MAKE_HIDDEN)
			$status = Post::STATUS_HIDDEN; // Set the post to hidden
		if (param_post('commentable'))
			$commentable = (bool)param_post('commentable');
		else
			$commentable = false;
		if (!($timestamp = param_post('timestamp')))
			$timestamp = 'now'; 
		$image = param_post('image_url');
		$tags = param_post('tags');
		$content = param_post('content');

		$post = new Post;
		$post->setValues(0,$user_id,$title,null,$status,$commentable,
			$timestamp,$image,$content);
		$post_id = $db->insertObjectIntoTable($post);

		if ($tags !== '') {
			$tagsArray = explode(',',$tags);
			$db->addTagsToPost($tagsArray,$post_id);
		}
		$result = new APIResult(APIPostsFactory::getPostsByIds(
			$post_id,0,0,0,$user->user_id)); 
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}

	output($result);
});

$app->get('/posts/:slug', function($slug) use($app) {
	// Get post by title slug
	$user_id = getUserId();
	try { 
		$posts = APIPostsFactory::getPostBySlug($slug,$user_id);
		$result = new APIResult($posts);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/id/:idList', function($idList) use($app) {
	// Get posts by comma-separated IDs
	$user_id = getUserId();
	try { 
		$ids = explode(',',$idList);
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$posts = APIPostsFactory::getPostsByIds($ids,$desc,$perPage,$page,
			$user_id);
		$result = new APIResult($posts);
		paginate($result);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/by_author/id/:id', function($id) use($app) {
	// Get posts by author ID
	$user_id = getUserId();
	try { 
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$posts = APIPostsFactory::getPostsByAuthorId($id,$desc,$perPage,
			$page,$user_id);
		$result = new APIResult($posts);
		paginate($result);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/by_author/:login', function($login) use($app) {
	// Get posts by author login
	$user_id = getUserId();
	try { 
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$posts = APIPostsFactory::getPostsByAuthorLogin($login,$desc,$perPage,
			$page,$user_id);
		$result = new APIResult($posts);
		paginate($result);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->delete('/posts/id/:id', function($id) use($app) {
	// Delete single post by post ID
	try {
		global $db;
		$user = APIOAuth::validate();

		switch($user->edit_post) {
		case Group::PERM_EDIT_NONE:
			throw new APIError(1207); // Invalid edit permissions
			break;
		case Group::PERM_EDIT_OWN:
			$author = $db->getPostAuthor();
			if ($author->user_id != $user->user_id)
				throw new APIError(1207); 
		case Group::PERM_EDIT_GROUP:
			$author = $db->getPostAuthor();
			if ($author->group_id != $user->group_id)
				throw new APIError(1207); 
		}
		$db->deletePost($id);
		$result = new APIResult();
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/tagged/:tagList', function($tagList) use($app) {
	$user_id = getUserId();
	try { 
		$and = !stringToBool($app->request()->get('any'));
		$tags = explode(',',$tagList);
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));
		
		$include = array();
		$exclude = array();
		foreach ($tags as $tag) {
			if ($tag[0] === '-')
				array_push($exclude, $tag);
			else
				array_push($include, $tag);
		}

		if (empty($exclude))
			$posts = APIPostsFactory::getPostsByTags($tags,$and,
				$desc,$perPage,$page,$user_id);
		else if (empty($include))
			throw new APIError(1404); // No included tags
		else
			$posts = APIPostsFactory::getPostsByTagsExclude(
				$include,$exclude,$and,$desc,$perPage,$page,$user_id);

		$result = new APIResult($posts);
		paginate($result);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}

	output($result);
});

$app->get('/posts/id/:id/comments', function($id) {
	// Get comments by post ID
	try { 
		$comments = APICommentsFactory::getCommentsByPostId($id);
		$result = new APIResult($comments);
		setUp($result,'/posts/id/'.$id);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/:slug/comments', function($slug) {
	// Get comments by post slug
	try { 
		$comments = APICommentsFactory::getCommentsByPostSlug($slug);
		$result = new APIResult($comments);
		setUp($result,'/posts/'.$slug);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/comments', function() use($app) {
	// Get most recent comments
	try {
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$from = $app->request()->get('from');
		$to = $app->request()->get('to');

		$comments = APICommentsFactory::getCommentsBetweenIds(
			$from,$to,$desc,$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->post('/posts/id/:post_id/comments', function($post_id) {
	// Post a comment
	try {
		global $db;
		$user = APIOAuth::validate();

		if ($user->make_comment == Group::PERM_MAKE_NONE) {
			throw new APIError(1305); // Invalid comment permissions
			$app->response()->status(401);
		}
		// Check if params are set
		$user_id = (int)$user->user_id;
		if (($parent = param_post('parent_comment_id'))!=='') {
			// Check if parent comment even exists
			$parent = (int)$parent;
			$post_id = $db->getPostIdFromCommentId($parent);
		}
		else {
			$post_id = (int)$post_id;
			$parent = null;
		}
		if (!($timestamp = param_post('timestamp')))
			$timestamp = 'now'; 
		$ip = param_post('ip');
		$visible = true;
		$content = param_post('content');
		$name = param_post('name');

		// Check if post is commentable if user is not admin
		$posts = $db->getObjectsFromTableByIds('posts',$post_id);

		if (empty($posts))
			throw new APIError(1201); // Post doesn't exist
		if (!$user->admin) {
			$post = $posts[0];
			if (!$post->commentable)
				throw new APIError(1307); // Invalid permissions on this post
		}

		$comment = new Comment;
		$comment->setValues(0,$post_id,$user_id,$parent,$timestamp,
			$ip,$visible,$content,$name);
		$comment_id = $db->insertObjectIntoTable($comment);

		$result = new APIResult(APICommentsFactory::getCommentsByIds(
			$comment_id)); 
		setUp($result,'/posts/id/'.$post_id.'/comments');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->delete('/comments/id/:id', function($id) use($app) {
	// Delete single comment by comment ID
	try {
		global $db;
		$user = APIOAuth::validate();

		switch($user->edit_post) {
		case Group::PERM_EDIT_NONE:
			throw new APIError(1306); // Invalid edit permissions
			break;
		case Group::PERM_EDIT_OWN:
			$author = $db->getCommentAuthor();
			if ($author->user_id != $user->user_id)
				throw new APIError(1306); 
		case Group::PERM_EDIT_GROUP:
			$author = $db->getCommentAuthor();
			if ($author->group_id != $user->group_id)
				throw new APIError(1306); 
		}
		$db->deleteComment($id);
		$result = new APIResult();
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/comments/id/:idList', function($idList) use($app) {
	// Get comments by comma-separated list of IDs
	try {
		$ids = explode(',',$idList);
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$comments = APICommentsFactory::getCommentsByIds($ids,$desc,
				$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'/comments/');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/comments/by_author/id/:id', function($id) use($app) {
	// Get comments by author ID
	try {
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$comments = APICommentsFactory::getCommentsByAuthorId($id,$desc,
			$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'/comments');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/comments/by_author/:login', function($login) use($app) {
	// Get comments by author login
	try {
		$perPage = $app->request()->get('perpage');
		$page = $app->request()->get('page');
		$desc = stringToBool($app->request()->get('reverse'));

		$comments = APICommentsFactory::getCommentsByAuthorLogin($login,
			$desc,$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'/comments');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e); 
	}
	output($result);
});
$app->get('/users/id/:idList', function($idList) {
	try {
		$ids = explode(',',$idList);
		$users = APIUsersFactory::getUsersByIds($ids);
		$result = new APIResult($users);
		setUp($result,'/users');
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
		setUp($result,'/groups');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}

	output($result);
});

$app->put('/put', function () {
    echo 'This is a PUT route';
});

$app->get('/', function() {
	$response = array(
		makeRouteDecription('/posts',
			'Most recent posts'),
		makeRouteDecription('/posts/id/1,2,3',
			'Posts with comma-separated IDs'),
		makeRouteDecription('/posts/title',
			'Post with title slug'),
		makeRouteDecription('/posts/by_author/admin',
			'Posts by author with name'),
		makeRouteDecription('/posts/by_author/id/1',
			'Posts by author with ID'),
		makeRouteDecription('/posts/tagged/tag1,tag2',
			'Posts with comma-separated tags'),
		makeRouteDecription('/comments',
			'Most recent comments'),
		makeRouteDecription('/comments/id/1,2,3',
			'Comments with comma-separated IDs'),
		makeRouteDecription('/comments/by_author/admin',
			'Comments by author with name'),
		makeRouteDecription('/comments/by_author/id/1',
			'Comments by author with ID'),
		makeRouteDecription('/groups/id/1,2,3',
			'Groups with comma-separated IDs')
	);
	$result = new APIResult($response);
	output($result);
});

$app->run();

function makeRouteDecription($route, $desc) {
	return array(
		'about' => $desc,
		'down' => getCurrentFileURL().$route
	);
}

function setUp(APIResult &$content, $uri) {
	$content->meta['up'] = getCurrentFileURL() . $uri;
}

function paginate(APIResult &$content) {
	global $app;
	$baseURL = getCurrentAPIURL();
	$params = $app->request()->get();

	if (isset($params['page'])) 
		$page = $params['page'];
	else {
		$params['page'] = $page = 1;
	}

	if (!isset($params['perpage'])) 
		$perPage = POSTS_DEFAULT_NUM;
	else
		$perPage = $params['perpage'];

	if ($params['page'] <= 1)
		$content->meta['prev'] = null;
	else {
		$params['page'] = $page-1;
		$content->meta['prev'] = $baseURL.'?'.
			http_build_query($params);
	}

	if (count($content->response) < $perPage)
		$content->meta['next'] = null;
	else {
		$params['page'] = $page+1;
		$content->meta['next'] = $baseURL.'?'.
			http_build_query($params);
	}
}

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

function getUserId() {
	try {
		$user = APIOAuth::validate();
		return $user->user_id;
	}
	catch (APIError $e) { return 0; }
}

function param_post($key) {
	// Get POST parameter if exists
	global $app;
	return $app->request()->post($key);
}
