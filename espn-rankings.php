<?php
/**
 * ESPN College Football Rankings Integration
 *
 * Handles fetching, saving, scheduling, and admin UI for AP Top 25 rankings.
 *
 * @package CollegePicks
 */

// API URL.
if ( ! defined( 'RANKINGS_API_URL' ) ) {
	define( 'RANKINGS_API_URL', 'https://site.api.espn.com/apis/site/v2/sports/football/college-football/rankings' );
}
/**
 * Javascript enqueue for admin area (AP Top 25 page only).
 *
 * @param string $hook The current admin page hook.
 */
function cp_enqueue_admin_scripts( $hook ) {
	if ( 'toplevel_page_ap-top-25' !== $hook ) {
		return;
	}
	wp_enqueue_script( 'cp-admin-js', get_template_directory_uri() . '/assets/js/cp-admin.js', array( 'jquery' ), filemtime( get_template_directory() . '/assets/js/cp-admin.js' ), true );
	wp_localize_script(
		'cp-admin-js',
		'cpAPTop25',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cp_fetch_ap_top_25' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'cp_enqueue_admin_scripts' );

/**
 * AJAX handler to fetch and save AP Top 25 rankings.
 */
add_action(
	'wp_ajax_cp_fetch_ap_top_25',
	function () {
		check_ajax_referer( 'cp_fetch_ap_top_25', 'nonce' );
		$rankings = get_ap_top_25_rankings();
		if ( empty( $rankings ) ) {
			wp_send_json_error( array( 'message' => 'No rankings found from API.' ) );
		}
		$response = wp_remote_get( RANKINGS_API_URL );
		$body     = wp_remote_retrieve_body( $response );
		$data     = json_decode( $body, true );
		$week     = $data['latestWeek']['value'] ?? null;
		$season   = $data['latestSeason']['year'] ?? null;
		cp_save_ap_top_25_rankings( $rankings, $week, $season );
		wp_send_json_success(
			array(
				'message' => 'AP Top 25 rankings fetched and saved.',
				'data'    => $data,
			)
		);
	}
);

/**
 * Fetch AP Top 25 rankings from ESPN API.
 *
 * @return array List of teams with all available data points.
 */
function get_ap_top_25_rankings() {
	$response = wp_remote_get( RANKINGS_API_URL );
	if ( is_wp_error( $response ) ) {
		return array();
	}
	$body = wp_remote_retrieve_body( $response );
	$data = json_decode( $body, true );
	if ( ! $data || ! isset( $data['rankings'] ) ) {
		return array();
	}
	foreach ( $data['rankings'] as $ranking ) {
		if ( isset( $ranking['id'] ) && 1 === (int) $ranking['id'] ) { // AP Top 25.
			$teams = array();
			foreach ( $ranking['ranks'] as $team ) {
				$team_info = array(
					'id'               => $team['team']['id'],
					'nickname'         => $team['team']['nickname'] ?? '',
					'name'             => $team['team']['name'] ?? '',
					'abbreviation'     => $team['team']['abbreviation'] ?? '',
					'displayName'      => $team['team']['name'] ?? '',
					'shortDisplayName' => $team['team']['shortDisplayName'] ?? '',
					'location'         => $team['team']['location'] ?? '',
					'color'            => $team['team']['color'] ?? '',
					'logo'             => isset( $team['team']['logos'][0]['href'] ) ? $team['team']['logos'][0]['href'] : '',
					'currentRank'      => $team['current'] ?? null,
					'points'           => $team['points'] ?? null,
					'firstPlaceVotes'  => $team['firstPlaceVotes'] ?? null,
					'record'           => isset( $team['recordSummary'] ) ? $team['recordSummary'] : '',
					'trend'            => $team['trend'] ?? '-',
				);
				$teams[]   = $team_info;
			}
			return $teams;
		}
	}
	return array();
}

/**
 * Create custom table for AP Top 25 rankings on theme activation.
 */
function cp_create_rankings_table() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'ap_top_25_rankings';
	$charset_collate = $wpdb->get_charset_collate();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        team_id bigint(20) NOT NULL,
        team_name varchar(255) NOT NULL,
        team_abbreviation varchar(32) DEFAULT NULL,
        team_display_name varchar(255) DEFAULT NULL,
        team_short_display_name varchar(255) DEFAULT NULL,
        team_location varchar(255) DEFAULT NULL,
        team_color varchar(16) DEFAULT NULL,
        team_logo text DEFAULT NULL,
        `rank` int(11) DEFAULT NULL,
        points int(11) DEFAULT NULL,
        first_place_votes int(11) DEFAULT NULL,
        record varchar(64) DEFAULT NULL,
        week int(11) DEFAULT NULL,
        season int(11) DEFAULT NULL,
        trend varchar(16) DEFAULT NULL,
        last_updated datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY team_id (team_id)
    ) $charset_collate;";
	dbDelta( $sql );
}
add_action( 'admin_init', 'cp_create_rankings_table' );
/**
 * Insert or update AP Top 25 rankings data in custom table.
 *
 * @param array $teams Array of team data from API.
 * @param int   $week Current week.
 * @param int   $season Current season.
 */
