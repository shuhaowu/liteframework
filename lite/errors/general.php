<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Unknown Error: <?php echo $error[0]; ?></title>
</head>

<body>
<h1>An Unknown Error has Occurred: <?php echo $error[0]; ?></h1>
<p>An unknown error has occured when executing the page, <strong><?php echo $page->NAME; ?></strong>. Or this may be caused by an undefined error page.</p>
<p>Error code: <?php echo $error[0]; ?></p>
<p>Error Message: <?php echo $error[1]; ?></p>
<p><a href="javascript:history.go(-1)">Go back</a></p>
<?php $page->render("debug", FRAMEWORK_DIR . "/errors/debug.php"); ?>
<p>Powered by <a href="https://github.com/ultimatebuster/liteframework">liteFramework</a></p>
</body>
</html>
