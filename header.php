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
	<div class="wrap header-inner" style="display:flex;align-items:center;justify-content:space-between;">
		<div class="site-branding" style="display:flex;align-items:center;gap:16px;">
			<h1 class="site-title" style="margin:0;font-size:1.25rem;"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a></h1>
			<nav class="site-nav" role="navigation">
				<?php
				if ( has_nav_menu( 'primary' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'cp-primary-menu',
						)
					);
				} else {
					// fallback: show pages
					wp_page_menu( array( 'menu_class' => 'cp-primary-menu' ) );
				}
				?>
			</nav>
		</div>
		<div class="site-profile" style="margin-left:auto;">
			<?php
			if ( is_user_logged_in() ) :
				$user = wp_get_current_user();
				?>
				<div class="cp-profile" style="display:flex;align-items:center;gap:8px;">
					<span class="cp-profile-name"><?php echo esc_html( $user->display_name ); ?></span>
					<a class="button" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Log out</a>
				</div>
			<?php else : ?>
				<a class="button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in</a>
			<?php endif; ?>
		</div>
	</div>
</header>
