<?php
/**
 * Footer template.
 *
 * @package boltfolio
 */

$boltfolio_stats    = function_exists( 'boltfolio_stats' ) ? boltfolio_stats() : array( 'docs' => 0, 'projects' => 0 );
$boltfolio_docs_url = (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE );
$boltfolio_work_url = (string) get_post_type_archive_link( 'project' );
?>
</main><!-- #primary -->

<footer class="site-footer">
	<div class="shell">
		<div class="footer__grid">

			<div>
				<p class="footer__title"><?php bloginfo( 'name' ); ?></p>
				<p class="footer__text">
					<?php esc_html_e( 'WordPress performance engineering — page caching, object caching, asset pipelines and the measurement work that proves they helped.', 'boltfolio' ); ?>
				</p>
			</div>

			<div>
				<p class="footer__head"><?php esc_html_e( 'Index', 'boltfolio' ); ?></p>
				<ul class="footer__list">
					<li><a href="<?php echo esc_url( $boltfolio_work_url ); ?>"><?php esc_html_e( 'Work', 'boltfolio' ); ?></a></li>
					<li><a href="<?php echo esc_url( $boltfolio_docs_url ); ?>"><?php esc_html_e( 'Documentation', 'boltfolio' ); ?></a></li>
					<?php
					foreach ( array( 'about', 'contact' ) as $boltfolio_slug ) :
						$boltfolio_page = get_page_by_path( $boltfolio_slug );

						if ( ! $boltfolio_page instanceof WP_Post ) {
							continue;
						}
						?>
						<li><a href="<?php echo esc_url( (string) get_permalink( $boltfolio_page ) ); ?>"><?php echo esc_html( get_the_title( $boltfolio_page ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>

			<div>
				<p class="footer__head"><?php esc_html_e( 'Elsewhere', 'boltfolio' ); ?></p>
				<ul class="footer__list">
					<?php foreach ( boltfolio_social_links() as $boltfolio_link ) : ?>
						<li>
							<a href="<?php echo esc_url( $boltfolio_link['url'] ); ?>"<?php echo str_starts_with( $boltfolio_link['url'], 'mailto:' ) ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>>
								<?php echo esc_html( $boltfolio_link['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>

		</div>

		<div class="footer__bottom">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></p>
			<p>
				<?php
				printf(
					/* translators: 1: number of documented source files, 2: number of documentation pages. */
					esc_html__( '%1$d source files documented across %2$d pages', 'boltfolio' ),
					(int) ( $boltfolio_stats['classes'] ?? 0 ),
					(int) ( $boltfolio_stats['docs'] ?? 0 )
				);
				?>
			</p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
