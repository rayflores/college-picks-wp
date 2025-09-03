<?php
/**
 * Template Name: My Picks
 *
 * Modern, dark, Bootstrap 5-inspired layout for user's picks.
 *
 * @package college-picks
 */

get_header();
?>
<div class="container py-4" style="min-height:80vh;">
<?php
if ( ! is_user_logged_in() ) {
	echo '<div class="wrap"><p>You must <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to view your picks.</p></div>';
	get_footer();
	return;
}

$current_user_id = get_current_user_id();

// Get all weeks with games
$weeks = array();
$games = get_posts(
	array(
		'post_type'      => 'game',
		'posts_per_page' => -1,
		'orderby'        => 'meta_value_num',
		'meta_key'       => 'week',
		'order'          => 'ASC',
		'fields'         => 'ids',
	)
);
foreach ( $games as $gid ) {
	$w = get_post_meta( $gid, 'week', true );
	if ( $w && ! in_array( $w, $weeks ) ) {
		$weeks[] = $w;
	}
}
sort( $weeks, SORT_NUMERIC );

// Determine active week (first future kickoff or last week)
$active_week = null;
$now         = current_time( 'timestamp' );
foreach ( $games as $g_id ) {
	$kick = get_post_meta( $g_id, 'kickoff_time', true );
	if ( empty( $kick ) ) {
		continue;
	}
	$dt = DateTime::createFromFormat( 'Y-m-d H:i', $kick );
	if ( false === $dt ) {
		try {
			$dt = new DateTime( $kick );
		} catch ( Exception $e ) {
			$dt = null; }
	}
	if ( $dt && $dt->getTimestamp() >= $now ) {
		$active_week = get_post_meta( $g_id, 'week', true );
		break;
	}
}
if ( null === $active_week && ! empty( $games ) ) {
	$last        = end( $games );
	$active_week = get_post_meta( $last, 'week', true );
}
if ( ! $active_week && ! empty( $weeks ) ) {
	$active_week = end( $weeks );
}

// Week Tabs with date (inspiration style)
echo '<div class="cp-weeks-bar bg-dark rounded-3 px-2 py-2 mb-4 d-flex align-items-center" style="overflow-x:auto; white-space:nowrap;">';
foreach ( $weeks as $w ) {
	$active = ( $w == $active_week ) ? 'active' : '';
	// Find a game in this week to get the date
	$game_id = null;
	foreach ( $games as $gid ) {
		if ( get_post_meta( $gid, 'week', true ) == $w ) {
			$game_id = $gid;
			break; }
	}
	$kickoff = $game_id ? get_post_meta( $game_id, 'kickoff_time', true ) : '';
	$dt      = $kickoff ? DateTime::createFromFormat( 'Y-m-d H:i', $kickoff ) : false;
	if ( ! $dt && $kickoff ) {
		try {
			$dt = new DateTime( $kickoff );
		} catch ( Exception $e ) {
			$dt = false; }
	}
	$date_str = $dt ? $dt->format( 'M j' ) : '';
	echo '<div class="me-2">';
	echo '<a class="btn btn-outline-light fw-bold px-4 py-2 cp-week-tab ' . $active . '" style="border-radius:12px; min-width:110px;" href="#" data-week="' . esc_attr( $w ) . '">';
	echo 'Week ' . esc_html( $w ) . '<div class="small text-info">' . esc_html( $date_str ) . '</div>';
	echo '</a>';
	echo '</div>';
}
echo '</div>';

// Fetch picks for current user
$user_picks         = get_posts(
	array(
		'post_type'      => 'pick',
		'author'         => $current_user_id,
		'posts_per_page' => -1,
		'orderby'        => 'post_date',
		'order'          => 'DESC',
	)
);
$user_picks_by_game = array();
foreach ( $user_picks as $pick ) {
	$gid = get_post_meta( $pick->ID, 'game_id', true );
	if ( $gid ) {
		$user_picks_by_game[ $gid ] = $pick;
	}
}

