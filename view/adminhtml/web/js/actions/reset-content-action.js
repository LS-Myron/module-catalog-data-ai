/**
 * Copyright Elgentos BV. All rights reserved.
 * https://www.elgentos.nl/
 */
define([
    'jquery',
    'underscore',
    'MageOS_CatalogDataAI/js/actions/core-action',
], function ($, _, catalogDataAiCore) {
    'use strict';

    function resetContent(element) {
        const target = catalogDataAiCore.getTarget(element),
            productId = element.product_id;

        console.log(catalogDataAiCore.getPreviousValue());

        // TODO: run reset action
    }

    function enableResetValue() {
        // todo: create/display button after existing button
    }

    function resetValueToPrevious() {
        // todo: get previous value and put this value back into the attributes' field
    }

    return {
        resetContent: resetContent,
        enableResetValue: enableResetValue,
        resetValueToPrevious: resetValueToPrevious,
    };
});
