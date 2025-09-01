<?php
/**
 * Template Name: My Picks
 *
 * Shows the current user's picks with game details and result status.
 *
 * @package college-picks
 */

get_header();
?>
<div class="main-container">
<?php

if ( ! is_user_logged_in() ) {
	echo '<div class="wrap"><p>You must <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to view your picks.</p></div>';
	get_footer();
	return;
}

$current_user_id = get_current_user_id();

// Determine active week for progress calculation: first future kickoff or last week.
$all_games   = get_posts(
	array(
		'post_type'      => 'game',
		'posts_per_page' => -1,
		'meta_key'       => 'kickoff_time',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'fields'         => 'ids',
	)
);
$active_week = null;
$now         = current_time( 'timestamp' );
if ( ! empty( $all_games ) ) {
	foreach ( $all_games as $g_id ) {
		$kick = get_post_meta( $g_id, 'kickoff_time', true );
		if ( empty( $kick ) ) {
			continue;
		}
		$dt = DateTime::createFromFormat( 'Y-m-d H:i', $kick );
		if ( false === $dt ) {
			try {
				$dt = new DateTime( $kick );
			} catch ( Exception $e ) {
				$dt = null;
			}
		}
		if ( $dt && $dt->getTimestamp() >= $now ) {
			$active_week = get_post_meta( $g_id, 'week', true );
			break;
		}
	}
}
if ( null === $active_week ) {
	// fallback: use the week of the last game
	if ( ! empty( $all_games ) ) {
		$last        = end( $all_games );
		$active_week = get_post_meta( $last, 'week', true );
	}
}

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

	<?php
	// Include the week archive UI partial (dark matchup cards + tabs)
	require locate_template( 'templates/cp-week-archive.php' );
	?>

	
<!-- Submit bar that rests above the bottom nav on mobile -->
<?php
// Compute progress for the active week: how many picks user made / total games in week
$progress_made  = 0;
$progress_total = 0;
if ( ! empty( $active_week ) ) {
	$games_in_week  = get_posts(
		array(
			'post_type'      => 'game',
			'posts_per_page' => -1,
			'meta_key'       => 'week',
			'meta_value'     => $active_week,
			'fields'         => 'ids',
		)
	);
	$progress_total = is_array( $games_in_week ) ? count( $games_in_week ) : 0;
	if ( $progress_total > 0 ) {
		// Count picks by current user for these games
		$user_picks    = get_posts(
			array(
				'post_type'      => 'pick',
				'author'         => $current_user_id,
				'posts_per_page' => -1,
				'meta_key'       => 'game_id',
				'meta_value'     => $games_in_week,
				'meta_compare'   => 'IN',
				'fields'         => 'ids',
			)
		);
		$progress_made = is_array( $user_picks ) ? count( $user_picks ) : 0;
	}
}
?>
<div class="cp-submit-bar" role="region" aria-label="Submit picks">
	<div class="container-fluid d-flex align-items-center justify-content-between">
		<div>
			<a class="btn btn-primary btn-lg" href="<?php echo esc_url( get_permalink( get_page_by_path( 'make-picks' ) ) ); ?>">Submit Your Pick</a>
		</div>
		<div class="cp-progress-wrap text-end">
			<div class="small text-muted mb-1"><?php echo intval( $progress_made ); ?> / <?php echo intval( $progress_total ); ?> Picks Made</div>
			<div class="progress cp-progress" style="width:180px;">
				<div class="progress-bar" role="progressbar" style="width: <?php echo $progress_total ? round( ( $progress_made / $progress_total ) * 100 ) : 0; ?>%;" aria-valuenow="<?php echo intval( $progress_made ); ?>" aria-valuemin="0" aria-valuemax="<?php echo intval( $progress_total ); ?>"></div>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