// Render games for each week (inspiration style)
foreach ( $weeks as $w ) {
	$games_in_week = get_posts(
		array(
			'post_type'      => 'game',
			'posts_per_page' => -1,
			'meta_key'       => 'week',
			'meta_value'     => $w,
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);
	$show          = ( $w == $active_week ) ? 'block' : 'none';
	echo '<div class="cp-week-card" data-week="' . esc_attr( $w ) . '" style="display:' . $show . ';">';
	foreach ( $games_in_week as $gid ) {
		if ( ! function_exists( 'post_exists' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$home   = get_post_meta( $gid, 'home_team', true );
		$away   = get_post_meta( $gid, 'away_team', true );
		$kick   = get_post_meta( $gid, 'kickoff_time', true );
		$result = get_post_meta( $gid, 'result', true );
		$dt     = $kick ? DateTime::createFromFormat( 'Y-m-d H:i', $kick ) : false;
		if ( ! $dt && $kick ) {
			try {
				$dt = new DateTime( $kick );
			} catch ( Exception $e ) {
				$dt = false;
			}
		}
		$kick_str    = $dt ? $dt->format( 'D n/j • g:i A' ) : esc_html( $kick );
		$pick        = isset( $user_picks_by_game[ $gid ] ) ? $user_picks_by_game[ $gid ] : null;
		$pick_choice = $pick ? get_post_meta( $pick->ID, 'pick_choice', true ) : null;
		$is_correct  = ( $pick && $result && $pick_choice === $result );
		$is_wrong    = ( $pick && $result && $pick_choice && $pick_choice !== $result );
		$card_border = $is_correct ? 'border-success' : ( $is_wrong ? 'border-danger' : 'border-secondary' );

		// Get team objects by name (title)
		$home_team_id  = post_exists( $home, '', '', 'team' );
		$home_team_obj = get_post( $home_team_id );
		$away_team_id  = post_exists( $away, '', '', 'team' );
		$away_team_obj = get_post( $away_team_id );
		$home_logo     = $home_team_obj ? get_the_post_thumbnail_url( $home_team_obj->ID, 'thumbnail' ) : '';
		$away_logo     = $away_team_obj ? get_the_post_thumbnail_url( $away_team_obj->ID, 'thumbnail' ) : '';
		$home_rank     = $home_team_obj ? get_post_meta( $home_team_obj->ID, 'cp_team_rank', true ) : 'NR';
		$away_rank     = $away_team_obj ? get_post_meta( $away_team_obj->ID, 'cp_team_rank', true ) : 'NR';

		echo '<div class="card mb-4 shadow-sm bg-dark rounded-4 ' . $card_border . '" style="border-width:4px;">';
		echo '<div class="card-body p-0">';
		// Top row: kickoff and result
		echo '<div class="d-flex justify-content-between align-items-center px-4 pt-3">';
		echo '<span class="badge bg-secondary fs-6 px-3 py-2">' . esc_html( $kick_str ) . '</span>';
		if ( $result ) {
			$res_label = $result === 'home' ? $home : ( $result === 'away' ? $away : 'Tie' );
			echo '<span class="badge bg-secondary fs-6 px-3 py-2">Winner: ' . esc_html( $res_label ) . '</span>';
		}
		echo '</div>';
		// Main row: two team blocks, single row, centered vertically
		echo '<div class="d-flex align-items-stretch justify-content-between px-4 py-4 col-12">';
		// Home team block
		echo '<div class="card col-5 rounded-4">';
		if ( $home_logo ) {
			echo '<img class="card-img-top" src="' . esc_url( $home_logo ) . '" alt="' . esc_attr( $home ) . ' logo" style="width:64px; height:64px; object-fit:contain; background:#ffffff; margin-right:auto;margin-left:auto;">';
		} else {
			echo '<div style="width:64px; height:64px; background:#333; border-radius:12px; margin-right:18px;"></div>';
		}
		echo '<div class="card-body d-flex flex-column align-items-center border-top">';
		echo '<div class="card-title fw-bold text-dark text-center" style="font-size:1.4rem;">' . esc_html( $home ) . '</div>';
		if ( $home_rank > 0 ) {
			echo '<div class="text-info small text-center">Rank: ' . intval( $home_rank ) . '</div>';
		} else {
			echo '<div class="text-info small text-center">Rank: NR</div>';
		}
		echo '</div>';
		// Your Pick badge (home)
		if ( $pick_choice === 'home' ) {
			echo '<div class="card-footer badge bg-success rounded-bottom-4">Your Pick</div>';
		}
		echo '</div>';
		// Away team block
		echo '<div class="card col-5 rounded-4">';
		if ( $away_logo ) {
			echo '<img class="card-img-top" src="' . esc_url( $away_logo ) . '" alt="' . esc_attr( $away ) . ' logo" style="width:64px; height:64px; object-fit:contain; background:#23272a; border-radius:12px; margin-right:auto;margin-left:auto;">';
		} else {
			echo '<div style="width:64px; height:64px; background:#333; border-radius:12px; margin-right:18px;"></div>';
		}
		echo '<div class="card-body">';
		echo '<div class="card-title fw-bold text-dark text-center" style="font-size:1.4rem;">' . esc_html( $away ) . '</div>';
		if ( $away_rank > 0 ) {
			echo '<div class="text-info small text-center">Rank: ' . intval( $away_rank ) . '</div>';
		} else {
			echo '<div class="text-info small text-center">Rank: NR</div>';
		}

		echo '</div>';
		if ( $pick_choice === 'away' ) {
			echo '<div class="card-footer badge bg-success rounded-bottom-4">Your Pick</div>';
		}
		echo '</div>';
		echo '</div>';
		// Correct/Incorrect icon (optional, can be moved)
		if ( $pick && $result ) {
			if ( $is_correct ) {
				echo '<div class="mt-3 text-success fs-2"><i class="bi bi-check-circle-fill"></i></div>';
			} elseif ( $is_wrong ) {
				echo '<div class="mt-3 text-danger fs-2"><i class="bi bi-x-circle-fill"></i></div>';
			}
		}
		echo '</div>';
		echo '</div>';
	}

	echo '</div>'; // cp-week-card
}

// Progress bar for active week
$progress_made  = 0;
$progress_total = 0;
if ( $active_week ) {
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
<div class="position-fixed bottom-0 start-0 w-100 bg-dark shadow-lg py-3 px-2" style="z-index:1050; border-radius:18px 18px 0 0;">
	<div class='container-fluid d-flex align-items-center justify-content-between'>
		<div>
			<a class='btn btn-primary btn-lg' href='<?php echo esc_url( get_permalink( get_page_by_path( 'make-picks' ) ) ); ?>'>Submit Your Picks</a>
		</div>
		<div class="cp-progress-wrap text-end">
			<div class="small text-light mb-1"><?php echo intval( $progress_made ); ?> / <?php echo intval( $progress_total ); ?> Picks Made</div>
			<div class="progress" style="width:180px; height:10px; border-radius:8px; background:#23272f;">
				<div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $progress_total ? round( ( $progress_made / $progress_total ) * 100 ) : 0; ?>%; border-radius:8px;" aria-valuenow="<?php echo intval( $progress_made ); ?>" aria-valuemin="0" aria-valuemax="<?php echo intval( $progress_total ); ?>"></div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); ?>
