<?
class APIError extends Exception {
	private $errors;

	/**
	 * Custom exception class to handle multiple errors
	 *
	 * @param array $errors An array, expressed in JSON format as: 
	 *	[{"code":123,"message":"hi"},{"code:":321,"message","bye"}]
	 */
	public function __construct(array $errors, $message, $code=0, 
		Exception $previous=null) 
	{
		$this->errors = $errors;
		parent::__construct($message,$code,$previous);
	}

	/**
	 * Return an error array based on the error number
	 *
	 * @param int $id The error number
	 * @return array {"code":123,"message":"hi"}
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
		case 1008: $msg = 'Website URL length exceeds max length'; break;

		// Group errors
		case 1101: $msg = 'Invalid group ID'; break;
		case 1102: $msg = 'Group name exceeds max length'; break;
		case 1103: $msg = 'Group name contains invalid characters'; break;
		case 1104: $msg = "Invalid 'make posts' permissions value"; break;
		case 1105: $msg = "Invalid 'edit posts' permissions value"; break;
		case 1105: $msg = "Invalid 'make comments' permissions value"; break;
		case 1106: $msg = "Invalid 'edit comments' permissions value"; break;

		// Post errors
		case 1104: $msg = 'Title cannot be null'; break;
		
		// Default
		default: $msg = 'Unknown error'; break;
		}

		$error = array('code' => $id, 'message' => $msg);
		return $error;
	}

	public static function pushErrorArray(&$array,$id) {
		array_push($array,APIError::getErrorById($id));
	}

	public function getErrors() {
		return $this->errors;
	}
}

?>
