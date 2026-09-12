<?php
/**
 * Front page.
 *
 * The hero states the thesis and then proves it: the chart beside the
 * headline is this page measuring its own load in the reader's browser.
 *
 * @package boltfolio
 */

get_header();

$boltfolio_projects_url = (string) get_post_type_archive_link( 'project' );
$boltfolio_docs_url     = (string) get_post_type_archive_link( Boltfolio_Docs::POST_TYPE );
$boltfolio_stats        = boltfolio_stats();
$boltfolio_version      = boltfolio_documented_version();
?>

<section class="hero">
	<div class="shell">
		<div class="hero__grid">

			<div>
				<p class="eyebrow"><?php esc_html_e( 'WordPress Performance Engineer', 'boltfolio' ); ?></p>

				<h1 class="hero__title">
					<?php
					printf(
						/* translators: %s: emphasised word in the headline. */
						esc_html__( 'I make WordPress load %s faster.', 'boltfolio' ),
						'<em>' . esc_html__( 'measurably', 'boltfolio' ) . '</em>'
					);
					?>
				</h1>

				<p class="hero__lede">
					<?php esc_html_e( 'I build the caching layers, asset pipelines and database work that decide whether a WordPress site feels instant. The chart beside this text is not an illustration — it is this page, measuring itself in your browser as it loads.', 'boltfolio' ); ?>
				</p>

				<div class="btn-row hero__actions">
					<?php if ( $boltfolio_projects_url ) : ?>
						<a class="btn" href="<?php echo esc_url( $boltfolio_projects_url ); ?>"><?php esc_html_e( 'See the work', 'boltfolio' ); ?></a>
					<?php endif; ?>
					<a class="btn btn--ghost" href="<?php echo esc_url( $boltfolio_docs_url ); ?>"><?php esc_html_e( 'Read the docs', 'boltfolio' ); ?></a>
				</div>
			</div>

			<figure class="wf" data-waterfall data-state="pending">
				<div class="wf__head">
					<span class="wf__title"><?php esc_html_e( 'Navigation timing', 'boltfolio' ); ?></span>
					<span class="wf__status" data-wf-status><span class="wf__dot" aria-hidden="true"></span><?php esc_html_e( 'measuring', 'boltfolio' ); ?></span>
				</div>

				<ol class="wf__rows" data-wf-rows>
					<?php
					// Server-rendered skeleton: the phase names are real and
					// appear before any script runs, so the figure is legible
					// even if the measurement never arrives.
					$boltfolio_phases = array(
						__( 'Waiting', 'boltfolio' ),
						__( 'Download', 'boltfolio' ),
						__( 'Parse', 'boltfolio' ),
						__( 'First paint', 'boltfolio' ),
					);

					foreach ( $boltfolio_phases as $boltfolio_phase ) :
						?>
						<li class="wf__row" data-empty="true">
							<span class="wf__label"><?php echo esc_html( $boltfolio_phase ); ?></span>
							<span class="wf__track"><span class="wf__bar"></span></span>
							<span class="wf__ms">&mdash;</span>
						</li>
					<?php endforeach; ?>
				</ol>

				<div class="wf__axis" data-wf-axis aria-hidden="true">
					<span>0</span><span>0.25</span><span>0.50</span><span>0.75</span><span>1.0</span>
				</div>

				<p class="wf__fallback">
					<?php esc_html_e( 'Your browser did not expose navigation timing for this page, so the chart cannot be drawn.', 'boltfolio' ); ?>
				</p>

				<figcaption class="wf__cap">
					<strong><?php esc_html_e( 'Measured live.', 'boltfolio' ); ?></strong>
					<?php esc_html_e( 'Every bar is a real phase from the Navigation Timing API for this page load — nothing here is a stock chart.', 'boltfolio' ); ?>
				</figcaption>
			</figure>

		</div>

		<div class="hero__metrics" data-metrics>
			<div class="metric">
				<span class="metric__label"><?php esc_html_e( 'Time to first byte', 'boltfolio' ); ?></span>
				<span class="metric__value" data-metric="ttfb" data-pending>&mdash;</span>
				<span class="metric__note"><?php esc_html_e( 'Server response', 'boltfolio' ); ?></span>
			</div>
			<div class="metric">
				<span class="metric__label"><?php esc_html_e( 'Largest paint', 'boltfolio' ); ?></span>
				<span class="metric__value" data-metric="lcp" data-pending>&mdash;</span>
				<span class="metric__note"><?php esc_html_e( 'LCP, Core Web Vital', 'boltfolio' ); ?></span>
			</div>
			<div class="metric">
				<span class="metric__label"><?php esc_html_e( 'Page weight', 'boltfolio' ); ?></span>
				<span class="metric__value" data-metric="weight" data-pending>&mdash;</span>
				<span class="metric__note"><?php esc_html_e( 'Transferred, compressed', 'boltfolio' ); ?></span>
			</div>
			<div class="metric">
				<span class="metric__label"><?php esc_html_e( 'Requests', 'boltfolio' ); ?></span>
				<span class="metric__value" data-metric="requests" data-pending>&mdash;</span>
				<span class="metric__note"><?php esc_html_e( 'Including this document', 'boltfolio' ); ?></span>
			</div>
		</div>
	</div>
