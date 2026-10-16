(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const {
        PanelBody,
        RangeControl,
        ToggleControl,
        SelectControl,
        ComboboxControl,
        TextControl,
        Notice
    } = wp.components;
    const { createElement: el } = wp.element;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;

    const config = window.CourseTransitCourseCards || {
        categories: [],
        instructors: []
    };

    const categoryOptions = [
        { label: __('All Categories', 'coursetransit'), value: '' }
    ].concat(config.categories || []);

    const instructorOptions = [
        { label: __('All Instructors', 'coursetransit'), value: '' }
    ].concat(config.instructors || []);

    const sortOptions = [
        { label: __('Alpha', 'coursetransit'), value: 'alpha' },
        { label: __('Price', 'coursetransit'), value: 'price' },
        { label: __('Added', 'coursetransit'), value: 'added' },
        { label: __('Popular', 'coursetransit'), value: 'popular' }
    ];

    const orderOptions = {
        alpha: [
            { label: __('A → Z', 'coursetransit'), value: 'asc' },
            { label: __('Z → A', 'coursetransit'), value: 'desc' }
        ],
        price: [
            { label: __('Low → High', 'coursetransit'), value: 'asc' },
            { label: __('High → Low', 'coursetransit'), value: 'desc' }
        ],
        added: [
            { label: __('Oldest → Newest', 'coursetransit'), value: 'asc' },
            { label: __('Newest → Oldest', 'coursetransit'), value: 'desc' }
        ],
        popular: [
            { label: __('Least → Most', 'coursetransit'), value: 'asc' },
            { label: __('Most → Least', 'coursetransit'), value: 'desc' }
        ]
    };

    registerBlockType('coursetransit/course-cards', {
        edit: function (props) {
            const { attributes, setAttributes } = props;

            return el(
                'div',
                useBlockProps(),
                el(
                    InspectorControls,
                    null,
                    el(
                        PanelBody,
                        {
                            title: __('Course Cards', 'coursetransit'),
                            initialOpen: true
                        },
                        el(SelectControl, {
                            label: __('Sort by', 'coursetransit'),
                            value: attributes.orderBy || 'added',
                            options: sortOptions,
                            onChange: function (value) {
                                const nextSort = value || 'added';
                                const validOrders = orderOptions[nextSort] || orderOptions.added;
                                const currentOrder = attributes.order || 'desc';
                                const nextOrder = validOrders.some(function (option) {
                                    return option.value === currentOrder;
                                }) ? currentOrder : 'desc';
                                setAttributes({ orderBy: nextSort, order: nextOrder });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Order', 'coursetransit'),
                            value: attributes.order || 'desc',
                            options: orderOptions[attributes.orderBy || 'added'] || orderOptions.added,
                            onChange: function (value) {
                                setAttributes({ order: value || 'desc' });
                            }
                        }),
                        el(ComboboxControl, {
                            label: __('Category', 'coursetransit'),
                            value: attributes.category || '',
                            options: categoryOptions,
                            onChange: function (value) {
                                setAttributes({ category: value || '' });
                            }
                        }),
                        el(ComboboxControl, {
                            label: __('Instructor', 'coursetransit'),
                            value: attributes.instructor || '',
                            options: instructorOptions,
                            onChange: function (value) {
                                setAttributes({ instructor: value || '' });
                            }
                        }),
                        el(RangeControl, {
                            label: __('Number of courses', 'coursetransit'),
                            value: attributes.limit,
                            onChange: function (value) {
                                setAttributes({ limit: value || 6 });
                            },
                            min: 1,
                            max: 12
                        }),
                        el(RangeControl, {
                            label: __('Columns', 'coursetransit'),
                            help: __('Choose the desktop column count. The block automatically reduces columns on smaller screens.', 'coursetransit'),
                            value: attributes.columns,
                            onChange: function (value) {
                                setAttributes({ columns: value || 3 });
                            },
                            min: 1,
                            max: 4
                        }),
                        el(ToggleControl, {
                            label: __('Show image', 'coursetransit'),
                            checked: attributes.showImage,
                            onChange: function (value) {
                                setAttributes({ showImage: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Image size', 'coursetransit'),
                            help: __('Uses a WordPress image size when the course image is stored in the Media Library.', 'coursetransit'),
                            value: attributes.imageSize || 'medium_large',
                            options: [
                                { label: __('Thumbnail', 'coursetransit'), value: 'thumbnail' },
                                { label: __('Medium', 'coursetransit'), value: 'medium' },
                                { label: __('Medium Large', 'coursetransit'), value: 'medium_large' },
                                { label: __('Large', 'coursetransit'), value: 'large' },
                                { label: __('Full Size', 'coursetransit'), value: 'full' }
                            ],
                            onChange: function (value) {
                                setAttributes({ imageSize: value });
                            }
                        }),
                        el(SelectControl, {
                            label: __('Image ratio', 'coursetransit'),
                            help: __('Keeps course images consistent. Auto keeps the original image ratio.', 'coursetransit'),
                            value: attributes.imageRatio || '16-9',
                            options: [
                                { label: '16:9', value: '16-9' },
                                { label: '4:3', value: '4-3' },
                                { label: '1:1', value: '1-1' },
                                { label: __('Original', 'coursetransit'), value: 'auto' }
                            ],
                            onChange: function (value) {
                                setAttributes({ imageRatio: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show description', 'coursetransit'),
                            checked: attributes.showDescription,
                            onChange: function (value) {
                                setAttributes({ showDescription: value });
                            }
                        }),
                        el(ToggleControl, {
                            label: __('Show price', 'coursetransit'),
                            checked: attributes.showPrice,
                            onChange: function (value) {
                                setAttributes({ showPrice: value });
                            }
                        }),
                        el(TextControl, {
                            label: __('Button text', 'coursetransit'),
                            value: attributes.buttonText,
                            onChange: function (value) {
                                setAttributes({ buttonText: value });
                            }
                        })
                    )
                ),
                ServerSideRender
                    ? el(ServerSideRender, {
                        block: 'coursetransit/course-cards',
                        attributes: attributes
                    })
                    : el(
                        Notice,
                        { status: 'warning', isDismissible: false },
                        __('Course preview is unavailable in this editor.', 'coursetransit')
                    )
            );
        },

        save: function () {
            return null;
        }
    });
})(window.wp);