function cp_save_ap_top_25_rankings( $teams, $week = null, $season = null ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ap_top_25_rankings';
	update_option( 'cp_current_week', $week );
	foreach ( $teams as $team ) {
		$wpdb->replace(
			$table_name,
			array(
				'team_id'                 => $team['id'],
				'team_name'               => $team['nickname'] ?? $team['name'],
				'team_abbreviation'       => $team['abbreviation'] ?? '',
				'team_display_name'       => $team['name'] ?? '',
				'team_short_display_name' => $team['shortDisplayName'] ?? '',
				'team_location'           => $team['location'] ?? '',
				'team_color'              => $team['color'] ?? '',
				'team_logo'               => $team['logo'] ?? '',
				'rank'                    => $team['currentRank'] ?? null,
				'points'                  => $team['points'] ?? null,
				'first_place_votes'       => $team['firstPlaceVotes'] ?? null,
				'record'                  => $team['record'] ?? '',
				'week'                    => $week,
				'season'                  => $season,
				'trend'                   => $team['trend'] ?? '-',
				'last_updated'            => current_time( 'mysql', 1 ),
			)
		);
	}
}
/**
 * Helper function to cache the data
 *
 * @param string $cache_key Cache key for the data.
 * @return array Cached data.
 */
function cp_get_data_from_cache( $cache_key ) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'ap_top_25_rankings';
	$results    = wp_cache_get( $cache_key, 'college_picks' );
	if ( false === $results ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY `rank` ASC', $table_name ), ARRAY_A );
		wp_cache_set( $cache_key, $results, 'college_picks', 10 * MINUTE_IN_SECONDS );
	}
	return $results;
}

/**
 * Schedule weekly AP Top 25 rankings update (every Monday at 8am EST).
 */
function cp_schedule_ap_top_25_cron() {
	if ( ! wp_next_scheduled( 'cp_update_ap_top_25_rankings' ) ) {
		$tz          = new DateTimeZone( 'America/New_York' );
		$now         = new DateTime( 'now', $tz );
		$next_monday = clone $now;
		$next_monday->modify( 'next monday' );
		$next_monday->setTime( 8, 0, 0 );
		$timestamp = $next_monday->getTimestamp();
		if ( $now->format( 'N' ) == 1 && $now->getTimestamp() < $next_monday->getTimestamp() ) {
			$timestamp = $now->setTime( 8, 0, 0 )->getTimestamp();
		}
		$dt_utc = new DateTime( '@' . $timestamp );
		$dt_utc->setTimezone( new DateTimeZone( 'UTC' ) );
		wp_schedule_event( $dt_utc->getTimestamp(), 'cp_weekly_monday_8am', 'cp_update_ap_top_25_rankings' );
	}
}
add_action( 'wp', 'cp_schedule_ap_top_25_cron' );



add_filter(
	'cron_schedules',
	function ( $schedules ) {
		$schedules['cp_weekly_monday_8am'] = array(
			'interval' => 7 * 24 * 60 * 60,
			'display'  => __( 'Every Monday at 8am EST' ),
		);
		return $schedules;
	}
);

add_action(
	'cp_update_ap_top_25_rankings',
	function () {
		$rankings = get_ap_top_25_rankings();
		if ( ! empty( $rankings ) ) {
			$response = wp_remote_get( RANKINGS_API_URL );
			$body     = wp_remote_retrieve_body( $response );
			$data     = json_decode( $body, true );
			$week     = $data['occurance']['value'] ?? null;
			$season   = $data['season']['year'] ?? null;
			cp_save_ap_top_25_rankings( $rankings, $week, $season );
		}
	}
);

/**
 * Add AP Top 25 admin page to view stored rankings.
 */
