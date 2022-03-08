<?php

namespace ProcessWire;

use \Exception;

require_once __DIR__ . '/TwackApiAccess.class.php';

/**
 * Twack: Reusable components for your ProcessWire-templates
 *
 * @author Sebastian Schendel
 */
class Twack extends WireData implements Module, ConfigurableModule {
	// Global namespace
	// Could be replaced with an other namespace to prevent classname-conflicts.
	const TEMPLATENAMESPACE = '\\ProcessWire\\';

	// Global parameters which will be passed on every created component.
	protected $parameters = [];

	// Global components can be accessed by their global name from any other component.
	protected $globalComponents = [];

	// Array, to save all created service-objects. Every service is a singleton and can be created only once.
	protected $services = [];

	// $control holds the central control-class, which locates component-classes and generates new Objects from them.
	protected $control;

	// $devEchoComponent is a statically accessable component, which can be used to echo development outputs. It's basically a var_dump, but with a nicer formatted output. A custom devEcho-component can be used to give the development-output a place in the site's layout.
	protected static $devEchoComponent;

	// If true, Twack will not Render in HTML but render the Ajax-representation of the components.
	protected $forceAjax = false;

	// If set, Twack will not make an Ajax-Output with Statuscode and Exit. It will only generate a valid JSON-String or an Exception, which must be handled otherwise.
	// public $forcePlainAjaxOutput = false;

	public static $manifest = false;

	/**
	 * @internal
	 */
	public static function getModuleInfo() {
		return [
			'title' => 'Twack',
			'author' => 'Sebastian Schendel',
			'version' => '2.2.3',
			'summary' => 'Reusable components for your ProcessWire-templates.',
			'singular' => true,
			'autoload' => true,
			'icon' => 'cubes',
			'requires' => [
				'PHP>=5.5.0',
				'ProcessWire>=3.0.0'
			],
		];
	}

	/**
	 * @internal
	 */
	public function getModuleConfigArray() {
		return [
			'twack_components_directory' => [
				'type' => 'text',
				'label' => 'Components Directory',
				'value' => 'site/templates/components',
				'notes' => 'site/templates/components',
				'description' => 'Location of the directory, where Twack will search for its components. Base: ProcessWire-Root (Location of index.php)'
			],
			'twack_services_directory' => [
				'type' => 'text',
				'label' => 'Services Directory',
				'value' => 'site/templates/services',
				'notes' => 'default: site/templates/services',
				'description' => 'Location of the directory, where Twack will search for service-components. Base: ProcessWire-Root (Location of index.php)'
			],
			'twack_control_class' => [
				'type' => 'text',
				'label' => 'TwackControl',
				'value' => '',
				'description' => 'You can specify a custom TwackControl-class, where you can customise the way Twack handles component\’s paths and names. This option is optional and should normally not be necessary to use.',
				'notes' => 'A TwackControl-Class must inherit from \ProcessWire\TwackControl.'
			],
			'twack_manifest_path' => [
				'type' => 'text',
				'label' => 'Path to a manifest.json',
				'value' => '',
				'description' => 'Path to a manifest.json file, which contains links from the original filenames to generated filenames (e.g. filenames with hash-values for cache-busting)'
			]
		];
	}

	public function __construct() {
		require_once 'Exceptions.php';
		require_once 'TwackComponent.class.php';
		require_once 'TwackNullComponent.class.php';
		require_once 'TwackControl.class.php';
		require_once 'DevEchoComponent.class.php';
	}

