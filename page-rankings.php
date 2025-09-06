<?php
/**
 * Template Name: Rankings
 * Description: A template to display the AP Top 25 rankings.
 *
 * @package CollegePicks
 */

get_header();

// Helper for trend icon (reuse from espn-rankings.php if available).
if ( ! function_exists( 'cp_render_trend_icon' ) ) {
	/**
	 * Render a trend icon (up, down, or dash) based on the trend value.
	 *
	 * @param string $trend The trend value (e.g., '+2', '-1', '0', or '-').
	 * @return string HTML for the trend icon.
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
}

$cache_key = 'cp_ap_top_25_rankings_results';
$teams     = cp_get_data_from_cache( $cache_key );

?><style>
.table-dark .logo-cell {
	background-color: var(--bs-table-bg-type, #222);
}
</style>
<div class="container rankings-page my-4">
	<h1 class="mb-4">AP Top 25 Rankings</h1>
	<div class="table-responsive">
		<table class="table table-dark table-striped table-bordered align-middle text-center">
			<thead class="table-dark">
				<tr>
					<th>Rank</th>
					<th>Logo</th>
					<th>Team Name</th>
					<th>Abbr</th>
					<th>Mascot Name</th>
					<th>Points</th>
					<th>Trend</th>
					<th>Record</th>
				</tr>
			</thead>
			<tbody class="table-dark">
<?php
if ( ! empty( $teams ) ) :
	foreach ( $teams as $team ) :
		?>
				<tr data-team-id="<?php echo esc_attr( $team['team_id'] ); ?>">
					<td><?php echo isset( $team['rank'] ) ? esc_html( $team['rank'] ) : ''; ?></td>
					<td class="logo-cell" style="--bs-table-bg-type:#<?php echo esc_attr( $team['team_color'] ); ?>!important;">
						<?php if ( ! empty( $team['team_logo'] ) ) : ?>
							<img src="<?php echo esc_url( $team['team_logo'] ); ?>" alt="<?php echo esc_attr( $team['team_name'] ); ?> logo" style="height:32px;vertical-align:middle;" />
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $team['team_name'] ); ?></td>
					<td><?php echo esc_html( $team['team_abbreviation'] ); ?></td>
					<td><?php echo esc_html( $team['team_display_name'] ); ?></td>
					<td><?php echo esc_html( $team['points'] ); ?></td>
					<td><?php echo wp_kses_post( cp_render_trend_icon( $team['trend'] ?? '-' ) ); ?></td>
					<td><?php echo esc_html( $team['record'] ); ?></td>
				</tr>
		<?php
	endforeach;
else :
	?>
				<tr><td colspan="11">No rankings found.</td></tr>
<?php endif; ?>
			</tbody>
		</table>
	</div>
</div>
<?php get_footer(); ?>
