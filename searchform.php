<?php
/**
 * Search form.
 *
 * @package boltfolio
 */

$boltfolio_field_id = 'search-field-' . wp_unique_id();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $boltfolio_field_id ); ?>">
		<?php esc_html_e( 'Search', 'boltfolio' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $boltfolio_field_id ); ?>"
		name="s"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		placeholder="<?php esc_attr_e( 'Search docs, projects, notes…', 'boltfolio' ); ?>"
	>
	<button class="btn" type="submit"><?php esc_html_e( 'Search', 'boltfolio' ); ?></button>
</form>
