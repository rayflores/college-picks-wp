<?php
/**
 * Template Name: Make Picks
 *
 * Modern, dark, Bootstrap 5-inspired layout for making picks.
 *
 * @package college-picks
 */

get_header();
?>
<div class="container py-4" style="min-height:80vh;">
<?php
if ( ! is_user_logged_in() ) {
	echo '<div class="wrap"><p>You must <a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">log in</a> to make picks.</p></div>';
	get_footer();
	return;
}

$current_user_id = get_current_user_id();
$week            = get_option( 'cp_current_week' );

// pull games for this week that have not started and have no result yet.
$all_week_games = get_posts(
	array(
		'post_type'      => 'game',
		'posts_per_page' => -1,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'relation' => 'OR',
				array(
					'key'     => 'result',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => 'result',
					'value'   => '',
					'compare' => '=',
				),
			),
			array(
				'key'     => 'week',
				'value'   => strval( $week ),
				'compare' => 'LIKE',
			),
		),
	)
);

$games = $all_week_games;
?>
<div class="bg-dark rounded-4 shadow-sm p-4">
	<h1 class="text-light mb-4">Make Your Picks for week <?php echo esc_html( $week ); ?></h1>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'cp_submit_picks', 'cp_submit_picks_nonce' ); ?>
		<input type="hidden" name="action" value="submit_picks_bulk">
		<div class="row g-4">
		<?php if ( $games ) : ?>
			<?php
			foreach ( $games as $g ) :
				$home        = get_post_meta( $g->ID, 'home_team', true );
				$away        = get_post_meta( $g->ID, 'away_team', true );
				$kick        = get_post_meta( $g->ID, 'kickoff_time', true );
				$existing    = get_posts(
					array(
						'post_type'      => 'pick',
						'author'         => $current_user_id,
						'meta_key'       => 'game_id',
						'meta_value'     => $g->ID,
						'posts_per_page' => 1,
					)
				);
				$user_choice = '';
				if ( ! empty( $existing ) ) {
					$user_choice = get_post_meta( $existing[0]->ID, 'pick_choice', true );
				}

				// Get team objects by name (title).
				if ( ! function_exists( 'post_exists' ) ) {
					require_once ABSPATH . 'wp-admin/includes/post.php';
				}
				$home_team_id  = post_exists( $home, '', '', 'team' );
				$home_team_obj = get_post( $home_team_id );
				$away_team_id  = post_exists( $away, '', '', 'team' );
				$away_team_obj = get_post( $away_team_id );
				$home_logo     = $home_team_obj ? get_the_post_thumbnail_url( $home_team_obj->ID, 'thumbnail' ) : '';
				$away_logo     = $away_team_obj ? get_the_post_thumbnail_url( $away_team_obj->ID, 'thumbnail' ) : '';
				$home_rank     = $home_team_obj ? get_post_meta( $home_team_obj->ID, 'cp_team_rank', true ) : 'NR';
				$home_record   = $home_team_obj ? get_post_meta( $home_team_obj->ID, 'cp_team_record', true ) : '0-0';
				$away_rank     = $away_team_obj ? get_post_meta( $away_team_obj->ID, 'cp_team_rank', true ) : 'NR';
				$away_record   = $away_team_obj ? get_post_meta( $away_team_obj->ID, 'cp_team_record', true ) : '0-0';

				$dt = $kick ? DateTime::createFromFormat( 'Y-m-d H:i', $kick ) : false;
				if ( ! $dt && $kick ) {
					try {
						$dt = new DateTime( $kick );
					} catch ( Exception $e ) {
						$dt = false;
					}
				}
				$kick_str = $dt ? $dt->format( 'D n/j • g:i A' ) : esc_html( $kick );
				?>
			<div class="col-12">
				<div class="card mb-4 shadow-sm bg-dark rounded-4 border-secondary">
					<div class="card-body p-0">
						<div class="d-flex justify-content-between align-items-center px-4 pt-3">
							<span class="badge bg-secondary fs-6 px-3 py-2"><?php echo esc_html( $kick_str ); ?></span>
						</div>
						<div class="d-flex align-items-stretch justify-content-between px-4 py-4 col-12">
							<!-- Home team block -->
							<div class="card col-5 rounded-4 bg-dark border-secondary">
								<div class="card-header text-center fw-bold border-secondary bg-light">
									<?php if ( $home_logo ) : ?>
										<img class="card-img-top" src="<?php echo esc_url( $home_logo ); ?>" alt="<?php echo esc_attr( $home ); ?> logo" style="width:64px; height:64px; object-fit:contain; background:#ffffff; margin-right:auto;margin-left:auto;">
									<?php else : ?>
										<div style="width:64px; height:64px; background:#333; border-radius:12px; margin-right:18px;"></div>
									<?php endif; ?>
								</div>
								<div class="card-body d-flex flex-column align-items-center">
									<div class="card-title fw-bold text-light text-center" style="font-size:1.4rem;">
										<?php echo esc_html( $home ); ?>
									</div>
									<div class="text-info small text-center">( <?php echo esc_html( $home_record ); ?> )</div>
									<?php if ( $home_rank > 0 ) : ?>
										<div class="text-info small text-center">Rank: <?php echo intval( $home_rank ); ?></div>
									<?php else : ?>
										<div class="text-info small text-center">Rank: NR</div>
									<?php endif; ?>
								</div>
								<div class="card-footer bg-transparent border-0 text-center">
									<label class="btn btn-outline-info w-100 mb-0 
									<?php
									if ( 'home' === $user_choice ) {
										echo 'active';
									}
									?>
									">
										<input type="radio" class="btn-check" name="cp_picks[<?php echo intval( $g->ID ); ?>]" value="home" autocomplete="off" <?php checked( $user_choice, 'home' ); ?>>
										Pick <?php echo esc_html( $home ); ?>
									</label>
								</div>
							</div>
							<!-- Away team block -->
							<div class="card col-5 rounded-4 bg-dark border-secondary">
								<div class="card-header text-center fw-bold border-secondary bg-light">
									<?php if ( $away_logo ) : ?>
										<img class="card-img-top" src="<?php echo esc_url( $away_logo ); ?>" alt="<?php echo esc_attr( $away ); ?> logo" style="width:64px; height:64px; object-fit:contain; background:#ffffff; margin-right:auto;margin-left:auto;">
									<?php else : ?>
										<div style="width:64px; height:64px; background:#333; border-radius:12px; margin-right:18px;"></div>
									<?php endif; ?>
								</div>
								<div class="card-body d-flex flex-column align-items-center">
									<div class="card-title fw-bold text-light text-center" style="font-size:1.4rem;">
										<?php echo esc_html( $away ); ?>
									</div>
									<div class="text-info small text-center">( <?php echo esc_html( $away_record ); ?> )</div>
									<?php if ( $away_rank > 0 ) : ?>
										<div class="text-info small text-center">Rank: <?php echo intval( $away_rank ); ?></div>
									<?php else : ?>
										<div class="text-info small text-center">Rank: NR</div>
									<?php endif; ?>
								</div>
								<div class="card-footer bg-transparent border-0 text-center">
									<label class="btn btn-outline-info w-100 mb-0 
									<?php
									if ( 'away' === $user_choice ) {
										echo 'active';
									}
									?>
									">
										<input type="radio" class="btn-check" name="cp_picks[<?php echo intval( $g->ID ); ?>]" value="away" autocomplete="off" <?php checked( $user_choice, 'away' ); ?>>
										Pick <?php echo esc_html( $away ); ?>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="text-light">No games available.</div>
		<?php endif; ?>
		</div>
		<div class="mt-4 text-end">
			<button class="btn btn-primary btn-lg px-5 py-2 rounded-3 fw-bold" type="submit">Save all picks</button>
		</div>
	</form>
</div>
<script src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/js/cp-make-picks.js' ); ?>?v=<?php echo filemtime( get_stylesheet_directory() . '/assets/js/cp-make-picks.js' ); ?>" defer></script>
<?php get_footer(); ?>
