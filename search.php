<?php
/**
 * Search results template.
 *
 * @package boltfolio
 */

get_header();
?>

<div class="shell">
	<header class="page-head">
		<p class="eyebrow"><?php esc_html_e( 'Search', 'boltfolio' ); ?></p>
		<h1 class="page-head__title">
			<?php
			printf(
				/* translators: %s: search query. */
				esc_html__( 'Results for “%s”', 'boltfolio' ),
				esc_html( get_search_query() )
			);
			?>
		</h1>

		<?php if ( have_posts() ) : ?>
			<p class="page-head__sub">
				<?php
				printf(
					/* translators: %d: number of results. */
					esc_html__( '%d results across the site.', 'boltfolio' ),
					(int) $wp_query->found_posts
				);
				?>
			</p>
		<?php endif; ?>

		<div style="max-width:26rem;margin-top:1.5rem">
			<?php get_search_form(); ?>
		</div>
	</header>

	<?php if ( have_posts() ) : ?>

		<ul class="index section section--tight">
			<?php
			$boltfolio_i = 0;

			while ( have_posts() ) :
				the_post();

				$boltfolio_type = get_post_type_object( get_post_type() );
				?>
				<li class="index__item">
					<a class="index__link" href="<?php the_permalink(); ?>">
						<span class="index__num"><?php echo esc_html( str_pad( (string) ( $boltfolio_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="index__body">
							<span class="index__title"><?php the_title(); ?></span>
							<span class="index__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></span>
							<?php if ( $boltfolio_type ) : ?>
								<span class="index__tags">
									<span class="tag"><?php echo esc_html( $boltfolio_type->labels->singular_name ); ?></span>
								</span>
							<?php endif; ?>
						</span>
						<span class="index__aside">
							<span class="index__meta"><?php echo esc_html( get_the_date() ); ?></span>
						</span>
					</a>
				</li>
				<?php
				++$boltfolio_i;
			endwhile;
			?>
		</ul>

		<?php
		if ( $wp_query->max_num_pages > 1 ) {
			echo '<nav class="pager" aria-label="' . esc_attr__( 'Search results navigation', 'boltfolio' ) . '">';
			echo '<div style="grid-column:1/-1;background:var(--paper);padding:1.1rem 1.25rem">';
			the_posts_pagination( array( 'mid_size' => 1 ) );
			echo '</div></nav>';
		}
		?>

	<?php else : ?>

		<div class="empty section section--tight">
			<p class="empty__title"><?php esc_html_e( 'Nothing matched that search', 'boltfolio' ); ?></p>
			<p class="empty__text">
				<?php esc_html_e( 'Try a shorter query, or a term you would expect to see in the page itself — a class name, a hook, or a setting.', 'boltfolio' ); ?>
			</p>
			<p class="empty__foot">
				<a class="btn btn--ghost" href="<?php echo esc_url( (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE ) ); ?>"><?php esc_html_e( 'Browse the documentation', 'boltfolio' ); ?></a>
			</p>
		</div>

	<?php endif; ?>
</div>

<?php
get_footer();