	public function init() {
		$this->wire('twack', $this);
		$this->addHookBefore('Page::render', $this, 'hookBeforePageRender');

		// If AppApi is installed: Add ApiEndpoint 'tpage'
		if ($this->wire('modules')->isInstalled('AppApi')) {
			$moduleInfo = $this->wire('modules')->getModuleInfo('AppApi');
			if (!empty($moduleInfo['version']) && version_compare($moduleInfo['version'], '1.2.0') >= 0) {
				$module = $this->wire('modules')->get('AppApi');
				$module->registerRoute(
					'tpage',
					[
						['OPTIONS', '{id:\d+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
						['OPTIONS', '{path:.+}', ['GET', 'POST', 'UPDATE', 'DELETE']],
						['OPTIONS', '', ['GET', 'POST', 'UPDATE', 'DELETE']],
						['GET', '{id:\d+}', TwackApiAccess::class, 'pageIDRequest'],
						['GET', '{path:.+}', TwackApiAccess::class, 'pagePathRequest'],
						['GET', '', TwackApiAccess::class, 'dashboardRequest'],
						['POST', '{id:\d+}', TwackApiAccess::class, 'pageIDRequest'],
						['POST', '{path:.+}', TwackApiAccess::class, 'pagePathRequest'],
						['POST', '', TwackApiAccess::class, 'dashboardRequest'],
						['UPDATE', '{id:\d+}', TwackApiAccess::class, 'pageIDRequest'],
						['UPDATE', '{path:.+}', TwackApiAccess::class, 'pagePathRequest'],
						['UPDATE', '', TwackApiAccess::class, 'dashboardRequest'],
						['DELETE', '{id:\d+}', TwackApiAccess::class, 'pageIDRequest'],
						['DELETE', '{path:.+}', TwackApiAccess::class, 'pagePathRequest'],
						['DELETE', '', TwackApiAccess::class, 'dashboardRequest']
					]
				);
			}
		}
	}

	/**
	 * Render-Hook, that looks for ajax-calls and answers them with json-strings. Must be enabled in module-config.
	 *
	 * @param  HookEvent $e
	 */
	public function hookBeforePageRender(HookEvent $e) {
		$page = $e->object;
		if (!$this->isTwackAjaxCall()) {
			return;
		}
	}

	public function enableAjaxResponse() {
		$this->forceAjax = true;
	}

	public function disableAjaxResponse() {
		$this->forceAjax = false;
	}

	public function isTwackAjaxCall() {
		if ($this->forceAjax) {
			return true;
		}

		// Processwire core-function which checks wether its an ajax call
		if (!Twack::isAjax()) {
			return false;
		}

		return true;
	}

	public static function getManifest() {
		if (is_array(self::$manifest)) {
			return self::$manifest;
		}

		$configdata = wire('modules')->getModuleConfigData('Twack');

		if (!isset($configdata['twack_manifest_path'])) {
			self::$manifest = [];
			return self::$manifest;
		}

		$manifestPath = $configdata['twack_manifest_path'];
		if (empty($manifestPath)) {
			self::$manifest = [];
			return self::$manifest;
		}
		$manifestPath = wire('config')->paths->root . $manifestPath;
		$manifest = file_get_contents($manifestPath);
		if (!$manifest) {
			self::$manifest = [];
			return self::$manifest;
		}

		$manifest = json_decode($manifest, true);
		if (!$manifest) {
			self::$manifest = [];
			return self::$manifest;
		}

		self::$manifest = $manifest;
		return self::$manifest;
	}

	public static function getManifestFilename($originalFilename) {
		if (!isset(self::getManifest()[$originalFilename])) {
			return $originalFilename;
		}

		$filename = explode('/', self::getManifest()[$originalFilename]);
		return end($filename);
	}

	/**
	 * Provides the current instance of TwackControl.
	 * @return TwackControl
	 */
	protected function getControl() {
		// TwackControl is a singleton. If an instance is already created, it can be returned directly:
		if ($this->control) {
			return $this->control;
		}

		// Paths for TwackControl:
		$controlArgs = [
			'components' => [],
			'services' => []
		];

		$rootPath = wire('config')->paths->root;

		// Determine which path to choose for components directory:
		$componentsDirectory = $this->configValue('twack_components_directory');
		if (empty($componentsDirectory)) {
			$componentsDirectory = 'site/templates/components';
		}
		$componentsDirectory = Twack::addTrailingSeparator($componentsDirectory);

		$controlArgs['components']['directory'] = $componentsDirectory;
		$controlArgs['components']['path'] = $rootPath . $componentsDirectory;

		// Determine which path to choose for service directory:
		$servicesDirectory = $this->configValue('twack_services_directory');
		if (empty($servicesDirectory)) {
			$servicesDirectory = 'site/templates/services';
		}
		$servicesDirectory = Twack::addTrailingSeparator($servicesDirectory);

		$controlArgs['services']['directory'] = $servicesDirectory;
		$controlArgs['services']['path'] = $rootPath . $servicesDirectory;

		// Should a custom TwackControl-class be used? It can be set in module’s configuration:
		if (!empty($this->configValue('twack_control_class'))) {
			$controlClassPath = Twack::addTrailingSeparator($this->configValue('twack_control_class'));
			if (file_exists($rootPath . $controlClassPath)) {
				require_once $rootPath . $controlClassPath;
			}
			$controlClassname = Twack::TEMPLATENAMESPACE . Twack::getClassnameFromPath($controlClassPath);

			if (class_exists($controlClassname)) {
				try {
					$this->control = new $controlClassname($controlArgs);
				} catch (Exception $e) {
					$this->control = new TwackControl($controlArgs);
				}
			}
		}

		if (!$this->control || !is_subclass_of($this->control, Twack::TEMPLATENAMESPACE . 'TwackControl')) {
			$this->control = new TwackControl($controlArgs);
		}

		return $this->control;
	}

	/**
	 * Owerwrites currently used instance of TwackControl.
	 * @param TwackControl $control
	 */
	public function setControl(TwackControl $control) {
		$this->control = $control;
	}

	/**
	 * Can be used to require a component without generating an instance. Useful for Extends.
	 * @param  string $componentname
	 * @param  array  $args
	 */
	public function requireComponent($componentname, $args = []) {
		return $this->getControl()->getPathsForComponent($componentname, $args = []);
	}

	/**
	 * Gets or sets a value from module’s configuration
	 *
	 * @param  string $key name of config-field
	 * @param  $wert (optional) new value for the field
	 * @return value
	 */
	private function configValue($key, $value = null) {
		$configdata = wire('modules')->getModuleConfigData('Twack');

		if (!isset($configdata[$key])) {
			return null;
		}

		if ($value !== null) {
			// Value should be set:
			$configdata[$key] = $value;
			wire('modules')->saveModuleConfigData('Twack', $configdata);
		}

		return $configdata[$key];
	}

	/**
	 * Returns a global Twack-parameter. Every component has access to these parameters and can do changes to them.
	 * @internal
	 */
	public function getGlobalParameter($parametername = null) {
		if (!empty($parametername)) {
			if (isset($this->parameters[$parametername])) {
				return $this->parameters[$parametername];
			}
		}
		return null;
	}

	/**
	 * Returns all globally set parameters.
	 * @return array
	 */
	public function getGlobalParameters() {
		return $this->parameters;
	}

	/**
	 * Adds parameters to the globally set parameters. If a parameter-key already exists it will be overwritten.
	 * @param array $parameter
	 * @internal
	 * @deprecated
	 */
	public function addGlobalParameters(array $parameters) {
		$this->parameters = array_merge($this->parameters, $parameters);
	}

	/**
	 * Adds an alias-name for a component, under which the component can be alternatively accessed.
	 *
	 * @param string $aliasname
	 * @param string $componentname Classname of the Component (in CamelCase or under_score)
	 * @param array $args (optional) You can add custom arguments (i.e. component-parameters) for this alias. The arguments will influence the component only if it is called by the alias-name.
	 *
	 * @return boolean
	 */
	public function addComponentAlias($aliasname, $componentname, $args = []) {
		return $this->getControl()->addComponentAlias($aliasname, $componentname, $args);
	}

	/**
	 * Returns the original name of the component from an alias-name. Aliases must be added via addComponentAlias().
	 *
	 * @param  string $aliasname
	 * @return string|null  Classname of the original component or NULL if the alias does not exist.
	 */
	public function getComponentnameForAlias($aliasname) {
		return $this->getControl()->getComponentnameForAlias($aliasname);
	}

	/**
	 * Returns all custom arguments that are set for the requested alias-name.
	 *
	 * @param  string $aliasname
	 * @return array|null  Array of arguments or NULL, if alias does not exist.
	 */
	public function getComponentArgsForAlias($aliasname) {
		return $this->getControl()->getComponentArgsForAlias($aliasname);
	}

	/**
	 * Registers a component-object for global use. Global components can be called anywhere in the code via TwackObject->getComponent()
	 *
	 * @param TwackComponent    $component      Classname of the component which should be globalized.
	 * @param string            $globalName     Global name, under which the component can be accessed.
	 */
	public function makeComponentGlobal(TwackComponent $component, $globalName) {
		if (!is_string($globalName) || empty($globalName)) {
			throw new TwackException('No global name set. You have to set the $globalName (second parameter) for the component, under which it will be accessable globally.');
		}
		$this->globalComponents[$globalName] = $component;
		return $component;
	}

	/**
	 * Returns a global component. It has to be globalized previously via TwackModule->makeComponentGlobal().
	 *
	 * @param  string $globalName                   Global name, under which the component can be accessed.
	 * @return TwackComponent|TwackNullComponent    Returns the requested TwackComponent or TwackNullComponent, if the global component could not be found.
	 */
	public function getComponent($globalName) {
		if (!isset($this->globalComponents[$globalName])) {
			return new TwackNullComponent();
		}
		return $this->globalComponents[$globalName];
	}

	/**
	 * Returns a new TwackComponent-object for the requested classname.
	 *
	 * @param string $componentname         Classname (or alias) of the requested component.
	 * @param array $args                   Additional arguments, which should be passed to the component-object.
	 *                                      You can pass all custom values and the following predefined arguments to
	 *                                      influence the component's behaviour:
	 *                                          page            Page, which should be used in the component
	 *                                                          (Default: NULL, which results in passing the page of
	 *                                                          the calling component or wire('page')).
	 *                                          parameters      Parameters, which will be accessable in the component
	 *                                                          via $this->[parameterkey] (Default: array())
	 *                                          directory       Where can the component be found? Base is the components-folder.
	 *                                          globalName      Makes the new component globally accessable.
	 *
	 * @throws TwackException
	 * @throws ComponentNotFoundException
	 * @return TwackComponent
	 */
	public function getNewComponent($componentname, $args = []) {
		if (!isset($args['backtrace'])) {
			$args['backtrace'] = debug_backtrace();
		}

		$component = $this->getControl()->getNewComponent($componentname, $args);
		if (isset($args['globalName']) && !empty($args['globalName'])) {
			$this->makeComponentGlobal($component, $args['globalName']);
		}
		return $component;
	}

	/**
	 * Returns a service-instance. Services are singletons, so you will get the same instance everytime.
	 * @param  string $servicename
	 * @return TwackComponent
	 */
	public function getService($servicename) {
		if (!isset($this->services[$servicename])) {
			// The service was not initialized yet. Search it and save it for later calls.
			$this->services[$servicename] = $this->getNewComponent($servicename, ['componentType' => 'service']);
		}
		return $this->services[$servicename];
	}

	/*
	 *************************************************
	 * Helpers
	 */

	/**
	 * Ensures that there is a separator at the end of a path-string
	 * @param string $path
	 */
	public static function addTrailingSeparator($path) {
		if (is_string($path) && strlen($path) > 0 && substr($path, -1) != DIRECTORY_SEPARATOR) {
			$path .= DIRECTORY_SEPARATOR;
		}
		return $path;
	}

	public static function addLeadingSeparator($path) {
		if (is_string($path) && strlen($path) > 0 && substr($path, 0, 1) != DIRECTORY_SEPARATOR) {
			$path = DIRECTORY_SEPARATOR . $path;
		}
		return $path;
	}

	/**
	 * Transforms a CamelCaseString to an under_score_string
	 * @param string $str   String in camelcase-format
	 * @return string       Translated into underscore-format
	 */
	public static function camelCaseToUnderscore($camelCaseString) {
		$camelCaseString[0] = strtolower($camelCaseString[0]);
		return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $camelCaseString));
	}

	/**
	 * Transforms an under_score_string to a CamelCaseString (Upper)
	 * @param  string $underscoreString     String in underscore-format
	 * @return string                       Translated into camelcase-format
	 */
	public static function underscoreToUpperCamelCase($underscoreString) {
		return self::underscoreToCamelCase($underscoreString, true);
	}

	/**
	 * Transforms an under_score_string to a camelCaseString (Lower)
	 * @param  string $underscoreString     String in underscore-format
	 * @return string                       Translated into camelcase-format
	 */
	public static function underscoreToLowerCamelCase($underscoreString) {
		return self::underscoreToCamelCase($underscoreString, false);
	}

	/**
	 * Translates a string with underscores
	 * into camel case (e.g. first_name -> firstName)
	 * by https://paulferrett.com/2009/php-camel-case-functions/
	 *
	 * @param string $str String in underscore format
	 * @param bool $capitalise_first_char If true, capitalise the first char in $str
	 * @return string $str translated into camel caps
	 */
	public static function underscoreToCamelCase($str, $capitalizeFirstCharacter = false) {
		if (!$capitalizeFirstCharacter) {
			$str = lcfirst($str);
		}
		$str = str_replace('_', '', ucwords($str, '_'));

		return $str;
	}

	/**
	 * Returns the classname for a given path.
	 * @param  string $path
	 * @return string classname.
	 */
	public static function getClassnameFromPath($path) {
		return Twack::underscoreToUpperCamelCase(basename($path, '.class.php'));
	}

	/**
	 * Shortens a text to a characterlimit, but splits between complete words.
	 * @param  string  $str     Text that should be shortened
	 * @param  integer $limit   Maximum count of characters (default: 120)
	 * @param  string  $endstr  Suffix which will be added to a shortened string (default: '…')
	 * @return string           Returns the shortened string with suffix
	 */
	public static function wordLimiter($str, $limit = 120, $endstr = '…') {
		$str = strip_tags($str);
		if (strlen($str) <= $limit) {
			return $str;
		}

		$out = substr($str, 0, $limit);
		$pos = strrpos($out, ' ');
		if ($pos > 0) {
			$out = substr($out, 0, $pos);
		}
		return $out .= $endstr;
	}

	/**
	 * Registers a custom DevEchoComponent, which handles all Twack::devEcho()-outputs. If no custom component is defined the devEcho()-outputs will be rendered before the site's contents in <pre>-tags.
	 * @param  DevEchoComponent $component
	 */
	public function registerDevEchoComponent(DevEchoComponent $component) {
		self::$devEchoComponent = $component;
	}

	/**
	 * Returns the currently registered DevEchoComponent
	 * @return DevEchoComponent
	 */
	public static function getDevEchoComponent() {
		if (self::$devEchoComponent && self::$devEchoComponent instanceof DevEchoComponent) {
			return self::$devEchoComponent;
		}

		$args = [
			'location' => [
				'directory' => '',
				'path' => '',
				'processwirePath' => '',
				'classname' => '',
				'type' => 'controller',
			]
		];
		return new DevEchoComponent($args);
	}

	/**
	 * Outputs custom dev-outputs for development purposes. Twack::devEcho()-outputs will only be rendered if a superuser is logged in and the call is not an ajax-call.
	 */
	public static function devEcho() {
		if (!wire('user')->isSuperuser() || self::isAjax()) {
			return;
		}

		if (func_num_args() > 0) {
			$args = func_get_args();
			$backtrace = debug_backtrace();

			$filename = '';
			$functionCall = '';
			$line = '';

			// Trace for the last call from our components-folder, don't output processwire's traces:
			foreach ($backtrace as $btElement) {
				if (!isset($btElement['file']) || !isset($btElement['line']) || !isset($btElement['function'])) {
					break;
				}

				$filename = $btElement['file'];
				if (strstr($filename, '/site/templates/') === false) {
					continue;
				}

				$filename = substr(strstr($filename, '/site/templates/'), strlen('/site/templates/') - 1);
				$functionCall = $btElement['function'];
				$line = $btElement['line'];
				break;
			}

			$devEchoComponent = self::getDevEchoComponent();
			$devEchoComponent->devEcho($args, $filename, $functionCall, $line);
		}
	}

	public static function isAjax() {
		return wire('config')->ajax;
	}

	/**
	 * Liefert zu einem HTTP-Statuscode die korrekte Status-Message
	 * Returns the http-statustext for a given statuscode
	 * @param  int $status
	 * @return string
	 */
	protected static function getStatusCodeMessage($status) {
		$codes = [
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported'
		];

		return (isset($codes[$status])) ? $codes[$status] : '';
	}

	/**
	 * Outputs a given body with explicitly set HTTP-headers and exits after it.
	 *
	 * @param  string  $body            Content which should be outputted. If an array is given,
	 *                                  application/json will be chosen as contentType automatically.
	 * @param  integer $status          HTTP-statuscode (Default: 200 ->ok)
	 * @param  string  $contentType     ContentType (i.e. 'text/html' or 'application/json')
	 */
	public static function sendResponse($body = '', $status = 200, $contentType = 'text/html') {
		$statusHeader = 'HTTP/1.1 ' . $status . ' ' . self::getStatusCodeMessage($status);
		header($statusHeader);

		if (is_array($body)) {
			$body = json_encode($body);
			if ($contentType === 'text/html') {
				$contentType = 'application/json';
			}
		}

		header('Content-type: ' . $contentType);

		echo $body;
		exit();
	}

	/**
	 * Helper function, to convert common PHP-Objects to arrays which can be output in ajax.
	 * @param  Object $content
	 * @return array
	 */
	public static function getAjaxOf($content) {
		$output = [];

		if ($content instanceof PageFiles) {
			foreach ($content as $file) {
				$output[] = self::getAjaxOf($file);
			}
		} elseif ($content instanceof PageFile) {
			$output = [
				'basename' => $content->basename,
				'name' => $content->name,
				'description' => $content->description,
				'created' => $content->created,
				'modified' => $content->modified,
				'filesize' => $content->filesize,
				'filesizeStr' => $content->filesizeStr,
				'page_id' => $content->page->id,
				'ext' => $content->ext
			];

			if ($content instanceof PageImage) {
				$output['basename_mini'] = $content->size(600, 0)->basename;
				$output['width'] = $content->width;
				$output['height'] = $content->height;
				$output['dimension_ratio'] = round($content->width / $content->height, 2);

				if ($content->original) {
					$output['original'] = [
						'basename' => $content->original->basename,
						'name' => $content->original->name,
						'filesize' => $content->original->filesize,
						'filesizeStr' => $content->original->filesizeStr,
						'ext' => $content->original->ext,
						'width' => $content->original->width,
						'height' => $content->original->height,
						'dimension_ratio' => round($content->original->width / $content->original->height, 2)
					];
				}
			}

			// Output custom filefield-values (since PW 3.0.142)
			$fieldValues = $content->get('fieldValues');
			if (!empty($fieldValues) && is_array($fieldValues)) {
				foreach ($fieldValues as $key => $value) {
					$output[$key] = $value;
				}
			}
		} elseif ($content instanceof Template && $content->id) {
			$output = [
				'id' => $content->id,
				'name' => $content->name,
				'label' => $content->label
			];
		} elseif ($content instanceof PageArray) {
			foreach ($content as $page) {
				$output[] = self::getAjaxOf($page);
			}
		} elseif ($content instanceof Page && $content->id) {
			$output = [
				'id' => $content->id,
				'name' => $content->name,
				'title' => $content->title,
				'created' => $content->created,
				'modified' => $content->modified,
				'url' => $content->url,
				'httpUrl' => $content->httpUrl,
				'template' => self::getAjaxOf($content->template)
			];
		}

		return $output;
	}
}
