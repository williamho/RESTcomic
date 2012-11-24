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
	public function __construct($message='', $errno=null, $code=0, 
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
		case 1108: $msg = "A group with this name already exists"; break;

		// Post errors
		case 1201: $msg = 'Invalid post ID'; break;
		case 1202: $msg = 'Invalid user ID'; break;
		case 1203: $msg = 'Title too long'; break;
		case 1204: $msg = 'Invalid status value'; break;
		case 1205: $msg = "Invalid date (try 'YYYY-MM-DD HH:MM:SS')"; break;
		
		// Default
		default: $msg = 'Unknown error'; break;
		}

		$error = array('code' => $id, 'message' => $msg);
		return $error;
	}

	public function getErrors() {
		return $this->errors;
	}
}

?>
