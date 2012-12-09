<?php
defined('API_PATH') or die('No direct script access.');
require_once API_PATH.'lib/oauth/OAuth.php';

class APIOAuth {
	private static $timestampThreshold = 300;
	public static $sigMethod;

	public static function validate() {
		global $db, $config;
		$req = OAuthRequest::from_request();

		if (!isset($_GET['oauth_consumer_key']))
			throw new APIError(2002); // Invalid consumer key
		if (!isset($_GET['oauth_signature']))
			throw new APIError(2003); // Invalid signature
		if (!isset($_GET['oauth_timestamp']))
			throw new APIError(2004); // Invalid timestamp

		$user = $_GET['oauth_consumer_key'];
		$sig = $_GET['oauth_signature'];
		$timestamp = $_GET['oauth_timestamp'];

		// Check timestamp
		if (abs(time() - $timestamp) > self::$timestampThreshold)
			throw new APIError(2005); // Expired timestamp

		$query = "
			SELECT user_id, api_key
			FROM {$config->tables['users']} u
			WHERE u.login = :login
		";
		$stmt = $db->prepare($query);
		$stmt->bindParam(':login',$user);
		$stmt->execute();
		$userInfo = $stmt->fetchObject();

		if (!isset($userInfo->api_key))
			throw new APIError(2002); // Invalid consumer key
			
		$consumer = new OAuthConsumer($user,$userInfo->api_key);
		
		$valid = self::$sigMethod->check_signature($req, $consumer, 
						null, $sig);	

		if ($valid)
			return $userInfo->user_id;
		throw new APIError(2003);
	}
}
APIOAuth::$sigMethod = new OAuthSignatureMethod_HMAC_SHA1();

