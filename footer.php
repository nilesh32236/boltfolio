<?php
/**
 * Footer template.
 *
 * @package boltfolio
 */

?>
</main><!-- #primary -->

<footer class="site-footer">
	<div class="bolt-container bolt-container-wide">
		<div class="footer-inner">
			<div class="footer-nav">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'menu_class'     => 'footer-menu',
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</div>
			<div class="footer-socials">
				<?php boltfolio_social_icons(); ?>
			</div>
		</div>
		<p class="copyright">
			&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Nilesh Kanzariya
			<span aria-hidden="true"> &middot; </span>
			<?php esc_html_e( 'Built with WordPress and optimized by hand.', 'boltfolio' ); ?>
			<span class="heart" aria-hidden="true">&#9889;</span>
		</p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
