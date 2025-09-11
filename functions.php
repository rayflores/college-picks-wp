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

	// Fetch teams for dropdowns
	$teams = get_posts(
		array(
			'post_type'      => 'team',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
			'post_status'    => 'publish',
		)
	);
	?>
	<p>
		<label>Home Team:<br>
		<select name="home_team">
			<option value="">-- Select Team --</option>
			<?php foreach ( $teams as $team ) : ?>
				<option value="<?php echo esc_attr( $team->post_title ); ?>" <?php selected( $home, $team->post_title ); ?>><?php echo esc_html( $team->post_title ); ?></option>
			<?php endforeach; ?>
		</select></label>
	</p>
	<p>
		<label>Away Team:<br>
		<select name="away_team">
			<option value="">-- Select Team --</option>
			<?php foreach ( $teams as $team ) : ?>
				<option value="<?php echo esc_attr( $team->post_title ); ?>" <?php selected( $away, $team->post_title ); ?>><?php echo esc_html( $team->post_title ); ?></option>
			<?php endforeach; ?>
		</select></label>
	</p>
	<p>
		<label>Kickoff Time (YYYY-MM-DD HH:MM):<br>
		<input type="text" name="kickoff_time" value="<?php echo esc_attr( $kick ); ?>"></label>
	</p>
	<p>
		<label>Week:<br>
		<input type="text" name="week" value="<?php echo esc_attr( $week ); ?>"></label>
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


/**
 * Seed picks for specific games for all users. Admin-only action.
 */
function cp_seed_picks_for_games() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Insufficient permissions' );
	}
	// optional nonce protection via _wpnonce in query string
	if ( isset( $_GET['_wpnonce'] ) && ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'cp_seed_picks' ) ) {
		wp_die( 'Invalid nonce' );
	}

	$mapping = array(
		5483 => 'home',
		5456 => 'home',
	);

	$user_ids = get_users( array( 'fields' => 'ID' ) );
	$inserted = 0;
	$skipped  = 0;

	foreach ( $user_ids as $uid ) {
		foreach ( $mapping as $game_id => $choice ) {
			$game_id = intval( $game_id );
			if ( ! in_array( $choice, array( 'home', 'away', 'tie' ), true ) ) {
				++$skipped;
				continue;
			}

			$existing = get_posts(
				array(
					'post_type'      => 'pick',
					'author'         => $uid,
					'meta_key'       => 'game_id',
					'meta_value'     => $game_id,
					'posts_per_page' => 1,
					'fields'         => 'ids',
				)
			);
			if ( ! empty( $existing ) ) {
				++$skipped;
				continue;
			}

			$title   = sprintf( 'Pick: user %d - game %d', $uid, $game_id );
			$pick_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_type'   => 'pick',
					'post_status' => 'publish',
					'post_author' => $uid,
				)
			);
			if ( $pick_id && ! is_wp_error( $pick_id ) ) {
				update_post_meta( $pick_id, 'game_id', $game_id );
				update_post_meta( $pick_id, 'pick_choice', sanitize_text_field( $choice ) );
				++$inserted;
			} else {
				++$skipped;
			}
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'cp_seed'  => 'done',
				'inserted' => $inserted,
				'skipped'  => $skipped,
			),
			wp_get_referer() ?: admin_url()
		)
	);
	exit;
}
add_action( 'admin_post_cp_seed_picks', 'cp_seed_picks_for_games' );


/**
 * Add a Tools submenu page to run the seed picks action from the admin.
 */
function cp_add_seed_tool_page() {
	add_management_page(
		__( 'Seed Picks', 'college-picks' ),
		__( 'Seed Picks', 'college-picks' ),
		'manage_options',
		'cp-seed-picks',
		'cp_render_seed_tool_page'
	);
}
add_action( 'admin_menu', 'cp_add_seed_tool_page' );


/**
 * Render the seed picks admin page with a nonce-protected link.
 */
function cp_render_seed_tool_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Insufficient permissions', 'college-picks' ) );
	}

	$url = wp_nonce_url( admin_url( 'admin-post.php?action=cp_seed_picks' ), 'cp_seed_picks' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Seed Picks', 'college-picks' ); ?></h1>
		<p><?php esc_html_e( 'This will create picks for all users for the two historical games (5483 and 5456). Use only once.', 'college-picks' ); ?></p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $url ); ?>" onclick="return confirm('<?php echo esc_js( 'Are you sure you want to seed picks for all users? This cannot be easily undone.' ); ?>');">
				<?php esc_html_e( 'Run Seed Picks', 'college-picks' ); ?>
			</a>
		</p>
	</div>
	<?php
}


/**
 * Modify the admin columns for the 'game' post type.
 *
 * @param array $columns Existing columns for the post list table.
 * @return array Modified columns with 'cp_result' inserted after the title.
 */
function cp_game_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		$new[ $key ] = $label;
		// Insert our column after the Title column.
		if ( 'title' === $key ) {
			$new['cp_result'] = __( 'Result', 'college-picks' );
		}
	}
	return $new;
}
add_filter( 'manage_edit-game_columns', 'cp_game_admin_columns' );

