<?php
namespace ProcessWire;

use \Exception;
use \ReflectionClass;

class TwackNullComponent extends TwackComponent {

	protected $fehler;

	public function __construct($args = array()) {
	}

	public function __invoke($key) {
		return false;
	}

	public function ___render($format = 'auto') {
		return '';
	}
}
