<?php
/**
 * Single game view with pick form
 */
get_header();
?>
<div class="main-container">
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$home = get_post_meta( get_the_ID(), 'home_team', true );
		$away = get_post_meta( get_the_ID(), 'away_team', true );
		$kick = get_post_meta( get_the_ID(), 'kickoff_time', true );
		?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<h2><?php echo esc_html( $away ); ?> @ <?php echo esc_html( $home ); ?></h2>
		<?php if ( $kick ) : ?>
			<div>Kickoff: <?php echo esc_html( cp_format_kickoff( $kick ) ); ?></div>
		<?php endif; ?>

		<?php
		$game_result = get_post_meta( get_the_ID(), 'result', true );
		if ( ! empty( $game_result ) ) :
			// Show result and skip pick form
			if ( 'home' === $game_result ) {
				$winner = esc_html( $home );
			} elseif ( 'away' === $game_result ) {
				$winner = esc_html( $away );
			} else {
				$winner = 'Tie/Push';
			}
			echo '<p><strong>Result:</strong> ' . $winner . '</p>';
		elseif ( is_user_logged_in() ) :
			$current_user_id = get_current_user_id();
			$existing        = get_posts(
				array(
					'post_type'      => 'pick',
					'author'         => $current_user_id,
					'meta_key'       => 'game_id',
					'meta_value'     => get_the_ID(),
					'posts_per_page' => 1,
				)
			);
			$user_choice     = '';
			if ( ! empty( $existing ) ) {
				$user_choice = get_post_meta( $existing[0]->ID, 'pick_choice', true );
			}
			?>
			<form class="cp-pick-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cp_submit_pick', 'cp_pick_nonce' ); ?>
				<input type="hidden" name="action" value="submit_pick">
				<input type="hidden" name="game_id" value="<?php echo esc_attr( get_the_ID() ); ?>">
				<label>
					<input type="radio" name="pick_choice" value="home" <?php checked( $user_choice, 'home' ); ?>> <?php echo esc_html( $home ); ?>
				</label><br>
				<label>
					<input type="radio" name="pick_choice" value="away" <?php checked( $user_choice, 'away' ); ?>> <?php echo esc_html( $away ); ?>
				</label><br>
				<button type="submit">Save Pick</button>
			</form>
		<?php else : ?>
			<p><a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">Log in to make a pick</a></p>
		<?php endif; ?>

		<h3>All Picks</h3>
			<?php
			$picks = get_posts(
				array(
					'post_type'      => 'pick',
					'meta_key'       => 'game_id',
					'meta_value'     => get_the_ID(),
					'posts_per_page' => -1,
				)
			);
			if ( $picks ) {
				echo '<ul>';
				foreach ( $picks as $p ) {
					$user   = get_userdata( $p->post_author );
					$choice = get_post_meta( $p->ID, 'pick_choice', true );
					echo '<li>' . esc_html( $user ? $user->display_name : 'User' ) . ': ' . esc_html( $choice ) . '</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>No picks yet.</p>';
			}
			?>
	</article>
			<?php
endwhile;
endif;
?>
</div>
<?php
get_footer();
