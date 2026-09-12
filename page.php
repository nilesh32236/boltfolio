<?php
/**
 * Static page template.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="bolt-container">
			<header class="entry-header">
				<?php if ( ! is_front_page() ) : ?>
					<?php boltfolio_breadcrumbs(); ?>
				<?php endif; ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( '' !== trim( (string) get_post()->post_excerpt ) && ! is_front_page() ) : ?>
					<p class="entry-sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</header>

			<div class="entry-content">
				<?php
				the_content();
				wp_link_pages(
					array(
						'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'boltfolio' ),
						'after'  => '</div>',
					)
				);
				?>
			</div>

			<?php
			// Theme ships no comments.php — never fall back to deprecated theme-compat.
			if ( locate_template( 'comments.php' ) && ( comments_open() || get_comments_number() ) ) {
				comments_template();
			}
			?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
