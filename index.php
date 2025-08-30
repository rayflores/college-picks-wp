<?php get_header(); ?>
<main id="site-content" role="main">
	<section class="cp-leaderboard-top">
		<?php echo do_shortcode( '[college_picks_leaderboard]' ); ?>
	</section>
	<section class="cp-games">
	<h1>Upcoming Games</h1>
	<?php
	$games = new WP_Query(
		array(
			'post_type'      => 'game',
			'posts_per_page' => 20,
			'orderby'        => 'meta_value',
			'meta_key'       => 'kickoff_time',
			'order'          => 'ASC',
		)
	);
	if ( $games->have_posts() ) :
		echo '<ul class="cp-game-list">';
		while ( $games->have_posts() ) :
			$games->the_post();
			$home = get_post_meta( get_the_ID(), 'home_team', true );
			$away = get_post_meta( get_the_ID(), 'away_team', true );
			$kick = get_post_meta( get_the_ID(), 'kickoff_time', true );
			$week = get_post_meta( get_the_ID(), 'week', true );
			echo '<li class="cp-game-item">';
			echo '<a href="' . esc_url( get_permalink() ) . '"><strong>' . esc_html( $away ) . ' @ ' . esc_html( $home ) . '</strong></a>';
			if ( $kick ) {
				echo '<div class="cp-kick">Kickoff: ' . esc_html( $kick ) . '</div>';
			}
			if ( $week ) {
				echo '<div class="cp-week">Week: ' . esc_html( $week ) . '</div>';
			}
			echo '</li>';
		endwhile;
		echo '</ul>';
		wp_reset_postdata();
	else :
		echo '<p>No games found.</p>';
	endif;
	?>
	</section>
</main>
<?php get_footer(); ?>
