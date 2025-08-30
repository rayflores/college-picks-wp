<?php
/**
 * Theme header
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
	body { font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; margin:20px; }
	.cp-game-list { list-style:none; padding:0; }
	.cp-game-item { margin:12px 0; padding:10px; border:1px solid #eee; }
	.cp-pick-form { margin-top:8px; }
	</style>
</head>
<body <?php body_class(); ?>>
<header class="site-header">
	<div class="wrap">
		<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
	</div>
</header>
