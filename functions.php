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
	$home = get_post_meta( $post->ID, 'home_team', true );
	$away = get_post_meta( $post->ID, 'away_team', true );
	$kick = get_post_meta( $post->ID, 'kickoff_time', true );
	$week = get_post_meta( $post->ID, 'week', true );
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

// Leaderboard shortcode: counts picks per user
function cp_leaderboard_shortcode( $atts ) {
	$picks  = get_posts(
		array(
			'post_type'      => 'pick',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		)
	);
	$scores = array();
	foreach ( $picks as $p ) {
		$author = $p->post_author;
		if ( ! isset( $scores[ $author ] ) ) {
			$scores[ $author ] = 0;
		}
		++$scores[ $author ];
	}
	// convert to array of arrays with display name
	$rows = array();
	foreach ( $scores as $user_id => $count ) {
		$user   = get_userdata( $user_id );
		$rows[] = array(
			'name'  => $user ? $user->display_name : 'User ' . $user_id,
			'count' => $count,
		);
	}
	usort(
		$rows,
		function ( $a, $b ) {
			return $b['count'] - $a['count'];
		}
	);
	ob_start();
	echo '<div class="cp-leaderboard"><h3>Leaderboard</h3><ol>';
	foreach ( $rows as $r ) {
		echo '<li>' . esc_html( $r['name'] ) . ' — ' . intval( $r['count'] ) . ' picks</li>';
	}
	echo '</ol></div>';
	return ob_get_clean();
}
add_shortcode( 'college_picks_leaderboard', 'cp_leaderboard_shortcode' );
