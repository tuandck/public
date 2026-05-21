(function (wp) {
	'use strict';

	if (!wp || !wp.hooks || !wp.compose || !wp.element || !wp.data || !wp.blockEditor || !wp.components) {
		return;
	}

	const { addFilter } = wp.hooks;
	const { createHigherOrderComponent } = wp.compose;
	const { createElement: el, Fragment } = wp.element;
	const { BlockControls } = wp.blockEditor;
	const { ToolbarButton, ToolbarGroup } = wp.components;
	const { select, dispatch } = wp.data;
	const apiFetch = wp.apiFetch;
	const { __ } = wp.i18n || { __: (value) => value };

	const resolveAttachmentId = async (attributes) => {
		if (attributes.id) {
			return Number(attributes.id);
		}

		if (!attributes.url || !apiFetch) {
			return 0;
		}

		const result = await apiFetch({
			path: `/bhdt/v1/media/resolve-image?url=${encodeURIComponent(attributes.url)}`,
		});

		return Number(result.id || 0);
	};

	const setFeaturedImage = async (attributes) => {
		try {
			const attachmentId = await resolveAttachmentId(attributes);
			if (!attachmentId) {
				throw new Error(__('Could not resolve this image in the Media Library.', 'bhdt-core-system'));
			}

			dispatch('core/editor').editPost({ featured_media: attachmentId });
			dispatch('core/notices').createSuccessNotice(
				__('Featured image set from the selected image. Update the post to save it.', 'bhdt-core-system'),
				{ type: 'snackbar' }
			);
		} catch (error) {
			dispatch('core/notices').createErrorNotice(
				error.message || __('Could not set featured image.', 'bhdt-core-system'),
				{ type: 'snackbar' }
			);
		}
	};

	const withFeaturedImageControl = createHigherOrderComponent((BlockEdit) => {
		return (props) => {
			if (props.name !== 'core/image' || !props.isSelected || !props.attributes || (!props.attributes.id && !props.attributes.url)) {
				return el(BlockEdit, props);
			}

			const currentFeaturedId = Number(select('core/editor').getEditedPostAttribute('featured_media') || 0);
			const imageId = Number(props.attributes.id || 0);
			const isCurrent = imageId && currentFeaturedId === imageId;

			return el(
				Fragment,
				null,
				el(BlockEdit, props),
				el(
					BlockControls,
					null,
					el(
						ToolbarGroup,
						null,
						el(ToolbarButton, {
							icon: 'format-image',
							label: isCurrent
								? __('This image is the featured image', 'bhdt-core-system')
								: __('Set as featured image', 'bhdt-core-system'),
							text: __('Set featured', 'bhdt-core-system'),
							isPressed: !!isCurrent,
							onClick: () => setFeaturedImage(props.attributes),
						})
					)
				)
			);
		};
	}, 'withFeaturedImageControl');

	addFilter('editor.BlockEdit', 'bhdt/featured-image-control', withFeaturedImageControl);
})(window.wp);
