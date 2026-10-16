(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, ToggleControl, TextControl, SelectControl, ComboboxControl, Spinner } = wp.components;
    const { createElement: el, useEffect, useState } = wp.element;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;
    const apiFetch = wp.apiFetch;

    const IMAGE_SIZES = [
        { label: __('Thumbnail', 'coursetransit'), value: 'thumbnail' },
        { label: __('Medium', 'coursetransit'), value: 'medium' },
        { label: __('Medium Large', 'coursetransit'), value: 'medium_large' },
        { label: __('Large', 'coursetransit'), value: 'large' },
        { label: __('Full', 'coursetransit'), value: 'full' }
    ];

    const IMAGE_RATIOS = [
        { label: '16:9', value: '16-9' },
        { label: '4:3', value: '4-3' },
        { label: '1:1', value: '1-1' },
        { label: __('Original', 'coursetransit'), value: 'auto' }
    ];

    registerBlockType('coursetransit/course-description', {
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const [options, setOptions] = useState([]);
            const [search, setSearch] = useState('');
            const [loading, setLoading] = useState(false);
            const [selectedLabel, setSelectedLabel] = useState('');

            useEffect(function () {
                const config = window.CourseTransitCourseSelector || {};
                if (!config.ajaxUrl || !config.searchNonce) {
                    setOptions([]);
                    return;
                }
                setLoading(true);
                const body = new URLSearchParams();
                body.append('action', 'coursetransit_block_course_search');
                body.append('nonce', config.searchNonce);
                body.append('search', search || '');
                if (!search && attributes.courseId) body.append('course_id', String(attributes.courseId));
                fetch(config.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                }).then(function (response) {
                    if (!response.ok) throw new Error('Course search request failed: ' + response.status);
                    return response.json();
                }).then(function (items) {
                    const mapped = Array.isArray(items) ? items.map(function (item) {
                        return { label: item.title, value: String(item.id), productId: Number(item.productId || 0) };
                    }) : [];
                    setOptions(mapped);
                    const selected = mapped.find(function (item) {
                        return Number(item.value) === Number(attributes.courseId || 0);
                    });
                    if (selected) setSelectedLabel(selected.label);
                }).catch(function () {
                    setOptions([]);
                }).finally(function () {
                    setLoading(false);
                });
            }, [search, attributes.courseId]);

            const currentSelection = options.find(function (item) {
                return Number(item.value) === Number(attributes.courseId || 0);
            });

            return el('div', useBlockProps(),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Course Selection', 'coursetransit'), initialOpen: true },
                        el(ComboboxControl, {
                            label: __('Select Course', 'coursetransit'),
                            value: currentSelection ? currentSelection.value : (attributes.courseId ? String(attributes.courseId) : ''),
                            options: options,
                            onFilterValueChange: setSearch,
                            onChange: function (value) {
                                const id = parseInt(value, 10) || 0;
                                const selected = options.find(function (item) { return Number(item.value) === id; });
                                setAttributes({ courseId: id, productId: 0 });
                                setSelectedLabel(selected ? selected.label : '');
                            },
                            help: loading
                                ? __('Searching courses…', 'coursetransit')
                                : __('Search by course name and select a synced published course. Product ID can also be entered separately.', 'coursetransit')
                        }),
                        loading ? el(Spinner, null) : null,
                        selectedLabel ? el('p', { className: 'description' }, selectedLabel) : null,
                        el(TextControl, {
                            label: __('Product ID', 'coursetransit'),
                            help: __('Optional direct WooCommerce product ID. If set, it takes priority over the selected course.', 'coursetransit'),
                            type: 'number',
                            value: attributes.productId || '',
                            onChange: function (value) {
                                const productId = parseInt(value, 10) || 0;
                                setAttributes({ productId: productId, courseId: productId ? 0 : (attributes.courseId || 0) });
                                if (productId) {
                                    setSelectedLabel('');
                                }
                            }
                        }),
                        el(SelectControl, {
                            label: __('Image Size', 'coursetransit'),
                            value: attributes.imageSize,
                            options: IMAGE_SIZES,
                            onChange: function (value) { setAttributes({ imageSize: value }); }
                        }),
                        el(SelectControl, {
                            label: __('Image Ratio', 'coursetransit'),
                            value: attributes.imageRatio,
                            options: IMAGE_RATIOS,
                            onChange: function (value) { setAttributes({ imageRatio: value }); }
                        })
                    ),
                    el(PanelBody, { title: __('Course Details', 'coursetransit'), initialOpen: true },
                        el(ToggleControl, { label: __('Show image', 'coursetransit'), checked: attributes.showImage, onChange: function (v) { setAttributes({ showImage: v }); } }),
                        el(ToggleControl, { label: __('Show title', 'coursetransit'), checked: attributes.showTitle, onChange: function (v) { setAttributes({ showTitle: v }); } }),
                        el(ToggleControl, { label: __('Show description', 'coursetransit'), checked: attributes.showDescription, onChange: function (v) { setAttributes({ showDescription: v }); } }),
                        el(ToggleControl, { label: __('Show price', 'coursetransit'), checked: attributes.showPrice, onChange: function (v) { setAttributes({ showPrice: v }); } }),
                        el(ToggleControl, { label: __('Show category', 'coursetransit'), checked: attributes.showCategory, onChange: function (v) { setAttributes({ showCategory: v }); } }),
                        el(ToggleControl, { label: __('Show instructor', 'coursetransit'), checked: attributes.showInstructor, onChange: function (v) { setAttributes({ showInstructor: v }); } }),
                        el(ToggleControl, { label: __('Show learner progress', 'coursetransit'), checked: attributes.showProgress !== false, onChange: function (v) { setAttributes({ showProgress: v }); } }),
                        el(TextControl, {
                            label: __('Button text', 'coursetransit'),
                            value: attributes.buttonText,
                            onChange: function (value) { setAttributes({ buttonText: value }); }
                        })
                    )
                ),
                ServerSideRender
                    ? el(ServerSideRender, { block: 'coursetransit/course-description', attributes: attributes })
                    : el('p', null, __('Preview is unavailable in this editor.', 'coursetransit'))
            );
        },
        save: function () { return null; }
    });
})(window.wp);
