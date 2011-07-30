======================
 liteFramework Design
======================

.. |date| date::
.. |time| date:: %H:%M

:Author: Shuhao Wu
:Version: 1.0
:Contact: shuhao@shuhaowu.com
:Last Modified: |date| |time|

This document describes the design of liteFramework.

Introduction
============

liteFramework is a PHP web framework designed to be easy to use. We all know the
term MVC, which is exactly what liteFramework tries to accomplish. However, I
personally think that all other major PHP frameworks (not to say they are not
nice) are either too hard to learn and/or missing the M from the MVC. Afterall,
MVC does stand for Magic, Views, Controllers.

A brief history on liteFramework: It started as a CMS project. I thought it
would be nice to write a CMS that's easy for businesses to use (which is what I
do for work at the time). This was before I know what MVC was all about (a.k.a.
me writing spaghetti code in PHP with ``PHPCODE ... HTML ... PHPCODE``). I
designed it to break the CMS into "database interactions" (Model/Magic),
"template rendering" (Views), "application logic" (Controllers). I even drew
diagrams and all. However, the project went dead after a while, when I got
sidetracked by python + web.

After, I discovered MVC and tried to learn a couple of frameworks. I learned
Django and GAE's webapp framework. Those are automagical (especially GAE), where
I could write an app in hours (Wrote a basic blog/journal in GAE's webapp
framework in 45 minutes, during lunch at school). Then, I had to use PHP for a
project. I was frustrated with all the major frameworks such as cakePHP,
codeIgnitor, Zend. Not to say they're not great if you're familiar with them,
they make n00bs work hard to get familiar with them.

So, I designed my own. The VC part is mostly my own design (I wrote the
initial framework with VC, fully tested in 3 hours). It was initially only ~120
lines of code, easy to use. Soon, I realized I needed more features, started
documenting the framework and extended the VC part. This was done in PHP 5.2.

It wasn't long before I realized I need to write the model component. After
trying to integrate Doctrine into the original framework, I thought that I would
just write my own, based of a combination of Django and GAE-Python. That's why I
switched to PHP 5.3, as the lack of namespace and late static binding was too
much to deal with in PHP 5.2.

Application Flow
================
The application flow is very simple. The .htaccess file uses mod_rewrite to
redirect all traffic other than to directories and files (some directories and
files are restricted also with .htaccess, such as the private dir) to index.php.
It passes the path of the request to a GET variable named page
(index.php?page=what/ever/your/path/is).

The ``index.php`` file defines a few variables and constants that will be used
by the framework (settings file like Django), such as where the templates,
controllers are located and such.

It then loads the file ``lite/dispatcher.php``. The list of variables/constants
is as follows:

- ``DEBUG``: Constant. boolean. If set to true, default error pages will
  show some more info (as of the moment).
- ``BASE_DIR``: Constant. string. This should always set to
  ``dirname(__FILE__)``.
- ``$views_location``: The path to the folder of the views php files.
- ``$controller_file``: Controller File location containing the class
  ``Controllers``
- ``$template_location``: The path to the central template php file. False to
  disable.
- ``$errors_location``: The path to the folder of the custom errors pages for
  different HTTP code. False to disable and use the default.
- ``$lib_location``: The path to the folder of the custom library.
- ``$url_map``: An url map to functions under the ``Controllers`` class.
  Modeled after Django with regex. Eventually it will get support for passing
  of arguments. (Incomplete/experimental)
- ``$use_db``: Use a database or not (Boolean). Optional.
- ``$dbinfo``: An associative array containing the info on the database.
 * ``'driver'`` => The class name of the desired DB driver. (Required if
   ``$use_db = true``)
 * ``'database'`` => The database location (depending on the driver)
 * ``'username'`` => Username
 * ``'password'`` => Password
 * ``'host'`` => Host

``lite/dispatcher.php`` is pretty much the entire application. When this script
ends, the page is rendered and the connection is closed. The first thing the
dispatcher does is process the url. It strips the slashes from the beginning and
the end of the URL. It breaks it down into 3 different (global) variables,
``$requestURL``, ``$name``, and ``$args``.

- ``$requestURL`` is a string containing the full path after your domain name.
  It is the URL requested without the trailing slash. However, it will always
  have a slash in the beginning.
- ``$name`` is the first part of the url. The URL is ``explode``d by ``/`` and
  the first element of the resulting array is the ``$name`` variable. It is
  also a string.
- ``$args`` is the rest of the array ``explode``d.

Example: http://yoursite.com/somepage/arg1/arg2/ will have the following result:

- ``$requestURL = '/somepage/arg1/arg2'``
- ``$name = 'somepage'``
- ``$args = array('arg1', 'arg2')``

Then the dispatcher should load up all the libraries, specified in the
``$lib_location`` as well as the default ones (which includes the Model).

Dispatcher then proceeds to initialize the database driver (however, it doesn't
connect until the first time the model calls `->put()`) if one is demanded.

Controllers gets initialized next. The URL map will get parsed, the associated
functions will be called. If no match is found, the function with the name of
the value for ``$name`` will be fired. In both cases, the function will receive
``$args`` as its arguments.

If ``$name`` is not found, a 404 error will be issued, where custom/default
error pages will be rendered with a 404 HTTP status.

Framework terminates here.

Controllers, Views, ane Rendering
=================================

Controllers
-----------

Controllers in the liteFramework are functions under the class ``Controllers``.
This class is found in the file specified in ``$controller_file``. Under this
file, you should have at least a class named ``Controllers`` which
``extends BaseControllers``. BaseControllers provides some shortcut functions,
such as rendering and initializing.

URLs are mapped in such ways that the ``$name``, or the first segment before a /
symbol. For example, http://yoursite.com/controller/args will fire
``$controllers->controller(array('args'))``, given that ``$controllers`` is an
instance of the class ``Controllers``.

If there's nothing after the base URL (http:/yoursite.com/), $name will be
automatically converted to ``index``. Hence, you must have an ``index`` function
under the class ``Controllers``. http://yoursite.com/index will also fire the
same function, provided that the url map doesn't override both of this.

Controllers are technically allowed to do anything. You're allowed to not render
anything, or even send any data back. You can use another rendering framework if
you want. However, liteFramework does provide you with templating and the
passing of variables (and even functions! via this thing called Helpers) to the
view file.

Views
-----

Views in the liteFramework is a the same as a generic php file. You're allowed
to do anything PHP allows you to do. However, it's recommended that you have as
little application logic in the view as possible. It's recommended that you have
mostly just HTML with some embedded PHP to display variables or perform a loop.

Views are .php files placed under the ``$views_location`` directory. Views are
identified (their name) by the path of the .php file without the
``$views_location`` part and without the .php extension and any slashes at the
beginning or the end. For example, the view file placed at
/www/your/views/directory/about/view.php will have the name of ``about/view``.

Controllers can pass variables via the render function (through an associative
array) to the view. The view can access those variables through the ``$page``
variable.

Rendering
---------

Rendering in the liteFramework is done via the renderer. What the renderer
actually does is taking the php file, setup the variables correctly, and
``require`` the template file. It's very primitive but it does the job right.
Rendering could also happen within the view php file (i.e. templates).

The ``Renderer`` class also provides a few shortcuts that allows error
renderings and more.


``$page`` variable
==================

The ``$page`` variable is an important concept in the liteFramework renderer.
Essentially, in the ``$page`` variable, you can access the variables passed to
the renderer in the associative array. (similar to Django)

For example, you could pass the array
``array('mewvalue' => 24, 'moovalue' => 3.14)`` to the renderer, it takes that
array, then creates an ``PageHelper`` object (a.k.a. ``$page`` variable). It
then assigns the associative array to become the attributes of that object.
Hence ``$page->mewvalue == 24`` and ``$page->moovalue == 3.14``.

The ``$page`` variable also provides a couple of functions, as well as access
to an (the) instance of the ``Helper`` class, where you can define more
variables and even functions.

To summarize, the ``$page`` variable is how you access the variables passed to
the view by the controller.


Libraries and Helpers
=====================

Libraries
---------

Libraries is an important concept for the liteFramework as everything, except
the renderer, the dispatcher (which really is just a linear php script), and the
PageHelper (``$page`` variable), is a library. They are dynamically loaded
during runtime and they can be deactivated.

liteFramework will include a couple of "standard libraries" such as the
Magic Models (ORM) and the Navbar constructor.

Libraries are found in folders, where the main file to be ``require``d has the
prefix of ``lib_`` in its filename. For example, ``lib_orm.php`` under the
folder of ``/lite/libraries/`` will be automatically loaded by the dispatcher.
Any code inside the php file will be executed as it's ``require``d.

Default libraries included by the framework itself is found under the
``/lite/libraries`` directory. However, developers should not place their own
libraries here, rather in the directory specified in the index.php file under
the variable ``$lib_location``.

Helpers
-------

TO BE WRITTEN.

Magic Models
============

TO BE WRITTEN.

Other Standard Libraries
========================

TO BE WRITTEN.

Design Patterns
===============

TO BE WRITTEN.