add_action(
	'admin_menu',
	function () {
		add_menu_page(
			'AP Top 25',
			'AP Top 25',
			'manage_options',
			'ap-top-25',
			'cp_ap_top_25_admin_page',
			'dashicons-awards',
			25
		);
	}
);
/**
 * Render trend value with arrow icon and color.
 *
 * @param string $trend Trend value (e.g., '+2', '-1', '0', or '-').
 * @return string
 */
function cp_render_trend_icon( $trend ) {
	$trend = trim( (string) $trend );
	if ( '' === $trend || '-' === $trend || '0' === $trend ) {
		return '<span style="color:#888;">&ndash;</span>';
	}
	if ( strpos( $trend, '+' ) === 0 ) {
		$num = ltrim( $trend, '+' );
		return '<span style="color:green;font-weight:bold;">&#9650; ' . esc_html( $trend ) . '</span>';
	}
	if ( strpos( $trend, '-' ) === 0 ) {
		$num = ltrim( $trend, '-' );
		return '<span style="color:red;font-weight:bold;">&#9660; ' . esc_html( $trend ) . '</span>';
	}
	return esc_html( $trend );
}
/**
 * Render the AP Top 25 admin page.
 */
function cp_ap_top_25_admin_page() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'ap_top_25_rankings';
		$cache_key  = 'cp_ap_top_25_rankings_results';
		// Handle cache clear action.
	if ( isset( $_POST['cp_clear_ap_top_25_cache'] ) && check_admin_referer( 'cp_clear_ap_top_25_cache_action', 'cp_clear_ap_top_25_cache_nonce' ) ) {
		// Delete cache.
		wp_cache_delete( $cache_key, 'college_picks' );
		// Truncate table.
		$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', $table_name ) );
		echo '<div class="notice notice-success is-dismissible"><p>Cache cleared and rankings table truncated.</p></div>';
	}

		$results = cp_get_data_from_cache( $cache_key );
		echo '<div class="wrap"><h1>AP Top 25 Rankings</h1>';
		// Cache clear button form
		echo '<form method="post" style="margin-bottom:16px;display:inline-block;">';
		wp_nonce_field( 'cp_clear_ap_top_25_cache_action', 'cp_clear_ap_top_25_cache_nonce' );
		echo '<button type="submit" class="button button-secondary" name="cp_clear_ap_top_25_cache" value="1" onclick="return confirm(\'Are you sure you want to clear the cache and truncate the table?\');">Clear Cache & Truncate Table</button>';
		echo '</form>';
		echo '<button class="button" id="fetch-ap-top-25">Fetch AP Top 25</button>';
		echo '<div id="ap-top-25-status" style="margin-top:10px;"></div>';
		echo '<table class="widefat fixed striped"><thead><tr>';
		echo '<th>Rank</th><th>Logo</th><th>Team Name</th><th>Abbr</th><th>Mascot Name</th><th>Team ID</th><th>Location</th><th>Color</th><th>Points</th><th>Trend</th><th>Record</th><th>Week</th><th>Season</th>';
		echo '</tr></thead><tbody>';
	foreach ( $results as $row ) {
		echo '<tr>';
		echo '<td>' . esc_html( $row['rank'] ) . '</td>';
		echo '<td>';
		if ( $row['team_logo'] ) {
			echo '<img src="' . esc_url( $row['team_logo'] ) . '" alt="logo" style="height:32px;vertical-align:middle;" />';
		}
		echo '</td>';
		echo '<td>' . esc_html( $row['team_name'] ) . '</td>';
		echo '<td>' . esc_html( $row['team_abbreviation'] ) . '</td>';
		echo '<td>' . esc_html( $row['team_display_name'] ) . '</td>';
		echo '<td>' . esc_html( $row['team_id'] ) . '</td>';
		echo '<td>' . esc_html( $row['team_location'] ) . '</td>';
		echo '<td><span style="background:#' . esc_attr( $row['team_color'] ) . ';padding:2px 8px;border-radius:3px;color:#fff;">' . esc_html( $row['team_color'] ) . '</span></td>';
		echo '<td>' . esc_html( $row['points'] ) . '</td>';
		echo '<td>' . wp_kses_post( cp_render_trend_icon( $row['trend'] ) ) . '</td>';
		echo '<td>' . esc_html( $row['record'] ) . '</td>';
		echo '<td>' . esc_html( $row['week'] ) . '</td>';
		echo '<td>' . esc_html( $row['season'] ) . '</td>';
		echo '</tr>';
	}
		echo '</tbody></table></div>';
}
