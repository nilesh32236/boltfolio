<?php
/**
 * 404 template.
 *
 * @package boltfolio
 */

get_header();
?>
<div class="error-404">
	<p class="error-code">404</p>
	<h1 class="page-title"><?php esc_html_e( 'Page not found', 'boltfolio' ); ?></h1>
	<p class="section-desc error-desc"><?php esc_html_e( 'The page you are looking for was moved or never existed. Try a search instead.', 'boltfolio' ); ?></p>

	<div class="error-search">
		<?php get_search_form(); ?>
	</div>

	<p class="error-back">
		<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">&larr; <?php esc_html_e( 'Back home', 'boltfolio' ); ?></a>
	</p>
</div>
<?php
get_footer();
