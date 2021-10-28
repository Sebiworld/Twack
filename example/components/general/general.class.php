<?php
namespace ProcessWire;

class General extends TwackComponent {

	public function __construct($args) {
		parent::__construct($args);

		// Collect additional meta-tags:
		$this->metatags = new WireData();

		// This component should be available global under the keyword "general":
		$this->wire('twack')->makeComponentGlobal($this, 'general');

		// Add vendor-styles:
		$this->addStyle(wire('config')->urls->templates . 'assets/css/bootstrap.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/swiper.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/lightgallery.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/starability.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/ionicons.min.css', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/hamburgers.min.css', true);

		// Add main style and script:
		$this->addStyle(wire('config')->urls->templates . 'assets/css/main.min.css', true);
		$this->addScript(wire('config')->urls->templates . 'assets/js/general.min.js', true);

		// Cookie-script and style:
		$this->addScript(wire('config')->urls->templates . 'assets/js/cookies.min.js', true);
		$this->addStyle(wire('config')->urls->templates . 'assets/css/cookies.min.css', true, false);

		// Comment-javascripts:
		$this->addStyle(wire('config')->urls->FieldtypeComments . 'comments.css', true);
		$this->addScript(wire('config')->urls->FieldtypeComments . 'comments.min.js', true);

		// Register a custom DevEcho
		$devEcho = $this->addComponent('CustomDevEcho', ['globalName' => 'dev_echo']);
		wire('twack')->registerDevEchoComponent($devEcho);

		// Add layout-components, e.g.:
		// $this->addComponent('PageHeader', ['globalName' => 'header']);
	}

	/**
	 * Adds a new metatag
	 * @param string $metatag  	Metatag-String (with Html)
	 */
	public function addMeta($metaname, $metatag) {
		if (is_string($metaname) && !empty($metaname) && is_string($metatag) && !empty($metatag)) {
			$this->metatags->{$metaname} = $metatag;
		}
	}

	public function getAjax() {
		$output = $this->getAjaxOf($this->page);

		if ($this->childComponents) {
			foreach ($this->childComponents as $component) {
				$ajax = $component->getAjax();
				if(empty($ajax)) continue;
				$output = array_merge($output, $ajax);
			}
		}

		return $output;
	}
}
