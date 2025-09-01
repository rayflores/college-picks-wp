<?php
/**
 * Template Name: Leaderboard
 */
get_header();
?>
<main>
	<h1>Leaderboard</h1>
	<?php echo do_shortcode( '[college_picks_leaderboard week="1" top="10"]' ); ?>
</main>
<?php
get_footer();
