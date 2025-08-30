<?php
// Basic theme setup and registration of custom post types + meta boxes
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cp_theme_setup() {
	add_theme_support( 'title-tag' );
}
add_action( 'after_setup_theme', 'cp_theme_setup' );

function cp_enqueue_assets() {
	wp_enqueue_style( 'college-picks-style', get_stylesheet_uri() );
}
add_action( 'wp_enqueue_scripts', 'cp_enqueue_assets' );

// Register custom post type: Game
function cp_register_cpt_game() {
	$labels = array(
		'name'          => 'Games',
		'singular_name' => 'Game',
		'add_new_item'  => 'Add New Game',
		'edit_item'     => 'Edit Game',
		'new_item'      => 'New Game',
		'view_item'     => 'View Game',
		'search_items'  => 'Search Games',
		'not_found'     => 'No games found',
	);
	$args   = array(
		'labels'        => $labels,
		'public'        => true,
		'has_archive'   => true,
		'show_in_rest'  => true,
		'supports'      => array( 'title' ),
		'menu_position' => 20,
	);
	register_post_type( 'game', $args );
}
add_action( 'init', 'cp_register_cpt_game' );

// Register custom post type: Pick
function cp_register_cpt_pick() {
	$labels = array(
		'name'          => 'Picks',
		'singular_name' => 'Pick',
		'add_new_item'  => 'Add New Pick',
		'edit_item'     => 'Edit Pick',
		'new_item'      => 'New Pick',
		'view_item'     => 'View Pick',
		'search_items'  => 'Search Picks',
		'not_found'     => 'No picks found',
	);
	$args   = array(
		'labels'        => $labels,
		'public'        => false,
		'show_ui'       => true,
		'show_in_rest'  => true,
		'supports'      => array( 'title', 'author' ),
		'menu_position' => 21,
	);
	register_post_type( 'pick', $args );
}
add_action( 'init', 'cp_register_cpt_pick' );

// Meta boxes for Game: home_team, away_team, kickoff_time, week
function cp_add_game_metaboxes() {
	add_meta_box( 'cp_game_details', 'Game Details', 'cp_render_game_metabox', 'game', 'normal', 'default' );
}
add_action( 'add_meta_boxes', 'cp_add_game_metaboxes' );

function cp_render_game_metabox( $post ) {
	wp_nonce_field( 'cp_save_game_meta', 'cp_game_meta_nonce' );
	$home   = get_post_meta( $post->ID, 'home_team', true );
	$away   = get_post_meta( $post->ID, 'away_team', true );
	$kick   = get_post_meta( $post->ID, 'kickoff_time', true );
	$week   = get_post_meta( $post->ID, 'week', true );
	$result = get_post_meta( $post->ID, 'result', true );
	?>
	<p>
		<label>Home Team:<br>
		<input type="text" name="home_team" value="<?php echo esc_attr( $home ); ?>" style="width:100%"></label>
	</p>
	<p>
		<label>Away Team:<br>
		<input type="text" name="away_team" value="<?php echo esc_attr( $away ); ?>" style="width:100%"></label>
	</p>
	<p>
		<label>Kickoff Time (YYYY-MM-DD HH:MM):<br>
		<input type="text" name="kickoff_time" value="<?php echo esc_attr( $kick ); ?>" style="width:100%"></label>
	</p>
	<p>
		<label>Week:<br>
		<input type="text" name="week" value="<?php echo esc_attr( $week ); ?>" style="width:100%"></label>
	</p>
	<p>
		<label>Result:<br>
		<select name="result">
			<option value="" <?php selected( $result, '' ); ?>>-- Not set --</option>
			<option value="home" <?php selected( $result, 'home' ); ?>>Home team won</option>
			<option value="away" <?php selected( $result, 'away' ); ?>>Away team won</option>
			<option value="tie" <?php selected( $result, 'tie' ); ?>>Tie / Push</option>
		</select>
		</label>
	</p>
	<?php
}

