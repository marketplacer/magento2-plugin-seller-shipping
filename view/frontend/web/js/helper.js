define([
    'jquery',
    'mageUtils',
    'uiLayout',
    'rjsResolver'
], function ($, utils,  layout, rjsResolver) {
    'use strict';

    return {
        initChildrenComponents: function (components, parent) {
            let rendererComponent = [],
                children;
            $.each(components, (name, item) => {
                let newComponent = this.createComponent(name, item, parent)
                rendererComponent.push(newComponent)
                if (item.children !== undefined) {
                    children = item.children;
                    parent += '.' + name;
                }
            });
            if (rendererComponent.length > 0) {
                rjsResolver(function (){
                    layout(rendererComponent);
                });

                if (children !== undefined) {
                    this.initChildrenComponents(children, parent)
                }
            }
        },

        /**
         * @returns
         */
        createComponent: function (name, componentData, parent) {
            var rendererTemplate,
                rendererComponent,
                templateData;

            templateData = {
                parentName: parent,
                name: name
            };
            rendererTemplate = {
                parent: '${ $.$data.parentName }',
                name: '${ $.$data.name }',
                displayArea: componentData.displayArea ?? null,
                component: componentData.component
                //provider: 'checkoutProvider'
            };
            rendererComponent = utils.template(rendererTemplate, templateData);
            utils.extend(rendererComponent, componentData.config ?? {});

            return rendererComponent;
        },
    };
});
