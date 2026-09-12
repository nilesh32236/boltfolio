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
		<div class="shell">
			<header class="page-head">
				<?php
				if ( ! is_front_page() ) {
					boltfolio_crumbs(
						array(
							array(
								'label' => __( 'Home', 'boltfolio' ),
								'url'   => home_url( '/' ),
							),
							array( 'label' => get_the_title() ),
						)
					);
				}
				?>

				<h1 class="page-head__title"><?php the_title(); ?></h1>

				<?php if ( get_the_excerpt() && ! is_front_page() ) : ?>
					<p class="page-head__sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</header>

			<div class="entry-content prose section">
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
			// The theme ships no comments.php — never fall back to theme-compat.
			if ( locate_template( 'comments.php' ) && ( comments_open() || get_comments_number() ) ) {
				comments_template();
			}
			?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
