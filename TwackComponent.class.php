<?php

namespace ProcessWire;

use \Exception;
use \ReflectionClass;

/**
 * TwackComponent
 * Base class for all components managed by Twack. Each self-defined component must inherit from this class with "extends TwackComponent". The class internally handles the management of component paths, the creation and passing of parameters.
 */

class TwackComponent extends WireData {
    protected $page; 		// ProcessWire-Page, on which the component should build its information on.
                            // If not set explicitly to another value, $page contains wire('page')
    protected $twack; 		// Singleton-instance of the Twack-module
    protected $location;	// Location-information of the component. Contains absolute and relative paths to the component's files.
    protected $viewArgs;	// Args for the view.
    protected $childComponents; // Subcomponents, which were added via $this->addComponent() as children.
    protected $namedComponents; // Subcomponents, which were added via $this->addComponent() with an explicit componentname.
    protected $inlineStyles; // Paths to stylesheets, which should be added inline.

    public function __construct($args = array()) {
        if (!isset($args['location']) || !isset($args['location']['directory']) || !isset($args['location']['path']) || !isset($args['location']['processwirePath']) || (!isset($args['location']['classname']) && !isset($args['location']['viewname'])) || !isset($args['location']['type'])) {
            throw new ComponentNotInitializedException((new ReflectionClass($this))->getShortName(), 'Die nötigen Pfad-Angaben wurden nicht übergeben.');
        }

        $this->location = $args['location'];
        $this->viewArgs = $args;

        // Save CSS-filepaths, which should be added in HTML right under the components HTML:
        $this->inlineStyles = new FilenameArray();

        // Set the view. If $viewArgs['viewname'] is not set, the default name {$classname}.view.php will be set.
        $viewname = (new ReflectionClass($this))->getShortName();
        if (isset($this->viewArgs['viewname'])) {
            $viewname = $this->viewArgs['viewname'];
        } elseif (isset($this->viewArgs['classname'])) {
            $viewname = $this->viewArgs['classname'];
        }
        $this->setView($viewname);

        // The module-instance will be available under $this->twack:
        $this->twack = wire('modules')->get('Twack');

        // All child-components will be stored in $this->childComponents (unnamed) or in $this->namedComponents (components with a given key)
        $this->childComponents = new WireArray();
        $this->namedComponents = new WireArray();

        // You can pass custom parameters to a component, which will be accessible directly as attributes. Pass i.e. $args['parameters']['testvalue'] = 1 to a component, and call it inside of it with $this->testvalue. All parameters will be passed to the view as well.
        if (isset($args['parameters']) && is_array($args['parameters'])) {
            $this->setArray($args['parameters']);
        }

        // Initialize $this->page, get it from $args if set (default is wire('page'))
        $this->page = wire('page');
        if (isset($args['page'])) {
            if (!($args['page'] instanceof Page)) {
                $args['page'] = wire('pages')->get('id=' . wire('sanitizer')->int($args['page']));
            }

            if ($args['page'] instanceof Page && $args['page']->id) {
                $this->page = $args['page'];
            }
        }
        $this->page = isset($args['page']) ? $args['page'] : wire('page');
    }

    /**
     * Adds a CSS-file to the collection of styles.
     *
     * @param string $css  					path to css-file
     * @param bool $absolute  (optional)  	if true: Use $css as an absolute path (base is processwire-templates directory).
     *                        				if false (default): $css is a filepath relative to the component's path
     * @param bool $inline 					set it to true, if you don't want the styles be added to the global
     *                          			wire('config')->styles array, but instead want it be imported inline
     *                          			behind the html of the component.
     */
    public function addStyle($css, $options = array()) {
        $styleURL = Twack::getManifestFilename("$css");

        if (isset($options['path']) && $options['path']) {
            $styleURL = $options['path'] . $styleURL;
        }

        if (!isset($options['absolute']) || !$options['absolute']) {
            $styleURL = $this->location['processwirePath'] . $styleURL;
        }

        if (isset($options['inline']) && $options['inline']) {
            $this->inlineStyles->add($styleURL);
        } else {
            wire('config')->styles->add($styleURL);
        }
    }

