<?php
namespace ProcessWire;

if ($this->outputs && count($this->outputs) > 0) {
	?>
	<div class="dev_echo">
		<span class="badge badge-warning">DEV-Output:</span>
		<?php
		foreach ($this->outputs as $singleOutput) {
			if (!isset($singleOutput->arguments) || empty($singleOutput->arguments)) {
				continue;
			}
			echo "<pre>";
			echo "<strong>";
			echo "DEV-Output";
			if (isset($singleOutput->filename) && !empty($singleOutput->filename)) {
				echo " in " . $singleOutput->filename;
			}

			if (isset($singleOutput->functionname) && !empty($singleOutput->functionname)) {
				echo ", Function call in " . $singleOutput->functionname . "()";
			}

			if (isset($singleOutput->line) && !empty($singleOutput->line)) {
				echo ", Line " . $singleOutput->line;
			}
			echo ":</strong><br/>";

			foreach ($singleOutput->arguments as $argument) {
				var_dump($argument);
			}
			echo "</pre>";
			echo "<hr/>";
		}
		?>
	</div>
	<?php
}
?>