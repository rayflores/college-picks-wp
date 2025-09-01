<?php
/**
 * Template Name: My Picks
 *
 * Shows the current user's picks with game details and result status.
 *
 * @package college-picks
 */

get_header();

if ( ! is_user_logged_in() ) {
	echo '<div class="wrap"><p>You must <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to view your picks.</p></div>';
	get_footer();
	return;
}

$current_user_id = get_current_user_id();

// Fetch picks for current user.
$picks = get_posts(
	array(
		'post_type'      => 'pick',
		'author'         => $current_user_id,
		'posts_per_page' => -1,
		'orderby'        => 'post_date',
		'order'          => 'DESC',
	)
);
?>
<div class="wrap">
	<h1>My Picks</h1>

	<?php if ( empty( $picks ) ) : ?>
		<p>You haven't made any picks yet. Visit the <a href="<?php echo esc_url( get_permalink( get_page_by_path( 'make-picks' ) ) ); ?>">Make Picks</a> page to add picks.</p>
	<?php else : ?>
		<ul class="cp-my-picks">
			<?php foreach ( $picks as $p ) : ?>
				<?php
				$game_id = get_post_meta( $p->ID, 'game_id', true );
				$choice  = get_post_meta( $p->ID, 'pick_choice', true );
				$game    = $game_id ? get_post( intval( $game_id ) ) : null;

				// Default values.
				$home   = '';
				$away   = '';
				$kick   = '';
				$result = '';
				if ( $game ) {
					$home   = get_post_meta( $game->ID, 'home_team', true );
					$away   = get_post_meta( $game->ID, 'away_team', true );
					$kick   = get_post_meta( $game->ID, 'kickoff_time', true );
					$result = get_post_meta( $game->ID, 'result', true );
				}

				// Determine pick status.
				if ( empty( $result ) ) {
					$pick_status = 'Pending';
				} elseif ( 'tie' === $result ) {
					$pick_status = 'Push / Tie';
				} elseif ( $choice === $result ) {
					$pick_status = 'Correct';
				} else {
					$pick_status = 'Incorrect';
				}
				?>
				<li class="cp-pick-item">
					<div class="cp-pick-game">
						<?php if ( $game ) : ?>
							<a href="<?php echo esc_url( get_permalink( $game ) ); ?>"><?php echo esc_html( $away ); ?> @ <?php echo esc_html( $home ); ?></a>
						<?php else : ?>
							<em>Game removed (#<?php echo intval( $game_id ); ?>)</em>
						<?php endif; ?>
					</div>
					<div class="cp-pick-meta">
						<span class="cp-pick-choice">Your pick: <strong><?php echo esc_html( ucfirst( $choice ) ); ?></strong></span>
						<?php if ( $kick ) : ?>
							<span class="cp-pick-kick">Kickoff: <?php echo esc_html( cp_format_kickoff( $kick ) ); ?></span>
						<?php endif; ?>
						<span class="cp-pick-status">Status: <strong><?php echo esc_html( $pick_status ); ?></strong></span>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>

<?php
get_footer();
