<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>404 Not Found</title>
</head>

<body>
<h1>404 Error: <?php echo $error[1] ?></h1>
<p>The url, <a href="<?php echo $page->currentURL();?>"><?php echo $page->currentURL(); ?></a>, cannot be found on our server. It might be deleted from our server or you've clicked on a bad link.</p>
<p><a href="javascript:history.go(-1)">Go back</a></p>
<?php $page->render("debug", \lite\FRAMEWORK_DIR . "/errors/debug.php"); ?>
<p>Powered by <a href="https://github.com/ultimatebuster/liteframework">liteFramework</a></p>
</body>
</html>
