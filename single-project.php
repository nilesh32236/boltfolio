<?php
/**
 * Single project template.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();

	$github = get_post_meta( get_the_ID(), 'github_url', true );
	$live   = get_post_meta( get_the_ID(), 'live_url', true );
	?>
	<article id="project-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="bolt-container">
			<a class="back-link" href="<?php echo esc_url( get_post_type_archive_link( 'project' ) ?: home_url( '/' ) ); ?>">
				&larr; <?php esc_html_e( 'All projects', 'boltfolio' ); ?>
			</a>

			<header class="project-hero">
				<?php boltfolio_term_badges(); ?>
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( get_the_excerpt() ) : ?>
					<p class="entry-sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<div class="project-hero-actions">
					<?php if ( $github ) : ?>
						<a class="btn btn-primary" href="<?php echo esc_url( $github ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'View source on GitHub', 'boltfolio' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $live ) : ?>
						<a class="btn btn-ghost" href="<?php echo esc_url( $live ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Live site', 'boltfolio' ); ?> ↗
						</a>
					<?php endif; ?>
				</div>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="featured-media"><?php the_post_thumbnail( 'large' ); ?></figure>
			<?php endif; ?>

			<div class="entry-content">
				<?php the_content(); ?>
			</div>

			<?php
			// Related projects: same primary term, else latest others.
			$related_args = array(
				'post_type'           => 'project',
				'posts_per_page'      => 3,
				'post__not_in'        => array( get_the_ID() ),
				'post_status'         => 'publish',
				'orderby'             => 'rand',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			);

			$terms = get_the_terms( get_the_ID(), Boltfolio_Projects::TAXONOMY );
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$related_args['tax_query'] = array(
					array(
						'taxonomy' => Boltfolio_Projects::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => wp_list_pluck( $terms, 'term_id' ),
					),
				);
			}

			$related = new WP_Query( $related_args );
			if ( $related->have_posts() ) :
				?>
				<section class="section section-alt" style="margin-top:3rem;border-radius:14px;">
					<h2 class="section-title" style="margin-bottom:1.4rem;font-size:1.3rem;"><?php esc_html_e( 'More projects', 'boltfolio' ); ?></h2>
					<div class="project-grid">
						<?php
						while ( $related->have_posts() ) :
							$related->the_post();
							boltfolio_project_card();
						endwhile;
						wp_reset_postdata();
						?>
					</div>
				</section>
				<?php
			endif;
			?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
