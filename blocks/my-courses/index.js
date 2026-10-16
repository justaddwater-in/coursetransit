(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl, ToggleControl, TextControl, SelectControl } = wp.components;
    const { createElement: el } = wp.element;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType('coursetransit/my-courses', {
        edit: function (props) {
            const { attributes, setAttributes } = props;
            return el('div', useBlockProps(),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('CT My Courses', 'coursetransit'), initialOpen: true },
                        el(RangeControl, { label: __('Number of courses', 'coursetransit'), value: attributes.limit,
                            min: 1, max: 12, onChange: v => setAttributes({limit: v || 6}) }),
                        el(RangeControl, { label: __('Columns', 'coursetransit'), value: attributes.columns,
                            min: 1, max: 4, onChange: v => setAttributes({columns: v || 3}) }),
                        el(ToggleControl, { label: __('Show section heading', 'coursetransit'), checked: attributes.showHeading === true, onChange: v => setAttributes({showHeading: v}) }),
                        el(ToggleControl, { label: __('Show image', 'coursetransit'), checked: attributes.showImage,
                            onChange: v => setAttributes({showImage: v}) }),
                        el(ToggleControl, { label: __('Show description', 'coursetransit'), checked: attributes.showDescription,
                            onChange: v => setAttributes({showDescription: v}) }),
                        el(ToggleControl, { label: __('Show progress', 'coursetransit'), checked: attributes.showProgress !== false,
                            onChange: v => setAttributes({showProgress: v}) }),
                        el(SelectControl, { label: __('Sort courses', 'coursetransit'), value: attributes.sort || 'recent',
                            options: [
                                { label: __('Recently enrolled', 'coursetransit'), value: 'recent' },
                                { label: __('Name A → Z', 'coursetransit'), value: 'name-asc' },
                                { label: __('Name Z → A', 'coursetransit'), value: 'name-desc' },
                                { label: __('Progress high → low', 'coursetransit'), value: 'progress-desc' },
                                { label: __('Progress low → high', 'coursetransit'), value: 'progress-asc' }
                            ], onChange: v => setAttributes({sort: v || 'recent'}) }),
                        el(SelectControl, { label: __('Image size', 'coursetransit'), value: attributes.imageSize || 'medium_large',
                            options: [
                                { label: __('Thumbnail', 'coursetransit'), value: 'thumbnail' },
                                { label: __('Medium', 'coursetransit'), value: 'medium' },
                                { label: __('Medium Large', 'coursetransit'), value: 'medium_large' },
                                { label: __('Large', 'coursetransit'), value: 'large' },
                                { label: __('Full', 'coursetransit'), value: 'full' }
                            ], onChange: v => setAttributes({imageSize: v}) }),
                        el(SelectControl, { label: __('Image ratio', 'coursetransit'), value: attributes.imageRatio || '16-9',
                            options: [
                                { label: '16:9', value: '16-9' }, { label: '4:3', value: '4-3' },
                                { label: '1:1', value: '1-1' }, { label: __('Original', 'coursetransit'), value: 'auto' }
                            ], onChange: v => setAttributes({imageRatio: v}) }),
                        el(TextControl, { label: __('Button text', 'coursetransit'), value: attributes.buttonText,
                            onChange: v => setAttributes({buttonText: v}) })
                    )
                ),
                ServerSideRender ? el(ServerSideRender, { block:'coursetransit/my-courses', attributes })
                    : el('p', null, __('Preview is unavailable in this editor.', 'coursetransit'))
            );
        },
        save: function () { return null; }
    });
})(window.wp);
