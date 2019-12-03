<?php
namespace ProcessWire;

use \Exception;

class TwackException extends WireException{
	public function __construct($messageAddition = '', $message = 'A Twack-Error occurred. ', $code = 0, Exception $previous = null) {
		parent::__construct($message . ' ' . $messageAddition, $code, $previous);
	}

	public function __toString() {
		// In development mode, a path to the trigger of the exception should also be output.
		if (wire('config')->debug == true || wire('user')->isSuperuser()) {
			$message = __CLASS__ . ": [Code {$this->getCode()}]: {$this->getMessage()}";
			$message .= "\r\Location: '{$this->getFile()}', \r\Line {$this->getLine()}";
			return $message;
		}

		// Without development mode: Output only the error description:
		return "{$this->getMessage()}";
	}
}

class ComponentNotFoundException extends TwackException{
	public function __construct($messageAddition = '', $message = 'Component not found. ', Exception $previous = null) {
		parent::__construct($messageAddition, $message, 404, $previous);
	}
}

class ViewNotFoundException extends TwackException{
	public function __construct($messageAddition = '', $message = 'View not found. We cannot render the component. ', Exception $previous = null) {
		parent::__construct($messageAddition, $message, 404, $previous);
	}
}

class ParameterNotFoundException extends TwackException{
	public function __construct($messageAddition = '', $message = 'Parameter not found. ', Exception $previous = null) {
		parent::__construct($messageAddition, $message, 404, $previous);
	}
}

class ComponentNotInitializedException extends TwackException{
	public function __construct($componentname, $messageAddition = '', $message = false, Exception $previous = null) {
		if (empty($componentname) || !is_string($componentname)) {
			$componentname = 'Unknown';
		}
		if ($message === false) {
			$message = 'The component "' . $componentname . '" could not be initialized. ';
		}
		parent::__construct($messageAddition, $message, 404, $previous);
	}
}

class ComponentParameterException extends ComponentNotInitializedException{
	public function __construct($componentname, $messageAddition = '', $message = false, Exception $previous = null) {
		if (empty($componentname) || !is_string($componentname)) {
			$componentname = 'Unknown';
		}
		if ($message === false) {
			$message = 'Wrong or incomplete parameters were committed. The component "' . $componentname . '" could not be initialized. ';
		}
		parent::__construct($componentname, $messageAddition, $message, $previous);
	}
}

class TwackAjaxException extends TwackException{

	public $additionalData;

	public function __construct($message = false, $additionalData = array(), $code = 400, Exception $previous = null) {
		$messageString = 'An Ajax-Error occurred.';
		if ($message && !empty($message)) {
			$messageString = 'An Ajax-Error occurred: ' . $message;
		}
		if (is_array($additionalData)) {
			$this->additionalData = $additionalData;
		}

		parent::__construct('', $messageString, $code, $previous);
	}
}