</section>

<?php
$boltfolio_projects = new WP_Query(
	array(
		'post_type'           => 'project',
		'posts_per_page'      => 6,
		'post_status'         => 'publish',
		'orderby'             => array(
			'menu_order' => 'DESC',
			'date'       => 'DESC',
		),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
?>

<?php if ( $boltfolio_projects->have_posts() ) : ?>
	<section class="section" id="work">
		<div class="shell">
			<header class="shead">
				<div class="shead__top">
					<div>
						<p class="eyebrow"><?php esc_html_e( 'Selected work', 'boltfolio' ); ?></p>
						<h2 class="shead__title"><?php esc_html_e( 'Six projects, every repository public', 'boltfolio' ); ?></h2>
					</div>
					<p class="shead__aside">
						<?php
						printf(
							/* translators: %d: number of documented source files. */
							esc_html__( 'A flagship WordPress performance plugin with %d documented source files, plus open-source tooling in Rust, Go and TypeScript.', 'boltfolio' ),
							(int) ( $boltfolio_stats['classes'] ?? 0 )
						);
						?>
					</p>
				</div>
			</header>

			<ul class="index">
				<?php
				$boltfolio_i = 0;

				while ( $boltfolio_projects->have_posts() ) :
					$boltfolio_projects->the_post();
					boltfolio_project_row( (int) get_the_ID(), $boltfolio_i );
					++$boltfolio_i;
				endwhile;

				wp_reset_postdata();
				?>
			</ul>

			<?php if ( $boltfolio_projects_url ) : ?>
				<div class="btn-row mt-2">
					<a class="arrow-link" href="<?php echo esc_url( $boltfolio_projects_url ); ?>"><?php esc_html_e( 'All projects', 'boltfolio' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
<?php endif; ?>

<section class="section" id="capabilities">
	<div class="shell">
		<header class="shead">
			<div class="shead__top">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'Capability', 'boltfolio' ); ?></p>
					<h2 class="shead__title"><?php esc_html_e( 'Where the milliseconds actually go', 'boltfolio' ); ?></h2>
				</div>
				<p class="shead__aside">
					<?php esc_html_e( 'Surface-level settings panels are not optimisation. The work happens in the architecture, and it starts with a profile rather than a guess.', 'boltfolio' ); ?>
				</p>
			</div>
		</header>

		<div class="spec">
			<div class="spec__row">
				<p class="spec__key">
					<b><?php esc_html_e( 'Caching architecture', 'boltfolio' ); ?></b>
					<?php esc_html_e( 'Page · object · edge', 'boltfolio' ); ?>
				</p>
				<p class="spec__val">
					<?php esc_html_e( 'Hand-written <code>object-cache.php</code> and <code>advanced-cache.php</code> drop-ins, Redis connection handling with TLS and clustering, and cache purging that stays correct when content changes underneath it.', 'boltfolio' ); ?>
				</p>
			</div>

			<div class="spec__row">
				<p class="spec__key">
					<b><?php esc_html_e( 'Asset pipeline', 'boltfolio' ); ?></b>
					<?php esc_html_e( 'Minify · combine · defer', 'boltfolio' ); ?>
				</p>
				<p class="spec__val">
					<?php esc_html_e( 'CSS and JavaScript processed with stream reads rather than regex sweeps, so memory stays flat on large files. Per-page unloading removes render-blocking assets from the pages where they do nothing.', 'boltfolio' ); ?>
				</p>
			</div>

			<div class="spec__row">
				<p class="spec__key">
					<b><?php esc_html_e( 'Media and LCP', 'boltfolio' ); ?></b>
					<?php esc_html_e( 'WebP · AVIF · critical CSS', 'boltfolio' ); ?>
				</p>
				<p class="spec__val">
					<?php esc_html_e( 'Conversion pipelines that generate modern formats alongside the original, lazy-loading that knows what is above the fold, and used-CSS extraction that inlines only what the first paint needs.', 'boltfolio' ); ?>
				</p>
			</div>

			<div class="spec__row">
				<p class="spec__key">
					<b><?php esc_html_e( 'Measurement', 'boltfolio' ); ?></b>
					<?php esc_html_e( 'RUM · PageSpeed · profiling', 'boltfolio' ); ?>
				</p>
				<p class="spec__val">
					<?php esc_html_e( 'Real-user monitoring with a first-party beacon so field data is not blocked by ad blockers, PageSpeed Insights pulled into the dashboard, and query-level profiling before any index is touched.', 'boltfolio' ); ?>
				</p>
			</div>

			<div class="spec__row">
				<p class="spec__key">
					<b><?php esc_html_e( 'Build tooling', 'boltfolio' ); ?></b>
					<?php esc_html_e( 'Webpack · block editor', 'boltfolio' ); ?>
				</p>
				<p class="spec__val">
					<?php esc_html_e( 'Custom Gutenberg block suites, asset bundling with code splitting, and build pipelines that keep editor and front-end output byte-identical.', 'boltfolio' ); ?>
				</p>
			</div>
		</div>
	</div>
</section>

<section class="section section--tight" id="plugin">
	<div class="shell">
		<div class="band">
			<p class="eyebrow"><?php esc_html_e( 'Open source', 'boltfolio' ); ?></p>

			<h2 class="band__title"><?php esc_html_e( 'Performance Optimisation is documented down to the method signature', 'boltfolio' ); ?></h2>

			<p class="band__text">
				<?php
				printf(
					/* translators: 1: number of documented source files, 2: number of documentation pages. */
					esc_html__( 'The plugin ships with %1$d source files written up across %2$d reference pages — every class, hook, WP-CLI command and REST route, with the parameter tables and return types you need to extend it without reading the code first.', 'boltfolio' ),
					(int) ( $boltfolio_stats['classes'] ?? 0 ),
					(int) ( $boltfolio_stats['docs'] ?? 0 )
				);
				?>
			</p>

			<div class="btn-row band__foot">
				<a class="btn" href="<?php echo esc_url( $boltfolio_docs_url ); ?>"><?php esc_html_e( 'Read the documentation', 'boltfolio' ); ?></a>
				<a class="btn btn--ghost" href="https://github.com/nilesh32236/performance-optimisation" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Source on GitHub', 'boltfolio' ); ?></a>
				<?php if ( $boltfolio_version ) : ?>
					<span class="meta">v<?php echo esc_html( $boltfolio_version ); ?></span>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<section class="section" id="contact-cta">
	<div class="shell">
		<header class="shead">
			<div class="shead__top">
				<div>
					<p class="eyebrow"><?php esc_html_e( 'Available for work', 'boltfolio' ); ?></p>
					<h2 class="shead__title"><?php esc_html_e( 'Need a faster WordPress site?', 'boltfolio' ); ?></h2>
				</div>
				<p class="shead__aside">
					<?php esc_html_e( 'Performance audits, custom caching work and code-level optimisation. You get the measurements alongside the changes, so the improvement is verifiable rather than asserted.', 'boltfolio' ); ?>
				</p>
			</div>
		</header>

		<div class="btn-row">
			<a class="btn" href="mailto:nilesh.kanzariya912@gmail.com"><?php esc_html_e( 'Email me', 'boltfolio' ); ?></a>
			<a class="btn btn--ghost" href="https://github.com/nilesh32236" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'GitHub', 'boltfolio' ); ?></a>
		</div>
	</div>
</section>

<?php get_footer(); ?>