function cp_save_game_meta( $post_id, $post ) {
	if ( ! isset( $_POST['cp_game_meta_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['cp_game_meta_nonce'], 'cp_save_game_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( 'game' !== $post->post_type ) {
		return;
	}
	if ( isset( $_POST['home_team'] ) ) {
		update_post_meta( $post_id, 'home_team', sanitize_text_field( wp_unslash( $_POST['home_team'] ) ) );
	}
	if ( isset( $_POST['away_team'] ) ) {
		update_post_meta( $post_id, 'away_team', sanitize_text_field( wp_unslash( $_POST['away_team'] ) ) );
	}
	if ( isset( $_POST['kickoff_time'] ) ) {
		update_post_meta( $post_id, 'kickoff_time', sanitize_text_field( wp_unslash( $_POST['kickoff_time'] ) ) );
	}
	if ( isset( $_POST['week'] ) ) {
		update_post_meta( $post_id, 'week', sanitize_text_field( wp_unslash( $_POST['week'] ) ) );
	}
	if ( isset( $_POST['result'] ) ) {
		$allowed = array( 'home', 'away', 'tie', '' );
		$res     = sanitize_text_field( wp_unslash( $_POST['result'] ) );
		if ( in_array( $res, $allowed, true ) ) {
			update_post_meta( $post_id, 'result', $res );
		}
	}
}
add_action( 'save_post', 'cp_save_game_meta', 10, 2 );

// Pick submission handler (frontend form posts to admin-post.php?action=submit_pick)
function cp_handle_pick_submission() {
	if ( ! isset( $_POST['cp_pick_nonce'] ) || ! wp_verify_nonce( $_POST['cp_pick_nonce'], 'cp_submit_pick' ) ) {
		wp_die( 'Invalid request' );
	}
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( wp_get_referer() ) );
		exit;
	}
	$user_id = get_current_user_id();
	$game_id = isset( $_POST['game_id'] ) ? intval( $_POST['game_id'] ) : 0;
	$choice  = isset( $_POST['pick_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['pick_choice'] ) ) : '';
	if ( ! $game_id || empty( $choice ) ) {
		wp_safe_redirect( add_query_arg( 'pick', 'error', wp_get_referer() ) );
		exit;
	}
	// If the user already has a pick for this game, update it
	$existing = get_posts(
		array(
			'post_type'      => 'pick',
			'author'         => $user_id,
			'meta_key'       => 'game_id',
			'meta_value'     => $game_id,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	if ( ! empty( $existing ) ) {
		$pick_id = $existing[0];
		wp_update_post(
			array(
				'ID'          => $pick_id,
				'post_status' => 'publish',
			)
		);
		update_post_meta( $pick_id, 'pick_choice', $choice );
	} else {
		$title   = sprintf( 'Pick: user %d - game %d', $user_id, $game_id );
		$pick_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => 'pick',
				'post_status' => 'publish',
				'post_author' => $user_id,
			)
		);
		if ( $pick_id && ! is_wp_error( $pick_id ) ) {
			update_post_meta( $pick_id, 'game_id', $game_id );
			update_post_meta( $pick_id, 'pick_choice', $choice );
		}
	}
	wp_safe_redirect( add_query_arg( 'pick', 'saved', wp_get_referer() ) );
	exit;
}
add_action( 'admin_post_submit_pick', 'cp_handle_pick_submission' );
add_action( 'admin_post_nopriv_submit_pick', 'cp_handle_pick_submission' );



/**
 * Compute leaderboard for a specific week. Returns ordered array of users with stats.
 *
 * @param string|int|null $week Week identifier. If null, uses latest week with results.
 * @return array Array of [ user_id => [name, correct, total, percent] ] ordered desc by correct.
 */
function cp_get_week_leaderboard( $week = null ) {
	// determine week if not provided: find latest game with a result and a week set
	if ( empty( $week ) ) {
		$games_with_result = get_posts(
			array(
				'post_type'      => 'game',
				'posts_per_page' => 200,
				'meta_query'     => array(
					array(
						'key'     => 'result',
						'compare' => '!=',
						'value'   => '',
					),
				),
				'meta_key'       => 'week',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $games_with_result ) ) {
			$latest_game = get_post( $games_with_result[0] );
			$week        = get_post_meta( $latest_game->ID, 'week', true );
		}
	}

	if ( empty( $week ) ) {
		return array();
	}

	// get games for the week that have results
	$games = get_posts(
		array(
			'post_type'      => 'game',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'   => 'week',
					'value' => $week,
				),
				array(
					'key'     => 'result',
					'compare' => '!=',
					'value'   => '',
				),
			),
			'fields'         => 'ids',
		)
	);
	if ( empty( $games ) ) {
		return array();
	}

	$user_stats = array(); // user_id => [correct=>int, total=>int]

	foreach ( $games as $game_id ) {
		$result = get_post_meta( $game_id, 'result', true );
		if ( empty( $result ) ) {
			continue;
		}
		// get picks for this game
		$picks = get_posts(
			array(
				'post_type'      => 'pick',
				'posts_per_page' => -1,
				'meta_key'       => 'game_id',
				'meta_value'     => $game_id,
			)
		);
		if ( empty( $picks ) ) {
			continue;
		}
		foreach ( $picks as $p ) {
			$uid = $p->post_author;
			if ( ! isset( $user_stats[ $uid ] ) ) {
				$user_stats[ $uid ] = array(
					'correct' => 0,
					'total'   => 0,
				);
			}
			$choice = get_post_meta( $p->ID, 'pick_choice', true );
			++$user_stats[ $uid ]['total'];
			if ( $choice === $result ) {
				++$user_stats[ $uid ]['correct'];
			}
		}
	}

	// Build rows with names and percentages
	$rows = array();
	foreach ( $user_stats as $uid => $stats ) {
		$user    = get_userdata( $uid );
		$name    = $user ? $user->display_name : 'User ' . $uid;
		$correct = intval( $stats['correct'] );
		$total   = intval( $stats['total'] );
		$percent = $total > 0 ? round( ( $correct / $total ) * 100, 1 ) : 0;
		$rows[]  = array(
			'user_id' => $uid,
			'name'    => $name,
			'correct' => $correct,
			'total'   => $total,
			'percent' => $percent,
		);
	}

	usort(
		$rows,
		function ( $a, $b ) {
			if ( $b['correct'] === $a['correct'] ) {
				return $b['percent'] <=> $a['percent'];
			}
			return $b['correct'] - $a['correct'];
		}
	);

	return $rows;
}

/**
 * Shortcode renderer: [college_picks_leaderboard week="1" top="10"]
 */
if ( ! function_exists( 'cp_leaderboard_shortcode' ) ) {
	function cp_leaderboard_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'week' => '',
				'top'  => 10,
			),
			$atts,
			'college_picks_leaderboard'
		);
		$week = $atts['week'] ?: null;
		$top  = intval( $atts['top'] );
		$rows = cp_get_week_leaderboard( $week );

		if ( empty( $rows ) ) {
			return '<div class="cp-leaderboard"><p>No leaderboard data available for this week.</p></div>';
		}

		ob_start();
		echo '<div class="cp-leaderboard"><h3>Leaderboard</h3><ol>';
		$count = 0;
		foreach ( $rows as $r ) {
			if ( $count >= $top ) {
				break;
			}
			++$count;
			printf( '<li>%s — %d correct of %d picks (%s%%)</li>', esc_html( $r['name'] ), intval( $r['correct'] ), intval( $r['total'] ), esc_html( number_format_i18n( $r['percent'], 1 ) ) );
		}
		echo '</ol></div>';
		return ob_get_clean();
	}
	// Ensure shortcode is registered once
	if ( shortcode_exists( 'college_picks_leaderboard' ) ) {
		remove_shortcode( 'college_picks_leaderboard' );
	}
	add_shortcode( 'college_picks_leaderboard', 'cp_leaderboard_shortcode' );
}

