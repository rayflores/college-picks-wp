<?php
/**
 * Template Name: Picks Matrix
 *
 * Displays a table of all users (columns) and their picks for the current week (rows = games).
 * Each cell shows the logo of the team picked by the user for that game.
 *
 * @package college-picks
 */

get_header();
?>


<div class="container py-4" style="min-height:80vh;">
	<?php
	// Get current week number (latest week with a result or games scheduled)
	if ( function_exists( 'cp_get_current_week_number' ) ) {
		$week = cp_get_current_week_number();
	} else {
		$week = null;
	}
	if ( isset( $_GET['week'] ) ) {
		$week = intval( $_GET['week'] );
	}

	// Get all weeks with games for the selector
	$all_games = get_posts(
		array(
			'post_type'      => 'game',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	$weeks     = array();
	foreach ( $all_games as $gid ) {
		$w = get_post_meta( $gid, 'week', true );
		if ( $w && ! in_array( $w, $weeks, true ) ) {
			$weeks[] = $w;
		}
	}
	sort( $weeks, SORT_NUMERIC );
	?>
	<div class="d-flex justify-content-between align-items-center mb-4">
		<h1 class="mb-0">Picks Matrix</h1>
		<?php if ( ! empty( $weeks ) ) : ?>
			<form method="get" class="d-inline-block ms-3">
				<label for="cp-week-select" class="me-2 fw-bold text-light">Week:</label>
				<select id="cp-week-select" name="week" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
					<?php foreach ( $weeks as $w ) : ?>
						<option value="<?php echo esc_attr( $w ); ?>" <?php selected( $week, $w ); ?>><?php echo esc_html( $w ); ?></option>
					<?php endforeach; ?>
				</select>
			</form>
		<?php endif; ?>
	</div>
	<?php
	if ( empty( $week ) ) {
		echo '<div class="alert alert-warning">No week found.</div>';
		get_footer();
		return;
	}

	// Get all users
	$users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
	if ( empty( $users ) ) {
		echo '<div class="alert alert-warning">No users found.</div>';
		get_footer();
		return;
	}

	// Get all games for this week
	$games = get_posts(
		array(
			'post_type'      => 'game',
			'posts_per_page' => -1,
			'meta_key'       => 'week',
			'meta_value'     => $week,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	if ( empty( $games ) ) {
		echo '<div class="alert alert-warning">No games found for week ' . esc_html( $week ) . '.</div>';
		get_footer();
		return;
	}

	// Build a lookup: [game_id][user_id] => pick_choice
	$picks_lookup = array();
	$game_ids     = wp_list_pluck( $games, 'ID' );
	$all_picks    = get_posts(
		array(
			'post_type'      => 'pick',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => 'game_id',
					'value'   => $game_ids,
					'compare' => 'IN',
				),
			),
		)
	);
	foreach ( $all_picks as $pick ) {
		$gid                          = get_post_meta( $pick->ID, 'game_id', true );
		$uid                          = $pick->post_author;
		$choice                       = get_post_meta( $pick->ID, 'pick_choice', true );
		$picks_lookup[ $gid ][ $uid ] = $choice;
	}

	// Helper to get team logo by team name
	function cp_get_team_logo_url( $team_name ) {
		if ( ! function_exists( 'post_exists' ) ) {
			require_once ABSPATH . 'wp-admin/includes/post.php';
		}
		$team_id = post_exists( $team_name, '', '', 'team' );
		if ( $team_id ) {
			$team = get_post( $team_id );
			if ( $team ) {
				$logo = get_the_post_thumbnail_url( $team->ID, 'thumbnail' );
				if ( $logo ) {
					return $logo;
				}
			}
		}
		return '';
	}
	?>
	<div class="table-responsive">
		<table class="table table-dark table-bordered align-middle text-center">
			<thead>
				<tr>
					<th>Game</th>
					<?php foreach ( $users as $user ) : ?>
						<th><?php echo esc_html( $user->display_name ); ?></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $games as $game ) :
					$home       = get_post_meta( $game->ID, 'home_team', true );
					$away       = get_post_meta( $game->ID, 'away_team', true );
					$game_label = esc_html( $away . ' @ ' . $home );
					?>
				<tr>
					<td><?php echo $game_label; ?></td>
					<?php
					foreach ( $users as $user ) :
						$pick      = isset( $picks_lookup[ $game->ID ][ $user->ID ] ) ? $picks_lookup[ $game->ID ][ $user->ID ] : '';
						$team_name = '';
						if ( $pick === 'home' ) {
							$team_name = $home;
						} elseif ( $pick === 'away' ) {
							$team_name = $away;
						}
						$logo_url = $team_name ? cp_get_team_logo_url( $team_name ) : '';
						?>
					<td>
						<?php if ( $logo_url ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $team_name ); ?> logo" style="width:48px; height:48px; object-fit:contain; background:#fff; border-radius:8px;">
						<?php elseif ( $team_name ) : ?>
							<span class="badge bg-info text-dark"><?php echo esc_html( $team_name ); ?></span>
						<?php else : ?>
							<span class="text-muted">—</span>
						<?php endif; ?>
					</td>
					<?php endforeach; ?>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php get_footer(); ?>
