<?php
/**
 * Search results template.
 *
 * @package boltfolio
 */

get_header();
?>
<div class="bolt-container">
<header class="archive-header">
	<p class="section-kicker"><?php esc_html_e( 'Search', 'boltfolio' ); ?></p>
	<h1 class="page-title">
		<?php
		printf(
			/* translators: %s: search query. */
			esc_html__( 'Results for “%s”', 'boltfolio' ),
			esc_html( get_search_query() )
		);
		?>
	</h1>
	<div style="margin-top:1.4rem;"><?php get_search_form(); ?></div>
</header>

<?php if ( have_posts() ) : ?>
	<div class="project-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="project-card">
				<span class="badge badge-muted"><?php echo esc_html( get_post_type_object( get_post_type() )->labels->singular_name ); ?></span>
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<?php if ( $wp_query->max_num_pages > 1 ) : ?>
	<div class="pagination">
		<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
	</div>
	<?php endif; ?>
	<?php
else :
	?>
	<p><?php esc_html_e( 'No results found. Try a different search.', 'boltfolio' ); ?></p>
	<?php get_search_form(); ?>
	<?php
endif;
?>
</div><!-- .bolt-container -->

<?php get_footer();