/**
 * Admin: add submenu page under Games for CSV import
 */
function cp_add_import_submenu() {
	add_submenu_page(
		'edit.php?post_type=game',
		'Import Games',
		'Import CSV',
		'manage_options',
		'cp_import_games',
		'cp_render_import_games_page'
	);
}
add_action( 'admin_menu', 'cp_add_import_submenu' );

/**
 * Render import page UI
 */
function cp_render_import_games_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}
	$base_url = admin_url( 'edit.php?post_type=game&page=cp_import_games' );
	$notice   = '';
	if ( isset( $_GET['cp_import'] ) ) {
		if ( 'success' === $_GET['cp_import'] ) {
			$inserted = isset( $_GET['inserted'] ) ? intval( $_GET['inserted'] ) : 0;
			$skipped  = isset( $_GET['skipped'] ) ? intval( $_GET['skipped'] ) : 0;
			$notice   = sprintf( '<div class="notice notice-success"><p>Import complete. Inserted: %d. Skipped: %d.</p></div>', $inserted, $skipped );
		} else {
			$notice = '<div class="notice notice-error"><p>Import failed. Check the CSV and try again.</p></div>';
		}
	}
	?>
	<div class="wrap">
		<h1>Import Games from CSV</h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<p>Upload a CSV file with columns: <code>home_team</code>, <code>away_team</code>, <code>kickoff_time</code> (YYYY-MM-DD HH:MM) and <code>week</code>. Header row optional but recommended.</p>
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cp_import_games_action', 'cp_import_games_nonce' ); ?>
			<input type="hidden" name="action" value="cp_import_games">
			<input type="file" name="cp_games_csv" accept=".csv" required>
			<p class="submit"><button type="submit" class="button button-primary">Upload and Import</button></p>
		</form>

		<h2>Sample CSV</h2>
		<pre>home_team,away_team,kickoff_time,week
