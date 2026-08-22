<?php
/**
 * Server-side rendering for the Callout block.
 *
 * @package boltfolio
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (already rendered HTML).
 * @var WP_Block $block      Block instance.
 */

$boltfolio_type  = isset( $attributes['type'] ) ? sanitize_key( $attributes['type'] ) : 'info';
$boltfolio_types = array( 'info', 'tip', 'warning' );

if ( ! in_array( $boltfolio_type, $boltfolio_types, true ) ) {
	$boltfolio_type = 'info';
}

$boltfolio_title = isset( $attributes['title'] ) ? sanitize_text_field( $attributes['title'] ) : '';
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'callout callout-' . esc_attr( $boltfolio_type ) ) ); ?>>
	<span class="callout-icon" aria-hidden="true">
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
			<?php if ( 'tip' === $boltfolio_type ) : ?>
				<path d="M9 18h6"/><path d="M10 22h4"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/>
			<?php elseif ( 'warning' === $boltfolio_type ) : ?>
				<path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
			<?php else : ?>
				<circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
			<?php endif; ?>
		</svg>
	</span>
	<div class="callout-body">
		<?php if ( '' !== $boltfolio_title ) : ?>
			<p class="callout-title"><?php echo esc_html( $boltfolio_title ); ?></p>
		<?php endif; ?>
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks escaped upstream. ?>
	</div>
</div>
