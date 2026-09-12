<?php
/**
 * Header template.
 *
 * @package boltfolio
 */

$boltfolio_is_docs = function_exists( 'boltfolio_is_docs_context' ) && boltfolio_is_docs_context();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#primary"><?php esc_html_e( 'Skip to content', 'boltfolio' ); ?></a>

<header class="site-header">
	<div class="shell site-header__inner">
		<?php
		if ( has_custom_logo() ) {
			the_custom_logo();
		} else {
			boltfolio_branding();
		}
		?>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="<?php esc_attr_e( 'Menu', 'boltfolio' ); ?>">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
		</button>

		<nav id="site-nav" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'boltfolio' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_class'     => 'primary-menu',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => 'boltfolio_primary_menu_fallback',
				)
			);
			?>

			<?php if ( $boltfolio_is_docs ) : ?>
				<button class="nav-search" type="button" data-search-open aria-label="<?php esc_attr_e( 'Search documentation', 'boltfolio' ); ?>">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
					<span><?php esc_html_e( 'Search', 'boltfolio' ); ?></span>
					<kbd><?php echo esc_html( boltfolio_search_shortcut_label() ); ?></kbd>
				</button>
			<?php endif; ?>
		</nav>
	</div>
</header>

<?php
if ( $boltfolio_is_docs ) {
	get_template_part( 'template-parts/search-modal' );
}
?>

<main id="primary" class="site-main">
