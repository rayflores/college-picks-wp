<?php
/**
 * Template Name: Make Picks
 */
get_header();
if ( ! is_user_logged_in() ) {
	echo '<div class="wrap"><p>You must <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to make picks.</p></div>';
	get_footer();
	return;
}

$current_user_id = get_current_user_id();
$week            = ''; // optional: could read from query or settings
// pull games for this week (unexpired)
$games = get_posts(
	array(
		'post_type'      => 'game',
		'posts_per_page' => -1,
		'meta_key'       => 'kickoff_time',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	)
);
?>
<div class="wrap">
	<h1>Make Your Picks</h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cp_submit_picks', 'cp_submit_picks_nonce' ); ?>
		<input type="hidden" name="action" value="submit_picks_bulk">
		<div class="cp-make-picks">
			<?php if ( $games ) : ?>
				<ul class="cp-game-list">
				<?php
				foreach ( $games as $g ) :
					$home        = get_post_meta( $g->ID, 'home_team', true );
					$away        = get_post_meta( $g->ID, 'away_team', true );
					$kick        = get_post_meta( $g->ID, 'kickoff_time', true );
					$existing    = get_posts(
						array(
							'post_type'      => 'pick',
							'author'         => $current_user_id,
							'meta_key'       => 'game_id',
							'meta_value'     => $g->ID,
							'posts_per_page' => 1,
						)
					);
					$user_choice = '';
					if ( ! empty( $existing ) ) {
						$user_choice = get_post_meta( $existing[0]->ID, 'pick_choice', true );
					}
					?>
					<li class="cp-game-item">
						<div class="cp-game-title"><?php echo esc_html( $away ); ?> @ <?php echo esc_html( $home ); ?></div>
						<?php
						if ( $kick ) :
							?>
							<div class="cp-game-kick"><?php echo esc_html( cp_format_kickoff( $kick ) ); ?></div><?php endif; ?>
						<label><input type="radio" name="cp_picks[<?php echo intval( $g->ID ); ?>]" value="home" <?php checked( $user_choice, 'home' ); ?>> <?php echo esc_html( $home ); ?></label>
						<label><input type="radio" name="cp_picks[<?php echo intval( $g->ID ); ?>]" value="away" <?php checked( $user_choice, 'away' ); ?>> <?php echo esc_html( $away ); ?></label>
					</li>
				<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p>No games available.</p>
			<?php endif; ?>
		</div>
		<p><button class="button button-primary" type="submit">Save all picks</button></p>
	</form>
</div>

<?php
get_footer();
