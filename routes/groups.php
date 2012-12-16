<?php

function getGroupsByIds($idList) {
	try {
		$ids = explode(',',$idList);
		$groups = APIGroupsFactory::getGroupsByIds($ids);
		$result = new APIResult($groups);
		setUp($result,'/groups');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

function getGroups() {
	try {
		$groups = APIGroupsFactory::getGroups();
		$result = new APIResult($groups);
		setUp($result,'/');
	}
	catch(APIError $e) { 
		$result = new APIResult(null,$e); 
	}
	return $result;
}

