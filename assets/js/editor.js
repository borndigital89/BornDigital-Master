(function(wp){
    if ( ! ( wp && wp.hooks && wp.compose && wp.element && wp.components && (wp.blockEditor || wp.editor) ) ) {
        return;
    }

    const { addFilter } = wp.hooks;
    const { createHigherOrderComponent } = wp.compose;
    const { Fragment } = wp.element;
    const blockEditor = wp.blockEditor || wp.editor;
    if ( ! blockEditor.InspectorControls ) return;

    const { InspectorControls } = blockEditor;
    const { PanelBody, SelectControl, ToggleControl } = wp.components;

    const animations = [ 'bounce','flash','pulse','rubberBand','shakeX','shakeY',
        'headShake','swing','tada','wobble','jello','heartBeat',
        'backInDown','backInLeft','backInRight','backInUp',
        'backOutDown','backOutLeft','backOutRight','backOutUp',
        'bounceIn','bounceInDown','bounceInLeft','bounceInRight','bounceInUp',
        'fadeIn','fadeInDown','fadeInLeft','fadeInRight','fadeInUp',
        'flip','flipInX','flipInY','lightSpeedInRight','lightSpeedInLeft',
        'rotateIn','rotateInDownLeft','rotateInDownRight','rotateInUpLeft','rotateInUpRight',
        'zoomIn','zoomInDown','zoomInLeft','zoomInRight','zoomInUp',
        'slideInDown','slideInLeft','slideInRight','slideInUp'
    ];

    // --- Attribute erweitern ---
    addFilter('blocks.registerBlockType','wba/add-attrs',(settings)=>{
        settings.attributes = Object.assign({}, settings.attributes, {
            wbaAnimation: { type: 'string', default: '' },
            wbaDelay: { type: 'string', default: '0s' },
            wbaInView: { type: 'boolean', default: false }
        });
        return settings;
    });

    // --- InspectorControls erweitern ---
    const withInspectorControls = createHigherOrderComponent( ( BlockEdit ) => {
        return ( props ) => {
            const { attributes, setAttributes, isSelected } = props;
            const { wbaAnimation, wbaDelay, wbaInView } = attributes || {};

            const delayOptions = [{ label: 'Keine Verzögerung', value: '0s' }];
            for (let i=0; i<=10; i++) {
                if (i===0) continue;
                delayOptions.push({ label: i+'s', value: i+'s' });
            }

            return wp.element.createElement( Fragment, null,
                wp.element.createElement( BlockEdit, props ),
                isSelected && wp.element.createElement(
                    InspectorControls, null,
                    wp.element.createElement( PanelBody, { title: 'Animation', initialOpen: true },
                        wp.element.createElement( SelectControl, {
                            label: 'Wähle eine Animation',
                            value: wbaAnimation,
                            options: [{ label: 'Keine', value: '' }].concat(
                                animations.map(anim=>({label: anim, value: anim}))
                            ),
                            onChange: ( newAnim ) => setAttributes({ wbaAnimation: newAnim })
                        }),
                        wp.element.createElement( SelectControl, {
                            label: 'Verzögerung',
                            value: wbaDelay,
                            options: delayOptions,
                            onChange: ( newDelay ) => setAttributes({ wbaDelay: newDelay })
                        }),
                        wp.element.createElement( ToggleControl, {
                            label: 'Nur starten, wenn sichtbar',
                            checked: !!wbaInView,
                            onChange: ( newVal ) => setAttributes({ wbaInView: newVal })
                        })
                    )
                )
            );
        };
    }, 'withInspectorControls');

    addFilter('editor.BlockEdit','wba/add-inspector-controls',withInspectorControls);

    // --- Extra Klassen/Props ins Frontend ---
    addFilter('blocks.getSaveContent.extraProps','wba/add-extra-class',( extraProps, blockType, attributes )=>{
        if (attributes && attributes.wbaAnimation) {
            extraProps.className = (extraProps.className || '') + ' animate__animated animate__' + attributes.wbaAnimation;
            if (attributes.wbaInView) {
                extraProps.className += ' wba-observe';
            }
            if (!extraProps.style) extraProps.style = {};
            extraProps.style.animationDelay = attributes.wbaDelay || '0s';
        }
        return extraProps;
    });
})(window.wp);
