/**
 * Editor UI for the Callout block.
 *
 * Renders a live preview of the callout (matching the PHP render output)
 * with sidebar controls for its type and optional title, plus InnerBlocks
 * for the body content.
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, useInnerBlocksProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import './editor.scss';

const TYPES = [
	{ label: __( 'Info', 'boltfolio' ), value: 'info' },
	{ label: __( 'Tip', 'boltfolio' ), value: 'tip' },
	{ label: __( 'Warning', 'boltfolio' ), value: 'warning' },
];

const DEFAULT_TITLES = {
	info: __( 'Info', 'boltfolio' ),
	tip: __( 'Tip', 'boltfolio' ),
	warning: __( 'Warning', 'boltfolio' ),
};

const ICONS = {
	info: (
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
			<circle cx="12" cy="12" r="10" />
			<line x1="12" y1="16" x2="12" y2="12" />
			<line x1="12" y1="8" x2="12.01" y2="8" />
		</svg>
	),
	tip: (
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
			<path d="M9 18h6" />
			<path d="M10 22h4" />
			<path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14" />
		</svg>
	),
	warning: (
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
			<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
			<line x1="12" y1="9" x2="12" y2="13" />
			<line x1="12" y1="17" x2="12.01" y2="17" />
		</svg>
	),
};

export default function Edit( { attributes, setAttributes } ) {
	const { type, title } = attributes;
	const blockProps = useBlockProps();
	const innerBlocksProps = useInnerBlocksProps(
		{},
		{
			template: [ [ 'core/paragraph' ] ],
			templateLock: false,
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Callout settings', 'boltfolio' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Type', 'boltfolio' ) }
						value={ type }
						options={ TYPES }
						onChange={ ( value ) => setAttributes( { type: value } ) }
					/>
					<TextControl
						label={ __( 'Title', 'boltfolio' ) }
						value={ title }
						placeholder={ DEFAULT_TITLES[ type ] || DEFAULT_TITLES.info }
						onChange={ ( value ) => setAttributes( { title: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className={ `callout callout-${ type }` }>
					<span className="callout-icon">{ ICONS[ type ] || ICONS.info }</span>
					<div className="callout-body">
						<p className="callout-title">{ title || DEFAULT_TITLES[ type ] }</p>
						<div { ...innerBlocksProps } />
					</div>
				</div>
			</div>
		</>
	);
}
