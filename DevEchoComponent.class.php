<?php

namespace ProcessWire;

class DevEchoComponent extends TwackComponent {
	public function __construct($args = []) {
		parent::__construct($args);
	}

	public function devEcho($args, $filename = '', $functionname = '', $line = '') {
		if (Twack::isAjax()) {
			// TODO Save messages for Ajax output
			return;
		}
		echo '<pre>';

		echo '<strong>';
		echo 'DEV-Output';
		if (!empty($filename)) {
			echo ' in ' . $filename;
		}
		if (!empty($functionname)) {
			echo ', function-call ' . $functionname . '()';
		}
		if (!empty($line)) {
			echo ', in line ' . $line;
		}
		echo ':</strong><br/>';

		foreach ($args as $argument) {
			var_dump($argument);
		}
		echo '</pre>';
		echo '<hr/>';
	}
}
