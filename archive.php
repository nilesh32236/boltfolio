<?php
/**
 * Archive template: project archive, project-type terms, dates and authors.
 *
 * @package boltfolio
 */

get_header();

$boltfolio_is_projects = is_post_type_archive( 'project' ) || is_tax( Boltfolio_Projects::TAXONOMY );

if ( is_post_type_archive( 'project' ) ) {
	$boltfolio_kicker = __( 'Portfolio', 'boltfolio' );
	$boltfolio_title  = __( 'Work', 'boltfolio' );
	$boltfolio_desc   = __( 'A WordPress performance plugin and open-source tooling in Rust, Go and TypeScript. Every repository is public, so the claims are checkable.', 'boltfolio' );
} elseif ( is_tax( Boltfolio_Projects::TAXONOMY ) ) {
	$boltfolio_kicker = __( 'Project type', 'boltfolio' );
	$boltfolio_title  = single_term_title( '', false );
	$boltfolio_desc   = wp_strip_all_tags( term_description() );
} else {
	$boltfolio_kicker = __( 'Archive', 'boltfolio' );
	$boltfolio_title  = get_the_archive_title();
	$boltfolio_desc   = wp_strip_all_tags( get_the_archive_description() );
}
?>

<div class="shell">
	<header class="page-head">
		<p class="eyebrow"><?php echo esc_html( $boltfolio_kicker ); ?></p>
		<h1 class="page-head__title"><?php echo esc_html( $boltfolio_title ); ?></h1>

		<?php if ( $boltfolio_desc ) : ?>
			<p class="page-head__sub"><?php echo esc_html( $boltfolio_desc ); ?></p>
		<?php endif; ?>

		<?php if ( $boltfolio_is_projects ) : ?>
			<?php
			$boltfolio_types = get_terms(
				array(
					'taxonomy'   => Boltfolio_Projects::TAXONOMY,
					'hide_empty' => true,
				)
			);

			if ( ! is_wp_error( $boltfolio_types ) && $boltfolio_types ) :
				$boltfolio_current = is_tax( Boltfolio_Projects::TAXONOMY ) ? get_queried_object_id() : 0;
				?>
				<nav class="filters" aria-label="<?php esc_attr_e( 'Filter by project type', 'boltfolio' ); ?>">
					<a href="<?php echo esc_url( (string) get_post_type_archive_link( 'project' ) ); ?>"<?php echo $boltfolio_current ? '' : ' aria-current="true"'; ?>>
						<?php esc_html_e( 'All', 'boltfolio' ); ?>
					</a>
					<?php foreach ( $boltfolio_types as $boltfolio_type ) : ?>
						<a href="<?php echo esc_url( (string) get_term_link( $boltfolio_type ) ); ?>"<?php echo $boltfolio_current === (int) $boltfolio_type->term_id ? ' aria-current="true"' : ''; ?>>
							<?php echo esc_html( $boltfolio_type->name ); ?>
							<span class="nav-count"><?php echo esc_html( (string) $boltfolio_type->count ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<?php if ( $boltfolio_is_projects ) : ?>

			<ul class="index section section--tight">
				<?php
				$boltfolio_i = 0;

				while ( have_posts() ) :
					the_post();

					// Offset keeps numbering continuous across paginated pages.
					boltfolio_project_row( (int) get_the_ID(), $boltfolio_i + ( ( max( 1, (int) get_query_var( 'paged' ) ) - 1 ) * (int) get_query_var( 'posts_per_page' ) ) );
					++$boltfolio_i;
				endwhile;
				?>
			</ul>

		<?php else : ?>

			<ul class="index section section--tight">
				<?php
				$boltfolio_i = 0;

				while ( have_posts() ) :
					the_post();
					?>
					<li class="index__item">
						<a class="index__link" href="<?php the_permalink(); ?>">
							<span class="index__num"><?php echo esc_html( str_pad( (string) ( $boltfolio_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="index__body">
								<span class="index__title"><?php the_title(); ?></span>
								<span class="index__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></span>
							</span>
							<span class="index__aside">
								<span class="index__meta"><?php echo esc_html( get_the_date() ); ?></span>
							</span>
						</a>
					</li>
					<?php
					++$boltfolio_i;
				endwhile;
				?>
			</ul>

		<?php endif; ?>

		<?php
		if ( $wp_query->max_num_pages > 1 ) {
			echo '<nav class="pager" aria-label="' . esc_attr__( 'Pagination', 'boltfolio' ) . '">';
			echo '<div style="grid-column:1/-1;background:var(--paper);padding:1.1rem 1.25rem">';
			the_posts_pagination( array( 'mid_size' => 1 ) );
			echo '</div></nav>';
		}
		?>

	<?php else : ?>

		<div class="empty section section--tight">
			<p class="empty__title"><?php esc_html_e( 'Nothing here yet', 'boltfolio' ); ?></p>
			<p class="empty__text"><?php esc_html_e( 'No projects match this filter. Try another category, or browse everything.', 'boltfolio' ); ?></p>
			<p class="empty__foot">
				<a class="btn btn--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'All projects', 'boltfolio' ); ?></a>
			</p>
		</div>

	<?php endif; ?>
</div>

<?php
get_footer();
