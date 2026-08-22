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
	<p class="section-desc" style="margin-inline:auto;"><?php esc_html_e( 'The page you are looking for was moved or never existed. Try a search instead.', 'boltfolio' ); ?></p>

	<div style="display:flex;justify-content:center;margin-top:1.6rem;">
		<?php get_search_form(); ?>
	</div>

	<p style="margin-top:2rem;">
		<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '&larr; Back home', 'boltfolio' ); ?></a>
	</p>
</div>
<?php
get_footer();
