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
<body <?php body_class( 'bg-dark text-light' ); ?>>
<header class="site-header">
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<div class="container-fluid">
			<a class="navbar-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php
			// Primary menu visible on large screens; mobile uses the fixed bottom nav.
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'navbar-nav me-auto mb-2 mb-lg-0 d-none d-lg-flex',
						'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
					)
				);
			} else {
				echo '<ul class="navbar-nav me-auto mb-2 mb-lg-0 d-none d-lg-flex"><li class="nav-item"><a class="nav-link" href="' . esc_url( home_url( '/' ) ) . '">Home</a></li></ul>';
			}
			?>

				<div class="d-flex align-items-center ms-auto">
					<?php
					if ( is_user_logged_in() ) :
						$user = wp_get_current_user();
						?>
						<div class="dropdown">
							<a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#" id="cpProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" role="button">
								<span class="me-2"><?php echo esc_html( $user->display_name ); ?></span>
							</a>
							<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="cpProfileDropdown">
								<li><a class="dropdown-item" href="<?php echo esc_url( get_permalink( get_page_by_path( 'my-picks' ) ) ); ?>">My Picks</a></li>
								<li><a class="dropdown-item" href="<?php echo esc_url( get_permalink( get_page_by_path( 'make-picks' ) ) ); ?>">Make Picks</a></li>
								<li><a class="dropdown-item" href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>">Log out</a></li>
							</ul>
						</div>
					<?php else : ?>
						<a class="btn btn-outline-primary" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</nav>
</header>

<!-- Bottom nav for mobile: show only on small screens -->
<?php if ( has_nav_menu( 'primary' ) ) : ?>
	<nav class="cp-bottom-navbar d-lg-none">
		<div class="container-fluid">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'cp-bottom-nav nav justify-content-around',
					'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				)
			);
			?>
		</div>
	</nav>
<?php endif; ?>
