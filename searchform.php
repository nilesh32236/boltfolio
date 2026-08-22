<?php
/**
 * Search form.
 *
 * @package boltfolio
 */

?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<input
		type="search"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search projects, posts…', 'boltfolio' ); ?>"
		aria-label="<?php esc_attr_e( 'Search', 'boltfolio' ); ?>"
	>
	<button class="btn btn-primary" type="submit"><?php esc_html_e( 'Search', 'boltfolio' ); ?></button>
</form>
