<?
class APIError extends Exception {
	private $errors;

	/**
	 * Custom exception class to handle multiple errors
	 *
	 * @param string $message Exception message
	 * @param int $errno API error number (see function getErrorById)
	 * @param int $code Exception code
	 */
	public function __construct($errno=null, $message='', $code=0, 
		Exception $previous=null) 
	{
		if (is_null($errno))
			$this->errors = array();
		else
			$this->errors = array($this->getErrorById($errno));
		parent::__construct($message,$code,$previous);
	}

	public function addError($errno) {
		array_push($this->errors,$this->getErrorById($errno));
	}

	public function isEmpty() {
		return empty($this->errors);
	}

	public function merge(APIError $e) {
		$this->errors = array_merge($this->errors,$e->getErrors());
	}

	/**
	 * Return an error array based on the error number
	 *
	 * @param int $id The error number
	 * @return array Example, in JSON format: {"code":123,"message":"hi"}
	 */
	public static function getErrorById($id) {
		$msg = '';

		switch($id) {
		case 0: $msg = ''; break;

		case 400: $msg = 'Bad request'; break;
		case 404: $msg = 'Resource not found'; break;
		case 500: $msg = 'Internal server error'; break;

		case 2001: $msg = 'Invalid ID'; break;
		case 2002: $msg = 'Invalid consumer key'; break;
		case 2003: $msg = 'Invalid OAuth signature'; break;
		case 2004: $msg = 'Invalid timestamp'; break;
		case 2005: $msg = 'Expired timestamp'; break;
		case 2006: $msg = 'Invalid parameters'; break;
		case 2007: $msg = 'Database error'; break;

		// User errors
		case 1001: $msg = 'Invalid user ID'; break;
		case 1002: $msg = 'Failed to hash password'; break;
		case 1003: $msg = 'Login exceeds max length'; break;
		case 1004: $msg = 'Login contains invalid characters'; break;
		case 1005: $msg = 'Username exceeds max length'; break;
		case 1006: $msg = 'Invalid user group ID'; break;
		case 1007: $msg = 'Email exceeds max length'; break;
		case 1008: $msg = 'A user with this login already exists'; break;
		case 1009: $msg = 'User does not exist'; break;

		// Group errors
		case 1101: $msg = 'Invalid group ID'; break;
		case 1102: $msg = 'Group name exceeds max length'; break;
		case 1103: $msg = 'Group name contains invalid characters'; break;
		case 1104: $msg = "Invalid 'make posts' permissions value"; break;
		case 1105: $msg = "Invalid 'edit posts' permissions value"; break;
		case 1106: $msg = "Invalid 'make comments' permissions value"; break;
		case 1107: $msg = "Invalid 'edit comments' permissions value"; break;
		case 1108: $msg = 'A group with this name already exists'; break;
		case 1109: $msg = 'Group does not exist'; break;
		case 1110: $msg = 'Invalid group color'; break;

		// Post errors
		case 1201: $msg = 'Invalid post ID'; break;
		case 1202: $msg = 'Title exceeds max length'; break;
		case 1203: $msg = 'Invalid status value'; break;
		case 1204: $msg = "Invalid date (try 'YYYY-MM-DD HH:MM:SS')"; break;
		case 1205: $msg = 'Post does not exist'; break;
		case 1206: $msg = 'User does not have permission to make posts'; 
							break;
		case 1207: $msg = 'User does not have permission to edit this post';
							break;
		case 1208: $msg = 'Title slug exceeds max length'; break;
		case 1209: $msg = 'Post already exists with this title slug'; break;
		case 1210: $msg = "Invalid format specified (try 'html' or 'md')"; 
							break;
		case 1211: $msg = 'Missing POST data'; break;

		// Comment errors
		case 1301: $msg = 'Invalid comment ID'; break;
		case 1302: $msg = 'Name exceeds max length'; break;
		case 1303: $msg = 'Invalid parent comment ID'; break;
		case 1304: $msg = 'Parent comment does not exist'; break;
		case 1305: $msg = 'User does not have permission to make comments';
							break;
		case 1306: $msg = 'User does not have permission to edit this
							comment'; break;

		// Tag errors
		case 1401: $msg = 'Invalid post ID'; break;
		case 1402: $msg = 'Tag name exceeds max length'; break;
		case 1403: $msg = 'Tag with this name already exists'; break;
		case 1404: $msg = 'Cannot specify excluded tags only'; break;
		
		// Default
		default: $msg = 'Unknown error'; break;
		}

		$error = array('code' => $id, 'msg' => $msg);
		return $error;
	}

	public function getErrors() {
		return $this->errors;
	}
}

