<?php

namespace ProcessWire;

use \Exception;

/**
 * By implementing a subclass of TwackControl you can overwrite each method's behaviour, if necessary.
 */
class TwackControl extends WireData {
	protected $seite;

	// Locations:
	protected $componentsDirectory;
	protected $componentsPath;
	protected $servicesDirectory;
	protected $servicesPath;

	// Store location-infos for each component:
	protected $classLocations;

	// Store aliases:
	protected $componentAliases;

	public function __construct($args) {
		if (!isset($args['components']) || !is_array($args['components']) || !isset($args['components']['directory']) || !isset($args['components']['path'])) {
			throw new TwackException('Required args not found: \'directory\' and \'path\' of components folder.');
		}
		if (!isset($args['services']) || !is_array($args['services']) || !isset($args['services']['directory']) || !isset($args['services']['path'])) {
			throw new TwackException('Required args not found: \'directory\' and \'path\' of services folder.');
		}

		$this->page = isset($args['page']) ? $args['page'] : wire('page');

		$this->componentsDirectory = Twack::addTrailingSeparator($args['components']['directory']);
		$this->componentsPath = Twack::addTrailingSeparator($args['components']['path']);

		$this->servicesDirectory = Twack::addTrailingSeparator($args['services']['directory']);
		$this->servicesPath = Twack::addTrailingSeparator($args['services']['path']);

		$this->componentAliases = [];
		$this->classLocations = [];
	}

	/**
	 * Returns a subpath that should be added to the components path. It could be useful to overwrite
	 * this method with a custom implementation, if you want i.e. a protected sub-path with components
	 * that should be accessed if the current user is authenticated.
	 */
	public function getSubpath() {
		return Twack::addTrailingSeparator('');
	}

	/**
	 * Looks for a component, if not found for alternative names and aliases. Returns path information if success.
	 * @throws TwackException if error or component not found.
	 */
	public function getPathsForComponent($componentname, $args = []) {
		if (empty($componentname) || !is_string($componentname)) {
			throw new TwackException('getPathsForComponent(): Componentname not valid. It must be a string and may not empty.');
		}

		// Possible types: "component" (default) and "service":
		$componentType = 'component';
		if (isset($args['componentType']) && !empty($args['componentType'])) {
			$componentType = $args['componentType'];
		}

		// Are there stored paths in classLocations for the requested component. Every componentlocation will be stored there after its first initialization.
		if (isset($this->classLocations[$componentname]) && is_array($this->classLocations[$componentname]) && class_exists(Twack::TEMPLATENAMESPACE . $componentname)) {
			$args['location'] = $this->classLocations[$componentname];
			return $args;
		}

		// Use subpath, if requested:
		$subPath = '';
		if ($componentType === 'component' && !isset($args['useSubpath']) || !!$args['useSubpath']) {
			$this->getSubpath();
		}

		$componentDirectory = '';
		if (isset($args['directory'])) {
			$componentDirectory = Twack::addTrailingSeparator($args['directory']);
		}

		$path = $this->componentsPath;
		$directory = $this->componentsDirectory;
		if ($componentType === 'service') {
			$path = $this->servicesPath;
			$directory = $this->servicesDirectory;
		}

		$resultComponent = null;
		if (file_exists($path . $subPath . $componentDirectory . Twack::camelCaseToUnderscore($componentname) . '.class.php')) {
			// class without containing folder of the same name.

			$resultComponent = [
				'directory' => $componentDirectory, // location inside of the components folder
				'path' => $path . $subPath . $componentDirectory, // full filepath
				'processwirePath' => $directory . $subPath . $componentDirectory, // path relative to processwire-root
				'filename' => Twack::camelCaseToUnderscore($componentname) . '.class.php',
				'classname' => $componentname,
				'type' => 'controller'
			];
		} elseif (file_exists($path . $subPath . $componentDirectory . Twack::camelCaseToUnderscore($componentname) . DIRECTORY_SEPARATOR . Twack::camelCaseToUnderscore($componentname) . '.class.php')) {
			// the component-class is in a directory which has the same name as the component

			$resultComponent = [
				'directory' => $componentDirectory . Twack::camelCaseToUnderscore($componentname) . DIRECTORY_SEPARATOR, // location inside of the components folder
				'path' => $path . $subPath . $componentDirectory . Twack::camelCaseToUnderscore($componentname) . DIRECTORY_SEPARATOR, // full filepath
				'processwirePath' => $directory . $subPath . $componentDirectory . Twack::camelCaseToUnderscore($componentname) . DIRECTORY_SEPARATOR, // path relative to processwire-root
				'filename' => Twack::camelCaseToUnderscore($componentname) . '.class.php',
				'classname' => $componentname,
				'type' => 'controller'
			];
		} elseif ($componentType === 'component' && file_exists($path . $subPath . $componentDirectory . Twack::camelCaseToUnderscore($componentname) . '.view.php')) {
			// No class found, but a single view-file

			$resultComponent = [
				'directory' => $componentDirectory, // location inside of the components folder
				'path' => $path . $subPath . $componentDirectory, // full filepath
				'processwirePath' => $directory . $subPath . $componentDirectory, // path relative to processwire-root
				'viewname' => Twack::camelCaseToUnderscore($componentname),
				'type' => 'view'
			];
		}

		if (is_array($resultComponent)) {
			if ($componentType !== 'component') {
				$resultComponent['type'] = $componentType;
			}

			// If a controller class was found, it has to be includes via require_once before it can be used as a class.
			// Single views don't have to be required, they will be included later via TemplateFile.
			if (isset($resultComponent['type']) && $resultComponent['type'] !== 'view') {
				require_once $resultComponent['path'] . $resultComponent['filename'];
			}

			$this->classLocations[$componentname] = $resultComponent;
			$args['location'] = $this->classLocations[$componentname];
			return $args;
		}

		throw new ComponentNotFoundException($componentname);
	}

