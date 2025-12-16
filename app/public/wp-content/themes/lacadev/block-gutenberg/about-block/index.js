import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import Save from './save';

// Styles are loaded from theme's compiled CSS (dist/styles/theme.css and dist/styles/editor.css)
// No need to import block-specific SCSS files

registerBlockType( metadata.name, {
	edit: Edit,
	save: Save,
} );
