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
		<div class="shell shell--narrow">
			<header class="page-head">
				<?php
				boltfolio_crumbs(
					array(
						array(
							'label' => __( 'Home', 'boltfolio' ),
							'url'   => home_url( '/' ),
						),
						array(
							'label' => __( 'Writing', 'boltfolio' ),
							'url'   => (string) get_permalink( (int) get_option( 'page_for_posts' ) ),
						),
						array( 'label' => get_the_title() ),
					)
				);
				?>

				<h1 class="page-head__title"><?php the_title(); ?></h1>

				<?php if ( get_the_excerpt() ) : ?>
					<p class="page-head__sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="page-head__meta">
					<span class="tag"><?php echo esc_html( get_the_date() ); ?></span>
					<span class="tag"><?php echo esc_html( (string) boltfolio_reading_time() ); ?> <?php esc_html_e( 'min read', 'boltfolio' ); ?></span>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="featured-media"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>

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
			$boltfolio_prev = get_previous_post();
			$boltfolio_next = get_next_post();

			if ( $boltfolio_prev || $boltfolio_next ) :
				?>
				<nav class="pager" aria-label="<?php esc_attr_e( 'Posts', 'boltfolio' ); ?>">
					<?php if ( $boltfolio_prev ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $boltfolio_prev ) ); ?>">
							<span class="pager__label">&larr; <?php esc_html_e( 'Previous', 'boltfolio' ); ?></span>
							<span class="pager__title"><?php echo esc_html( get_the_title( $boltfolio_prev ) ); ?></span>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>

					<?php if ( $boltfolio_next ) : ?>
						<a class="pager__next" href="<?php echo esc_url( (string) get_permalink( $boltfolio_next ) ); ?>">
							<span class="pager__label"><?php esc_html_e( 'Next', 'boltfolio' ); ?> &rarr;</span>
							<span class="pager__title"><?php echo esc_html( get_the_title( $boltfolio_next ) ); ?></span>
						</a>
					<?php else : ?>
						<span></span>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