    /**
     * Adds a JS-file to the collection of scripts.
     *
     * @param string $script 				path to js-file
     * @param bool $absolute  (optional) 	if true: Use $script as an absolute path (base is processwire-templates directory).
     *                        				if false (default): $script is a filepath relative to the component's path
     */
    public function addScript($script, $options = array()) {
        $scriptURL = Twack::getManifestFilename("$script");

        if (isset($options['path']) && $options['path']) {
            $scriptURL = $options['path'] . $scriptURL;
        }

        if (isset($options['fromRoot']) && $options['fromRoot']) {
            $scriptURL = wire('config')->urls->templates . $scriptURL;
        } elseif (!isset($options['absolute']) || !$options['absolute']) {
            $scriptURL = $this->location['processwirePath'] . $scriptURL;
        }

        wire('config')->scripts->add($scriptURL);
    }

    /**
     * Adds a component (if the component class is available). If no special page or paths are explicitly set, the page and paths of the current component will be passed to the added component.
     *
     * @param string $componentname 		Classname (or Aliasname) of the component
     * @param array $args 					Additional arguments for the new component (optional). Possible are i.e.:
     *                    page           	Page which should be the foundation of the new component-instance (Default: wire('page'))
     *                    parameters       	Additional Parameters for the component, which will be availble as an attribute inside
     *                    						the component. A component can define and handle custom parameters, so check the
     *                    						target-component's code for handled parameters.
     *                    directory 		Under which path, relative to the root-component's folder, is the component located?
     *                    						If no directory is set, Twack will search in the calling component's directory.
     *                    name 				If you set a name for the new component, it will be accessable via its name. Otherwise,
     *                    						it will be added to the collection $this->childComponents. Call
     *                    						$this->getComponent($name) to call the added component.
     *                    prepend 			If the component wasn't added with a individual name, it will be added to
     *                    						$this->childComponents. Set prepend to true to ensure that the new component will be
     *                    						added in front of existing elements in this collection.
     *                    position 			Like prepend, you can also give a specific position where the new component should be
     *                    						added in $this->childComponents.
     *                    throwErrors 		Set to true, if the method should throw an error if the new component cannot be added.
     *                    						(default: false - return TwackNullComponent instead)
     *
     * @return TwackComponent|TwackNullComponent
     */
    public function addComponent($componentname, $args = array()) {
        $throwErrors = isset($args['throwErrors']) && !!$args['throwErrors'];

        try {
            $args['throwErrors'] = true;

            if ($componentname instanceof TwackComponent) {
                $resultComponent = $componentname;
            } else {
                $resultComponent = $this->getNewComponent($componentname, $args);
            }

            if (!isset($args['name']) && isset($args['globalName'])) {
                $args['name'] = $args['globalName'];
            }

            if (isset($args['name']) && is_string($args['name'])) {
                // A name was passed for the new component, under which it will be available later:
                $this->namedComponents[$args['name']] = $resultComponent;
            } else {
                if (isset($args['prepend']) && !!$args['prepend']) {
                    // Add the component in front of existing elements in $this->childComponents:
                    $this->childComponents->prepend($resultComponent);
                } elseif (isset($args['position']) && is_int($args['position']) && $args['position'] < count($this->childComponents)) {
                    // Add the new component at a specific position in $this->childComponents:
                    $existingComponent = $this->childComponents->get($args['position']);
                    $this->childComponents->insertBefore($resultComponent, $existingComponent);
                } else {
                    // Default: Add component after existing components:
                    $this->childComponents->add($resultComponent);
                }
            }

            return $resultComponent;
        } catch (Exception $e) {
            if ($throwErrors) {
                throw $e;
            }
            return new TwackNullComponent(['backtrace' => (isset($args['backtrace']) ? $args['backtrace'] : debug_backtrace()), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Returns a new component instance (if the component class is available) without adding it to the child components. If no special page or paths are explicitly passed, the page and paths of the current component will be set.
     *
     * @param string $componentname 		Classname (or Aliasname) of the component
     * @param array $args 					Additional arguments for the new component (optional). Possible are i.e.:
     *                    page           	Page which should be the foundation of the new component-instance (Default: wire('page'))
     *                    parameters       	Additional Parameters for the component, which will be availble as an attribute inside
     *                    						the component. A component can define and handle custom parameters, so check the
     *                    						target-component's code for handled parameters.
     *                    directory 		Under which path, relative to the root-component's folder, is the component located?
     *                    						If no directory is set, Twack will search in the calling component's directory.
     *                    throwErrors 		Set to true, if the method should throw an error if the new component cannot be added.
     *                    						(default: false - return TwackNullComponent instead)
     * @return TwackComponent|TwackNullComponent
     */
    public function getNewComponent($componentname, $args = array()) {
        try {
            if (!isset($args['page'])) {
                $args['page'] = $this->page;
            }
            if (!isset($args['directory'])) {
                $args['directory'] = $this->location['directory'];
            }
            if (!isset($args['backtrace'])) {
                $args['backtrace'] = debug_backtrace();
            }

            $resultComponent = $this->twack->getNewComponent($componentname, $args);

            return $resultComponent;
        } catch (Exception $e) {
            if (isset($args['throwErrors']) && $args['throwErrors'] === true) {
                throw $e;
            }
            return new TwackNullComponent(['backtrace' => (isset($args['backtrace']) ? $args['backtrace'] : debug_backtrace()), 'fehler' => $e->getMessage()]);
        }
    }

    /**
     * Resets $this->childComponents, removes added subcomponents:
     */
    public function resetComponents() {
        $this->childComponents = new WireArray();
    }

    /**
     * Resets $this->namedComponents, remove all added named subcomponents:
     */
    public function resetNamedComponents() {
        $this->namedComponents = new WireArray();
    }

    /**
     * Counts, if any not-named subcomponent was added.
     * @param  boolean $skipEmptyComponents  set to true, if you want to check wether any of the subcomponents has an echoable
     *                                       	(not empty) content as well
     * @return boolean
     */
    public function hasComponents($skipEmptyComponents = false) {
        if (wireCount($this->childComponents->count())) {
            if (!$skipEmptyComponents) {
                return true;
            }

            foreach ($this->childComponents as $component) {
                if (strlen($component->render()) > 0) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }

    /**
     * Returns a single childcomponent.
     * @param  string $componentname 	Name of the component or position in $this->childComponents
     * @return TwackComponent|TwackNullComponent
     */
    public function getComponent($componentname, $args = []) {
        // Look for namedComponents:
        if ($this->namedComponents->has($componentname)) {
            return $this->namedComponents->get($componentname);
        }

        // No named component matched. Was a (numeric) position in $this->childComponents given?
        if (is_numeric($componentname)) {
            if ($this->childComponents->has($componentname)) {
                return $this->childComponents->get($componentname);
            }
        }

        // Nothing found, return TwackNullComponent
        return new TwackNullComponent(['backtrace' => (isset($args['backtrace']) ? $args['backtrace'] : debug_backtrace())]);
    }

    /**
     * Returns all childcomponents
     * @return WireArray
     */
    public function getChildComponents() {
        return $this->childComponents;
    }

    /**
     * Returns all subcomponents with a specifically set name.
     * @return WireArray
     */
    public function getNamedComponents() {
        return $this->namedComponents;
    }

    /**
     * Returns a component from the global component collection. It must have been previously registered with Twack using makeComponentGlobal().
     * @param  string $componentname
     * @return TwackComponent
     */
    public function getGlobalComponent($componentname) {
        return $this->twack->getComponent($componentname);
    }

    /**
     * Returns an instance of a service class.
     * @param  string $servicename
     * @return TwackComponent
     */
    public function getService($servicename) {
        return $this->twack->getService($servicename);
    }

    /**
     * Returns a global Twack-parameter. Every component has access to these parameters and can do changes to them.
     * @internal
     */
    public function getGlobalParameter($parametername = null) {
        return $this->twack->getGlobalParameter($parametername);
    }

    /**
     * Returns all globally set parameters.
     * @return array
     */
    public function getGlobalParameters() {
        return $this->twack->getGlobalParameters();
    }

    /**
     * Adds parameters to the globally set parameters. If a parameter-key already exists it will be overwritten.
     * @param array $parameters
     * @internal
     * @deprecated
     */
    public function addGlobalParameters(array $parameters) {
        $this->twack->addGlobalParameters($parameters);
    }

    /**
     * Adds the contents of a page to the component.
     * @param Page $page
     */
    public function setPage(Page $page) {
        $this->page = $page;
    }

    public function getPage() {
        return $this->page;
    }

    /**
     * Searches for a viewfile. For a view to be found automatically, it must be created as a file called [classname (lowercase)] + ".view.php" and located in the same folder as the component.
     *
     * @return array  location-information for the viewfile
     * @throws ViewNotFoundException
     */
    protected function findViewPath($viewname, $args = array()) {
        $viewname = Twack::camelCaseToUnderscore($viewname);

        if (!isset($args['location']['path'])) {
            throw new ViewNotFoundException('Unvollständige Pfadangaben: ' . $viewname . '.view.php');
        }

        if (file_exists($args['location']['path'] . $viewname . '.view.php')) {
            // A view was found under the specified path.
            return $args['location']['path'] . $viewname . '.view.php';
        }

        throw new ViewNotFoundException('Pfad: ' . $args['location']['path'] . $viewname . '.view.php');
    }

    /**
     * Returns the contents of a view as a string. If no viewname is specified, the default view will be used.
     *
     * @param  string $viewname
     * @param  array  $args
     * @return string
     * @throws ViewNotFoundException
     */
    public function getView($viewname = '', $args = array()) {
        if (!is_string($viewname)) {
            throw new ViewNotFoundException('No valid view name was passed.');
        }

        $args = array_merge($this->viewArgs, $args);
        if ($viewname === '') {
            $viewname = $args['viewname'];
        }

        // All parameters of the component should also be available in the view:
        $parameters = $this->getArray();

        // Were special parameters for the view passed using the args-array?
        if (isset($args['parameters']) && is_array($args['parameters'])) {
            $parameters = array_merge($parameters, $args['parameters']);
        }

        // The named components are available in the view under their names:
        foreach ($this->namedComponents as $component) {
            $parameters[$this->namedComponents->getItemKey($component)] = $component;
        }

        $viewPath = $this->findViewPath($viewname, $args);

        // Pass parameters to the view. They can then be called in the view via $this->{variablename}:
        $view = new TemplateFile($viewPath);
        $view->setArray($parameters);

        if ($this->page && $this->page instanceof Page) {
            $view->page = $this->page;
        }
        $view->component       = $this;
        $view->childComponents = $this->getChildComponents();

        return $view->render();
    }

    /**
     * Returns a view string. If an error occurs, only an empty string is output.
     *
     * @param  string $viewname
     * @param  array  $args
     *
     * @return string
     */
    public function renderView($viewname = '', $args = array()) {
        $output = '';
        try {
            $output .= $this->getView($viewname, $args);

            // If the styles have not yet been output within the component, they are appended at the end:
            $output .= $this->getInlineStyles(false);
        } catch (\Throwable $e) {
            Twack::devEcho($e->getMessage());
            // return '<div class="alert alert-danger" role="alert"><strong>An error has occurred:</strong> ' . $e->getMessage() . '</div>';
        }

        return $output;
    }

    /**
     * Returns all inline styles of the component as a string. By default, they are then deleted so that they are not output a second time.
     * @param  boolean $remove  	Do you want to delete the styles afterwards? Default: true
     * @return string
     */
    public function getInlineStyles($remove = true) {
        $output = '';
        if ($this->inlineStyles instanceof FilenameArray && $this->inlineStyles->count() > 0) {
            foreach ($this->inlineStyles as $stylePfad) {
                $output .= "\n<link rel='stylesheet' type='text/css' href='{$stylePfad}' />";
            }
        }

        if ($remove) {
            $this->inlineStyles = new FilenameArray();
        }
        return $output;
    }

    /**
     * Sets the default view called in the render method.
     */
    public function setView($viewname, $parameters = array()) {
        $this->viewArgs             = array_merge($this->viewArgs, $parameters);
        $this->viewArgs['viewname'] = $viewname;
    }

    /**
     * Output of the component. Can be defined by the child classes themselves. By default, a view file $name.view.php is searched for and displayed via ProcessWire TemplateFile.
     * The ProcessWire page is accessible in the template via $this->page. The controller class can be called via $this->component in the view.
     * @return string  Either the content of the view or an error message is output.
     */
    public function ___render() {
        if ($this->twack->isTwackAjaxCall()) {
            return $this->renderAjax();
        }
        return $this->renderView();
    }

    public function __toString() {
        return $this->render();
    }

    public function renderAjax() {
        $ajax    = $this->getAjax();
        $output = array();
        if (is_array($ajax)) {
            $output = $ajax;
        } elseif (!empty((string) $ajax)) {
            $output['value'] = (string) $ajax;
        }

        if ($this->twack->forcePlainAjaxOutput) {
            return json_encode($output);
        }
        Twack::sendResponse(200, json_encode($output), 'application/json');
    }

    /**
     * Returns an output of the component as a PHP array that can be converted for the Ajax output.
     */
    public function getAjax() {
        return array();
    }

    /**
     * Helper function, to convert common PHP-Objects to arrays which can be output in ajax.
     * @param  Object $content
     * @return array
     */
    public function getAjaxOf($content) {
        $output = array();

        if ($content instanceof PageFiles) {
            foreach ($content as $file) {
                $output[] = $this->getAjaxOf($file);
            }
        } elseif ($content instanceof PageFile) {
            $output = array(
                'basename'     => $content->basename,
                'name'         => $content->name,
                'description'  => $content->description,
                'created'      => $content->created,
                'modified'     => $content->modified,
                'filesize'     => $content->filesize,
                'filesizeStr'  => $content->filesizeStr,
                'page_id'      => $content->page->id,
                'ext'          => $content->ext
            );

            if ($content instanceof PageImage) {
                $output['basename_mini']          = $content->size(600, 0)->basename;
                $output['width']                  = $content->width;
                $output['height']                 = $content->height;
                $output['dimension_ratio']        = $content->width / $content->height;

                if ($content->original) {
                    $output['original'] = [
                        'basename'      => $content->original->basename,
                        'name'          => $content->original->name,
                        'filesize'      => $content->original->filesize,
                        'filesizeStr'   => $content->original->filesizeStr,
                        'ext'           => $content->original->ext,
                        'width'         => $content->original->width,
                        'height'        => $content->original->height,
                        'dimension_ratio' => $content->original->width / $content->original->height
                    ];
                }
            }
        } elseif ($content instanceof Template && $content->id) {
            $output = array(
                'id'    => $content->id,
                'name'  => $content->name,
                'label' => $content->label
            );
        } elseif ($content instanceof Page && $content->id) {
            $output = array(
                'id'       => $content->id,
                'name'     => $content->name,
                'title'    => $content->title,
                'created'  => $content->created,
                'modified' => $content->modified,
                'url'      => $content->url,
                'httpUrl'  => $content->httpUrl,
                'template' => $this->getAjaxOf($content->template)
            );
        }

        return $output;
    }
}
