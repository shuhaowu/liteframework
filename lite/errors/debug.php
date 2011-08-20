<?php if ($page->DEBUG): ?>
<p>Page Name: <?php echo $page->getName(); ?></p>
<p>Other errors: </p>
<pre>
<?php print_r($page->error[2]); ?>
</pre>
<?php endif;?>