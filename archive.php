<?php
/**
 * Archive template: project archive, project-type terms, dates and authors.
 *
 * @package boltfolio
 */

get_header();

$is_project_context = is_post_type_archive( 'project' ) || is_tax( Boltfolio_Projects::TAXONOMY );

if ( is_post_type_archive( 'project' ) ) {
	$kicker = __( 'Portfolio', 'boltfolio' );
	$title  = __( 'Projects', 'boltfolio' );
	$desc   = __( 'WordPress plugins and open-source tools — built in public, benchmarked against the best.', 'boltfolio' );
} elseif ( is_tax( Boltfolio_Projects::TAXONOMY ) ) {
	$kicker = __( 'Project type', 'boltfolio' );
	$title  = single_term_title( '', false );
	$desc   = wp_strip_all_tags( term_description() );
} elseif ( is_day() || is_month() || is_year() ) {
	$kicker = __( 'Archive', 'boltfolio' );
	$title  = get_the_archive_title();
	$desc   = '';
} else {
	$kicker = __( 'Archive', 'boltfolio' );
	$title  = get_the_archive_title();
	$desc   = get_the_archive_description();
}
?>
<div class="bolt-container">
<header class="archive-header">
	<p class="section-kicker"><?php echo esc_html( $kicker ); ?></p>
	<h1 class="page-title"><?php echo esc_html( $title ); ?></h1>
	<?php if ( $desc ) : ?>
		<p class="section-desc"><?php echo esc_html( wp_strip_all_tags( $desc ) ); ?></p>
	<?php endif; ?>
</header>

<?php if ( have_posts() ) : ?>
	<div class="project-grid">
		<?php
		while ( have_posts() ) :
			the_post();

			if ( $is_project_context ) {
				boltfolio_project_card();
			} else {
				?>
				<article class="project-card">
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26 ) ); ?></p>
					<div class="card-meta">
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					</div>
				</article>
				<?php
			}
			?>
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
	<p><?php esc_html_e( 'Nothing found in this archive yet.', 'boltfolio' ); ?></p>
	<?php
endif;
?>
</div><!-- .bolt-container -->

<?php get_footer();
