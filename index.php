<?php
/**
 * Fallback template: post listing.
 *
 * @package boltfolio
 */

get_header();
?>
<div class="bolt-container">
<header class="archive-header">
	<p class="section-kicker"><?php esc_html_e( 'Blog', 'boltfolio' ); ?></p>
	<h1 class="page-title"><?php esc_html_e( 'Latest posts', 'boltfolio' ); ?></h1>
</header>

<?php if ( have_posts() ) : ?>
	<div class="project-grid">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article class="project-card">
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
				<div class="card-meta">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>

	<div class="pagination">
		<?php
		the_posts_pagination(
			array(
				'mid_size'           => 1,
				'screen_reader_text' => __( 'Posts navigation', 'boltfolio' ),
			)
		);
		?>
	</div>
	<?php
else :
	?>
	<p><?php esc_html_e( 'No posts yet. Check back soon.', 'boltfolio' ); ?></p>
	<?php
endif;
?>
</div><!-- .bolt-container -->

<?php get_footer();
