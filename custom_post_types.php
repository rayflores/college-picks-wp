<?php
/**
 * Custom Post Types and related meta boxes for College Picks theme.
 */

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

// Register custom post type: Team
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
			$new['cp_week']   = __( 'Week', 'college-picks' );
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
/**
 * Render custom column content for games.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function cp_game_render_week_column( $column, $post_id ) {
	if ( 'cp_week' !== $column ) {
		return;
	}
	$week = get_post_meta( $post_id, 'week', true );
	$out  = $week ? esc_html( $week ) : '—';
	echo $out;
}
add_action( 'manage_game_posts_custom_column', 'cp_game_render_week_column', 10, 2 );
