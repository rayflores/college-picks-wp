<?php
/**
 * Partial: Week archive with dark matchup cards and tabbed navigation.
 *
 * Usage: include locate_template( 'templates/cp-week-archive.php' );
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Gather games and weeks
$games = get_posts(
	array(
		'post_type'      => 'game',
		'posts_per_page' => -1,
		'meta_key'       => 'kickoff_time',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
	)
);
if ( empty( $games ) ) {
	echo '<p>No games found.</p>';
	return;
}

$weeks         = array();
$games_by_week = array();
foreach ( $games as $g ) {
	$w = get_post_meta( $g->ID, 'week', true );
	if ( '' === $w ) {
		$w = '0';
	}
	if ( ! in_array( $w, $weeks, true ) ) {
		$weeks[] = $w;
	}
	if ( ! isset( $games_by_week[ $w ] ) ) {
		$games_by_week[ $w ] = array();
	}
	$games_by_week[ $w ][] = $g;
}

// Sort weeks numerically
usort(
	$weeks,
	function ( $a, $b ) {
		return intval( $a ) - intval( $b );
	}
);

// Determine active week: first week with a kickoff in the future, otherwise last week
$now         = current_time( 'timestamp' );
$active_week = null;
foreach ( $weeks as $wk ) {
	foreach ( $games_by_week[ $wk ] as $g ) {
		$kick = get_post_meta( $g->ID, 'kickoff_time', true );
		if ( ! empty( $kick ) ) {
			$dt = DateTime::createFromFormat( 'Y-m-d H:i', $kick );
			if ( false === $dt ) {
				try {
					$dt = new DateTime( $kick );
				} catch ( Exception $e ) {
					$dt = null;
				}
			}
			if ( $dt ) {
				$ts = $dt->getTimestamp();
				if ( $ts >= $now ) {
					$active_week = $wk;
					break 2;
				}
			}
		}
	}
}
if ( null === $active_week ) {
	$active_week = end( $weeks );
}

// Render tabs
echo '<div class="cp-weeks">';
echo '<div class="cp-week-tabs" role="tablist">';
foreach ( $weeks as $wk ) {
	$label = sprintf( 'Week %s', esc_html( $wk ) );
	$class = ( $wk == $active_week ) ? 'cp-week-tab active' : 'cp-week-tab';
	printf( '<button class="%s" data-week="%s">%s</button>', esc_attr( $class ), esc_attr( $wk ), esc_html( $label ) );
}
echo '</div>';

// Render panels
foreach ( $weeks as $wk ) {
	$panel_class = ( $wk == $active_week ) ? 'cp-week-panel active' : 'cp-week-panel';
	echo '<div class="' . esc_attr( $panel_class ) . '" data-week="' . esc_attr( $wk ) . '">';

	if ( empty( $games_by_week[ $wk ] ) ) {
		echo '<p>No games for this week.</p>';
	} else {
		echo '<div class="cp-matchup-grid">';
		foreach ( $games_by_week[ $wk ] as $g ) {
			$home   = get_post_meta( $g->ID, 'home_team', true );
			$away   = get_post_meta( $g->ID, 'away_team', true );
			$kick   = get_post_meta( $g->ID, 'kickoff_time', true );
			$result = get_post_meta( $g->ID, 'result', true );

			printf( '<div class="cp-matchup-card" data-matchup-id="%d">', intval( $g->ID ) );
			echo '<div class="cp-matchup-header">' . esc_html( strtoupper( date_i18n( 'D n/j', strtotime( $kick ) ) ) ) . ' • ' . esc_html( strtoupper( date_i18n( 'g:i A', strtotime( $kick ) ) ) ) . '</div>';
			echo '<div class="cp-matchup-body">';

			// Home team
			$home_classes = 'cp-matchup-team';
			if ( 'home' === $result ) {
				$home_classes .= ' cp-winner';
			}
			printf( '<div class="%s" data-team="home">', esc_attr( $home_classes ) );
			printf( '<div><div class="team-label">%s</div><div class="team-sub">Home</div></div>', esc_html( $home ) );
			echo '</div>';

			// Away team
			$away_classes = 'cp-matchup-team';
			if ( 'away' === $result ) {
				$away_classes .= ' cp-winner';
			}
			printf( '<div class="%s" data-team="away">', esc_attr( $away_classes ) );
			printf( '<div><div class="team-label">%s</div><div class="team-sub">Away</div></div>', esc_html( $away ) );
			echo '</div>';

			echo '</div>'; // body

			echo '<div class="cp-matchup-footer"><div class="kick">' . esc_html( date_i18n( 'D, M j g:i A', strtotime( $kick ) ) ) . '</div><div class="source">' . esc_html( get_post_meta( $g->ID, 'source', true ) ) . '</div></div>';

			echo '</div>'; // card
		}
		echo '</div>'; // grid
	}

	echo '</div>'; // panel
}

echo '</div>'; // weeks
