<?php

function getCommentsByPostId($id) {
	try { 
		$comments = APICommentsFactory::getCommentsByPostId($id);
		$result = new APIResult($comments);
		setUp($result,'/posts/id/'.$id);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getCommentsByPostSlug($slug) {
	try { 
		$comments = APICommentsFactory::getCommentsByPostSlug($slug);
		$result = new APIResult($comments);
		setUp($result,'/posts/'.$slug);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getComments($perPage=0,$page=1,$desc=true,$from=null,$to=null) {
	try {
		$comments = APICommentsFactory::getCommentsBetweenIds(
			$from,$to,$desc,$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getCommentsByIds($idList,$perPage=0,$page=1,$desc=false) {
	try {
		$ids = explode(',',(string)$idList);

		$comments = APICommentsFactory::getCommentsByIds($ids,$desc,
				$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'/comments/');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;	
}

function getCommentsByAuthorId($id,$perPage=0,$page=1,$desc=true) {
	try {
		$comments = APICommentsFactory::getCommentsByAuthorId($id,$desc,
			$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'/comments');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getCommentsByAuthorLogin($login,$perPage=0,$page=1,$desc=true) {
	try {
		$comments = APICommentsFactory::getCommentsByAuthorLogin($login,
			$desc,$perPage,$page);
		$result = new APIResult($comments);
		paginate($result);
		setUp($result,'/comments');
	}
	catch (APIError $e) {
		$result = new APIResult(null,$e); 
	}
	return $result;
}

