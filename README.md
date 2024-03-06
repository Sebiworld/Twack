# Twack
**Reusable components for your [ProcessWire](https://processwire.com)-templates!**

Welcome to Twack! Twack helps you build well-structured and maintainable ProcessWire-projects. Inspired by [angular](https://angular.io).

[![Current Version](https://img.shields.io/github/v/tag/Sebiworld/Twack?label=Current%20Version)](https://img.shields.io/github/v/tag/Sebiworld/Twack?label=Current%20Version) [![Current Version](https://img.shields.io/github/issues-closed-raw/Sebiworld/Twack?color=%2356d364)](https://img.shields.io/github/issues-closed-raw/Sebiworld/Twack?color=%2356d364) [![Current Version](https://img.shields.io/github/issues-raw/Sebiworld/Twack)](https://img.shields.io/github/issues-raw/Sebiworld/Twack)

<a href="https://www.buymeacoffee.com/Sebi.dev" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/default-orange.png" alt="Buy Me A Coffee" height="41" width="174"></a>

|||
| ------------------: | ------------------------------------------------------------ |
| ProcessWire-Module: | [https://modules.processwire.com/modules/twack/](https://modules.processwire.com/modules/twack/) |
|      Support-Forum: | [https://processwire.com/talk/topic/23549-twack/](https://processwire.com/talk/topic/23549-twack/) |
|         Repository: | [https://github.com/Sebiworld/Twack](https://github.com/Sebiworld/Twack) |
| Wiki: | [https://github.com/Sebiworld/Twack/wiki/](https://github.com/Sebiworld/Twack/wiki/) |
|||

<a name="features"></a>

## Features

* Create  **components** ! Twack components are designed for reusability and encapsulating a set of features for easy maintainability. They can handle hierarchical or recursive use (child components).
* **Based on ProcessWire's core functionality and principles**
* **HTML- and Ajax-Views** - specify json output for ajax-requests
* Define **services**, that handle global data. Twack manages the shared service-classes (->singleton) for you and lets you access the data from everywhere you need it.
* **Not exclusive** - use Twack components to extend existing templates. You don't have to rewrite everything!

<a name="table-of-contents"></a>
## Table of Contents

* [1: Home](https://github.com/Sebiworld/Twack/wiki)
    * [1.1: Features](https://github.com/Sebiworld/Twack/wiki#features)
    * [1.2: Installation](https://github.com/Sebiworld/Twack/wiki#installation)
    * [1.3: Quickstart: Creating a component](https://github.com/Sebiworld/Twack/wiki#quickstart)
    * [1.4: Changelog](https://github.com/Sebiworld/Twack/wiki#changelog)
    * [1.5: Versioning](https://github.com/Sebiworld/Twack/wiki#versioning)
    * [1.6: License](https://github.com/Sebiworld/Twack/wiki#license)
* [2: Naming conventions & component variants](https://github.com/Sebiworld/Twack/wiki/2:-Naming-conventions-&-component-variants)
* [3: Component Parameters](https://github.com/Sebiworld/Twack/wiki/3:-Component-Parameters)
    * [3.1: directory](https://github.com/Sebiworld/Twack/wiki/3:-Component-Parameters#component-parameters-directory)
    * [3.2: page](https://github.com/Sebiworld/Twack/wiki/3:-Component-Parameters#component-parameters-page)
    * [3.3: parameters](https://github.com/Sebiworld/Twack/wiki/3:-Component-Parameters#component-parameters-parameters)
    * [3.4: viewname](https://github.com/Sebiworld/Twack/wiki/3:-Component-Parameters#component-parameters-viewname)
* [4: Asset handling](https://github.com/Sebiworld/Twack/wiki/4:-Asset-handling)
* [5: Services](https://github.com/Sebiworld/Twack/wiki/5:-Services)
* [6: Named components](https://github.com/Sebiworld/Twack/wiki/6:-Named-components)
* [7: Global components](https://github.com/Sebiworld/Twack/wiki/7:-Global-components)
* [8: Ajax-Output](https://github.com/Sebiworld/Twack/wiki/8:-Ajax-Output)
* [9: Configuration](https://github.com/Sebiworld/Twack/wiki/9:-Configuration)

<a name="installation"></a>

## Installation

Twack can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

The prerequisites are **PHP>=5.5.0** and a **ProcessWire version >=3.0.0**. However, this is also checked during the installation of the module. No further dependencies.

<a name="quickstart"></a>

## Quickstart: Creating a component

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

<a name="changelog"></a>

## Changelog

### Changes in 2.3.1(2024-03-04)

- Use AppApi getAjaxOf() if available
- Improved Fallback getAjaxOf()

### Changes in 2.3.0(2024-03-03)

- Added componentLists to TwackComponent

### Changes in 2.2.6(2022-08-28)

- Bugfix catch error if manifest path does not exist

### Changes in 2.2.5(2022-06-01)

- Api: Throw 404 if page not found

### Changes in 2.2.4 (2022-04-29)

- Api: Added support for Multi-Language URLS

### Changes in 2.2.3 (2022-03-08)

* getAjaxOf: Use AppApi-function if installed

### Changes in 2.2.2 (2022-03-06)

* Api: Improve optional `lang` param, add many langugage codes that can be used as a shortcut

### Changes in 2.2.1 (2021-10-31)

* Api: Add optional `lang` param to select a language in multilang environments

### Changes in 2.2.0 (2021-10-28)

* JSON-API: Use AppApi (if installed) to add a 'tpage' endpoint. See more at [https://github.com/Sebiworld/Twack/wiki/8:-Ajax-Output](https://github.com/Sebiworld/Twack/wiki/8:-Ajax-Output)

### Changes in 2.1.4 (2021-01-04)

* Improved documentation
  * Moved most contents of README to Wiki
  * added page-links

### Changes in 2.1.3 (2020-06-14)

* Made Twack available as API-variable like `wire('twack')` . (Thanks to @BernhardBaumrock)

<a name="versioning"></a>

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/Sebiworld/Twack/tags).

<a name="license"></a>

## License

This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE.md](LICENSE.md) file for details.

***

[**:arrow_right: Continue with 2: Naming conventions & component variants**](https://github.com/Sebiworld/Twack/wiki/2:-Naming-conventions-&-component-variants)