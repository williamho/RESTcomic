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
	//$user_id = getUserId();
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$from = $app->request()->get('from');
	$to = $app->request()->get('to');

	$result = getPostsBetweenIds($from,$to,$desc,$perPage,$page);
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
		$title = paramPost('title');
		$status = (int)paramPost('status');
		if ($user->make_post == Group::PERM_MAKE_HIDDEN)
			$status = Post::STATUS_HIDDEN; // Set the post to hidden
		if (paramPost('commentable'))
			$commentable = (bool)paramPost('commentable');
		else
			$commentable = false;
		if (!($timestamp = paramPost('timestamp')))
			$timestamp = 'now'; 
		$image = paramPost('image_url');
		$tags = paramPost('tags');
		$content = paramPost('content');

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
	//$user_id = getUserId();
	$result = getPostBySlug($slug);
	output($result);
});

$app->get('/posts/id/:idList', function($idList) use($app) {
	// Get posts by comma-separated IDs
	//$user_id = GetUserId();
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));
	$result = getPostsByIds($idList,$desc,$perPage,$page);
	output($result);
});

$app->get('/posts/by_author/id/:id', function($id) use($app) {
	// Get posts by author ID
	//$user_id = getUserId();
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$result = getPostsByAuthorId($id,$desc,$perPage,$page);
	output($result);
});

