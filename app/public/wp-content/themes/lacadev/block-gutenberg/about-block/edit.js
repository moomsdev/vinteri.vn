/* eslint-disable jsx-a11y/label-has-associated-control */
import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
	RichText,
	useBlockProps, // ← Thêm useBlockProps
} from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Editor styles come from theme's compiled editor.css (includes _blocks.scss)

export default function Edit( { attributes, setAttributes, clientId } ) {
	const { blockID, welcomeContent, aboutImage, aboutTitle, aboutDesc } =
		attributes;

	// Set unique block ID
	if ( ! blockID ) {
		setAttributes( { blockID: clientId } );
	}

	// Get block props for proper Gutenberg integration
	const blockProps = useBlockProps( {
		className: 'block-about editor-view',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Block Settings', 'laca' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Circle Content', 'laca' ) }
						value={ welcomeContent }
						onChange={ ( value ) =>
							setAttributes( { welcomeContent: value } )
						}
						help={ __(
							'Nội dung hiển thị trong vòng tròn xoay',
							'laca'
						) }
					/>

					<MediaUploadCheck>
						<MediaUpload
							onSelect={ ( media ) =>
								setAttributes( {
									aboutImage: {
										id: media.id,
										url: media.url,
										alt: media.alt || '',
									},
								} )
							}
							allowedTypes={ [ 'image' ] }
							value={ aboutImage.id }
							render={ ( { open } ) => (
								<div className="components-base-control">
									<label className="components-base-control__label">
										{ __( 'About Image', 'laca' ) }
									</label>
									{ aboutImage.url ? (
										<div className="editor-post-featured-image">
											<img
												src={ aboutImage.url }
												alt={ aboutImage.alt }
												style={ {
													maxWidth: '100%',
													height: 'auto',
													marginBottom: '10px',
												} }
											/>
											<div
												className="editor-post-featured-image__actions"
												style={ {
													display: 'flex',
													gap: '8px',
													marginTop: '8px',
												} }
											>
												<Button
													onClick={ open }
													variant="secondary"
													style={ { flex: 1 } }
												>
													{ __(
														'Replace Image',
														'laca'
													) }
												</Button>
												<Button
													onClick={ () =>
														setAttributes( {
															aboutImage: {
																id: 0,
																url: '',
																alt: '',
															},
														} )
													}
													variant="secondary"
													isDestructive
													style={ { flex: 1 } }
												>
													{ __(
														'Remove Image',
														'laca'
													) }
												</Button>
											</div>
										</div>
									) : (
										<Button
											onClick={ open }
											variant="secondary"
											style={ { width: '100%' } }
										>
											{ __( 'Select Image', 'laca' ) }
										</Button>
									) }
								</div>
							) }
						/>
					</MediaUploadCheck>
				</PanelBody>
			</InspectorControls>

			<section { ...blockProps }>
				<div className="block-about__head">
					<div className="scroll-circle">
						<svg viewBox="0 0 200 200">
							<path
								id="circlePath"
								d="M100,100 m-75,0 a75,75 0 1,1 150,0 a75,75 0 1,1 -150,0"
								fill="none"
							/>
							<text>
								<textPath href="#circlePath" startOffset="0">
									{ welcomeContent }
								</textPath>
							</text>
						</svg>
						<div className="arrow"></div>
					</div>

					{ aboutImage.url && (
						<div className="block-about__img">
							<figure>
								<img
									src={ aboutImage.url }
									alt={ aboutImage.alt || aboutTitle }
									loading="lazy"
								/>
							</figure>
						</div>
					) }
				</div>

				<div className="block-about__body">
					<RichText
						tagName="h2"
						className="block-title text-center"
						value={ aboutTitle }
						onChange={ ( value ) =>
							setAttributes( { aboutTitle: value } )
						}
						placeholder={ __( 'Nhập tiêu đề của block…', 'laca' ) }
					/>
					<RichText
						tagName="div"
						className="block-desc"
						value={ aboutDesc }
						onChange={ ( value ) =>
							setAttributes( { aboutDesc: value } )
						}
						placeholder={ __( 'Nhập mô tả của block…', 'laca' ) }
						multiline="p"
					/>
				</div>
			</section>
		</>
	);
}
