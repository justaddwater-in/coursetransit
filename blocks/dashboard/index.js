(function (wp) {
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, ToggleControl } = wp.components;
    const { createElement: el } = wp.element;
    const { __ } = wp.i18n;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType('coursetransit/dashboard', {
        edit: function (props) {
            const { attributes, setAttributes } = props;
            return el('div', useBlockProps(),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('CT Dashboard', 'coursetransit'), initialOpen: true },
                        el(ToggleControl, { label: __('Show welcome message', 'coursetransit'), checked: attributes.showWelcome,
                            onChange: v => setAttributes({showWelcome: v}) }),
                        el(ToggleControl, { label: __('Show statistics', 'coursetransit'), checked: attributes.showStats,
                            onChange: v => setAttributes({showStats: v}) }),
                        el(ToggleControl, { label: __('Show account actions', 'coursetransit'), checked: attributes.showActions,
                            onChange: v => setAttributes({showActions: v}) }),
                        el(ToggleControl, { label: __('Show recent courses', 'coursetransit'), checked: attributes.showRecentCourses,
                            onChange: v => setAttributes({showRecentCourses: v}) }),
                        el(ToggleControl, { label: __('Show overall progress', 'coursetransit'), checked: attributes.showOverallProgress !== false,
                            onChange: v => setAttributes({showOverallProgress: v}) })
                    )
                ),
                ServerSideRender ? el(ServerSideRender, { block:'coursetransit/dashboard', attributes })
                    : el('p', null, __('Preview is unavailable in this editor.', 'coursetransit'))
            );
        },
        save: function () { return null; }
    });
})(window.wp);
