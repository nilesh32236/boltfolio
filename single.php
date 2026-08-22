<?php
/**
 * Single post template.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="bolt-container">
			<header class="entry-header">
				<?php boltfolio_breadcrumbs(); ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<div class="entry-meta">
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="featured-media"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>

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

			<nav class="post-nav">
				<?php
				previous_post_link( '<span>&larr; %link</span>' );
				next_post_link( '<span>%link &rarr;</span>' );
				?>
			</nav>
		</div>
	</article>
	<?php
endwhile;

get_footer();