$app->get('/posts/by_author/:login', function($login) use($app) {
	// Get posts by author login
	//$user_id = getUserId();
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$result = getPostsByAuthorLogin($login,$desc,$perPage,$page);
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
			break;
		case Group::PERM_EDIT_GROUP:
			$author = $db->getPostAuthor();
			if ($author->group_id != $user->group_id)
				throw new APIError(1207); 
			break;
		}
		$db->deletePost($id);
		$result = new APIResult();
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->delete('/groups/id/:id', function($id) use($app) {
	try {
		global $db;
		$user = APIOAuth::validate();

		if (!$user->admin)
			throw new APIError(1111);
		$db->deleteGroup($id);
		$result = new APIResult();
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->get('/posts/tagged/:tagList', function($tagList) use($app) {
	//$user_id = getUserId();
	$and = !stringToBool($app->request()->get('any'));
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$result = getPostsTagged($tagList,$perPage,$page,$desc,$and);
	output($result);
});

$app->get('/posts/id/:id/comments', function($id) {
	// Get comments by post ID
	$result = getCommentsByPostId($id);
	output($result);
});

$app->get('/posts/:slug/comments', function($slug) {
	// Get comments by post slug
	$result = getCommentsByPostSlug($slug);
	output($result);
});

$app->get('/comments', function() use($app) {
	// Get most recent comments
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$from = $app->request()->get('from');
	$to = $app->request()->get('to');

	$result = getComments($perPage,$page,$desc,$from,$to);
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
		if (!is_null($parent = paramPost('parent_comment_id'))) {
			// Check if parent comment even exists
			$parent = (int)$parent;
			$post_id = (int)$db->getPostIdFromCommentId($parent);
		}
		else {
			$post_id = (int)$post_id;
			$parent = null;
		}
		if (!($timestamp = paramPost('timestamp')))
			$timestamp = 'now'; 
		$ip = paramPost('ip');
		$visible = true;
		$content = paramPost('content');
		$name = paramPost('name');
		if ($user->user_id == 0 && $name == '')
			$name = $user->name;

		if ($content === '')
			throw new APIError(1308); // comment empty

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

		$apiComment = APICommentsFactory::getCommentsByIds($comment_id);
		$result = new APIResult($apiComment); 
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

		switch($user->edit_comment) {
		case Group::PERM_EDIT_NONE:
			throw new APIError(1306); // Invalid edit permissions
			break;
		case Group::PERM_EDIT_OWN:
			$author = $db->getCommentAuthor($id);
			if ($author->user_id != $user->user_id)
				throw new APIError(1306); 
			break;
		case Group::PERM_EDIT_GROUP:
			$author = $db->getCommentAuthor($id);
			if ($author->group_id != $user->group_id)
				throw new APIError(1306); 
			break;
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
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$result = getCommentsByIds($idList,$perPage,$page,$desc);
	output($result);
});

$app->get('/comments/by_author/id/:id', function($id) use($app) {
	// Get comments by author ID
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));

	$result = getCommentsByAuthorId($id,$perPage,$page,$reverse);
	output($result);
});

$app->get('/comments/by_author/:login', function($login) use($app) {
	// Get comments by author login
	$perPage = $app->request()->get('perpage');
	$page = $app->request()->get('page');
	$desc = stringToBool($app->request()->get('reverse'));
	
	$result = getCommentsByAuthorLogin($login,$perPage,$page,$desc);
	output($result);
});

$app->get('/users/id/:idList', function($idList) use($app) {
	$getemail = $app->request()->get('getemail');

	$result = getUsersByIds($idList,$getemail);
	output($result);
});

$app->get('/users/:login', function($login) {
	$result = getUserByLogin($login);
	output($result);
});

$app->post('/users', function() {
	// Add a new user
	try {
		global $db;

		$login = paramPost('username');
		$pass = paramPost('password');
		$confirmpass = paramPost('confirmpassword');

		if ($pass !== $confirmpass)
			throw new APIError(1010);

		$name = paramPost('name');
		$email = paramPost('email');
		$website = paramPost('website');
		$timestamp = null;

		$user = new User;
		$user->setValues(0,2,$login,$name,$pass,'now',$email,$website);
		$user->hashPassword();
		$user_id = $db->insertObjectIntoTable($user);

		$result = new APIResult(APIUsersFactory::getUsersByIds($user_id));
		setUp($result,'/users');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e);
	}
	output($result);
});



$app->post('/groups', function() {
	try {
		global $db;
		$user = APIOAuth::validate();
		if (!$user->admin)
			throw new APIError(1111);

		$name = paramPost('name');
		$color = paramPost('color');
		$admin = (bool)paramPost('admin_perm');
		$mp = (int)paramPost('make_post_perm');
		$ep = (int)paramPost('edit_post_perm');
		$mc = (int)paramPost('make_comment_perm');
		$ec = (int)paramPost('edit_comment_perm');

		$group = new Group;
		$group->setValues(0,$name,$color,$admin,$mp,$ep,$mc,$ec);
		$group_id = $db->insertObjectIntoTable($group);

		$result = new APIResult(APIGroupsFactory::getGroupsByIds($group_id));
		setUp($result,'/groups');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e);
	}
	output($result);
});

$app->get('/groups/id/:idList', function($idList) {
	$result = getGroupsByIds($idList);
	output($result);
});

$app->get('/groups', function() {
	$result = getGroups();
	output($result);
});

function checkOld(&$new, &$old) {
	foreach ($old as $var=>$val) {
		if ($var != 'password')
			$new->$var = paramPut($var);
		if (!isset($new->$var) || $new->$var == '' || is_null($new->$var))
			$new->$var = $old->$var;
	}
}

$app->put('/posts/id/:post_id', function($post_id) use($app) {
	// Replace a post
	try {
		global $db;
		$user = APIOAuth::validate();

		switch($user->edit_post) {
		case Group::PERM_EDIT_NONE:
			throw new APIError(1207); // Invalid edit permissions
			break;
		case Group::PERM_EDIT_OWN:
			$author = $db->getPostAuthor($post_id);
			if ($author->user_id != $user->user_id)
				throw new APIError(1207); 
			break;
		case Group::PERM_EDIT_GROUP:
			$author = $db->getPostAuthor($post_id);
			if ($author->group_id != $user->group_id)
				throw new APIError(1207); 
			break;
		}

		//$post_id = paramPut('post_id'); // comment out
		$oldPost = $db->getObjectsFromTableByIds('posts',$post_id);
		if (empty($oldPost))
			throw new APIError(1201);
		$oldPost = $oldPost[0];
		$post = new Post;
		$post->post_id = $post_id;
		checkOld($post,$oldPost);
		$tags = paramPut('tags');

		$db->insertObjectIntoTable($post,false);

		if ($tags != '') {
			$tagsArray = explode(',',$tags);
			$db->addTagsToPost($tagsArray,$post_id);
		}

		$result = new APIResult(APIPostsFactory::getPostsByIds(
			$post_id,0,0,0,$user->user_id)); 
		setUp($result,'/posts');
		convertMarkdown($result->response);
	}
	//catch(Exception $e) {
		//$a = array('errors' => array($e->getMessage(),$e->getLine(), $e->getFile()));
		//die(json_encode($a));
	//}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	output($result);
});

$app->put('/comments/id/:comment_id',function($comment_id) use($app) {
	// Replace comment
	try {
		global $db;
		$user = APIOAuth::validate();

		switch($user->edit_comment) {
		case Group::PERM_EDIT_NONE:
			throw new APIError(1306); // Invalid edit permissions
			break;
		case Group::PERM_EDIT_OWN:
			$author = $db->getCommentAuthor($comment_id);
			if ($author->user_id != $user->user_id)
				throw new APIError(1306); 
			break;
		case Group::PERM_EDIT_GROUP:
			$author = $db->getCommentAuthor($comment_id);
			if ($author->group_id != $user->group_id)
				throw new APIError(1306); 
			break;
		}

		//$comment_id = paramPut('comment_id'); // comment out
		$oldComment = $db->getObjectsFromTableByIds('comments',$comment_id);

		if (empty($oldComment))
			throw new APIError(1301);
		$oldComment = $oldComment[0];

		$comment = new Comment;
		//$comment->comment_id = $comment_id;
		//checkOld($comment,$oldComment,'comment_id');
		checkOld($comment,$oldComment);
		
		$db->insertObjectIntoTable($comment,false);
		$result = new APIResult(APICommentsFactory::getCommentsByIds(
			$comment_id)); 
		setUp($result,'/posts/id/'.$comment->post_id.'/comments');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e);
	}
	output($result);
});

