<?php
/**
 * Fallback template: post listing.
 *
 * @package boltfolio
 */

get_header();
?>

<div class="shell">
	<header class="page-head">
		<p class="eyebrow"><?php esc_html_e( 'Writing', 'boltfolio' ); ?></p>
		<h1 class="page-head__title">
			<?php
			if ( is_home() && ! is_front_page() ) {
				single_post_title();
			} else {
				esc_html_e( 'Notes on performance work', 'boltfolio' );
			}
			?>
		</h1>
		<p class="page-head__sub"><?php esc_html_e( 'Benchmarks, caching decisions and the occasional post-mortem — written up as they happen.', 'boltfolio' ); ?></p>
	</header>

	<?php if ( have_posts() ) : ?>

		<ul class="index section section--tight">
			<?php
			$boltfolio_i = 0;

			while ( have_posts() ) :
				the_post();
				?>
				<li class="index__item">
					<a class="index__link" href="<?php the_permalink(); ?>">
						<span class="index__num"><?php echo esc_html( str_pad( (string) ( $boltfolio_i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
						<span class="index__body">
							<span class="index__title"><?php the_title(); ?></span>
							<span class="index__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></span>
						</span>
						<span class="index__aside">
							<span class="index__meta"><?php echo esc_html( get_the_date() ); ?></span>
							<span class="index__year"><?php echo esc_html( (string) boltfolio_reading_time() ); ?> min</span>
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
			echo '<nav class="pager" aria-label="' . esc_attr__( 'Posts navigation', 'boltfolio' ) . '">';
			echo '<div style="grid-column:1/-1;background:var(--paper);padding:1.1rem 1.25rem">';
			the_posts_pagination( array( 'mid_size' => 1 ) );
			echo '</div></nav>';
		}
		?>

	<?php else : ?>

		<div class="empty section section--tight">
			<p class="empty__title"><?php esc_html_e( 'Nothing published here yet', 'boltfolio' ); ?></p>
			<p class="empty__text">
				<?php esc_html_e( 'There are no posts at this address yet. The documentation is where the writing currently lives — start there.', 'boltfolio' ); ?>
			</p>
			<p class="empty__foot">
				<a class="btn" href="<?php echo esc_url( (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE ) ); ?>"><?php esc_html_e( 'Read the documentation', 'boltfolio' ); ?></a>
			</p>
		</div>

	<?php endif; ?>
</div>

<?php
get_footer();
