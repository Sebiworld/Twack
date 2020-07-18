# Twack

**Reusable components for your [ProcessWire](https://processwire.com)-templates!**

Welcome to Twack! Twack helps you build well-structured and maintainable ProcessWire-projects. Inspired by [angular](https://angular.io).

| ProcessWire-Module: | [https://modules.processwire.com/modules/twack/](https://modules.processwire.com/modules/twack/) |
| ------------------: | ------------------------------------------------------------ |
|      Support-Forum: | [https://processwire.com/talk/topic/23549-twack/](https://processwire.com/talk/topic/23549-twack/) |
|         Repository: | [https://github.com/Sebiworld/Twack](https://github.com/Sebiworld/Twack) |

<a name="features"></a>


## Features

* Create  **components** ! Twack components are designed for reusability and encapsulating a set of features for easy maintainability. They can handle hierarchical or recursive use (child components).
* **Based on ProcessWire's core functionality and principles**
* **HTML- and Ajax-Views** - specify json output for ajax-requests
* Define **services**, that handle global data. Twack manages the shared service-classes (->singleton) for you and lets you access the data from everywhere you need it.
* **Not exclusive** - use Twack components to extend existing templates. You don't have to rewrite everything!

## Table Of Contents

* [Features](#features)
* [Installation](#installation)
* [Usage](#usage)
  * [Quickstart: Creating a component](#quickstart)
  * [Naming conventions & component variants](#naming-conventions)
  * [Component Parameters](#component-parameters)
    * [directory](#component-parameters-directory)
    * [page](#component-parameters-page)
    * [parameters](#component-parameters-parameters)
    * [viewname](#component-parameters-viewname)
  * [Asset handling](#asset-handling)
  * [Services](#services)
  * [Named components](#named-components)
  * [Global components](#global-componets)
  * [Ajax-Output](#ajax-output)
* [Configuration](#configuration)
* [Versioning](#versioning)
* [License](#license)
* [Changelog](#changelog)

<a name="installation"></a>

## Installation

Twack can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

The prerequisites are **PHP>=5.5.0** and a **ProcessWire version >=3.0.0**. However, this is also checked during the installation of the module. No further dependencies.

<a name="usage"></a>

## Usage

<a name="quickstart"></a>

### Quickstart: Creating a component

Twack uses components to subdivide the website into logical components. A typical component can stand for its own and has no dependencies, which make it reusable and easy to maintain. Most components consist of a controller and a view.

If you have not done create the `site/templates/components` directory. Here we will place all of our future components. Add a new file `hello_world.view.php` inside of the directory. Create `site/templates/services` where all our service classes will find a home.

Open the file and fill in the following contents:

```php
<?php
namespace ProcessWire;
?>
<h2>Hello World!</h2>
```

It's done! We have our first functioning component. Admittedly, the component consists of only one view, but even for simple components there are many useful purposes. We will add data-handling and functions in a moment. But first, let's learn how to use our component in one of our ProcessWire-templates.

If we had a controller-class for our view, the class would be called `HelloWorld`. More on that in the next chapter. To use our component in a ProcessWire-template, we have to ask the Twack-module for it and it will find and initialize it for us.

```php
<?php
namespace ProcessWire;

// Get a new instance of our HelloWorld-component:
$myComponent = wire('twack')->getNewComponent('HelloWorld');
?>

<html>
  <head>
    <title>Hello World!</title>
  </head>
  <body>
    <?php
    // Render the component`s view:
    echo $myComponent->render();
    ?>
  </body>
</html>

```

As you see, including a Twack-component in traditional ProcessWire-templates is quite simple. You can build the complete HTML in Twack-views and use its full potential, but you don't have to. It is possible to gradually replace individual parts of the page.

Let's go back to our component. You created your HelloWorld-component with nothing but a view that outputs a bold "Hello World!". Most components need more than just an output. We need a __controller__ to make the view more dynamic.

Create a new directory `site/templates/components/hello_world/` and move our view-file to this destination. Additionally create a controller file with the name `hello_world.class.php` in this new directory.

A Twack-controller needs a bit of boilerplate-code to correctly function. Copy the following code to your controller-file (`hello_world.class.php`):

```php
<?php
namespace ProcessWire;

class HelloWorld extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);
  }
}
```

Every Twack-controller has to extend our general TwackComponent, which brings a lot of background-functionality to our controller. With `parent::__construct($args);` we let the parent `TwackComponent` finish its general initialization work before our custom component's code will be executed.

In our constructor we will define variables, add child-components and do all logical work for the view.

An a little more advanced controller can look like this:

```php
<?php
namespace ProcessWire;

class HelloWorld extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    $this->title = 'Hello World!';
    if(isset($args['title'])) {
      $this->title = $args['title'];
    }

    // Add and initialise a child-component
    $testChildArgs = [
      'myTestValue'
    ];
    $this->addComponent('TestChild', $testChildArgs);
  }
}
```

`$this->title` will be "Hello World!", as long as we get no value for `$args['title']` from our constructor's `$args` parameter. If we had initialized the component with `$twack->getNewComponent('HelloWorld', ['title' => 'My new Title.']);`, we would set it to this new value.

Every attribute of the controller is also accessible in the view, you don't have to care about transferring values.

A child component can be added via `$this->addComponent()`. In our example, we add the Component 'TestChild', which shall be located under `site/templates/components/hello_world/test_child/test_child.class.php`. Twack automatically looks in the current component's directory for subdirectories. Specifying an other path is also possible. I created an array `$testChildArgs` to demonstrate passing additional parameters to the `TestChild`, which will be passed to its constructor.

Our new view could look like this:

```php
<?php
namespace ProcessWire;
?>

<div class="hello_world_component">
  <?php
  if(!empty($this->showTitle)){
    echo "<h2>{$this->showTitle}</h2>";
  }
  ?>
  <p>Lorem ipsum</p>

  <div class="children_wrapper">
    <?php
    foreach ($this->childComponents as $childComponent) {
      echo (string) $childComponent;
    }
    ?>
  </div>
</div>
```

As you see, we only show the title, if `$this->title` has a value. Under `$this->childComponents` we have a list of all components that were added via `$this->addComponent()` in the controller.

We now have created a basic Twack-component and you now the general concepts how Twack works. But Twack has are a lot of more great features that will emend and simplify your development process.

<a name="naming-conventions"></a>

### Naming conventions & component variants

As you saw in my `HelloWorld`-example above, Twack requires class and file names to be in a fixed format. (Controller-) classnames need to be written in Upper-CamelCase. Every filename and directory is, contrary to classnames, required to be specified in underscore_notation.

Please make sure that your class names do not match the names of ProcessWire core or module classes. That's how you avoid name collisions in the ProcessWire namespace with already existing classes.

If you need your component be available under another component name it can be useful to add an alias:

```php
wire('twack')->addComponentAlias('HalloWelt', 'HelloWorld');

// Call for "HalloWelt", but get the "HelloWorld"-component:
$myComponent = wire('twack')->getNewComponent('HalloWelt');
```

This makes our `HelloWorld`-component also available under the component name `HalloWelt`. As an additional third parameter you can pass custom arguments to only the alias component.

```php
wire('twack')->addComponentAlias('HalloWelt', 'HelloWorld', ['title' => 'Hallo Welt!']);

// Call for "HalloWelt", but get the "HelloWorld"-component:
$myComponent = wire('twack')->getNewComponent('HalloWelt');
```

Every `HalloWelt` component will now have the title "Hallo Welt!" for default, while the `HelloWorld` components still have "Hello World!" as default. I recommend to not overstrain the alias functionality - finding the original component for an alias can take a while and make your code less readable.

<a name="component-parameters"></a>

### Component Parameters

You cannot only pass custom parameters to newly instantiated components. There are a few core-parameters that influence the component's behavior as well.

<a name="component-parameters-directory"></a>

#### directory

```php
<?php
namespace ProcessWire;

$myComponent = wire('twack')->getNewComponent('PageTitleOutput', [
  'directory' => 'general'
]);
```

Setting a value for the `directory`-parameter makes Twack to look for the component files under a different location. With the code above, we instruct Twack to initialize a `PageTitleOutput`-component which is located not under `site/templates/components/page_title_output/page_title_output.class.php` but under `site/templates/components/general/page_title_output/page_title_output.class.php`.

The `directory`-path is relative to the components-directory, which is defined in the module-configuration. You can also set it to an empty string to instruct Twack to look for a component at the root-level. This is especially useful when you add a child-component that is not located in your parent components directory:

```php
<?php
namespace ProcessWire;

class HelloWorld extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    // Our "General"-component is located under site/templates/components/general/general.class.php:
    $this->addComponent('General', ['directory' => '']);
  }
}
```

<a name="component-parameters-page"></a>

#### page

Each component has a ProcessWire page it is internally linked with. Per default that would be the page which was initially called by the user - the same as in wire('page'). You can access the component's page via `$this->page`:

```php
<?php
namespace ProcessWire;
?>
<h2><?= $this->page->title; ?>(<?= $this->page->id; ?>)</h2>
```

_site/templates/components/general/page_title_output.view.php_

But you can also change the internally linked page. For example, if you want to show a list of pages you can call our `PageTitleOutput`component on each page:

```php
<?php
namespace ProcessWire;

foreach(wire('pages')->find('template.name=newsitem') as $item){
  $myComponent = wire('twack')->getNewComponent('PageTitleOutput', [
    'directory' => 'general',
    'page' => $item
  ]);
  echo $myComponent->render();
}
```

<a name="component-parameters-parameters"></a>

#### parameters

When you pass custom values to the new component they will be available in the component constructor's `$args` array. You can work with these values and set them via `$this->my_value = $args['my_value'];` to a component-attribute that will be also accessible in the view. If you don't want to do any logic and validation with a custom value, you can set it as a component-attribute right in the initialization step. Add a `parameters` array with your custom parameters, that should be available as a component attribute and in the view.

```php
<?php
namespace ProcessWire;

$myComponent = wire('twack')->getNewComponent('PageTitleOutput', [
  'directory' => 'general',
  'parameters' => [
    'showTitle' => false
  ]
]);
echo $myComponent->render();
```

<a name="component-parameters-viewname"></a>

#### viewname

Even though I haven't needed it much in my components so far, it's possible to set a custom view name. With this feature, you could for example initialize a component but say, that it should use a different view file than the default {class_name}.view.php.

```php
<?php
namespace ProcessWire;

$myComponent = wire('twack')->getNewComponent('PageTitleOutput', [
  'directory' => 'general',
  'viewname' => 'OnlyDescription'
]);
echo $myComponent->render();
```

The example above would instruct the component to use the view `site/templates/components/general/page_title_output/only_description.view.php`.

<a name="asset-handling"></a>

### Asset handling

In former times, we created a central `main.css` and `main.js` file that contained collected style- and script-information for all page components. And for all components, that could be, but are not shown at the current page. That could lead to a big file with large proportions of unused code.

Since __HTTP2__ we don't have any valid argument to keep on with strategy. We can speed up our site if we load multiple small files, because they will be loaded simultaneously. In addition, semantically spliced code allows us to reduce the weight of unused code as well. You only have to load the code which is really needed by a component.

Twack supports you by adding different ways to include your css- and js-assets. ProcessWire already has its global `wire('config')->scripts` and `wire('config')->styles` WireArrays, where scripts and styles can be collected. With that mechanism, you can collect all your scripts and styles, but you have to include them in your view:

```php
<?php
namespace ProcessWire;
?>

<html>
<head>
  <?php
  foreach (wire('config')->styles as $stylefile) {
    echo "\n\t<link rel='stylesheet' href='$stylefile' /> ";
  }
  ?>
</head>
<body>
  <?php
  foreach (wire('config')->scripts as $scriptfile) {
    echo "\n\t<script type='text/javascript' src='$scriptfile'></script>";
  }
  ?>
</body>
</html>
```

Every Twack component comes with a `$this->addStyle()` and a `$this->addScript()` function which add your scripts and styles to the global `wire('config')` collections. But they do more than that. The functions enable you to place CSS- and JS-Files in your component's directories. Per default, if you call `$this->addStyle('component-styles.css')` inside of our `page_title_output.class.php`, the url `/site/templates/components/general/page_title_output/component-styles.css` will be added to `wire('config')->styles`. The same will happen for scripts via `$this->addScript()` as well.

If you still want to add an absolute url, use `true` for the second parameter:

```php
$this->addStyle(
  wire('config')->urls->templates . 'assets/css/bootstrap.min.css',
  true
);
```

This will add exactly the url you passed.

<a name="services"></a>

### Services

In a complex website, you may want to have a central place where you can define general functions and attributes that can be used by multiple components.

Twack has a concept for this: __Services__! Every service class is a singleton that is globally managed by Twack. If multiple components ask for a service, they will all get the same instance. Services aren't meant to render anything, so they have no view.

You can define your own service-classes in `site/templates/services/`. This path is configurable in the module's configuration.

Here is, for example, a news service that can retrieve, sort and filter our news-item pages.

```php
<?php
namespace ProcessWire;

class NewsService extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);
  }

  public function getNewsPage() {
    return wire('pages')->get('template.name="newsroom"');
  }

  public function getArticles($args = array()) {
    $output = new \StdClass();
    $articles = $this->getNewsPage()->find('template=newsitem');

    if (isset($args['sort'])) {
      $articles->filter('sort=' . $args['sort']);
    } else {
      $articles->filter('sort=-date');
    }

    // Filter by Tags:
    if (isset($args['tags'])) {
      if (is_string($args['tags'])) {
        $args['tags'] = explode(',', $args['tags']);
      }

      if (is_array($args['tags'])) {
        $articles->filter('tags='.implode('|', $args['tags']));
      }
    }

    // Filter by search query:
    if (isset($args['query'])) {
      if (is_string($args['query'])) {
        $query = wire('sanitizer')->text($args['query']);
        $articles->filter("title|name|einleitung|inhalte.text%={$query}");
      }
    }

    // Save the original count of all items before applying limit and offset:
    $output->count = $articles->count;

    // Index of last element of the output:
    $output->lastElementIndex = 0;

    // Apply Limit and Offset:
    $limitSelector = array();

    if (isset($args['start'])) {
      $limitSelector[] = 'start=' . $args['start'];
      $output->lastElementIndex = intval($args['start']);
    } elseif (isset($args['offset'])) {
      $limitSelector[] = 'start=' . $args['offset'];
      $output->lastElementIndex = intval($args['offset']);
    } else {
      $limitSelector[] = 'start=0';
    }

    if (isset($args['limit']) && $args['limit'] >= 0) {
      $limitSelector[] = 'limit=' . $args['limit'];
      $output->lastElementIndex = $output->lastElementIndex + intval($args['limit']);
    } elseif (!isset($args['limit'])) {
      $limitSelector[] = 'limit=12';
      $output->lastElementIndex = $output->lastElementIndex + 12;
    }

    if (!empty($limitSelector)) {
      $articles->filter(implode(', ', $limitSelector));
    }

    // Are there any more posts that can be downloaded?
    $output->hasMore = $output->lastElementIndex + 1 < $output->count;

    $output->articles = $articles;

    return $output;
  }
}
```

_site/templates/services/news\_service.class.php_

We can call `$this->getService('NewsService');` in many components and then use the service's functions. Twack will look for the class in our services-directory and make a singleton-instance out of it. I think, to avoid name-conflicts with the "normal" components its a good convention to add "Service" as a suffix to every service-class.

<a name="named-components"></a>

### Named components

To add a child-component to your current component, you call `$this->addComponent();` and create a child-component instance which is accessible in `$this->childComponents`. These components are typically nameless components that will be outputted in a `for`-loop.

Sometimes you want to add a specific child-component for a special use. You want to add it and output at an individual position in your view. You could add it as the first component to `$this->addChildComponents` and then output the first child in your view. But the better way would be to add it as a named component:

```php
$this->addComponent('Tags', ['name' => 'tagcloud']);
```

The argument `name` identifies our `Tags`-instance as a named child-component. A named component will not be added to `$this->childComponents`, but it is individually accessible via `$this->getComponent('tagcloud')`. You can add multiple named child-components. The name of the components is not dependent on the component's class name. You can choose any name you want.

<a name="global-components"></a>

### Global components

Normally you can get very far with the classic concept: Each component only knows its children, so those components that were inside of the class via `$this->addComponent()`. Components can share code and data with a service.

But there are a few cases, in which you want to directly access and influence a component from a component that did not initialize it and is not directly linked with it. Twack allows you to make a component global and therefore accessible from every other component.

```php
<?php
namespace ProcessWire;

class General extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    $this->addComponent('Header', [
      'globalName' => 'header'
    ]);

    $this->addComponent('Footer', [
      'globalName' => 'footer'
    ]);
  }
}
```

You see, we have a global Header and a global Footer now. It could be useful to influence them from within other components. This (useless) component will, as an example, change the header's color. So, if any `ImageView` is initialized on our page, the header will be green.

```php
<?php
namespace ProcessWire;

class ImageView extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    $header = $this->getGlobalComponent('header');
    $header->changeColorTo('green');
  }
}
```

<a name="ajax-output"></a>

### Ajax-Output

Advanced sites with a lot of user interaction often have complex Javascript components that have to communicate with the backend. Some pages should also be connected to an app, or an external server needs secure access to special services. Sooner or later you will reach the point where you have to build your own API interface.

Good thing we have our Twack components! Twack comes with the ability to render JSON output instead of HTML views. So with Twack you can develop an API that is fully integrated into the rest of the site. If you write HTML output for a component, you can also define the appropriate JSON output.

I have written a second module for the definition of access routes and the administration of API accesses: [AppApi](https://github.com/Sebiworld/AppApi) works perfectly together with Twack. And there's more: AppApi also handles user authentication for you. The [Double-JWT-Authentication](https://github.com/Sebiworld/AppApi#double-jwt-recommended-for-apps) is for example very well suited to build a login and session system into a connected app. Check it out to find out, how to [create the endpoints and connect them to our Twack-components](https://github.com/Sebiworld/AppApi#example-universal-twack-api).

We will now, as announced, take care of the JSON issues of our components. First of all, you should know that the following command, called before the render process, forces Twack to initiate the Ajax output:

```php
wire('twack')->enableAjaxResponse();
```

Each component already has an implicit `getAjax()` function through its parent component `TwackComponent`, but by default it returns only an empty array of data:

```php
public function getAjax($ajaxArgs = []) {
  return array();
}
```

You can overwrite this function in each component with your own variant that delivers correct data. Let's try this using our `HelloWorld` component from the beginning as an example:

```php
class HelloWorld extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    $this->title = 'Hello World!';
    if(isset($args['title'])) {
      $this->title = $args['title'];
    }
  }

  public function getAjax($ajaxArgs = []) {
    return [
      'title' => $this->title;
    ];
  }
}
```

It is that simple! If you strictly follow the rule not to include any data processing and logic in the (HTML-)view, you can simply use all data in the `getAjax()` function for output. Calling `render()` on this component will lead to the following output:

```jsonc
{
  "title": "Hello World!"
}
```

You can also call child components in the `getAjax()` function to merge or nest the outputs. Just the way you need it.

```php
class HelloWorld extends TwackComponent {
  public function __construct($args) {
    parent::__construct($args);

    $this->title = 'Hello World!';
    if(isset($args['title'])) {
      $this->title = $args['title'];
    }

    // Add and initialise a child-component
    $testChildArgs = [
      'myTestValue'
    ];
    $this->addComponent('TestChild', $testChildArgs);
  }

  public function getAjax($ajaxArgs = []) {
    // Basic output: data of the current page:
    $output = $this->getAjaxOf($this->page);

    // We collect every output of the child-components in $output['children']
    if ($this->childComponents) {
      $output['children'] = [];
      foreach ($this->childComponents as $component) {
        $ajax = $component->getAjax($ajaxArgs);
        if (empty($ajax)) { continue; }
        $output['children'][] = $ajax;
      }
    }

    return $output;
  }
}
```

The output of this component will be something like that:

```jsonc
{
  "id": 1,
  "name": "home",
  "title": "Homepage",
  "created": 1494796565,
  "modified": 1494796588,
  "url": "/",
  "httpUrl": "https://my-website.dev/",
  "template": {
    "id": 1,
    "name": "home",
    "label": "Home-Template"
  },
  "children": [
    [
      "text" => "This is the getAjax()-output of or TestChild component"
    ]
  ]
}
```

You see, that you can collect the outputs of all sub-components in an array. Another approach could be to merge the data, to get  output that is not nested. You are free to do anything you want.

As you might have noticed, I also took the chance to demonstrate a little helper function. The function `getAjaxOf()` is included in every TwackComponent and delivers simple output from some very common Processwire classes. It can transform objects of ProcessWire's `Page`, `PageArray`, `Template`, `PageImage`, `PageFile` and `PageFiles` to arrays with the basic data that they contain.

<a name="configuration"></a>

## Configuration

![twack-configuration](https://raw.githubusercontent.com/Sebiworld/Twack/master/documentation/media/twack-configuration.png)

Twack allows you to change the **location** of the directories, where it looks for component- and service-classes. Change it to any location you like - they don't have to stay in your site/templates/ folder, if you want to have them somewhere else.

In the input "**TwackControl**", you can give an alternative TwackControl-class, which handles the way Twack looks for components. I don't think, that it is necessary in most cases. But if you need to manipulate it, you should take a look at the core [TwackControl-class](https://github.com/Sebiworld/Twack/blob/master/TwackControl.class.php). Create your own class which extends the core-class, and paste the location to the module's configuration.

Additionally, it is possible to give Twack the path to a **manifest.json**-file, which holds the real locations for your asset-files. I normally use frontend-workflows, that generate optimized and minified asset-files for me. Typically I let the workflow generate distribution-files, that have a unique hash in their filenames, what prevents the browser to load outdated files. But I surely don't want to paste these hashed-filenames in my code every time I do a rebuild. Twack can use the path to my [manifest.json](https://github.com/Sebiworld/musical-fabrik.de/blob/master/site/templates/assets/manifest.json)-file, that is generated at every asset-rebuild. Look at my this [webpack.json](https://github.com/Sebiworld/musical-fabrik.de/blob/master/webpack.config.js), that I does exactly this. To include my `main.js`file from `site/templates/assets/js/main-8f56bd9a.min.js`, I can use the following line in the view:

```php
<script src="<?= wire('config')->urls->templates; ?>assets/js/<?= Twack::getManifestFilename('main.js'); ?>"></script>
```

<a name="versioning"></a>

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/Sebiworld/Twack/tags).

<a name="license"></a>

## License

This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE.md](LICENSE.md) file for details.

<a name="changelog"></a>

## Changelog

### Changes in 2.1.3 (2020-06-14)

* Made Twack available as API-variable like `wire('twack')` . (Thanks to @BernhardBaumrock)