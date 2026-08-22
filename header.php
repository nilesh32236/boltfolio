<?php
/**
 * Header template.
 *
 * @package boltfolio
 */
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
	<div class="bolt-container bolt-container-wide">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="site-branding" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<?php boltfolio_bolt_mark(); ?>
				<span class="brand-name">Nilesh <em>Kanzariya</em></span>
			</a>
		<?php endif; ?>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
			<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'boltfolio' ); ?></span>
		</button>

		<nav id="site-nav" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary', 'boltfolio' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_class'     => 'primary-menu',
					'container'      => false,
					'depth'          => 1,
				)
			);
			?>
		</nav>
	</div>
</header>

<main id="primary" class="site-main">