Dolphins,Bills,2025-09-07 20:15,1
Jets,Patriots,2025-09-07 13:00,1
		</pre>
	</div>
	<?php
}

/**
 * Handle CSV upload and import rows as 'game' posts.
 */
function cp_handle_import_games() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}
	if ( ! isset( $_POST['cp_import_games_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['cp_import_games_nonce'] ), 'cp_import_games_action' ) ) {
		wp_die( 'Invalid request' );
	}
	if ( empty( $_FILES['cp_games_csv'] ) || ! is_uploaded_file( $_FILES['cp_games_csv']['tmp_name'] ) ) {
		wp_safe_redirect( add_query_arg( 'cp_import', 'failed', admin_url( 'edit.php?post_type=game&page=cp_import_games' ) ) );
		exit;
	}

	$file     = $_FILES['cp_games_csv'];
	$max_rows = 2000; // safety limit
	$inserted = 0;
	$skipped  = 0;

	$fh = fopen( $file['tmp_name'], 'r' );
	if ( ! $fh ) {
		wp_safe_redirect( add_query_arg( 'cp_import', 'failed', admin_url( 'edit.php?post_type=game&page=cp_import_games' ) ) );
		exit;
	}

	// Read header row to map columns if present
	$header     = fgetcsv( $fh );
	$map_header = false;
	if ( $header !== false ) {
		$normalized = array_map(
			function ( $h ) {
				return strtolower( trim( preg_replace( '/[^a-z0-9_]/', '_', $h ) ) );
			},
			$header
		);
		// if header contains any known keys, treat as header
		$known = array( 'home_team', 'away_team', 'kickoff_time', 'week', 'home', 'away', 'kickoff' );
		foreach ( $normalized as $col ) {
			if ( in_array( $col, $known, true ) ) {
				$map_header = $normalized;
				break;
			}
		}
		if ( ! $map_header ) {
			// no header detected, rewind to start and treat first line as data
			rewind( $fh );
		}
	}

	$row = 0;
	while ( ( $data = fgetcsv( $fh ) ) !== false ) {
		++$row;
		if ( $row > $max_rows ) {
			break;
		}
		if ( empty( array_filter( $data ) ) ) {
			// skip empty line
			continue;
		}
		// Map fields
		if ( $map_header ) {
			$assoc = array_combine( $map_header, $data );
		} else {
			// fallback positional mapping: home, away, kickoff, week
			$assoc = array(
				0 => isset( $data[0] ) ? $data[0] : '',
				1 => isset( $data[1] ) ? $data[1] : '',
				2 => isset( $data[2] ) ? $data[2] : '',
				3 => isset( $data[3] ) ? $data[3] : '',
			);
		}

		// Identify fields tolerant to different header names
		$home    = '';
		$away    = '';
		$kickoff = '';
		$week_v  = '';
		foreach ( $assoc as $k => $v ) {
			$key = is_string( $k ) ? $k : (string) $k;
			$lk  = strtolower( trim( $key ) );
			$val = trim( $v );
			if ( in_array( $lk, array( 'home_team', 'home', 'home-team' ), true ) ) {
				$home = $val;
			} elseif ( in_array( $lk, array( 'away_team', 'away', 'away-team' ), true ) ) {
				$away = $val;
			} elseif ( in_array( $lk, array( 'kickoff_time', 'kickoff', 'kickoff-time', 'kickoff_time_utc' ), true ) ) {
				$kickoff = $val;
			} elseif ( 'week' === $lk ) {
				$week_v = $val;
			} else {
				// also attempt positional numeric keys
				if ( '' === $home && isset( $assoc[0] ) ) {
					$home = trim( $assoc[0] );
				}
				if ( '' === $away && isset( $assoc[1] ) ) {
					$away = trim( $assoc[1] );
				}
				if ( '' === $kickoff && isset( $assoc[2] ) ) {
					$kickoff = trim( $assoc[2] );
				}
				if ( '' === $week_v && isset( $assoc[3] ) ) {
					$week_v = trim( $assoc[3] );
				}
			}
		}

		// Basic validation
		if ( empty( $home ) || empty( $away ) ) {
			++$skipped;
			continue;
		}

		// Build post title
		$title = sprintf( '%s @ %s', sanitize_text_field( $away ), sanitize_text_field( $home ) );

		// Insert post
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => 'game',
				'post_status' => 'publish',
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			++$skipped;
			continue;
		}

		// Save meta
		update_post_meta( $post_id, 'home_team', sanitize_text_field( $home ) );
		update_post_meta( $post_id, 'away_team', sanitize_text_field( $away ) );
		if ( ! empty( $kickoff ) ) {
			update_post_meta( $post_id, 'kickoff_time', sanitize_text_field( $kickoff ) );
		}
		if ( ! empty( $week_v ) ) {
			update_post_meta( $post_id, 'week', sanitize_text_field( $week_v ) );
		}
		++$inserted;
	}
	fclose( $fh );

	$redirect = add_query_arg(
		array(
			'cp_import' => 'success',
			'inserted'  => $inserted,
			'skipped'   => $skipped,
		),
		admin_url( 'edit.php?post_type=game&page=cp_import_games' )
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_cp_import_games', 'cp_handle_import_games' );

/**
 * Handle bulk picks submission from the Make Picks page.
 */
function cp_handle_bulk_picks() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( wp_login_url( wp_get_referer() ) );
		exit;
	}
	if ( ! isset( $_POST['cp_submit_picks_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['cp_submit_picks_nonce'] ), 'cp_submit_picks' ) ) {
		wp_die( 'Invalid request' );
	}
	$user_id = get_current_user_id();
	$picks   = isset( $_POST['cp_picks'] ) && is_array( $_POST['cp_picks'] ) ? wp_unslash( $_POST['cp_picks'] ) : array();
	$saved   = 0;
	foreach ( $picks as $game_id => $choice ) {
		$game_id = intval( $game_id );
		$choice  = sanitize_text_field( $choice );
		if ( ! in_array( $choice, array( 'home', 'away' ), true ) ) {
			continue;
		}
		// update or create pick
		$existing = get_posts(
			array(
				'post_type'      => 'pick',
				'author'         => $user_id,
				'meta_key'       => 'game_id',
				'meta_value'     => $game_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $existing ) ) {
			$pick_id = $existing[0];
			wp_update_post(
				array(
					'ID'          => $pick_id,
					'post_status' => 'publish',
				)
			);
			update_post_meta( $pick_id, 'pick_choice', $choice );
		} else {
			$title   = sprintf( 'Pick: user %d - game %d', $user_id, $game_id );
			$pick_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_type'   => 'pick',
					'post_status' => 'publish',
					'post_author' => $user_id,
				)
			);
			if ( $pick_id && ! is_wp_error( $pick_id ) ) {
				update_post_meta( $pick_id, 'game_id', $game_id );
				update_post_meta( $pick_id, 'pick_choice', $choice );
			}
		}
		++$saved;
	}
	$redirect = add_query_arg(
		array(
			'cp_picks' => 'saved',
			'saved'    => $saved,
		),
		wp_get_referer() ?: home_url()
	);
	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_submit_picks_bulk', 'cp_handle_bulk_picks' );
