<?php ?>

<?= $urlbase = 'http' . ($_SERVER['SERVER_PORT'] == 443 ? 's' : '') . '://' . $_SERVER['SERVER_NAME'] . '/'; ?><br>

<pre>
<?php print_r( $_SERVER, false); ?>
</pre>