/**
 * Render custom column content for games.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function cp_game_render_custom_column( $column, $post_id ) {
	if ( 'cp_result' !== $column ) {
		return;
	}
	$result = get_post_meta( $post_id, 'result', true );
	$labels = array(
		''     => '—',
		'home' => 'Home team won',
		'away' => 'Away team won',
		'tie'  => 'Tie / Push',
	);
	$out    = isset( $labels[ $result ] ) ? $labels[ $result ] : esc_html( $result );
	echo esc_html( $out );
}
add_action( 'manage_game_posts_custom_column', 'cp_game_render_custom_column', 10, 2 );
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
/**
 * Register custom post type: Team
 */
function cp_register_cpt_team() {
	$labels = array(
		'name'               => 'Teams',
		'singular_name'      => 'Team',
		'add_new'            => 'Add New',
		'add_new_item'       => 'Add New Team',
		'edit_item'          => 'Edit Team',
		'new_item'           => 'New Team',
		'view_item'          => 'View Team',
		'search_items'       => 'Search Teams',
		'not_found'          => 'No teams found',
		'not_found_in_trash' => 'No teams found in Trash',
		'menu_name'          => 'Teams',
	);
	$args   = array(
		'labels'        => $labels,
		'public'        => true,
		'show_ui'       => true,
		'show_in_menu'  => true,
		'menu_position' => 22,
		'menu_icon'     => 'dashicons-groups',
		'supports'      => array( 'title', 'thumbnail', 'custom-fields', 'revisions' ),
		'has_archive'   => false,
		'show_in_rest'  => true,
	);
	register_post_type( 'team', $args );
}
add_action( 'init', 'cp_register_cpt_team' );
add_theme_support( 'post-thumbnails' );

/**
 * Add Rank meta box to Team post type
 */
function cp_team_rank_metabox() {
	add_meta_box(
		'cp_team_rank',
		'Team Rank',
		'cp_team_rank_metabox_cb',
		'team',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'cp_team_rank_metabox' );

function cp_team_rank_metabox_cb( $post ) {
	$rank = get_post_meta( $post->ID, 'cp_team_rank', true );
	echo '<label for="cp_team_rank_field">Rank (number or leave blank):</label>';
	echo '<input type="text" name="cp_team_rank_field" id="cp_team_rank_field" value="' . $rank . '" style="width:100%;" />';
	echo '<label for="cp_team_background_field">Background Color:</label>';
	echo '<input type="text" name="cp_team_background_field" id="cp_team_background_field" value="' . esc_attr( get_post_meta( $post->ID, 'cp_team_background', true ) ) . '" style="width:100%;" />';
	echo '<label for="cp_team_record_field">Team Record:</label>';
	echo '<input type="text" name="cp_team_record_field" id="cp_team_record_field" value="' . esc_attr( get_post_meta( $post->ID, 'cp_team_record', true ) ) . '" style="width:100%;" />';
	echo '<label for="cp_team_team_id_field">Team ID:</label>';
	echo '<input type="text" name="cp_team_team_id_field" id="cp_team_team_id_field" value="' . esc_attr( get_post_meta( $post->ID, 'cp_team_team_id', true ) ) . '" style="width:100%;" />';
}

function cp_save_team_rank_meta( $post_id ) {
	if ( isset( $_POST['cp_team_rank_field'] ) ) {
		update_post_meta( $post_id, 'cp_team_rank', $_POST['cp_team_rank_field'] ? intval( $_POST['cp_team_rank_field'] ) : '' );
	}
	if ( isset( $_POST['cp_team_background_field'] ) ) {
		update_post_meta( $post_id, 'cp_team_background', sanitize_text_field( $_POST['cp_team_background_field'] ) );
	}
	if ( isset( $_POST['cp_team_record_field'] ) ) {
		update_post_meta( $post_id, 'cp_team_record', sanitize_text_field( $_POST['cp_team_record_field'] ) );
	}
	if ( isset( $_POST['cp_team_team_id_field'] ) ) {
		update_post_meta( $post_id, 'cp_team_team_id', sanitize_text_field( $_POST['cp_team_team_id_field'] ) );
	}
}
add_action( 'save_post_team', 'cp_save_team_rank_meta' );

/**
 * Add custom admin column for Team Rank
 *
 * @param array $columns Existing columns.
 * @return array Modified columns.
 */
function cp_team_columns( $columns ) {
	$columns['cp_team_rank'] = 'Rank';
	// place before date.
	$date_column = 'date';
	if ( isset( $columns[ $date_column ] ) ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( $key === $date_column ) {
				$new_columns['cp_team_rank'] = 'Rank';
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}
	return $columns;
}
add_filter( 'manage_team_posts_columns', 'cp_team_columns' );
/**
 * Custom content for Team Rank column.
 *
 * @param string $column  The column name.
 * @param int    $post_id The post ID.
 */
function cp_team_column_content( $column, $post_id ) {
	if ( 'cp_team_rank' === $column ) {
		$rank = get_post_meta( $post_id, 'cp_team_rank', true );
		if ( $rank ) {
			echo esc_html( $rank );
		}
	}
}
add_action( 'manage_team_posts_custom_column', 'cp_team_column_content', 10, 2 );
/**
 * Custom content for Team Rank column.
 *
 * @param string $columns  The column name.
 */
function cp_team_sortable_columns( $columns ) {
	$columns['cp_team_rank'] = 'cp_team_rank';
	return $columns;
}
add_filter( 'manage_edit-team_sortable_columns', 'cp_team_sortable_columns' );

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
