<?php
/**
 * 404 template.
 *
 * A wrong turn is a navigation problem, so this offers the three places
 * a visitor was most likely heading rather than a dead end.
 *
 * @package boltfolio
 */

get_header();
?>

<div class="shell nf">
	<p class="nf__code" aria-hidden="true">404</p>
	<h1 class="nf__title"><?php esc_html_e( 'That page is not here', 'boltfolio' ); ?></h1>
	<p class="nf__text">
		<?php esc_html_e( 'The address you followed does not match anything on this site. It may have moved, or the link that brought you here may be out of date.', 'boltfolio' ); ?>
	</p>

	<div class="btn-row" style="margin-top:2rem">
		<a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'boltfolio' ); ?></a>
		<a class="btn btn--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( 'project' ) ); ?>"><?php esc_html_e( 'See the work', 'boltfolio' ); ?></a>
		<a class="btn btn--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE ) ); ?>"><?php esc_html_e( 'Read the docs', 'boltfolio' ); ?></a>
	</div>

	<?php if ( function_exists( 'boltfolio_is_docs_context' ) ) : ?>
		<div class="measure section section--tight">
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
