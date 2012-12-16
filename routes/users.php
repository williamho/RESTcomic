<?php

function getUsersByIds($idList,$getemail=false) {
	try {
		$ids = explode(',',$idList);
		$users = APIUsersFactory::getUsersByIds($ids,true,$getemail);
		$result = new APIResult($users);
		setUp($result,'/users');
	}	
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getUserbyLogin($login) {
	try {
		$users = APIUsersFactory::getUserByLogin($login);
		$result = new APIResult($users);
		setUp($result,'/users');
	}	
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

