<?php
namespace ProcessWire;

use \Exception;

class DevEchoComponent extends TwackComponent {
	public function __construct($args = array()) {
		parent::__construct($args);
	}

	public function devEcho($args, $filename = '', $functionname = '', $line = '') {
		if (Twack::isAjax()) {
			// TODO Save messages for Ajax output
			return;
		}
		echo "<pre>";

		echo "<strong>";
		echo "DEV-Ausgabe";
		if (!empty($filename)) {
			echo " in " . $filename;
		}
		if (!empty($functionname)) {
			echo ", Funktionsaufruf " . $functionname . "()";
		}
		if (!empty($line)) {
			echo ", Zeile " . $line;
		}
		echo ":</strong><br/>";

		foreach ($args as $argument) {
			var_dump($argument);
		}
		echo "</pre>";
		echo "<hr/>";
	}
}
