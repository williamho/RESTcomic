<?php

function getPostsBetweenIds($from=null,$to=null,$desc=true,$perPage=0,$page=1) {
	try {
		$posts = APIPostsFactory::getPostsBetweenIds(
			$from,$to,$desc,$perPage,$page);//,$user_id);
		$result = new APIResult($posts);
		paginate($result,$perPage,$page,$desc);
		setUp($result,'');
		convertMarkdown($result->response);
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getPostBySlug($slug) {
	try { 
		$posts = APIPostsFactory::getPostBySlug($slug);//,$user_id);
		$result = new APIResult($posts);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getPostsByIds($idList,$desc=true,$perPage=0,$page=1) {
	try { 
		$ids = explode(',',(string)$idList);

		$posts = APIPostsFactory::getPostsByIds($ids,$desc,$perPage,$page);
			//$user_id);
		$result = new APIResult($posts);
		paginate($result,$perPage,$page,$desc);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getPostsByAuthorId($id,$desc=true,$perPage=0,$page=1) {
	try { 
		$posts = APIPostsFactory::getPostsByAuthorId($id,$desc,$perPage,
			$page);//,$user_id);
		$result = new APIResult($posts);
		paginate($result,$perPage,$page,$desc);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getPostsByAuthorLogin($login,$desc=true,$perPage=0,$page=1) {
	try { 
		$posts = APIPostsFactory::getPostsByAuthorLogin($login,$desc,$perPage,
			$page);//,$user_id);
		$result = new APIResult($posts);
		paginate($result,$perPage,$page,$desc);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getPostsTagged($tagList,$perPage=0,$page=1,$desc=true,$and=true) {
	try { 
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
			$posts = APIPostsFactory::getPostsByTags($tags,$and,
				$desc,$perPage,$page);//,$user_id);
		else if (empty($include))
			throw new APIError(1404); // No included tags
		else
			$posts = APIPostsFactory::getPostsByTagsExclude(
				$include,$exclude,$and,$desc,$perPage,$page,$user_id);

		$result = new APIResult($posts);
		paginate($result,$perPage,$page,$desc);
		setUp($result,'/posts');
		convertMarkdown($result->response); 
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

