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
  - ``'driver'`` => The class name of the desired DB driver. (Required if
    ``$use_db = true``)
  - ``'database'`` => The database location (depending on the driver)
  - ``'username'`` => Username
  - ``'password'`` => Password
  - ``'host'`` => Host
 
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

Views ane Rendering
===================

TO BE WRITTEN.
