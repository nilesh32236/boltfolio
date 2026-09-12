<?php
/**
 * Single project template.
 *
 * @package boltfolio
 */

get_header();

while ( have_posts() ) :
	the_post();

	$boltfolio_id     = (int) get_the_ID();
	$boltfolio_github = (string) get_post_meta( $boltfolio_id, 'github_url', true );
	$boltfolio_live   = (string) get_post_meta( $boltfolio_id, 'live_url', true );
	$boltfolio_terms  = get_the_terms( $boltfolio_id, Boltfolio_Projects::TAXONOMY );
	?>

	<article id="project-<?php the_ID(); ?>" <?php post_class(); ?>>
		<div class="shell">

			<header class="page-head">
				<?php
				boltfolio_crumbs(
					array(
						array(
							'label' => __( 'Home', 'boltfolio' ),
							'url'   => home_url( '/' ),
						),
						array(
							'label' => __( 'Work', 'boltfolio' ),
							'url'   => (string) get_post_type_archive_link( 'project' ),
						),
						array( 'label' => get_the_title() ),
					)
				);
				?>

				<h1 class="page-head__title"><?php the_title(); ?></h1>

				<?php if ( get_the_excerpt() ) : ?>
					<p class="page-head__sub"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>

				<?php if ( is_array( $boltfolio_terms ) && $boltfolio_terms ) : ?>
					<div class="page-head__meta">
						<?php foreach ( $boltfolio_terms as $boltfolio_term ) : ?>
							<a class="tag tag--accent" href="<?php echo esc_url( (string) get_term_link( $boltfolio_term ) ); ?>"><?php echo esc_html( $boltfolio_term->name ); ?></a>
						<?php endforeach; ?>
						<span class="tag"><?php echo esc_html( get_the_date( 'Y' ) ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $boltfolio_github || $boltfolio_live ) : ?>
					<div class="btn-row" style="margin-top:1.5rem">
						<?php if ( $boltfolio_github ) : ?>
							<a class="btn" href="<?php echo esc_url( $boltfolio_github ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View source', 'boltfolio' ); ?></a>
						<?php endif; ?>
						<?php if ( $boltfolio_live ) : ?>
							<a class="btn btn--ghost" href="<?php echo esc_url( $boltfolio_live ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Live site', 'boltfolio' ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</header>

			<div class="entry-content prose section">
				<?php the_content(); ?>
			</div>

			<?php
			// If this project has documentation, say so — it is the most
			// useful thing a reader of a plugin page can be sent to.
			$boltfolio_doc_roots = get_posts(
				array(
					'post_type'      => Boltfolio_Docs::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'post_parent'    => 0,
					'fields'         => 'ids',
					's'              => get_the_title(),
				)
			);

			if ( $boltfolio_doc_roots ) :
				?>
				<aside class="band section section--tight">
					<p class="eyebrow"><?php esc_html_e( 'Documentation', 'boltfolio' ); ?></p>
					<h2 class="band__title"><?php esc_html_e( 'This project is documented in full', 'boltfolio' ); ?></h2>
					<p class="band__text"><?php esc_html_e( 'Setup, configuration, hooks, WP-CLI commands and a method-level source reference.', 'boltfolio' ); ?></p>
					<div class="btn-row band__foot">
						<a class="btn" href="<?php echo esc_url( (string) get_permalink( $boltfolio_doc_roots[0] ) ); ?>"><?php esc_html_e( 'Read the documentation', 'boltfolio' ); ?></a>
					</div>
				</aside>
			<?php endif; ?>

			<?php
			$boltfolio_related_args = array(
				'post_type'           => 'project',
				'posts_per_page'      => 3,
				'post__not_in'        => array( $boltfolio_id ),
				'post_status'         => 'publish',
				'orderby'             => 'menu_order date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			);

			if ( is_array( $boltfolio_terms ) && $boltfolio_terms ) {
				$boltfolio_related_args['tax_query'] = array(
					array(
						'taxonomy' => Boltfolio_Projects::TAXONOMY,
						'field'    => 'term_id',
						'terms'    => wp_list_pluck( $boltfolio_terms, 'term_id' ),
					),
				);
			}

			$boltfolio_related = new WP_Query( $boltfolio_related_args );

			if ( ! $boltfolio_related->have_posts() && isset( $boltfolio_related_args['tax_query'] ) ) {
				unset( $boltfolio_related_args['tax_query'] );
				$boltfolio_related = new WP_Query( $boltfolio_related_args );
			}

			if ( $boltfolio_related->have_posts() ) :
				?>
				<section class="section" aria-labelledby="related-title">
					<header class="shead">
						<div class="shead__top">
							<h2 class="shead__title" id="related-title"><?php esc_html_e( 'Other work', 'boltfolio' ); ?></h2>
							<a class="arrow-link" href="<?php echo esc_url( (string) get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'All projects', 'boltfolio' ); ?></a>
						</div>
					</header>

					<ul class="index">
						<?php
						$boltfolio_i = 0;

						while ( $boltfolio_related->have_posts() ) :
							$boltfolio_related->the_post();
							boltfolio_project_row( (int) get_the_ID(), $boltfolio_i );
							++$boltfolio_i;
						endwhile;

						wp_reset_postdata();
						?>
					</ul>
				</section>
				<?php
			endif;
			?>

		</div>
	</article>
	<?php
endwhile;

get_footer();
