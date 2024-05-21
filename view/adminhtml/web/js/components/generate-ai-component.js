define([
    'jquery',
    'Magento_Ui/js/form/components/button',
    'uiComponent',
    'Magento_Ui/js/modal/alert',
    'MageOS_CatalogDataAI/js/actions/generate-ai-action',
], function ($, Button, Component, alert, aiAction) {
    'use strict';

    return Button.extend({
        initialize: function () {
            this._super();
        },

        getTemplate: function () {
            return 'ui/form/components/button/container';
        },

        action: function () {
            aiAction.generateContent(this);
        },
    });
});
