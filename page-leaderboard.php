<?php
/**
 * Template Name: Leaderboard
 */
get_header();
?>
<main>
	<h1>Leaderboard</h1>
	<?php echo do_shortcode( '[college_picks_leaderboard]' ); ?>
</main>
<?php
get_footer();