$app->put('/users/id/:user_id', function($user_id) {
	try {
		global $db;
		$apiuser = APIOAuth::validate();

		//$user_id = paramPut('user_id');//comment out

		if (!$apiuser->admin && $apiuser->user_id != $user_id)
			throw new APIError(1015);

		$oldUser = $db->getObjectsFromTableByIds('users',$user_id);
		if (empty($oldUser))
			throw new APIError(1001);
		$oldUser = $oldUser[0];

		$password = paramPut('password');
		$confirmpassword = paramPut('confirmpassword');
		$previouspassword = paramPut('previouspassword');

		$user = new User;
		$user->user_id = $user_id;
		if (!is_null($password) && $password != '' 
			&& $previouspassword != '') 
		{
			if ($password != $confirmpassword)
				throw new APIError(1010); // PWs don't match
			if (!User::$hasher->checkPassword(
				$previouspassword,$oldUser->password))
				throw new APIError(1016); // invalid old PW
			$user->password = $password;
			$user->hashPassword();
		}

		checkOld($user,$oldUser);
		// Can't assign users to unregistered group
		if ($user->group_id == 0 && $user->user_id != 0)
			throw new APIError(1017);
		// Can't change group of unregistered user
		if ($user->user_id == 0 && $user->group_id != 0)
			throw new APIError(1018);

		$user->login = $oldUser->login; // login cannot be changed

		$db->insertObjectIntoTable($user,false);
		$result = new APIResult(APIUsersFactory::getUsersByIds($user_id)); 
		setUp($result,'/users');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e);
	}
	output($result);
});

$app->put('/groups/id/:group_id', function($group_id) {
	try {
		global $db;
		$user = APIOAuth::validate();
		if (!$user->admin)
			throw new APIError(1111);

		$oldGroup = $db->getObjectsFromTableByIds('groups',$group_id);
		if (empty($oldGroup))
			throw new APIError(1101);
		$oldGroup = $oldGroup[0];

		$group = new Group;
		checkOld($group,$oldGroup);

		$group_id = $db->insertObjectIntoTable($group,false);
		$result = new APIResult(APIGroupsFactory::getGroupsByIds($group_id));
		setUp($result,'/groups');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e);
	}
	output($result);
});

$app->get('/', function() {
	$response = array(
		makeRouteDecription('/posts?reverse=true',
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
		makeRouteDecription('/comments?reverse=true',
			'Most recent comments'),
		makeRouteDecription('/comments/id/1,2,3',
			'Comments with comma-separated IDs'),
		makeRouteDecription('/comments/by_author/admin',
			'Comments by author with login'),
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



function output(APIResult $content) {
	global $app;
	$app->contentType('application/json');

	$json = json_encode($content);

	// Output JSONP if callback requested
	if (($callback = $app->request()->get('callback')) !== null)
		$json = "$callback($json)";

	echo $json;
}

function getUserId() {
	try {
		$user = APIOAuth::validate();
		return $user->user_id;
	}
	catch (APIError $e) { return 0; }
}

function paramPost($key) {
	// Get POST parameter if exists
	global $app;
	return $app->request()->post($key);
}

function paramPut($key) {
	// Get PUT parameter if exists
	global $app;
	return $app->request()->put($key);
}

