<?php
/**
 * Documentation search modal.
 *
 * Markup only — the index is fetched once on first open, so the modal
 * costs nothing on pages where nobody searches.
 *
 * @package boltfolio
 */

?>
<div class="search-modal" id="docs-search" data-search-modal data-open="false" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search documentation', 'boltfolio' ); ?>">
	<div class="search-modal__scrim" data-search-close></div>

	<div class="search-modal__panel">
		<div class="search-modal__field">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			<label class="screen-reader-text" for="docs-search-input"><?php esc_html_e( 'Search the documentation', 'boltfolio' ); ?></label>
			<input
				type="search"
				id="docs-search-input"
				data-search-input
				placeholder="<?php esc_attr_e( 'Search guides, hooks and classes…', 'boltfolio' ); ?>"
				autocomplete="off"
				spellcheck="false"
				aria-controls="docs-search-results"
				aria-expanded="false"
			>
			<button class="search-modal__esc" type="button" data-search-close aria-label="<?php esc_attr_e( 'Close search', 'boltfolio' ); ?>">ESC</button>
		</div>

		<ul class="search-results" id="docs-search-results" data-search-results role="listbox" aria-label="<?php esc_attr_e( 'Search results', 'boltfolio' ); ?>"></ul>

		<div class="search-modal__foot">
			<span><kbd>↑</kbd> <kbd>↓</kbd> <?php esc_html_e( 'to move', 'boltfolio' ); ?></span>
			<span><kbd>↵</kbd> <?php esc_html_e( 'to open', 'boltfolio' ); ?></span>
			<span data-search-scope><?php esc_html_e( 'All documentation', 'boltfolio' ); ?></span>
		</div>
	</div>
</div>
