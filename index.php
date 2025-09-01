<?php get_header(); ?>
<main id="site-content" role="main">
	<div class="main-container">
	<section class="cp-leaderboard-top">
		<?php echo do_shortcode( '[college_picks_leaderboard]' ); ?>
		<?php if ( is_user_logged_in() ) : ?>
			<p><a class="button button-primary" href="<?php echo esc_url( home_url( '/make-picks/' ) ); ?>">Make Picks for this Week</a></p>
		<?php else : ?>
			<p><a class="button" href="<?php echo esc_url( wp_login_url( home_url( '/make-picks/' ) ) ); ?>">Log in to make picks</a></p>
		<?php endif; ?>
	</section>
	<section class="cp-games">
	<h1>Upcoming Games</h1>
	<?php
	$now   = current_time( 'Y-m-d H:i' );
	$games = new WP_Query(
		array(
			'post_type'      => 'game',
			'posts_per_page' => 20,
			'orderby'        => 'meta_value',
			'meta_key'       => 'kickoff_time',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => 'kickoff_time',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'CHAR',
				),
			),
		)
	);
	if ( $games->have_posts() ) :
		$last_date = '';
		$opened_ul = false;
		while ( $games->have_posts() ) :
			$games->the_post();
			$home = get_post_meta( get_the_ID(), 'home_team', true );
			$away = get_post_meta( get_the_ID(), 'away_team', true );
			$kick = get_post_meta( get_the_ID(), 'kickoff_time', true );
			$week = get_post_meta( get_the_ID(), 'week', true );

			// Determine date grouping key and human label
			$date_key   = 'tbd';
			$date_label = 'TBD';
			if ( ! empty( $kick ) ) {
				$dt = DateTime::createFromFormat( 'Y-m-d H:i', $kick );
				if ( false === $dt ) {
					try {
						$dt = new DateTime( $kick );
					} catch ( Exception $e ) {
						$dt = false;
					}
				}
				if ( $dt ) {
					$date_key   = $dt->format( 'Y-m-d' );
					$date_label = $dt->format( 'l, M j' );
				}
			}

			if ( $date_key !== $last_date ) {
				if ( $opened_ul ) {
					echo '</ul>';
				}
				echo '<h2 class="cp-game-date">' . esc_html( $date_label ) . '</h2>';
				echo '<ul class="cp-game-list">';
				$opened_ul = true;
				$last_date = $date_key;
			}

			echo '<li class="cp-game-item">';
			echo '<a href="' . esc_url( get_permalink() ) . '"><strong>' . esc_html( $away ) . ' @ ' . esc_html( $home ) . '</strong></a>';
			if ( $kick ) {
				$readable = function_exists( 'cp_format_kickoff' ) ? cp_format_kickoff( $kick ) : $kick;
				echo '<div class="cp-kick">Kickoff: ' . esc_html( $readable ) . '</div>';
			}
			if ( $week ) {
				echo '<div class="cp-week">Week: ' . esc_html( $week ) . '</div>';
			}
			echo '</li>';
		endwhile;
		if ( $opened_ul ) {
			echo '</ul>';
		}
		wp_reset_postdata();
	else :
		echo '<p>No games found.</p>';
	endif;
	?>
	</section>
	</div>
</main>
<?php get_footer(); ?>
