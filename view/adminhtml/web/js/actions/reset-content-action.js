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
        this.resetValueToPrevious(catalogDataAiCore.getTarget(element));
    }

    function resetValueToPrevious(target) {
        // todo: get previous value and put this value back into the attributes' field
        catalogDataAiCore.updateTargetValue(target, catalogDataAiCore.getPreviousValue())
    }

    return {
        resetContent: resetContent,
        resetValueToPrevious: resetValueToPrevious,
    };
});
