<h1><?= $header{ 'title' } ?></h1>
<h2><?= $header{ 'subhead' } ?></h2>
<p><?= $header{ 'body' } ?></p>

<ul>
<? foreach ( $lines as $l ) : ?>
	<li><?= $l{ 'data' } ?></li>
<? endforeach ?>
</ul>

<p><?= $header{ 'footer' } ?></p>
