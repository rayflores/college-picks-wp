<?php
/**
 * Archive for games
 */
get_header();
?>
<main>
	<div class="main-container">
	<h1>Games Archive</h1>
	<?php
	if ( have_posts() ) :
		echo '<ul class="cp-game-list">';
		while ( have_posts() ) :
			the_post();
			$home         = get_post_meta( get_the_ID(), 'home_team', true );
			$away         = get_post_meta( get_the_ID(), 'away_team', true );
			$kick         = get_post_meta( get_the_ID(), 'kickoff_time', true );
			$kick_display = $kick ? cp_format_kickoff( $kick ) : '';
			echo '<li><a href="' . esc_url( get_permalink() ) . '">' . esc_html( $away ) . ' @ ' . esc_html( $home ) . '</a>' . ( $kick_display ? ' <span class="cp-kick">(' . esc_html( $kick_display ) . ')</span>' : '' ) . '</li>';
		endwhile;
		echo '</ul>';
		the_posts_pagination();
	else :
		echo '<p>No games found.</p>';
	endif;
	?>
	</div>
</main>
<?php
get_footer();
