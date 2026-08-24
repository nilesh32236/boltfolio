import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import './editor.scss';

const VARIANTS = [
	{ label: __('Audit', 'boltfolio'), value: 'audit' },
	{ label: __('GitHub', 'boltfolio'), value: 'github' },
	{ label: __('Hire', 'boltfolio'), value: 'hire' },
];

export default function Edit({ attributes, setAttributes }) {
	const { variant, title, text, buttonText, buttonUrl } = attributes;
	const blockProps = useBlockProps({ className: `cta-band cta-band--${variant}` });
	return (
		<>
			<InspectorControls>
				<PanelBody title={__('CTA Settings', 'boltfolio')} initialOpen>
					<SelectControl label={__('Variant', 'boltfolio')} value={variant} options={VARIANTS} onChange={(v)=>setAttributes({variant:v})} />
					<TextControl label={__('Title', 'boltfolio')} value={title} onChange={(v)=>setAttributes({title:v})} />
					<TextControl label={__('Text', 'boltfolio')} value={text} onChange={(v)=>setAttributes({text:v})} />
					<TextControl label={__('Button text', 'boltfolio')} value={buttonText} onChange={(v)=>setAttributes({buttonText:v})} />
					<TextControl label={__('Button URL', 'boltfolio')} value={buttonUrl} onChange={(v)=>setAttributes({buttonUrl:v})} />
				</PanelBody>
			</InspectorControls>
			<div {...blockProps}>
				<h2>{title || __('Need a faster WordPress site?', 'boltfolio')}</h2>
				<p>{text}</p>
				<span className="btn btn-primary">{buttonText || __('Get in touch', 'boltfolio')}</span>
			</div>
		</>
	);
}
