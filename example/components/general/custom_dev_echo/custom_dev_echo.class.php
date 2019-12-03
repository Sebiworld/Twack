<?php
namespace ProcessWire;

class CustomDevEcho extends DevEchoComponent {
	public function __construct($args) {
		parent::__construct($args);
		$this->outputs = new WireArray();
	}

	public function devEcho($arguments, $filename = '', $functionname = '', $line = '') {
		$outputs = new WireData();

		$outputs->arguments = $arguments;
		$outputs->filename = $filename;
		$outputs->functionname = $functionname;
		$outputs->line = $line;

		$this->outputs->add($outputs);
	}
}