	/**
	 * Returns a new instance of the requested component
	 * @param  string $componentname
	 * @param  array  $args            Additional arguments for the component (optional)
	 * @return TwackComponent
	 */
	public function getNewComponent($componentname, $args = []) {
		if (empty($componentname) || !is_string($componentname)) {
			throw new TwackException('getNewComponent(): No valid componentname. A componentname has to be a string an may not be empty.');
		}

		$logging = !isset($args['logging']) || !!$args['logging'];

		$componentname = Twack::underscoreToUpperCamelCase($componentname);

		$resultComponent = new TwackNullComponent(['backtrace' => (isset($args['backtrace']) ? $args['backtrace'] : debug_backtrace())]);

		// Should we look for an alias? (if no component was found with the requested classname, a second attempt will be made automatically with useAlias = true)
		if (isset($args['useAlias']) && !!$args['useAlias']) {
			if ($this->getComponentnameForAlias($componentname) !== null) {
				$args = array_merge($args, $this->getComponentArgsForAlias($componentname));
				$componentname = $this->getComponentnameForAlias($componentname);
			} else {
				throw new ComponentNotFoundException('Requested componentname: "' . $componentname . '"');
			}
		}

		// Merge the parameters:
		if (!isset($args['parameters'])) {
			$args['parameters'] = [];
		}
		$args['parameters'] = array_merge($this->wire('twack')->getGlobalParameters(), $args['parameters']);

		try {
			try {
				$args['useSubpath'] = true;
				// Throws an exception, if no component-files were found:
				$args = $this->getPathsForComponent($componentname, $args);
			} catch (Exception $e) {
				$args['useSubpath'] = false;
				// Throws an exception, if no component-files were found:
				$args = $this->getPathsForComponent($componentname, $args);
			}

			if (isset($args['location']['type']) && $args['location']['type'] !== 'view' && isset($args['location']['classname']) && class_exists(Twack::TEMPLATENAMESPACE . $args['location']['classname'])) {
				// A controller- or service-class was found and can be initialized

				$componentClassname = Twack::TEMPLATENAMESPACE . $args['location']['classname']; // We have to prepend the namespace to call the class dynamically

				$resultComponent = new $componentClassname($args);
			} elseif (isset($args['location']['type']) && $args['location']['type'] === 'view' && isset($args['location']['viewname']) && isset($args['location']['path'])) {
				// A single view was found. It will be loaded within the default TwackComponent-controller

				$componentClassname = Twack::TEMPLATENAMESPACE . 'TwackComponent';  // We have to prepend the namespace to call the class dynamically
				$resultComponent = new $componentClassname($args);
				$resultComponent->setView($args['location']['viewname']);
			} else {
				throw new ComponentNotFoundException('Requested componentname: "' . $componentname . '"');
			}
		} catch (Exception $e) {
			if ($e instanceof TwackException) {
				$args['errorClass'] = $e;
			}

			if (!isset($args['useAlias'])) {
				// Component not found. Was the requested componentname an alias?
				try {
					$args['useAlias'] = true;
					$resultComponent = $this->getNewComponent($componentname, $args);
				} catch (Exception $e) {
					if (isset($args['errorClass']) && $args['errorClass'] instanceof Exception) {
						$e = $args['errorClass'];
					}
					if ($logging) {
						Twack::devEcho($e->getMessage());
					}
					throw $e;
				}
			} else {
				if ($logging) {
					Twack::devEcho($e->getMessage());
				}
				throw $e;
			}
		}

		return $resultComponent;
	}

	/**
	 * Adds an alias, under which th component can be accessed alternatively. It could be useful to add custom
	 * args, that will be only added if the component is accessed via its aliasname.
	 *
	 * @param string $aliasname
	 * @param string $componentname Classname
	 * @param array $args (optional)
	 *
	 * @return boolean
	 */
	public function addComponentAlias($aliasname, $componentname, $args = []) {
		if (is_string($aliasname) && is_string($componentname)) {
			$this->componentAliases[$aliasname] = [
				'name' => $componentname,
				'args' => (is_array($args) ? $args : [])
			];
			return true;
		}
		return false;
	}

	/**
	 * Returns the original componentname for an aliasname.
	 * @param  string $aliasname
	 * @return string|NULL Returns NULL, if no alias with this name was found.
	 */
	public function getComponentnameForAlias($aliasname) {
		if (isset($this->componentAliases[$aliasname])) {
			return $this->componentAliases[$aliasname]['name'];
		}
		return null;
	}

	/**
	 * Returns the optional args for an aliasname
	 * @param  string $aliasname
	 * @return array|null Returns NULL, if no alias with this name was found.
	 */
	public function getComponentArgsForAlias($aliasname) {
		if (isset($this->componentAliases[$aliasname])) {
			return $this->componentAliases[$aliasname]['args'];
		}
		return null;
	}
}
