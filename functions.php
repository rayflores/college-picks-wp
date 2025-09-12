<?php
/**
 * College Picks Theme Functions
 *
 * This file contains the core functions for the College Picks theme.
 *
 * @package CollegePicks
 */

// Basic theme setup and registration of custom post types + meta boxes.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cp_theme_setup() {
	add_theme_support( 'title-tag' );
}
add_action( 'after_setup_theme', 'cp_theme_setup' );

/**
 * Register theme menus.
 */
function cp_register_menus() {
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'college-picks' ),
		)
	);
}
add_action( 'after_setup_theme', 'cp_register_menus' );

/**
 * Ensure wp_nav_menu outputs Bootstrap-friendly classes for items and links.
 *
 * @param array  $atts  HTML attributes for the menu item's anchor.
 * @param object $item  Menu item object.
 * @param array  $args  Arguments for wp_nav_menu.
 * @param int    $depth Depth.
 * @return array
 */
function cp_nav_menu_link_attributes( $atts, $item, $args, $depth ) {
	if ( isset( $args->menu_class ) && ( false !== strpos( $args->menu_class, 'navbar-nav' ) || false !== strpos( $args->menu_class, 'cp-bottom-nav' ) ) ) {
		$atts['class'] = isset( $atts['class'] ) ? $atts['class'] . ' nav-link' : 'nav-link';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'cp_nav_menu_link_attributes', 10, 4 );

/**
 * Add nav-item class to li elements when rendering nav menus.
 *
 * @param string $classes Space-separated list of classes.
 * @param object $item    Menu item.
 * @param array  $args    Arguments.
 * @param int    $depth   Depth.
 * @return string
 */
function cp_nav_menu_css_class( $classes, $item, $args, $depth ) {
	if ( isset( $args->menu_class ) && ( false !== strpos( $args->menu_class, 'navbar-nav' ) || false !== strpos( $args->menu_class, 'cp-bottom-nav' ) ) ) {
		$classes[] = 'nav-item';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'cp_nav_menu_css_class', 10, 4 );

/**
 * Hide the admin bar for non-administrators.
 * Administrators keep the admin bar visible.
 *
 * @param bool $show Whether to show the admin bar.
 * @return bool
 */
function cp_maybe_hide_admin_bar( $show ) {
	if ( ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	if ( in_array( 'administrator', (array) $user->roles, true ) ) {
		return $show;
	}
	return false;
}
add_filter( 'show_admin_bar', 'cp_maybe_hide_admin_bar' );

function cp_enqueue_assets() {
	// Bootstrap 5 CSS from CDN.
	wp_enqueue_style( 'cp-bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), '5.3.2' );

	// Bootstrap Icons
	wp_enqueue_style( 'cp-bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css', array(), '1.13.1' );

	// Theme stylesheet depends on Bootstrap so it can override styles.
	$theme_style_ver = file_exists( get_stylesheet_directory() . '/style.css' ) ? filemtime( get_stylesheet_directory() . '/style.css' ) : false;
	wp_enqueue_style( 'college-picks-style', get_stylesheet_uri(), array( 'cp-bootstrap-css' ), $theme_style_ver );

	// Bootstrap bundle (includes Popper) in footer.
	wp_enqueue_script( 'cp-bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array(), '5.3.2', true );

	// Theme UI behaviors
	wp_enqueue_script( 'cp-ui', get_stylesheet_directory_uri() . '/assets/js/cp-ui.js', array(), filemtime( get_stylesheet_directory() . '/assets/js/cp-ui.js' ), true );
}

add_action( 'wp_enqueue_scripts', 'cp_enqueue_assets' );

// Enqueue cp-make-picks.js only on the Make Picks page template
function cp_enqueue_make_picks_script() {
	if ( is_page_template( 'page-make-picks.php' ) ) {
		wp_enqueue_script(
			'cp-make-picks',
			get_stylesheet_directory_uri() . '/assets/js/cp-make-picks.js',
			array(),
			filemtime( get_stylesheet_directory() . '/assets/js/cp-make-picks.js' ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'cp_enqueue_make_picks_script' );

/**
 * Add small admin stylesheet for metabox input widths.
 */
function cp_admin_styles() {
	// Register a tiny inline stylesheet handle and attach CSS for metabox inputs.
	wp_register_style( 'cp-admin-inline', false );
	wp_enqueue_style( 'cp-admin-inline' );
	$css = "#cp_game_details input[type='text'] { width:100%; box-sizing:border-box; }";
	wp_add_inline_style( 'cp-admin-inline', $css );
}
add_action( 'admin_enqueue_scripts', 'cp_admin_styles' );

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
	// Prevent picking for games that already have a result (archived)
	if ( $game_id ) {
		$game_result = get_post_meta( $game_id, 'result', true );
		if ( ! empty( $game_result ) ) {
			wp_safe_redirect( add_query_arg( 'pick', 'closed', wp_get_referer() ) );
			exit;
		}
	}
	$choice = isset( $_POST['pick_choice'] ) ? sanitize_text_field( wp_unslash( $_POST['pick_choice'] ) ) : '';
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
		/** returns:
		 * $rows[]  = array(
		 *  'user_id' => $uid,
		 *  'name'    => $name,
		 *  'correct' => $correct,
		 *  'total'   => $total,
		 *  'percent' => $percent,
		 *);
		 */

		if ( empty( $rows ) ) {
			return '<div class="cp-leaderboard"><p>No leaderboard data available for this week.</p></div>';
		}

		ob_start();
		// Render as a dark table for better UX
		echo '<div class="cp-leaderboard" data-week="' . esc_attr( $week ) . '"><h3>Leaderboard</h3>';
		echo '<div class="cp-leaderboard-wrap"><table class="cp-leaderboard-table">';
		echo '<thead><tr><th class="rank">Rank</th><th class="entry">Entry</th><th class="wl">W-L</th><th class="pts">PTS</th><th class="pct">PCT</th><th class="wk">WK</th></tr></thead>';
		echo '<tbody>';
		$count = 0;
		foreach ( $rows as $r ) {
			if ( $count >= $top ) {
				break;
			}
			++$count;
			$rank   = $count;
			$losses = isset( $r['total'] ) && isset( $r['correct'] ) ? intval( $r['total'] ) - intval( $r['correct'] ) : 0;
			// Build a display for W-L and points if available; fallback to placeholders
			$wl  = isset( $r['correct'] ) ? esc_html( $r['correct'] ) . '-' . esc_html( $losses ) : '—';
			$pts = isset( $r['correct'] ) ? intval( $r['correct'] ) : 0;
			$pct = isset( $r['percent'] ) ? esc_html( number_format_i18n( $r['percent'], 1 ) ) : '0';
			$wk  = isset( $week ) ? esc_html( $week ) : '—';

			printf( '<tr><td class="rank">%d</td><td class="entry"><a href="#">%s</a></td><td class="wl">%s</td><td class="pts">%d</td><td class="pct">%s</td><td class="wk">%s</td></tr>', esc_html( $rank ), esc_html( $r['name'] ), $wl, $pts, $pct, $wk );
		}
		echo '</tbody></table></div></div>';
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

/**
 * Format a kickoff_time meta value into a human-readable string.
 * Accepts 'YYYY-MM-DD HH:MM' or other strtotime-friendly formats.
 *
 * @param string $kick Raw kickoff string from post meta.
 * @return string Formatted kickoff like 'Sat, Aug 30 3:30 PM' or empty string.
 */
function cp_format_kickoff( $kick ) {
	if ( empty( $kick ) ) {
		return '';
	}
	// Try strict format first
	$dt = DateTime::createFromFormat( 'Y-m-d H:i', $kick );
	if ( false === $dt ) {
		try {
			$dt = new DateTime( $kick );
		} catch ( Exception $e ) {
			return esc_html( $kick );
		}
	}
	return $dt->format( 'D, M j g:i A' );
}
// --- College Picks Leaderboard Archive & Accumulation Admin Page ---
add_action( 'admin_menu', 'cp_leaderboard_archive_menu' );
function cp_leaderboard_archive_menu() {
	add_menu_page(
		'Leaderboard Archive',
		'Leaderboard Archive',
		'manage_options',
		'cp-leaderboard-archive',
		'cp_leaderboard_archive_page',
		'dashicons-archive',
		30
	);
}

function cp_leaderboard_archive_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle archive action
	if ( isset( $_POST['cp_archive_leaderboard'] ) ) {
		cp_archive_weekly_leaderboard();
		echo '<div class="updated"><p>Weekly leaderboard archived and running tally updated.</p></div>';
	}

	echo '<div class="wrap">';
	echo '<h1>Leaderboard Archive & Running Tally</h1>';
	echo '<form method="post">';
	echo '<input type="submit" name="cp_archive_leaderboard" class="button button-primary" value="Archive This Week & Update Tally">';
	echo '</form>';

	// Display current running tally
	$tally = get_option( 'cp_leaderboard_running_tally', array() );
	if ( ! empty( $tally ) ) {
		echo '<h2>Running Tally</h2>';
		echo '<table class="widefat"><thead><tr><th>User</th><th>Total Points</th></tr></thead><tbody>';
		foreach ( $tally as $user_id => $points ) {
			$user_info    = get_userdata( $user_id );
			$display_name = $user_info ? esc_html( $user_info->display_name ) : 'User ID ' . intval( $user_id );
			echo '<tr><td>' . $display_name . '</td><td>' . intval( $points ) . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>No running tally yet.</p>';
	}

	// Display archive
	$archive = get_option( 'cp_leaderboard_archive', array() );
	if ( ! empty( $archive ) ) {
		echo '<h2>Archived Weeks</h2>';
		foreach ( $archive as $week => $week_data ) {
			echo '<h3>Week ' . esc_html( $week ) . '</h3>';
			echo '<table class="widefat"><thead><tr><th>User</th><th>Points</th></tr></thead><tbody>';
			foreach ( $week_data as $user_id => $points ) {
				$user_info    = get_userdata( $user_id );
				$display_name = $user_info ? esc_html( $user_info->display_name ) : 'User ID ' . intval( $user_id );
				echo '<tr><td>' . $display_name . '</td><td>' . intval( $points ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}
	}
	echo '</div>';
}

/**
 * Archives the current week's leaderboard and updates the running tally.
 * Assumes a function cp_get_current_week_leaderboard() returns [user_id => points] for the week.
 * Assumes a function cp_get_current_week_number() returns the current week number.
 */
function cp_archive_weekly_leaderboard() {
	if ( ! function_exists( 'cp_get_current_week_leaderboard' ) || ! function_exists( 'cp_get_current_week_number' ) ) {
		return;
	}
	$week             = cp_get_current_week_number();
	$week_leaderboard = cp_get_current_week_leaderboard();
	if ( empty( $week_leaderboard ) ) {
		return;
	}

	// Archive this week
	$archive          = get_option( 'cp_leaderboard_archive', array() );
	$archive[ $week ] = $week_leaderboard;
	update_option( 'cp_leaderboard_archive', $archive );

	// Update running tally
	$tally = get_option( 'cp_leaderboard_running_tally', array() );
	foreach ( $week_leaderboard as $user_id => $points ) {
		if ( ! isset( $tally[ $user_id ] ) ) {
			$tally[ $user_id ] = 0;
		}
		$tally[ $user_id ] += $points;
	}
	update_option( 'cp_leaderboard_running_tally', $tally );
}
// --- End College Picks Leaderboard Archive & Accumulation ---
/**
 * Returns an array of [user_id => points] for the current week leaderboard.
 * Points = number of correct picks for the week.
 */
function cp_get_current_week_leaderboard() {
	$week = cp_get_current_week_number();
	if ( empty( $week ) ) {
		return array();
	}
	$rows = cp_get_week_leaderboard( $week ); // $rows[] = [user_id, name, correct, total, percent]
	$out  = array();
	foreach ( $rows as $row ) {
		$out[ $row['user_id'] ] = intval( $row['correct'] );
	}
	return $out;
}

/**
 * Returns the current week number (latest week with a result).
 */
function cp_get_current_week_number() {
	// Find the latest game with a result and a week set
	$games_with_result = get_posts(
		array(
			'post_type'      => 'game',
			'posts_per_page' => 1,
			'meta_query'     => array(

				array(
					'key'     => 'week',
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
		return $week;
	}
	return null;
}

add_theme_support( 'post-thumbnails' );

/**
 * Update cp_team_rank for all teams based on the cached AP Top 25 rankings.
 * Matches by team_id (from cache) to cp_team_team_id (post meta).
 */
function cp_update_team_ranks_from_cache() {
	$cache_key = 'cp_ap_top_25_rankings_results';
	$rankings  = cp_get_data_from_cache( $cache_key );
	if ( empty( $rankings ) ) {
		return;
	}
	$args       = array(
		'post_type'      => 'team',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'meta_query'     => array(
			array(
				'key'     => 'cp_team_team_id',
				'compare' => 'EXISTS',
			),
		),
		'fields'         => 'ids',
	);
	$team_posts = get_posts( $args );
	foreach ( $team_posts as $post_id ) {
		$team_id = get_post_meta( $post_id, 'cp_team_team_id', true );
		if ( ! $team_id ) {
			continue;
		}
		foreach ( $rankings as $row ) {
			if ( isset( $row['team_id'] ) && (string) $row['team_id'] === (string) $team_id ) {
				update_post_meta( $post_id, 'cp_team_rank', isset( $row['rank'] ) ? intval( $row['rank'] ) : '' );
				break;
			}
		}
	}
}
// Add a bulk action to update team ranks from cache on the Team list page
add_filter(
	'bulk_actions-edit-team',
	function ( $bulk_actions ) {
		$bulk_actions['cp_update_team_ranks'] = 'Update Team Ranks';
		return $bulk_actions;
	}
);

// Handle the bulk action
add_filter(
	'handle_bulk_actions-edit-team',
	function ( $redirect_to, $doaction, $post_ids ) {
		if ( $doaction === 'cp_update_team_ranks' ) {
			if ( current_user_can( 'manage_options' ) ) {
				cp_update_team_ranks_from_cache();
				$redirect_to = add_query_arg( 'cp_team_ranks_updated', '1', $redirect_to );
			}
		}
		return $redirect_to;
	},
	10,
	3
);

// Show admin notice after bulk action
add_action(
	'admin_notices',
	function () {
		if ( isset( $_GET['cp_team_ranks_updated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>Team ranks updated from AP Top 25 cache.</p></div>';
		}
	}
);
// Include ESPN Rankings integration.
require_once get_template_directory() . '/espn-rankings.php';
// Include Custom Post Types registration.
require_once get_template_directory() . '/custom_post_types.php';
