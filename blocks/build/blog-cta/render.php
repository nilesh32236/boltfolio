<?php
/**
 * Blog CTA — server-rendered.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner content (unused).
 * @var WP_Block $block      Block instance.
 */
$variant    = $attributes['variant'] ?? 'audit';
$title      = $attributes['title'] ?? '';
$text       = $attributes['text'] ?? '';
$buttonText = $attributes['buttonText'] ?? '';
$buttonUrl  = $attributes['buttonUrl'] ?? '';

$allowed = ['audit','github','hire'];
if (!in_array($variant, $allowed, true)) $variant = 'audit';
?>
<div <?php echo get_block_wrapper_attributes(['class' => 'wp-block-boltfolio-blog-cta cta-band cta-band--' . $variant]); ?>>
	<h2><?php echo esc_html($title ?: 'Need a faster WordPress site?'); ?></h2>
	<?php if ($text) : ?><p><?php echo esc_html($text); ?></p><?php endif; ?>
	<?php if ($buttonText && $buttonUrl) : ?>
		<a class="btn btn-primary" href="<?php echo esc_url($buttonUrl); ?>"><?php echo esc_html($buttonText); ?></a>
	<?php endif; ?>
</div>
