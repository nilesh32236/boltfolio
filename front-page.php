<?php
/**
 * Front page: hero, featured projects, skills, approach and contact CTA.
 *
 * @package boltfolio
 */

get_header();

$projects_url = get_post_type_archive_link( 'project' );
?>

<section class="hero">
	<div class="bolt-container">
		<p class="hero-kicker"><?php esc_html_e( 'WordPress Performance Engineer', 'boltfolio' ); ?></p>
		<h1 class="hero-title">Nilesh <span class="accent">Kanzariya</span></h1>
		<p class="hero-tagline">
			<?php esc_html_e( 'I build fast WordPress sites and open-source tooling — from custom object-cache drop-ins and stream-based minification to Rust MCP servers and AI-powered code review.', 'boltfolio' ); ?>
		</p>
		<div class="hero-actions">
			<?php if ( $projects_url ) : ?>
				<a class="btn btn-primary" href="<?php echo esc_url( $projects_url ); ?>"><?php esc_html_e( 'View Projects', 'boltfolio' ); ?></a>
			<?php endif; ?>
			<a class="btn btn-ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Get in Touch', 'boltfolio' ); ?></a>
		</div>
		<div class="hero-socials">
			<?php boltfolio_social_icons(); ?>
		</div>
	</div>
</section>

<?php
$featured = new WP_Query(
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
<section class="section section-alt" id="projects">
	<div class="bolt-container">
		<div class="section-head">
			<p class="section-kicker"><?php esc_html_e( 'Selected Work', 'boltfolio' ); ?></p>
			<h2 class="section-title"><?php esc_html_e( 'Projects built for speed', 'boltfolio' ); ?></h2>
			<p class="section-desc"><?php esc_html_e( 'Flagship WordPress performance engineering and open-source experiments — every repo is public.', 'boltfolio' ); ?></p>
		</div>

		<?php if ( $featured->have_posts() ) : ?>
			<div class="project-grid">
				<?php
				while ( $featured->have_posts() ) :
					$featured->the_post();
					boltfolio_project_card();
				endwhile;
				wp_reset_postdata();
				?>
			</div>

			<?php if ( $projects_url ) : ?>
				<div class="section-foot">
					<a class="btn btn-ghost" href="<?php echo esc_url( $projects_url ); ?>"><?php esc_html_e( 'Browse all projects', 'boltfolio' ); ?> &rarr;</a>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Projects are being prepared. Check back soon.', 'boltfolio' ); ?></p>
		<?php endif; ?>
	</div>
</section>

<section class="section" id="skills">
	<div class="bolt-container">
		<div class="section-head">
			<p class="section-kicker"><?php esc_html_e( 'Technical Skills', 'boltfolio' ); ?></p>
			<h2 class="section-title"><?php esc_html_e( 'Deep, code-level optimization', 'boltfolio' ); ?></h2>
		</div>

		<div class="skills-grid">
			<div class="skill-card">
				<h3><?php esc_html_e( 'Advanced Caching Architecture', 'boltfolio' ); ?></h3>
				<p><?php esc_html_e( 'Custom caching layers built from the ground up, including hand-written object-cache drop-ins that cut database load and drop Time to First Byte.', 'boltfolio' ); ?></p>
			</div>
			<div class="skill-card">
				<h3><?php esc_html_e( 'Stream-Based Minification', 'boltfolio' ); ?></h3>
				<p><?php esc_html_e( 'CSS and JavaScript minification engineered around efficient stream reads instead of naive regex passes — better memory use and faster processing at scale.', 'boltfolio' ); ?></p>
			</div>
			<div class="skill-card">
				<h3><?php esc_html_e( 'Per-Page Asset Control', 'boltfolio' ); ?></h3>
				<p><?php esc_html_e( 'Granular exclusion logic to unload individual scripts and styles on any page, eliminating render-blocking resources where they do nothing but hurt.', 'boltfolio' ); ?></p>
			</div>
			<div class="skill-card">
				<h3><?php esc_html_e( 'Media &amp; LCP Optimization', 'boltfolio' ); ?></h3>
				<p><?php esc_html_e( 'Enhanced lazy-loading strategies for images and heavy embeds that shrink initial page weight and pull Largest Contentful Paint into shape.', 'boltfolio' ); ?></p>
			</div>
			<div class="skill-card">
				<h3><?php esc_html_e( 'Webpack Build Pipelines', 'boltfolio' ); ?></h3>
				<p><?php esc_html_e( 'Modern asset bundling and workflow automation for themes and plugins — optimized bundles, code splitting and clean CSS output.', 'boltfolio' ); ?></p>
			</div>
			<div class="skill-card">
				<h3><?php esc_html_e( 'Profiling &amp; Query Tuning', 'boltfolio' ); ?></h3>
				<p><?php esc_html_e( 'Backend debugging with Code Profiler: bottleneck hunts, database query audits and targeted fixes instead of guesswork.', 'boltfolio' ); ?></p>
			</div>
		</div>
	</div>
</section>

<section class="section section-alt" id="approach">
	<div class="bolt-container">
		<div class="section-head">
			<p class="section-kicker"><?php esc_html_e( 'Development Approach', 'boltfolio' ); ?></p>
			<h2 class="section-title"><?php esc_html_e( 'Benchmark-driven, not plugin-config-driven', 'boltfolio' ); ?></h2>
			<p class="section-desc"><?php esc_html_e( 'Surface-level settings panels are not optimization. The work happens at the architecture level.', 'boltfolio' ); ?></p>
		</div>

		<ul class="approach-list">
			<li>
				<span><strong><?php esc_html_e( 'Reverse-engineering market leaders.', 'boltfolio' ); ?></strong> <?php esc_html_e( 'Feature sets from WP Rocket, LiteSpeed Cache, NitroPack, FlyingPress, Asset CleanUp and WP-Optimize are benchmarked and dissected before anything gets built.', 'boltfolio' ); ?></span>
			</li>
			<li>
				<span><strong><?php esc_html_e( 'Profiling before optimizing.', 'boltfolio' ); ?></strong> <?php esc_html_e( 'Every bottleneck is proven with profiling data first — then fixed with the smallest correct change, never shotgun tweaks.', 'boltfolio' ); ?></span>
			</li>
			<li>
				<span><strong><?php esc_html_e( 'Pushing WordPress rendering forward.', 'boltfolio' ); ?></strong> <?php esc_html_e( 'Active research into SPA-style navigation inside WordPress, drawing on concepts from AjaxPress to blur the line between classic MPA rendering and app-like speed.', 'boltfolio' ); ?></span>
			</li>
		</ul>
	</div>
</section>

<section class="section" id="contact-cta">
	<div class="bolt-container">
		<div class="cta-band">
			<h2><?php esc_html_e( 'Need a faster WordPress site?', 'boltfolio' ); ?></h2>
			<p><?php esc_html_e( 'Performance audits, custom caching solutions and code-level optimization work. Open source is where I share everything I learn along the way.', 'boltfolio' ); ?></p>
			<div class="hero-actions" style="justify-content:center;">
				<a class="btn btn-primary" href="mailto:nilesh.kanzariya912@gmail.com"><?php esc_html_e( 'Email me', 'boltfolio' ); ?></a>
				<a class="btn btn-ghost" href="https://github.com/nilesh32236" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'GitHub', 'boltfolio' ); ?></a>
			</div>
		</div>
	</div>
</section>

<?php get_footer(); ?>
